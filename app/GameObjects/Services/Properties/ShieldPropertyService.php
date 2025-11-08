<?php

namespace OGame\GameObjects\Services\Properties;

use OGame\GameObjects\Services\Properties\Abstracts\ObjectPropertyService;
use OGame\Services\PlayerService;

/**
 * Class ShieldPropertyService.
 *
 * @package OGame\Services
 */
class ShieldPropertyService extends ObjectPropertyService
{
    protected string $propertyName = 'shield';

    /**
     * @inheritdoc
     */
    protected function getBonusPercentage(PlayerService $player): int
    {
        // Use effective combat research level which includes class bonuses (e.g., General +2)
        $shielding_technology_level = $player->getEffectiveCombatResearchLevel('shielding_technology');
        // Every level technology gives 10% bonus.
        return $shielding_technology_level * 10;
    }
}
