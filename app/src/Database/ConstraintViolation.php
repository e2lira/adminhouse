<?php
declare(strict_types=1);

namespace App\Database;

use PDOException;
use Throwable;

/**
 * A unique (`23505`) or foreign key (`23503`) violation raised by PostgreSQL, for example by a race that the
 * application rules could not see (AD4b).
 *
 * Only the SQLSTATE and the column NAMES are read. The values in the server message are row data and are dropped, and
 * the message is not parsed beyond the `Key (columns)=(` shape, which PostgreSQL emits in every language.
 */
final class ConstraintViolation
{
    private const UNIQUE = '23505';
    private const FOREIGN_KEY = '23503';

    /**
     * @param string $rule `_unique` or `_existsIn`, the rule name an application rule would have used.
     * @param list<string> $columns Columns named by the violated constraint.
     */
    private function __construct(public readonly string $rule, private readonly array $columns)
    {
    }

    /**
     * @param \Throwable $error Anything thrown by a save; the previous chain is searched for the driver error.
     * @return self|null Null when the error is not a unique or foreign key violation.
     */
    public static function from(Throwable $error): ?self
    {
        for ($e = $error; $e !== null; $e = $e->getPrevious()) {
            if (!$e instanceof PDOException || !is_array($e->errorInfo)) {
                continue;
            }
            $rule = match ($e->errorInfo[0] ?? null) {
                self::UNIQUE => '_unique',
                self::FOREIGN_KEY => '_existsIn',
                default => null,
            };
            if ($rule !== null) {
                preg_match('/\(([^()]*)\)=\(/', (string)($e->errorInfo[2] ?? ''), $match);

                return new self($rule, array_map('trim', explode(',', $match[1] ?? '')));
            }
        }

        return null;
    }

    /**
     * The field to flag: the last constrained column that is not the development column.
     *
     * @param string $except Column to skip (the development column, which the user cannot correct).
     * @return string The column, or `_constraint` when the message named none.
     */
    public function field(string $except = 'development_id'): string
    {
        $columns = array_values(array_filter($this->columns, fn(string $c): bool => $c !== '' && $c !== $except));

        return $columns === [] ? '_constraint' : $columns[array_key_last($columns)];
    }
}
