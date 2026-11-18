<?php
declare(strict_types=1);

namespace App\Model\Exception;

use RuntimeException;

/**
 * A write targets a development outside the active scope. Controllers answer it as not found (404).
 *
 * The message is fixed on purpose: it never carries ids, names or any row data.
 */
class DevelopmentScopeViolation extends RuntimeException
{
    /**
     * @return void
     */
    public function __construct()
    {
        parent::__construct('The operation targets a development outside the active scope.');
    }
}
