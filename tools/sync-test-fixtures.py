#!/usr/bin/env python3
"""Align baked CakePHP test fixtures with the production-derived schema.

Usage:
    python -I tools/sync-test-fixtures.py [--check] [FixtureName ...]

Reads `config/schema/kualitet-postgresql.sql` (generated from the production dump by
`tools/convert-mysql-dump-to-pgsql.py`) and, for each fixture whose `$fields` differ
from its table, rewrites the `$fields` block and removes record keys for columns that
no longer exist. Required columns without a default that a record lacks get a sample
value. `--check` only reports drift and exits 1 when any is found.
"""
import glob
import os
import re
import sys

SCHEMA = 'config/schema/kualitet-postgresql.sql'


def load_schema():
    text = open(SCHEMA, encoding='utf-8').read()
    tables = {}
    for name, body in re.findall(r'CREATE TABLE "?(\w+)"? \((.*?)\n\);', text, re.S):
        cols, pk = [], None
        for line in body.split('\n'):
            pkm = re.match(r'\s+PRIMARY KEY \("?(\w+)"?\)', line)
            if pkm:
                pk = pkm.group(1)
                continue
            m = re.match(r'\s+"?(\w+)"? (.+?),?$', line)
            if m:
                cols.append((m.group(1), m.group(2)))
        tables[name] = (cols, pk)
    return tables


def php(value):
    if value is None:
        return 'null'
    if value is True:
        return 'true'
    if value is False:
        return 'false'
    if isinstance(value, int):
        return str(value)
    return "'" + str(value).replace("'", "\\'") + "'"


def column_definition(spec, is_pk):
    s = spec.strip()
    not_null = ' NOT NULL' in s
    auto = 'nextval(' in s
    default = None
    dm = re.search(r"DEFAULT ('(?:[^']|'')*'|[^\s]+)", s)
    if dm and not auto:
        raw = dm.group(1)
        if raw.upper() == 'CURRENT_TIMESTAMP':
            default = 'CURRENT_TIMESTAMP'
        elif raw in ('true', 'false'):
            default = raw == 'true'
        elif re.match(r'^-?\d+$', raw):
            default = int(raw)
        else:
            default = raw.strip("'")
    base = s.split(' COLLATE')[0].split(' DEFAULT')[0].split(' NOT NULL')[0].strip()
    d = {'null': not not_null, 'default': default}
    if base == 'integer':
        d.update(type='integer', length=11, unsigned=False)
    elif base == 'bigint':
        d.update(type='biginteger', length=20, unsigned=False)
    elif base == 'smallint':
        d.update(type='smallinteger', length=6, unsigned=False)
    elif base == 'boolean':
        d.update(type='boolean', length=None)
    elif base.startswith('numeric'):
        p, sc = re.match(r'numeric\((\d+),(\d+)\)', base).groups()
        d.update(type='decimal', length=int(p), precision=int(sc), unsigned=False)
    elif base in ('real', 'double precision'):
        d.update(type='float', length=None, unsigned=False)
    elif base.startswith('varchar'):
        d.update(type='string', length=int(re.search(r'\d+', base).group(0)))
    elif base == 'text':
        d.update(type='text', length=None)
    elif base == 'date':
        d.update(type='date', length=None)
    elif base == 'time':
        d.update(type='time', length=None)
    elif base.startswith('timestamp'):
        d.update(type='datetime', length=None)
    else:
        raise ValueError('Unsupported column type: ' + s)
    if auto or (is_pk and d['type'] in ('integer', 'biginteger')):
        d['autoIncrement'] = True
    order = ['type', 'length', 'precision', 'unsigned', 'null', 'default', 'autoIncrement']
    return '[' + ', '.join("'%s' => %s" % (k, php(d[k])) for k in order if k in d) + ']'


def sample(defn):
    t = re.search(r"'type' => '(\w+)'", defn).group(1)
    return {'integer': '1', 'biginteger': '1', 'smallinteger': '1', 'boolean': 'true', 'decimal': '1.5',
            'float': '1.5', 'string': "'Lorem'", 'text': "'Lorem'", 'date': "'2025-01-01'",
            'time': "'10:00:00'", 'datetime': "'2025-01-01 10:00:00'"}[t]


def snake(name):
    return re.sub(r'(?<!^)([A-Z])', r'_\1', name).lower()


def main(argv):
    check = '--check' in argv
    only = [a for a in argv if not a.startswith('--')]
    tables = load_schema()
    drift = 0
    for path in sorted(glob.glob('tests/Fixture/*Fixture.php')):
        cls = os.path.basename(path)[:-len('Fixture.php')]
        if only and cls not in only:
            continue
        text = open(path, encoding='utf-8').read()
        tm = re.search(r"public \$table\s*=\s*'(\w+)'", text)
        table = tm.group(1) if tm else snake(cls)
        if table not in tables:
            continue
        cols, pk = tables[table]
        fm = re.search(r'(    public \$fields = \[\n)(.*?)(\n    \];)', text, re.S)
        current = [c for c in re.findall(r"^\s{8}'(\w+)' =>", fm.group(2), re.M) if not c.startswith('_')]
        names = [c for c, _ in cols]
        broken_type = "'type' => ''" in fm.group(2)
        if current == names and not broken_type:
            continue
        drift += 1
        print('%-20s missing=%s extra=%s%s' % (cls, [c for c in names if c not in current],
                                                [c for c in current if c not in names],
                                                ' (empty type)' if broken_type else ''))
        if check:
            continue
        defs = {c: column_definition(s, c == pk) for c, s in cols}
        lines = ["        '%s' => %s," % (c, defs[c]) for c in names]
        if pk:
            lines.append("        '_constraints' => [\n            'primary' => ['type' => 'primary', 'columns' => ['%s'], 'length' => []],\n        ]," % pk)
        text = text[:fm.start(2)] + '\n'.join(lines) + text[fm.end(2):]

        rm = re.search(r'(\$this->records = \[)(.*?)(\n        \];)', text, re.S)
        if rm:
            records = rm.group(2)
            records = re.sub(r"\n\s+'(\w+)' => [^\n]*,?(?=\n)",
                             lambda m: m.group(0) if m.group(1) in names else '', records)
            required = [c for c, s in cols if ' NOT NULL' in s and 'DEFAULT' not in s]

            def fill(record):
                block = record.group(0)
                for c in required:
                    if "'%s' =>" % c not in block:
                        block = block.replace('\n            ],', "\n                '%s' => %s,\n            ]," % (c, sample(defs[c])), 1)
                return block
            records = re.sub(r'\n            \[.*?\n            \],', fill, records, flags=re.S)
            text = text[:rm.start(2)] + records + text[rm.end(2):]
        open(path, 'w', encoding='utf-8', newline='\n').write(text)
    print('fixtures with drift: %d%s' % (drift, ' (check only)' if check else ' (rewritten)'))
    return 1 if (check and drift) else 0


if __name__ == '__main__':
    sys.exit(main(sys.argv[1:]))
