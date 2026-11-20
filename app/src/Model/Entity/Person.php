<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * A person linked to one or more developments. Entity-level policies (App\Policy\PersonPolicy) hang from this class.
 *
 * A person has no development column: the policies judge the person for the development the request is about, which
 * the controller states with `inDevelopment()` (never taken from a request). It is not a column and is never saved.
 *
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $rfc
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class Person extends Entity
{
    /**
     * Only the editable fields are assignable from a request: never the key or the timestamps.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        '*' => false,
        'name' => true,
        'email' => true,
        'phone' => true,
        'rfc' => true,
    ];

    private ?int $development = null;

    /**
     * @param int $developmentId The development the policies judge this person for.
     * @return static
     */
    public function inDevelopment(int $developmentId): static
    {
        $this->development = $developmentId;

        return $this;
    }

    /**
     * @return int|null The development this person is being handled in, or null when none was stated.
     */
    public function development(): ?int
    {
        return $this->development;
    }
}
