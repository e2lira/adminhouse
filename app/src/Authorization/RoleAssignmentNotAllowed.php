<?php
declare(strict_types=1);

namespace App\Authorization;

use RuntimeException;

/**
 * The actor may not manage role assignments in that development. Callers answer 404, never 403, so a
 * development the actor cannot manage is indistinguishable from one that does not exist.
 */
final class RoleAssignmentNotAllowed extends RuntimeException
{
}
