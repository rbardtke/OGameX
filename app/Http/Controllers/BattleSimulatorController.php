<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use OGame\GameMissions\BattleEngine\PhpBattleEngine;
use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Services\ObjectService;
use OGame\Services\SettingsService;
use OGame\ViewModels\UnitViewModel;

class BattleSimulatorController extends Controller
{
    public function index(): View
    {
        // Get all ships for both attacker and defender
        $ships = [];
        foreach (ObjectService::getShipObjects() as $ship) {
            $ships[] = new UnitViewModel($ship, 0, 0);
        }

        // Get all defense units for defender
        $defense = [];
        foreach (ObjectService::getDefenseObjects() as $defenseUnit) {
            $defense[] = new UnitViewModel($defenseUnit, 0, 0);
        }

        return view('ingame.battlesimulator', [
            'ships' => $ships,
            'defense' => $defense,
        ]);
    }

    public function simulate(Request $request)
    {
        // Parse attacker fleet
        $attackerData = $request->input('attacker', []);
        $attackerFleet = $this->createUnitCollection($attackerData);

        // Parse defender fleet
        $defenderData = $request->input('defender', []);
        $defenderFleet = $this->createUnitCollection($defenderData);

        // Get tech levels
        $attackerWeapon = (int)$request->input('attacker_weapon', 0);
        $attackerShield = (int)$request->input('attacker_shield', 0);
        $attackerArmor = (int)$request->input('attacker_armor', 0);

        $defenderWeapon = (int)$request->input('defender_weapon', 0);
        $defenderShield = (int)$request->input('defender_shield', 0);
        $defenderArmor = (int)$request->input('defender_armor', 0);

        // Create mock player services with tech levels
        $attackerPlayer = new \OGame\Services\PlayerService();
        $attackerPlayer->setTechLevels([
            'weapon_technology' => $attackerWeapon,
            'shielding_technology' => $attackerShield,
            'armor_technology' => $attackerArmor,
        ]);

        $defenderPlayer = new \OGame\Services\PlayerService();
        $defenderPlayer->setTechLevels([
            'weapon_technology' => $defenderWeapon,
            'shielding_technology' => $defenderShield,
            'armor_technology' => $defenderArmor,
        ]);

        // Create a mock planet service
        $mockPlanet = new \OGame\Services\PlanetService();
        $mockPlanet->setDefenderFleet($defenderFleet);
        $mockPlanet->setPlayer($defenderPlayer);

        $settingsService = resolve(SettingsService::class);

        // Run battle simulation
        $battleEngine = new PhpBattleEngine($attackerFleet, $attackerPlayer, $mockPlanet, $settingsService);
        $battleResult = $battleEngine->simulateBattle();

        return response()->json([
            'success' => true,
            'result' => [
                'rounds' => count($battleResult->rounds),
                'attacker_start' => $battleResult->attackerUnitsStart->getAmount(),
                'defender_start' => $battleResult->defenderUnitsStart->getAmount(),
                'attacker_end' => $battleResult->attackerUnitsResult->getAmount(),
                'defender_end' => $battleResult->defenderUnitsResult->getAmount(),
                'attacker_losses' => $battleResult->attackerUnitsStart->getAmount() - $battleResult->attackerUnitsResult->getAmount(),
                'defender_losses' => $battleResult->defenderUnitsStart->getAmount() - $battleResult->defenderUnitsResult->getAmount(),
                'winner' => $this->determineWinner($battleResult),
                'attacker_units_result' => $this->formatUnits($battleResult->attackerUnitsResult),
                'defender_units_result' => $this->formatUnits($battleResult->defenderUnitsResult),
            ],
        ]);
    }

    private function createUnitCollection(array $units): UnitCollection
    {
        $fleet = new UnitCollection();

        foreach ($units as $unitName => $amount) {
            if ($amount > 0) {
                try {
                    $unit = ObjectService::getUnitObjectByMachineName($unitName);
                    $fleet->addUnit($unit, (int)$amount);
                } catch (\Exception $e) {
                    // Skip invalid units
                    continue;
                }
            }
        }

        return $fleet;
    }

    private function determineWinner($battleResult): string
    {
        $attackerRemaining = $battleResult->attackerUnitsResult->getAmount();
        $defenderRemaining = $battleResult->defenderUnitsResult->getAmount();

        if ($attackerRemaining > 0 && $defenderRemaining === 0) {
            return 'attacker';
        } elseif ($defenderRemaining > 0 && $attackerRemaining === 0) {
            return 'defender';
        } else {
            return 'draw';
        }
    }

    private function formatUnits(UnitCollection $units): array
    {
        $result = [];
        foreach ($units->units as $unit) {
            if ($unit->amount > 0) {
                $result[] = [
                    'name' => $unit->unitObject->title,
                    'amount' => $unit->amount,
                ];
            }
        }
        return $result;
    }
}
