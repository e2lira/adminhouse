<?php
declare(strict_types=1);

namespace App\Database;

use PDOException;
use Throwable;

/**
 * A unique (`23505`) or foreign key (`23503`) violation raised by PostgreSQL, for example by a race that the
 * application rules could not see (AD4b).
 *
 * Only the SQLSTATE, the column NAMES and the constraint name are read. The values in the server message are row data
 * and are dropped, and the message is not parsed beyond the `Key (columns)=(` shape, which PostgreSQL emits in every
 * language, and the identifier that follows the word for the constraint in its first line (`constraint "x"`, or the
 * Spanish `restricción de unicidad «x»` / `llave foránea «x»`; any other wording gives no name). A session under row-level security
 * never receives the DETAIL line (PostgreSQL leaves it out so key values do not leak), so then the columns come from
 * the constraint name, looked up in the catalog by the caller (`withColumns()`).
 */
final class ConstraintViolation
{
    private const UNIQUE = '23505';
    private const FOREIGN_KEY = '23503';

    /**
     * @param string $rule `_unique` or `_existsIn`, the rule name an application rule would have used.
     * @param list<string> $columns Columns named by the violated constraint.
     * @param string|null $constraint Name of the violated constraint, when the message quotes one.
     */
    private function __construct(
        public readonly string $rule,
        private readonly array $columns,
        public readonly ?string $constraint = null,
    ) {
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
                $message = (string)($e->errorInfo[2] ?? '');
                preg_match('/\(([^()]*)\)=\(/', $message, $match);
                // Only the first line (the primary message) is read; the DETAIL line is not.
                $constraint = self::constraintName(strtok($message, "\n") ?: '');

                return new self(
                    $rule,
                    array_map('trim', explode(',', $match[1] ?? '')),
                    $constraint,
                );
            }
        }

        return null;
    }

    /**
     * @param string $line First line of the server message.
     * @return string|null The name that follows the word for the constraint, or null when no known wording matches.
     */
    private static function constraintName(string $line): ?string
    {
        $found = preg_match(
            '/(?:constraint\s+"|(?:unicidad|for[áa]nea)\s+«)([^"«»]+)["»]/u',
            $line,
            $match,
        );

        return $found === 1 ? $match[1] : null;
    }

    /**
     * @param list<string> $columns Columns of the constraint, as the catalog lists them.
     * @return self The same violation, with those columns when the message named none.
     */
    public function withColumns(array $columns): self
    {
        return new self($this->rule, $columns, $this->constraint);
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
