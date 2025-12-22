<?php

namespace OGame\Services;

use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Models\Highscore;
use OGame\Models\User;

/**
 * Class HonorService.
 *
 * This class is responsible for handling honor points logic. This covers
 * calculating honor points from battles, determining honorable target status,
 * and managing honor ranks.
 *
 * @package OGame\Services
 */
class HonorService
{
    /**
     * Calculate honor points gained/lost from a battle.
     *
     * Formula: HP = (Units destroyed ^ 0.9) / 1000
     * Minimum 1% of total combat units must be destroyed to earn/lose points.
     *
     * @param UnitCollection $unitsDestroyed The units that were destroyed in combat.
     * @param UnitCollection $totalDefenderUnits The total units the defender had at start.
     * @param PlayerService $attackerPlayer The attacking player.
     * @param PlayerService $defenderPlayer The defending player.
     * @return array{attacker: int, defender: int} Honor points change for attacker and defender.
     */
    public function calculateHonorPointsFromBattle(
        UnitCollection $unitsDestroyed,
        UnitCollection $totalDefenderUnits,
        PlayerService $attackerPlayer,
        PlayerService $defenderPlayer
    ): array {
        // Calculate total value of units destroyed (exclude civil ships)
        $destroyedValue = $this->calculateCombatUnitValue($unitsDestroyed);
        $totalDefenderValue = $this->calculateCombatUnitValue($totalDefenderUnits);

        // Check 1% minimum threshold
        if ($totalDefenderValue > 0 && ($destroyedValue / $totalDefenderValue) < 0.01) {
            return ['attacker' => 0, 'defender' => 0];
        }

        // Calculate base honor points using formula: HP = (Units destroyed ^ 0.9) / 1000
        $baseHonorPoints = (int)floor(pow($destroyedValue, 0.9) / 1000);

        // Determine if target is honorable using their units at battle start
        $isHonorableTarget = $this->isHonorableTarget($attackerPlayer, $defenderPlayer, $totalDefenderUnits);

        if ($isHonorableTarget) {
            // Honorable combat: attacker gains points, defender gains points if they destroyed units
            return [
                'attacker' => $baseHonorPoints,
                'defender' => 0, // Defender gains will be calculated from attacker's losses separately
            ];
        } else {
            // Dishonorable combat: attacker loses points
            return [
                'attacker' => -$baseHonorPoints,
                'defender' => 0,
            ];
        }
    }

    /**
     * Determine if a target is honorable based on military points and rank.
     *
     * A target is honorable if any of these conditions are met:
     * - Attacker has fewer or equal military points than target
     * - Target retains 50%+ of attacker's military points
     * - Target is within 10 military points of attacker
     * - Target ranks no more than 100 positions behind attacker
     * - Target holds bandit status (honor_points < 0)
     *
     * @param PlayerService $attackerPlayer The attacking player.
     * @param PlayerService $defenderPlayer The defending player.
     * @param UnitCollection|null $defenderUnitsAtBattleStart Optional: defender's units at battle start for more accurate calculation
     * @return bool True if target is honorable, false otherwise.
     */
    public function isHonorableTarget(PlayerService $attackerPlayer, PlayerService $defenderPlayer, UnitCollection|null $defenderUnitsAtBattleStart = null): bool
    {
        // Get military points from highscore
        $attackerHighscore = Highscore::where('player_id', $attackerPlayer->getId())->first();
        $defenderHighscore = Highscore::where('player_id', $defenderPlayer->getId())->first();

        if (!$attackerHighscore || !$defenderHighscore) {
            // If no highscore data, consider honorable
            return true;
        }

        $attackerMilitaryPoints = $attackerHighscore->military ?? 0;

        // If defender units at battle start are provided, calculate their military value
        // This is more accurate than using potentially outdated highscore data
        if ($defenderUnitsAtBattleStart !== null) {
            $defenderMilitaryValue = $this->calculateCombatUnitValue($defenderUnitsAtBattleStart);
            $defenderMilitaryPoints = (int)($defenderMilitaryValue / 1000); // Convert to points
        } else {
            $defenderMilitaryPoints = $defenderHighscore->military ?? 0;
        }

        $attackerMilitaryRank = $attackerHighscore->military_rank ?? 999999;
        $defenderMilitaryRank = $defenderHighscore->military_rank ?? 999999;

        // Check if defender is a bandit (negative honor points)
        $defenderUser = $defenderPlayer->getUser();
        if ($defenderUser->honor_points < 0) {
            return true;
        }

        // Check if attacker has fewer or equal military points than defender
        if ($attackerMilitaryPoints <= $defenderMilitaryPoints) {
            return true;
        }

        // Check if defender has 50%+ of attacker's military points
        if ($defenderMilitaryPoints >= ($attackerMilitaryPoints * 0.5)) {
            return true;
        }

        // Check if within 10 military points
        if (abs($attackerMilitaryPoints - $defenderMilitaryPoints) <= 10) {
            return true;
        }

        // Check if defender rank is within 100 positions of attacker
        // Lower rank number = better rank, so defender can be up to 100 positions worse (higher number)
        // Only apply rank check if:
        // 1. We're using highscore data (not calculated from units) - rank is only meaningful with highscore
        // 2. Defender has meaningful military points (at least 1000)
        // 3. Defender is within range: from attacker's rank up to 100 positions worse
        if ($defenderUnitsAtBattleStart === null &&
            $defenderMilitaryPoints >= 1000 &&
            $defenderMilitaryRank >= $attackerMilitaryRank &&
            $defenderMilitaryRank <= ($attackerMilitaryRank + 100)) {
            return true;
        }

        // Not an honorable target
        return false;
    }

    /**
     * Calculate the total value of combat units (exclude civil ships).
     *
     * Civil ships like Small Cargo, Large Cargo, Colony Ship, Recycler, and Espionage Probe
     * are excluded from honor point calculations.
     *
     * @param UnitCollection $units The units to calculate value for.
     * @return int Total value of combat units.
     */
    private function calculateCombatUnitValue(UnitCollection $units): int
    {
        $civilShips = [
            'small_cargo',
            'large_cargo',
            'colony_ship',
            'recycler',
            'espionage_probe',
        ];

        $totalValue = 0;
        foreach ($units->units as $unit) {
            // Skip civil ships
            if (in_array($unit->unitObject->machine_name, $civilShips)) {
                continue;
            }

            // Calculate unit value (metal + crystal + deuterium)
            $price = \OGame\Services\ObjectService::getObjectRawPrice($unit->unitObject->machine_name);
            $unitValue = $price->metal->get() + $price->crystal->get() + $price->deuterium->get();

            $totalValue += $unitValue * $unit->amount;
        }

        return $totalValue;
    }

    /**
     * Get the honor rank name for a given honor points value.
     *
     * @param int $honorPoints The honor points value.
     * @param int $playerRank The player's general rank position.
     * @return string|null The rank identifier (e.g., 'rank_starlord2', 'rank_bandit1') or null.
     */
    public function getHonorRank(int $honorPoints, int $playerRank): string|null
    {
        // Negative honor = Bandit ranks
        if ($honorPoints < 0) {
            $absHonor = abs($honorPoints);
            if ($absHonor >= 10000) {
                return 'rank_bandit3'; // Bandit King
            } elseif ($absHonor >= 5000) {
                return 'rank_bandit2'; // Bandit Lord
            } elseif ($absHonor >= 1000) {
                return 'rank_bandit1'; // Bandit
            }
            return null;
        }

        // Positive honor ranks require both points and rank position
        if ($honorPoints >= 10000 && $playerRank <= 10) {
            return 'rank_starlord3'; // Grand Emperor
        } elseif ($honorPoints >= 5000 && $playerRank <= 50) {
            return 'rank_starlord2'; // Emperor
        } elseif ($honorPoints >= 1000 && $playerRank <= 100) {
            return 'rank_starlord1'; // Star Lord
        }

        return null;
    }

    /**
     * Award honor points to a player.
     *
     * @param User $user The user to award points to.
     * @param int $points The number of points to award (can be negative).
     * @return void
     */
    public function awardHonorPoints(User $user, int $points): void
    {
        if ($points == 0) {
            return;
        }

        $user->honor_points += $points;
        $user->save();
    }
}
