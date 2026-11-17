#!/usr/bin/env python3
"""Convert the phpMyAdmin MariaDB dump of the kualitet database to PostgreSQL SQL.

Usage:
    python tools/convert-mysql-dump-to-pgsql.py <dump.sql> <schema-out.sql> <data-out.sql>

The schema file contains tables, sequences and indexes. The data file contains the
rows, sequence positions and foreign keys, so it must be loaded after the schema.
The data file holds real records (including password hashes): keep it out of the
repository and out of any deployment.

Type mapping (chosen to keep CakePHP 3.9 column types unchanged):
    tinyint(1)          -> boolean        (CakePHP maps both to "boolean")
    int / int unsigned  -> integer / bigint
    float(M,D), decimal -> numeric(M,D)   (CakePHP returns PHP floats for both)
    float, double       -> real / double precision
    datetime, timestamp -> timestamp(0) without time zone
    AUTO_INCREMENT      -> sequence "<table>_<column>_seq" with a nextval() default
    varchar / text      -> same type with the case-insensitive collation below
"""
import re
import sys

COLLATION = 'kualitet_ci'
RESERVED = {'end', 'user', 'order', 'group', 'limit', 'offset', 'table', 'column', 'default', 'check'}


def ident(name):
    return '"%s"' % name if name in RESERVED else name


def convert_type(mysql_type):
    t = mysql_type.strip().lower()
    unsigned = 'unsigned' in t
    t = t.replace('unsigned', '').strip()
    m = re.match(r'^([a-z]+)(?:\(([^)]*)\))?$', t)
    if not m:
        raise ValueError('Unsupported column type: %s' % mysql_type)
    base, args = m.group(1), m.group(2)
    if base == 'tinyint':
        return ('boolean' if args == '1' else 'smallint'), False
    if base == 'smallint':
        return ('integer' if unsigned else 'smallint'), False
    if base == 'int':
        return ('bigint' if unsigned else 'integer'), False
    if base == 'bigint':
        return 'bigint', False
    if base in ('float', 'double') and args:
        return 'numeric(%s)' % args.replace(' ', ''), False
    if base == 'float':
        return 'real', False
    if base == 'double':
        return 'double precision', False
    if base == 'decimal':
        return 'numeric(%s)' % args.replace(' ', ''), False
    if base in ('datetime', 'timestamp'):
        return 'timestamp(0) without time zone', False
    if base in ('date', 'time'):
        return base, False
    if base == 'varchar':
        return 'varchar(%s)' % args, True
    if base in ('text', 'mediumtext', 'longtext', 'tinytext'):
        return 'text', True
    raise ValueError('Unsupported column type: %s' % mysql_type)


def convert_default(default, pg_type):
    d = default.strip()
    if d.upper() == 'NULL':
        return None
    if d.lower().startswith('current_timestamp'):
        return 'CURRENT_TIMESTAMP'
    if pg_type == 'boolean':
        return 'true' if d.strip("'") == '1' else 'false'
    return d


COLUMN_RE = re.compile(
    r"^\s*`(?P<name>[^`]+)`\s+(?P<type>[a-z]+(?:\([^)]*\))?(?:\s+UNSIGNED)?)"
    r"(?P<rest>.*?),?\s*$", re.IGNORECASE)


def parse_column(line):
    m = COLUMN_RE.match(line)
    if not m:
        raise ValueError('Cannot parse column line: %r' % line)
    rest = m.group('rest')
    pg_type, textual = convert_type(m.group('type'))
    not_null = bool(re.search(r'\bNOT NULL\b', rest))
    dm = re.search(r"\bDEFAULT\s+('(?:[^']|'')*'|[^\s,]+)", rest)
    default = convert_default(dm.group(1), pg_type) if dm else None
    return {
        'name': m.group('name'), 'type': pg_type, 'textual': textual,
        'not_null': not_null, 'default': default,
    }


def main(dump_path, schema_path, data_path):
    with open(dump_path, encoding='utf-8') as fh:
        sql = fh.read()

    tables = {}
    order = []
    for m in re.finditer(r"CREATE TABLE `([^`]+)` \((.*?)\n\) ENGINE=", sql, re.S):
        name = m.group(1)
        tables[name] = {
            'columns': [parse_column(line) for line in m.group(2).strip().split('\n')],
            'pk': None, 'indexes': [], 'auto': None, 'auto_start': None, 'fks': [],
        }
        order.append(name)

    inserts = []
    for m in re.finditer(r"^INSERT INTO `([^`]+)` \(([^)]*)\) VALUES\n(.*?);\n", sql, re.S | re.M):
        if '\\' in m.group(3):
            raise ValueError('Backslash escapes in data for %s are not handled.' % m.group(1))
        columns = [ident(c.strip().strip('`')) for c in m.group(2).split(',')]
        inserts.append((m.group(1), columns, m.group(3)))

    for m in re.finditer(r"^ALTER TABLE `([^`]+)`\n(.*?);\n", sql, re.S | re.M):
        table = tables[m.group(1)]
        for clause in m.group(2).split('\n'):
            clause = clause.strip().rstrip(',')
            pk = re.match(r"ADD PRIMARY KEY \(`([^`]+)`\)", clause)
            key = re.match(r"ADD KEY `[^`]+` \(`([^`]+)`\)", clause)
            auto = re.match(r"MODIFY `([^`]+)` .*?AUTO_INCREMENT(?:, AUTO_INCREMENT=(\d+))?$", clause)
            fk = re.match(
                r"ADD CONSTRAINT `([^`]+)` FOREIGN KEY \(`([^`]+)`\) REFERENCES `([^`]+)` \(`([^`]+)`\)(.*)",
                clause)
            if pk:
                table['pk'] = pk.group(1)
            elif key:
                if key.group(1) not in table['indexes']:
                    table['indexes'].append(key.group(1))
            elif auto:
                table['auto'] = auto.group(1)
                table['auto_start'] = int(auto.group(2)) if auto.group(2) else None
            elif fk:
                table['fks'].append(fk.groups())
            else:
                raise ValueError('Unhandled ALTER clause for %s: %r' % (m.group(1), clause))

    schema = [
        '-- Generated by tools/convert-mysql-dump-to-pgsql.py. Do not edit by hand.',
        '-- Case-insensitive, accent-sensitive comparisons, matching the *_ci collations of the source.',
        "CREATE COLLATION IF NOT EXISTS %s (provider = icu, locale = 'und-u-ks-level2', deterministic = false);"
        % COLLATION,
        '',
        '-- MySQL-compatible date helpers so the application SQL (month(fecha), YEAR(X.fecha), ...)',
        '-- runs unchanged on both MariaDB (production) and PostgreSQL (this machine).',
    ]
    for func in ('month', 'year'):
        for arg in ('date', 'timestamp without time zone'):
            schema.append(
                'CREATE OR REPLACE FUNCTION %s(%s) RETURNS integer LANGUAGE sql IMMUTABLE PARALLEL SAFE '
                "AS 'SELECT EXTRACT(%s FROM $1)::integer';" % (func, arg, func.upper()))
    schema += [
        '',
        '-- MySQL sums text columns holding amounts (for example conceptos.gasto) by reading their',
        '-- leading number (non-numeric text counts as 0; all NULL gives NULL). Same behaviour here.',
        'CREATE OR REPLACE FUNCTION kualitet_text_sum_step(numeric, text) RETURNS numeric LANGUAGE sql IMMUTABLE '
        "AS $$ SELECT CASE WHEN $2 IS NULL THEN $1 ELSE COALESCE($1, 0) + "
        "COALESCE(CAST(SUBSTRING(TRIM($2) FROM '^[-+]?[0-9]*\\.?[0-9]+') AS numeric), 0) END $$;",
        'DROP AGGREGATE IF EXISTS sum(varchar);',
        'CREATE AGGREGATE sum(varchar) (SFUNC = kualitet_text_sum_step, STYPE = numeric);',
        'DROP AGGREGATE IF EXISTS sum(text);',
        'CREATE AGGREGATE sum(text) (SFUNC = kualitet_text_sum_step, STYPE = numeric);',
    ]
    schema.append('')
    for name in order:
        table = tables[name]
        seq = None
        if table['auto']:
            seq = '%s_%s_seq' % (name, table['auto'])
            schema.append('CREATE SEQUENCE %s;' % seq)
        lines = []
        for col in table['columns']:
            parts = [ident(col['name']), col['type']]
            if col['textual']:
                parts.append('COLLATE %s' % COLLATION)
            if col['name'] == table['auto']:
                parts.append("DEFAULT nextval('%s')" % seq)
            elif col['default'] is not None:
                parts.append('DEFAULT %s' % col['default'])
            if col['not_null']:
                parts.append('NOT NULL')
            lines.append('    ' + ' '.join(parts))
        if table['pk']:
            lines.append('    PRIMARY KEY (%s)' % ident(table['pk']))
        schema.append('CREATE TABLE %s (\n%s\n);' % (ident(name), ',\n'.join(lines)))
        if seq:
            schema.append('ALTER SEQUENCE %s OWNED BY %s.%s;' % (seq, ident(name), ident(table['auto'])))
        for column in table['indexes']:
            schema.append('CREATE INDEX idx_%s_%s ON %s (%s);' % (name, column, ident(name), ident(column)))
        schema.append('')

    data = ['-- Generated by tools/convert-mysql-dump-to-pgsql.py. Contains real records; do not commit.', '']
    for name, columns, values in inserts:
        data.append('INSERT INTO %s (%s) VALUES\n%s;\n' % (ident(name), ', '.join(columns), values))
    for name in order:
        table = tables[name]
        if not table['auto']:
            continue
        seq = '%s_%s_seq' % (name, table['auto'])
        floor = (table['auto_start'] or 1) - 1
        data.append(
            "SELECT setval('%s', GREATEST(%d, COALESCE(MAX(%s), 0), 1), GREATEST(%d, COALESCE(MAX(%s), 0)) > 0) FROM %s;"
            % (seq, floor, ident(table['auto']), floor, ident(table['auto']), ident(name)))
    data.append('')
    for name in order:
        for constraint, column, ref_table, ref_column, actions in tables[name]['fks']:
            data.append('ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s)%s;' % (
                ident(name), constraint, ident(column), ident(ref_table), ident(ref_column), actions.rstrip()))

    with open(schema_path, 'w', encoding='utf-8', newline='\n') as fh:
        fh.write('\n'.join(schema) + '\n')
    with open(data_path, 'w', encoding='utf-8', newline='\n') as fh:
        fh.write('\n'.join(data) + '\n')

    print('tables=%d inserts=%d auto_increment=%d indexes=%d foreign_keys=%d' % (
        len(order), len(inserts), sum(1 for t in tables.values() if t['auto']),
        sum(len(t['indexes']) for t in tables.values()), sum(len(t['fks']) for t in tables.values())))


if __name__ == '__main__':
    if len(sys.argv) != 4:
        sys.exit(__doc__)
    main(*sys.argv[1:])
