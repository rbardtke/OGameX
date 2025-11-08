<?php

namespace OGame\GameObjects\Services\Properties;

use OGame\GameObjects\Services\Properties\Abstracts\ObjectPropertyService;
use OGame\Services\PlayerService;

/**
 * Class AttackPropertyService.
 *
 * @package OGame\Services
 */
class AttackPropertyService extends ObjectPropertyService
{
    protected string $propertyName = 'attack';

    /**
     * @inheritdoc
     */
    protected function getBonusPercentage(PlayerService $player): int
    {
        // Use effective combat research level which includes class bonuses (e.g., General +2)
        $weapons_technology_level = $player->getEffectiveCombatResearchLevel('weapon_technology');
        // Every level technology gives 10% bonus.
        return $weapons_technology_level * 10;
    }
}
