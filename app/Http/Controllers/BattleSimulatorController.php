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
    public function index(Request $request): View
    {
        // Check if battle simulator is enabled
        $settingsService = resolve(SettingsService::class);
        if (!$settingsService->battleSimulatorEnabled()) {
            abort(403, 'Battle Simulator is disabled by the administrator.');
        }

        // Check if API code was provided
        $prefillData = null;
        if ($request->has('api')) {
            $apiCodeService = resolve(\OGame\Services\EspionageApiCodeService::class);
            $prefillData = $apiCodeService->parseApiCode($request->get('api'));
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
            'prefillData' => $prefillData,
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

        // Parse input data
        $attackerData = $request->input('attacker', []);
        $defenderData = $request->input('defender', []);

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

        $playerServiceFactory = resolve(\OGame\Factories\PlayerServiceFactory::class);

        // Create ATTACKER simulation user with attacker tech
        $attackerUser = $this->getSimulationUser('attacker');
        $attackerPlayerService = $playerServiceFactory->make($attackerUser->id);

        // Set attacker tech levels on attacker user
        $attackerPlayerService->setResearchLevel('weapon_technology', $attackerWeapon, true);
        $attackerPlayerService->setResearchLevel('shielding_technology', $attackerShield, true);
        $attackerPlayerService->setResearchLevel('armor_technology', $attackerArmor, true);
        $attackerPlayerService->setResearchLevel('hyperspace_technology', $attackerHyperspace, true);

        // Get attacker's planet and add attacker units
        $attackerPlanetService = $attackerPlayerService->planets->current();

        // Clear existing units on attacker planet
        foreach (ObjectService::getShipObjects() as $ship) {
            $attackerPlanetService->removeUnit($ship->machine_name, $attackerPlanetService->getObjectAmount($ship->machine_name));
        }

        // Add attacker units to attacker planet
        foreach ($attackerData as $unitName => $amount) {
            if ($amount > 0) {
                try {
                    $unit = ObjectService::getUnitObjectByMachineName($unitName);
                    $attackerPlanetService->addUnit($unit->machine_name, (int)$amount);
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        // Create attacker fleet from attacker planet (with tech bonuses)
        $attackerFleet = new UnitCollection();
        foreach ($attackerData as $unitName => $amount) {
            if ($amount > 0) {
                try {
                    $unit = ObjectService::getUnitObjectByMachineName($unitName);
                    $attackerFleet->addUnit($unit, (int)$amount);
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        // Create DEFENDER simulation user with defender tech
        $defenderUser = $this->getSimulationUser('defender');
        $defenderPlayerService = $playerServiceFactory->make($defenderUser->id);

        // Set defender tech levels on defender user
        $defenderPlayerService->setResearchLevel('weapon_technology', $defenderWeapon, true);
        $defenderPlayerService->setResearchLevel('shielding_technology', $defenderShield, true);
        $defenderPlayerService->setResearchLevel('armor_technology', $defenderArmor, true);

        // Get defender's planet
        $planetService = $defenderPlayerService->planets->current();

        // Clear existing units on defender planet
        foreach (ObjectService::getShipObjects() as $ship) {
            $planetService->removeUnit($ship->machine_name, $planetService->getObjectAmount($ship->machine_name));
        }
        foreach (ObjectService::getDefenseObjects() as $defense) {
            $planetService->removeUnit($defense->machine_name, $planetService->getObjectAmount($defense->machine_name));
        }

        // Add defender units to defender planet
        foreach ($defenderData as $unitName => $amount) {
            if ($amount > 0) {
                try {
                    $unit = ObjectService::getUnitObjectByMachineName($unitName);
                    $planetService->addUnit($unit->machine_name, (int)$amount);
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        // Clear existing resources and set defender resources for loot calculations
        $existingResources = $planetService->getResources();
        $planetService->deductResources($existingResources, false);
        $planetService->addResources(new \OGame\Models\Resources($defenderMetal, $defenderCrystal, $defenderDeuterium, 0));

        $settingsService = resolve(SettingsService::class);

        // Run battle simulation
        // Pass: attacker fleet, attacker PlayerService (for attacker tech), defender PlanetService (for defender tech)
        $battleEngine = new PhpBattleEngine($attackerFleet, $attackerPlayerService, $planetService, $settingsService);
        $battleResult = $battleEngine->simulateBattle();

        // Calculate recyclers needed
        // Base recycler capacity: 20,000
        // Hyperspace tech bonus: +5% per level
        $baseRecyclerCapacity = 20000;
        $hyperspaceBonus = 1 + ($attackerHyperspace * 0.05);
        $adjustedRecyclerCapacity = $baseRecyclerCapacity * $hyperspaceBonus;
        $totalDebris = $battleResult->debris->metal->get() + $battleResult->debris->crystal->get();
        $recyclersNeeded = $totalDebris > 0 ? (int)ceil($totalDebris / $adjustedRecyclerCapacity) : 0;

        // Calculate attacker's remaining cargo capacity using attacker's tech
        $attackerCargoCapacity = $battleResult->attackerUnitsResult->getTotalCargoCapacity($attackerPlayerService);

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
     *
     * @param string $role Either 'attacker' or 'defender' to create separate users with different tech
     */
    private function getSimulationUser(string $role = 'default'): \OGame\Models\User
    {
        $email = 'battlesimulator-' . $role . '@system.local';

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
