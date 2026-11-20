<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Development;

/**
 * For the controllers of the records inside a development (units, people): the development named in the URL, resolved
 * under the scope of the permission the action needs, so one outside it answers the same bare 404 as one that does not
 * exist (AC-MD-1). The controller must load `DevelopmentScope`.
 *
 * @property \App\Controller\Component\DevelopmentScopeComponent $DevelopmentScope
 */
trait ResolvesDevelopmentTrait
{
    /**
     * @param string|int $developmentId Development id from the URL.
     * @param string $permission Permission the action needs.
     * @return \App\Model\Entity\Development Only what the screens show (never the bank or contact fields).
     */
    private function developmentUnder(int|string $developmentId, string $permission): Development
    {
        $id = $this->DevelopmentScope->development($developmentId, $permission);
        /** @var \App\Model\Entity\Development */
        return $this->fetchTable('Developments')
            ->find('all', developmentScope: $this->DevelopmentScope->for($permission))
            ->select([
                'Developments.id',
                'Developments.name',
                'Developments.section_label',
                'Developments.number_label',
            ])
            ->where(['Developments.id' => $id])
            ->firstOrFail();
    }
}
