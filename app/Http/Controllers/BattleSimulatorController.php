<?php

namespace OGame\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use OGame\GameMissions\BattleEngine\PhpBattleEngine;
use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Services\ObjectService;
use OGame\Services\SettingsService;
use OGame\ViewModels\UnitViewModel;

class BattleSimulatorController extends OGameController
{
    public function index(): View
    {
        // Check if battle simulator is enabled
        $settingsService = resolve(SettingsService::class);
        if (!$settingsService->battleSimulatorEnabled()) {
            abort(403, 'Battle Simulator is disabled by the administrator.');
        }

        // Get all ships for attacker (excluding solar satellites)
        $attackerShips = [];
        $count = 0;
        foreach (ObjectService::getShipObjects() as $ship) {
            // Skip solar satellites for attacker
            if ($ship->machine_name === 'solar_satellite') {
                continue;
            }
            $count++;
            $viewModel = new UnitViewModel();
            $viewModel->object = $ship;
            $viewModel->count = $count;
            $viewModel->amount = 0;
            $viewModel->requirements_met = false;
            $viewModel->enough_resources = false;
            $viewModel->max_build_amount = 0;
            $viewModel->currently_building = false;
            $viewModel->currently_building_amount = 0;
            $attackerShips[] = $viewModel;
        }

        // Get all ships for defender (including solar satellites)
        $defenderShips = [];
        foreach (ObjectService::getShipObjects() as $ship) {
            $count++;
            $viewModel = new UnitViewModel();
            $viewModel->object = $ship;
            $viewModel->count = $count;
            $viewModel->amount = 0;
            $viewModel->requirements_met = false;
            $viewModel->enough_resources = false;
            $viewModel->max_build_amount = 0;
            $viewModel->currently_building = false;
            $viewModel->currently_building_amount = 0;
            $defenderShips[] = $viewModel;
        }

        // Get all defense units for defender
        $defense = [];
        foreach (ObjectService::getDefenseObjects() as $defenseUnit) {
            $count++;
            $viewModel = new UnitViewModel();
            $viewModel->object = $defenseUnit;
            $viewModel->count = $count;
            $viewModel->amount = 0;
            $viewModel->requirements_met = false;
            $viewModel->enough_resources = false;
            $viewModel->max_build_amount = 0;
            $viewModel->currently_building = false;
            $viewModel->currently_building_amount = 0;
            $defense[] = $viewModel;
        }

        return view('ingame.battlesimulator', [
            'attackerShips' => $attackerShips,
            'defenderShips' => $defenderShips,
            'defense' => $defense,
        ]);
    }

    public function simulate(Request $request)
    {
        // Check if battle simulator is enabled
        $settingsService = resolve(SettingsService::class);
        if (!$settingsService->battleSimulatorEnabled()) {
            return response()->json([
                'success' => false,
                'error' => 'Battle Simulator is disabled by the administrator.',
            ], 403);
        }

        // Parse attacker fleet
        $attackerData = $request->input('attacker', []);
        $attackerFleet = $this->createUnitCollection($attackerData);

        // Parse defender fleet
        $defenderData = $request->input('defender', []);
        $defenderFleet = $this->createUnitCollection($defenderData);

        // Get tech levels from request
        $attackerWeapon = (int)$request->input('attacker_weapon', 0);
        $attackerShield = (int)$request->input('attacker_shield', 0);
        $attackerArmor = (int)$request->input('attacker_armor', 0);
        $attackerHyperspace = (int)$request->input('attacker_hyperspace', 0);

        $defenderWeapon = (int)$request->input('defender_weapon', 0);
        $defenderShield = (int)$request->input('defender_shield', 0);
        $defenderArmor = (int)$request->input('defender_armor', 0);

        $defenderMetal = (int)$request->input('defender_metal', 0);
        $defenderCrystal = (int)$request->input('defender_crystal', 0);
        $defenderDeuterium = (int)$request->input('defender_deuterium', 0);

        // Get or create a simulation user (reusable for all simulations)
        $simulationUser = $this->getSimulationUser();

        $playerServiceFactory = resolve(\OGame\Factories\PlayerServiceFactory::class);
        $playerService = $playerServiceFactory->make($simulationUser->id);

        // Set attacker tech levels
        $playerService->setResearchLevel('weapon_technology', $attackerWeapon, true);
        $playerService->setResearchLevel('shielding_technology', $attackerShield, true);
        $playerService->setResearchLevel('armor_technology', $attackerArmor, true);
        $playerService->setResearchLevel('hyperspace_technology', $attackerHyperspace, true);

        // Get the planet
        $planetService = $playerService->planets->current();

        // Clear existing units and set defender fleet
        foreach (ObjectService::getShipObjects() as $ship) {
            $planetService->removeUnit($ship->machine_name, $planetService->getObjectAmount($ship->machine_name));
        }
        foreach (ObjectService::getDefenseObjects() as $defense) {
            $planetService->removeUnit($defense->machine_name, $planetService->getObjectAmount($defense->machine_name));
        }

        // Add defender units to planet
        foreach ($defenderFleet->units as $unit) {
            $planetService->addUnit($unit->unitObject->machine_name, $unit->amount);
        }

        // Clear existing resources and set defender resources for loot calculations
        $existingResources = $planetService->getResources();
        $planetService->deductResources($existingResources, false);
        $planetService->addResources(new \OGame\Models\Resources($defenderMetal, $defenderCrystal, $defenderDeuterium, 0));

        // Debug: verify resources are set
        $verifyResources = $planetService->getResources();
        \Log::info('Defender resources after setting', [
            'metal' => $verifyResources->metal->get(),
            'crystal' => $verifyResources->crystal->get(),
            'deuterium' => $verifyResources->deuterium->get(),
        ]);

        $settingsService = resolve(SettingsService::class);

        // Run battle simulation with attacker tech
        // Note: For simplicity, we use attacker's tech for the attacker fleet
        // The defender's tech would need a separate player service to implement properly
        $battleEngine = new PhpBattleEngine($attackerFleet, $playerService, $planetService, $settingsService);
        $battleResult = $battleEngine->simulateBattle();

        // Calculate recyclers needed
        // Base recycler capacity: 20,000
        // Hyperspace tech bonus: +5% per level
        $baseRecyclerCapacity = 20000;
        $hyperspaceBonus = 1 + ($attackerHyperspace * 0.05);
        $adjustedRecyclerCapacity = $baseRecyclerCapacity * $hyperspaceBonus;
        $totalDebris = $battleResult->debris->metal->get() + $battleResult->debris->crystal->get();
        $recyclersNeeded = $totalDebris > 0 ? (int)ceil($totalDebris / $adjustedRecyclerCapacity) : 0;

        // Calculate attacker's remaining cargo capacity
        $attackerCargoCapacity = $battleResult->attackerUnitsResult->getTotalCargoCapacity($playerService);

        // Debug: log loot calculation info
        \Log::info('Battle simulation result', [
            'attacker_cargo_capacity' => $attackerCargoCapacity,
            'loot_total' => $battleResult->loot->metal->get() + $battleResult->loot->crystal->get() + $battleResult->loot->deuterium->get(),
            'loot_metal' => $battleResult->loot->metal->get(),
            'loot_crystal' => $battleResult->loot->crystal->get(),
            'loot_deuterium' => $battleResult->loot->deuterium->get(),
        ]);

        return response()->json([
            'success' => true,
            'result' => [
                'rounds' => count($battleResult->rounds),
                'winner' => $this->determineWinner($battleResult),
                'attacker_start' => $battleResult->attackerUnitsStart->getAmount(),
                'defender_start' => $battleResult->defenderUnitsStart->getAmount(),
                'attacker_end' => $battleResult->attackerUnitsResult->getAmount(),
                'defender_end' => $battleResult->defenderUnitsResult->getAmount(),
                'attacker_losses' => $battleResult->attackerUnitsStart->getAmount() - $battleResult->attackerUnitsResult->getAmount(),
                'defender_losses' => $battleResult->defenderUnitsStart->getAmount() - $battleResult->defenderUnitsResult->getAmount(),
                'attacker_units_result' => $this->formatUnits($battleResult->attackerUnitsResult),
                'defender_units_result' => $this->formatUnits($battleResult->defenderUnitsResult),
                'attacker_cargo_capacity' => $attackerCargoCapacity,
                'debris' => [
                    'metal' => $battleResult->debris->metal->get(),
                    'crystal' => $battleResult->debris->crystal->get(),
                    'deuterium' => $battleResult->debris->deuterium->get(),
                    'total' => $battleResult->debris->metal->get() + $battleResult->debris->crystal->get() + $battleResult->debris->deuterium->get(),
                ],
                'loot' => [
                    'metal' => $battleResult->loot->metal->get(),
                    'crystal' => $battleResult->loot->crystal->get(),
                    'deuterium' => $battleResult->loot->deuterium->get(),
                    'total' => $battleResult->loot->metal->get() + $battleResult->loot->crystal->get() + $battleResult->loot->deuterium->get(),
                ],
                'attacker_resource_loss' => [
                    'metal' => $battleResult->attackerResourceLoss->metal->get(),
                    'crystal' => $battleResult->attackerResourceLoss->crystal->get(),
                    'deuterium' => $battleResult->attackerResourceLoss->deuterium->get(),
                    'total' => $battleResult->attackerResourceLoss->metal->get() + $battleResult->attackerResourceLoss->crystal->get() + $battleResult->attackerResourceLoss->deuterium->get(),
                ],
                'defender_resource_loss' => [
                    'metal' => $battleResult->defenderResourceLoss->metal->get(),
                    'crystal' => $battleResult->defenderResourceLoss->crystal->get(),
                    'deuterium' => $battleResult->defenderResourceLoss->deuterium->get(),
                    'total' => $battleResult->defenderResourceLoss->metal->get() + $battleResult->defenderResourceLoss->crystal->get() + $battleResult->defenderResourceLoss->deuterium->get(),
                ],
                'moon_chance' => $battleResult->moonChance,
                'recyclers_needed' => $recyclersNeeded,
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

    /**
     * Get or create a dedicated simulation user for battle calculations
     */
    private function getSimulationUser(): \OGame\Models\User
    {
        $email = 'battlesimulator@system.local';

        // Check if simulation user already exists
        $user = \OGame\Models\User::where('email', $email)->first();

        if (!$user) {
            // Create new simulation user
            $creator = resolve(\OGame\Actions\Fortify\CreateNewUser::class);
            $user = $creator->create([
                'email' => $email,
                'password' => bin2hex(random_bytes(32)), // Random password, won't be used for login
            ]);
        }

        return $user;
    }
}
