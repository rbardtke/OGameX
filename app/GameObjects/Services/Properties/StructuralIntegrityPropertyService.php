<?php

namespace OGame\GameObjects\Services\Properties;

use Exception;
use OGame\GameObjects\Services\Properties\Abstracts\ObjectPropertyService;
use OGame\Services\PlayerService;

/**
 * Class StructuralIntegrityPropertyService.
 *
 * @package OGame\Services
 */
class StructuralIntegrityPropertyService extends ObjectPropertyService
{
    protected string $propertyName = 'structural_integrity';

    /**
     * @inheritdoc
     * @throws Exception
     */
    protected function getBonusPercentage(PlayerService $player): int
    {
        // Use effective combat research level which includes class bonuses (e.g., General +2)
        $armor_technology_level = $player->getEffectiveCombatResearchLevel('armor_technology');
        // Every level of armor technology gives 10% bonus.
        return $armor_technology_level * 10;
    }
}
