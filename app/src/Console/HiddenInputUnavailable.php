<?php
declare(strict_types=1);

namespace App\Console;

use RuntimeException;

/**
 * The terminal cannot read a secret without showing it, so the caller must not fall back to echoed input.
 */
class HiddenInputUnavailable extends RuntimeException
{
}
