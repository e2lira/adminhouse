<?php
declare(strict_types=1);

namespace App\Test\Support\Architecture;

use App\Model\Table\ScopedBulkOperationsTrait;
use Cake\ORM\Association;
use Cake\ORM\Locator\LocatorInterface;
use Cake\ORM\Table;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

/**
 * Static checks behind the architecture tests of the development scope (AD2, AD4c). Every check takes the tables or
 * the source it audits as arguments, so the tests can prove that each one fails on a fixture.
 *
 * Nothing here touches the database: associations are inspected from their declaration, and the binding key (which
 * can need schema reflection) is read only when the foreign key already points at a development column.
 */
final class ScopedTableAudit
{
    public const BEHAVIOR = 'DevelopmentScoped';

    private const TABLE_DIRECTORIES = [
        'production' => ['Model' . DIRECTORY_SEPARATOR . 'Table', 'App\\Model\\Table', 'src'],
        'support' => ['Scoped', 'App\\Test\\Support\\Scoped', 'tests' . DIRECTORY_SEPARATOR . 'Support'],
    ];

    /**
     * @return string Absolute path of the application root (`app`).
     */
    private static function root(): string
    {
        return dirname(__DIR__, 3);
    }

    /**
     * @param bool $production Include the tables in `src/Model/Table`.
     * @param bool $support Include the test-only scoped tables in `tests/Support/Scoped`.
     * @return list<class-string<\Cake\ORM\Table>>
     */
    public static function discover(bool $production = true, bool $support = true): array
    {
        $classes = [];
        foreach (['production' => $production, 'support' => $support] as $group => $wanted) {
            if (!$wanted) {
                continue;
            }
            [$directory, $namespace, $base] = self::TABLE_DIRECTORIES[$group];
            $path = self::root() . DIRECTORY_SEPARATOR . $base . DIRECTORY_SEPARATOR . $directory;
            foreach (glob($path . DIRECTORY_SEPARATOR . '*Table.php') ?: [] as $file) {
                $class = $namespace . '\\' . basename($file, '.php');
                if (
                    class_exists($class) && is_subclass_of($class, Table::class)
                    && !(new ReflectionClass($class))->isAbstract()
                ) {
                    $classes[] = $class;
                }
            }
        }
        sort($classes);

        return $classes;
    }

    /**
     * @param list<class-string<\Cake\ORM\Table>> $classes Table classes.
     * @param \Cake\ORM\Locator\LocatorInterface $locator Locator that builds them (and their association targets).
     * @return list<\Cake\ORM\Table>
     */
    public static function load(array $classes, LocatorInterface $locator): array
    {
        $tables = [];
        foreach ($classes as $class) {
            $alias = preg_replace('/Table$/', '', self::shortName($class));
            $tables[] = $locator->exists($alias)
                ? $locator->get($alias)
                : $locator->get($alias, ['className' => $class]);
        }

        return $tables;
    }

    /**
     * @param \Cake\ORM\Table $table Table.
     * @return bool Whether the table adds the development-scope behavior.
     */
    public static function isScoped(Table $table): bool
    {
        return $table->hasBehavior(self::BEHAVIOR);
    }

    /**
     * @param list<\Cake\ORM\Table> $tables Tables.
     * @return list<class-string> Scoped tables that do not use the bulk-operations trait.
     */
    public static function missingBulkGuard(array $tables): array
    {
        $missing = [];
        foreach ($tables as $table) {
            if (self::isScoped($table) && !in_array(ScopedBulkOperationsTrait::class, self::allTraits($table::class), true)) {
                $missing[] = $table::class;
            }
        }

        return $missing;
    }

    /**
     * An association may enter a scoped table only from a scoped table whose foreign key equates the two development
     * columns (`development_id` to `development_id`, or `id` to `development_id` for a development and its children).
     * Anything else must be whitelisted, with a reason, as `SourceTable::AssociationName`.
     *
     * @param list<\Cake\ORM\Table> $tables Tables.
     * @param array<string, string> $whitelist Allowed entries and why.
     * @return list<string> One message per association that is neither safe nor listed.
     */
    public static function associationProblems(array $tables, array $whitelist): array
    {
        $problems = [];
        foreach ($tables as $source) {
            foreach ($source->associations() as $association) {
                $target = $association->getTarget();
                $label = self::label($source, $association);
                if (!self::isScoped($target) || isset($whitelist[$label])) {
                    continue;
                }
                if (!self::isScoped($source)) {
                    $problems[] = $label . ' enters a scoped table from an unscoped one.';
                } elseif (!self::equatesDevelopmentColumns($association, $source, $target)) {
                    $problems[] = $label . ' enters a scoped table without equating the development columns.';
                }
            }
        }

        return $problems;
    }

    /**
     * @param list<\Cake\ORM\Table> $tables Tables.
     * @param array<string, string> $whitelist Allowed entries and why.
     * @return list<string> Entries that no longer name an association that enters a scoped table.
     */
    public static function staleWhitelistEntries(array $tables, array $whitelist): array
    {
        $live = [];
        foreach ($tables as $source) {
            foreach ($source->associations() as $association) {
                if (self::isScoped($association->getTarget())) {
                    $live[self::label($source, $association)] = true;
                }
            }
        }

        return array_values(array_filter(array_keys($whitelist), fn(string $entry) => !isset($live[$entry])));
    }

    /**
     * @param list<\Cake\ORM\Table> $tables Tables.
     * @return list<string> Database table names (without schema) of the scoped tables.
     */
    public static function scopedTableNames(array $tables): array
    {
        $names = [];
        foreach ($tables as $table) {
            if (self::isScoped($table)) {
                $names[] = substr(strrchr('.' . $table->getTable(), '.'), 1);
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * Scans `src` (except `src/Database`, which owns RLS and provisioning SQL; migrations live outside `src`) for
     * raw SQL or manual joins that name a scoped table. Residual risk: SQL assembled from pieces escapes the scan and
     * is bounded only by row-level security.
     *
     * @param list<string> $names Scoped table names.
     * @return list<string> `path: snippet` for each hit.
     */
    public static function rawSqlOffenders(array $names): array
    {
        $src = self::root() . DIRECTORY_SEPARATOR . 'src';
        $database = $src . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR;
        $offenders = [];

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            $path = $file->getPathname();
            if ($file->getExtension() !== 'php' || str_starts_with($path, $database)) {
                continue;
            }
            foreach (self::rawSqlHits((string)file_get_contents($path), $names) as $hit) {
                $offenders[] = substr($path, strlen($src) + 1) . ': ' . $hit;
            }
        }

        return $offenders;
    }

    /**
     * @param string $source PHP source.
     * @param list<string> $names Scoped table names.
     * @return list<string> Snippets of the SQL or join calls that name one of them; comments are ignored.
     */
    public static function rawSqlHits(string $source, array $names): array
    {
        if ($names === []) {
            return [];
        }
        $code = '';
        foreach (token_get_all($source) as $token) {
            if (is_array($token)) {
                if ($token[0] !== T_COMMENT && $token[0] !== T_DOC_COMMENT) {
                    $code .= $token[1];
                }
            } else {
                $code .= $token;
            }
        }

        $name = '(?:' . implode('|', array_map(fn(string $n) => preg_quote($n, '/'), $names)) . ')';
        $patterns = [
            '/\b(?:FROM|JOIN|UPDATE|INTO)\s+(?:ONLY\s+)?["`]?(?:\w+\.)?' . $name . '(?!\w)/i',
            '/->\s*(?:(?:inner|left|right)J|j)oin\s*\([^;]{0,300}?[\'"](?:\w+\.)?' . $name . '[\'"]/s',
            '/->\s*from\s*\([^;]{0,200}?[\'"](?:\w+\.)?' . $name . '[\'"]/s',
        ];
        $hits = [];
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $code, $matches) > 0) {
                foreach ($matches[0] as $match) {
                    $hits[] = trim((string)preg_replace('/\s+/', ' ', $match));
                }
            }
        }

        return $hits;
    }

    private static function label(Table $source, Association $association): string
    {
        return self::shortName($source::class) . '::' . $association->getName();
    }

    private static function shortName(string $class): string
    {
        return substr(strrchr('\\' . $class, '\\'), 1);
    }

    /**
     * @param \Cake\ORM\Table $table Table.
     * @return string|null The column that holds the development, or null for a through-mode table (no such column).
     */
    private static function developmentField(Table $table): ?string
    {
        $config = $table->behaviors()->get(self::BEHAVIOR)->getConfig();

        return $config['through'] !== null ? null : (string)$config['field'];
    }

    private static function equatesDevelopmentColumns(Association $association, Table $source, Table $target): bool
    {
        $sourceField = self::developmentField($source);
        $targetField = self::developmentField($target);
        if ($sourceField === null || $targetField === null || $association->type() === Association::MANY_TO_MANY) {
            return false;
        }

        $foreignKey = (array)$association->getForeignKey();
        $ownsForeignKey = $association->type() === Association::MANY_TO_ONE ? $sourceField : $targetField;
        // Read the binding key (it can need schema reflection) only once the foreign key is on the development column.
        if (!in_array($ownsForeignKey, $foreignKey, true)) {
            return false;
        }

        $bindingKey = (array)$association->getBindingKey();
        [$sourceColumns, $targetColumns] = $association->type() === Association::MANY_TO_ONE
            ? [$foreignKey, $bindingKey]
            : [$bindingKey, $foreignKey];
        foreach ($sourceColumns as $i => $column) {
            if ($column === $sourceField && ($targetColumns[$i] ?? null) === $targetField) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param class-string $class Class.
     * @return list<string> Every trait the class uses, through parents and through other traits.
     */
    private static function allTraits(string $class): array
    {
        $traits = [];
        $pending = [];
        for ($c = $class; $c !== false; $c = get_parent_class($c)) {
            $pending = array_merge($pending, array_values(class_uses($c) ?: []));
        }
        while ($pending !== []) {
            $trait = array_pop($pending);
            if (!in_array($trait, $traits, true)) {
                $traits[] = $trait;
                $pending = array_merge($pending, array_values(class_uses($trait) ?: []));
            }
        }

        return $traits;
    }
}
