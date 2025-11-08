<?php

namespace OGame\GameObjects\Services\Properties;

use OGame\GameObjects\Models\Fields\GameObjectPropertyDetails;
use OGame\GameObjects\Services\Properties\Abstracts\ObjectPropertyService;
use OGame\Services\PlayerService;

/**
 * Class CapacityPropertyService.
 *
 * @package OGame\Services
 */
class CapacityPropertyService extends ObjectPropertyService
{
    protected string $propertyName = 'capacity';

    /**
     * Calculate the total value of the capacity property, including:
     * - hyperspace technology bonus
     * - player class bonuses
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

        $bonuses = [
            [
                'type' => 'Research bonus',
                'value' => $bonusValue,
                'percentage' => $bonusPercentage,
            ],
        ];

        if ($classBonusValue > 0) {
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
     * @inheritDoc
     */
    protected function getBonusPercentage(PlayerService $player): int
    {
        $hyperspace_technology_level = $player->getResearchLevel('hyperspace_technology');
        return 5 * $hyperspace_technology_level;
    }

    /**
     * Calculate class-based cargo capacity bonus percentage.
     * Collector: +25% for cargo ships (small_cargo, large_cargo)
     * General: +25% for recyclers and pathfinders
     *
     * @param PlayerService $player
     * @return int
     */
    protected function getClassBonusPercentage(PlayerService $player): int
    {
        $object = $this->parent_object;
        $machineName = $object->machine_name;

        // Collector class: +25% cargo capacity for cargo ships
        if ($player->isCollector()) {
            if (in_array($machineName, ['small_cargo', 'large_cargo'])) {
                return 25;
            }
        }

        // General class: additional cargo on recyclers and pathfinders
        if ($player->isGeneral()) {
            if (in_array($machineName, ['recycler', 'pathfinder'])) {
                return 25;
            }
        }

        return 0;
    }
}
