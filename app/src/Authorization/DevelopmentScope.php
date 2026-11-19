<?php
declare(strict_types=1);

namespace App\Authorization;

/**
 * The developments a user may act on for one permission: every development, or an explicit list.
 */
final class DevelopmentScope
{
    /**
     * @param bool $all Whether every development is allowed.
     * @param list<int> $ids Allowed development ids (ignored when `$all`).
     */
    private function __construct(private readonly bool $all, private readonly array $ids)
    {
    }

    /**
     * @return self
     */
    public static function all(): self
    {
        return new self(true, []);
    }

    /**
     * @return self
     */
    public static function none(): self
    {
        return new self(false, []);
    }

    /**
     * @param list<int> $ids Development ids.
     * @return self
     */
    public static function only(array $ids): self
    {
        $ids = array_values(array_unique($ids));
        sort($ids);

        return new self(false, $ids);
    }

    /**
     * @return bool
     */
    public function isAll(): bool
    {
        return $this->all;
    }

    /**
     * @return bool True when the scope allows no development at all.
     */
    public function isEmpty(): bool
    {
        return !$this->all && $this->ids === [];
    }

    /**
     * @return list<int> Allowed ids, ascending. Empty for "all": check isAll() first.
     */
    public function ids(): array
    {
        return $this->ids;
    }

    /**
     * @param int $developmentId Development id.
     * @return bool
     */
    public function allows(int $developmentId): bool
    {
        return $this->all || in_array($developmentId, $this->ids, true);
    }

    /**
     * The scope that allows only what both scopes allow: an inner scope may narrow an outer one, never widen it.
     *
     * @param self $other Other scope.
     * @return self
     */
    public function intersect(self $other): self
    {
        if ($this->all) {
            return $other;
        }
        if ($other->all) {
            return $this;
        }

        return self::only(array_values(array_intersect($this->ids, $other->ids)));
    }
}
