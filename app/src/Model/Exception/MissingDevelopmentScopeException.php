<?php
declare(strict_types=1);

namespace App\Model\Exception;

use LogicException;

/**
 * A development-scoped table was used with no scope: a programming error, never a user error (HTTP 500).
 *
 * Layer 1 fails closed: it throws instead of returning every row (ADR-0005).
 */
class MissingDevelopmentScopeException extends LogicException
{
    /**
     * @param string $table Alias of the scoped table.
     */
    public function __construct(string $table)
    {
        parent::__construct(sprintf(
            'The scoped table `%s` was used without the `developmentScope` option or an active operation scope.',
            $table,
        ));
    }
}
