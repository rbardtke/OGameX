<?php

namespace OGame\GameObjects\Services\Properties;

use OGame\GameObjects\Models\Fields\GameObjectPropertyDetails;
use OGame\GameObjects\Services\Properties\Abstracts\ObjectPropertyService;
use OGame\Services\PlayerService;

/**
 * Class FuelPropertyService.
 *
 * @package OGame\Services
 */
class FuelPropertyService extends ObjectPropertyService
{
    protected string $propertyName = 'fuel';

    /**
     * Calculate the total value of the fuel property, including:
     * - research bonuses
     * - player class bonuses (General: -25% deuterium consumption)
     *
     * @param PlayerService $player
     * @return GameObjectPropertyDetails
     */
    public function calculateProperty(PlayerService $player): GameObjectPropertyDetails
    {
        $bonusPercentage = $this->getBonusPercentage($player);
        $classBonusPercentage = $this->getClassBonusPercentage($player);

        $bonusValue = (($this->base_value / 100) * $bonusPercentage);
        $classBonusValue = (($this->base_value / 100) * $classBonusPercentage);
        $totalValue = $this->base_value + $bonusValue + $classBonusValue;

        $bonuses = [];

        if ($bonusPercentage != 0) {
            $bonuses[] = [
                'type' => 'Research bonus',
                'value' => $bonusValue,
                'percentage' => $bonusPercentage,
            ];
        }

        if ($classBonusValue != 0) {
            $bonuses[] = [
                'type' => 'Class bonus',
                'value' => $classBonusValue,
                'percentage' => $classBonusPercentage,
            ];
        }

        $breakdown = [
            'rawValue' => $this->base_value,
            'bonuses' => $bonuses,
            'totalValue' => $totalValue,
        ];

        return new GameObjectPropertyDetails($this->base_value, $bonusValue + $classBonusValue, $totalValue, $breakdown);
    }

    /**
     * @inheritdoc
     */
    protected function getBonusPercentage(PlayerService $player): int
    {
        // TODO: implement fuel bonus/extra calculation per object id.
        return 0;
    }

    /**
     * Calculate class-based fuel consumption bonus percentage.
     * General: -25% deuterium consumption for all ships
     *
     * @param PlayerService $player
     * @return int
     */
    protected function getClassBonusPercentage(PlayerService $player): int
    {
        // General class: -25% fuel consumption for all ships
        if ($player->isGeneral()) {
            return -25;
        }

        return 0;
    }
}
