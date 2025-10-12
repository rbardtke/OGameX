<?php

namespace OGame\Console\Commands\Tests;

use Illuminate\Support\Carbon;
use OGame\GameMissions\BattleEngine\Models\BattleResult;
use OGame\GameMissions\BattleEngine\BattleEngine;
use OGame\GameMissions\BattleEngine\PhpBattleEngine;
use OGame\GameMissions\BattleEngine\RustBattleEngine;
use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Services\ObjectService;
use OGame\Models\Resources;
use OGame\Services\SettingsService;
use InvalidArgumentException;
use Exception;

/**
 * This command is used to test the performance of a specific battle engine with specified fleets and research levels.
 *
 * Example usage:
 * ---
 * php artisan test:battle-engine-performance php --fleet='{"attacker": {"units": {"light_fighter": 1667}, "research": {"weapon_technology": 15}}, "defender": {"units": {"rocket_launcher": 1667}, "research": {"shielding_technology": 12}}}'
 * ---
 *
 * Supports all standard OGame research technologies:
 * - weapon_technology
 * - shielding_technology
 * - armor_technology
 * - energy_technology
 * - laser_technology
 * - ion_technology
 * - hyperspace_technology
 * - combustion_drive
 * - impulse_drive
 * - hyperspace_drive
 * - graviton_technology
 */
class SimulateBattle extends TestCommand
{
    protected $signature = 'test:battle-engine-simulation
        {engine : The battle engine to test (php/rust)}
        {--fleet= : JSON string defining attacker and defender fleets with optional research}';
    protected $description = 'Test battle engine performance with specified fleets and customizable research levels';

    protected string $email = 'battleengineperformance@test.com';
    private float $startTime;

    // List of valid research technologies in OGame
    private array $validResearchTechnologies = [
        'weapon_technology',
        'shielding_technology',
        'armor_technology',
        'energy_technology',
        'laser_technology',
        'ion_technology',
        'hyperspace_technology',
        'combustion_drive',
        'impulse_drive',
        'hyperspace_drive',
        'graviton_technology',
    ];

    public function handle(): int
    {
        if (!$this->option('fleet') || !$fleets = $this->parseFleets($this->option('fleet'))) {
            $this->error('Specify valid --fleet option in JSON format. Example: --fleet=\'{"attacker": {"units": {"light_fighter": 1667}, "research": {"weapon_technology": 10}}, "defender": {"units": {"rocket_launcher": 1667}}}\'');
            return 1;
        }

        parent::setup();

        $engine = $this->argument('engine');
        if (!in_array($engine, ['php', 'rust'])) {
            $this->error('Invalid engine specified. Use "php" or "rust"');
            return 1;
        }

        return $this->runSingleEngineTest($engine, $fleets);
    }

    private function runSingleEngineTest(string $engine, array $fleets): int
    {
        Carbon::setTestNow(Carbon::create(2024, 1, 1, 0, 0, 0));
        $this->currentPlanetService->addResources(new Resources(1_000_000, 1_000_000, 1_000_000, 0));

        // Apply defender units
        foreach ($fleets['defender']->units as $unit) {
            $this->currentPlanetService->addUnit($unit->unitObject->machine_name, $unit->amount);
        }

        gc_collect_cycles();
        $this->startTime = microtime(true);

        $attackerFleet = $fleets['attacker'];
        $this->info("\nAttacker (" . number_format($attackerFleet->getAmount()) . ") and defender (" . number_format($this->currentPlanetService->getShipUnits()->getAmount() + $this->currentPlanetService->getDefenseUnits()->getAmount()) . ") fleet created");

        $battleEngine = $this->createBattleEngine($engine, $attackerFleet);
        $this->info("\nBattle engine starting simulation...");
        $battleResult = $battleEngine->simulateBattle();
        $this->info("--> Battle engine finished simulation...");

        $this->displayMetrics($engine, $battleResult);

        return 0;
    }

    private function createBattleEngine(string $engine, UnitCollection $attackerFleet): BattleEngine
    {
        $settingsService = resolve(SettingsService::class);

        return $engine === 'php'
            ? new PhpBattleEngine($attackerFleet, $this->playerService, $this->currentPlanetService, $settingsService)
            : new RustBattleEngine($attackerFleet, $this->playerService, $this->currentPlanetService, $settingsService);
    }

    private function displayMetrics(string $engine, BattleResult $battleResult): void
    {
        gc_collect_cycles();

        $executionTime = (microtime(true) - $this->startTime) * 1000;
        $peakMemoryUsage = memory_get_peak_usage(true) / 1024 / 1024;

        $this->info("\n========================================================");
        $this->info("Battle Statistics:");
        $this->info("========================================================");
        $this->info("Attacker initial fleet size: " . number_format($battleResult->attackerUnitsStart->getAmount()));
        $this->info("Defender initial fleet size: " . number_format($battleResult->defenderUnitsStart->getAmount()));
        $this->info("Number of rounds: " . number_format(count($battleResult->rounds)));
        $this->info("Attacker final fleet size: " . number_format($battleResult->attackerUnitsResult->getAmount()));
        $this->info("Defender final fleet size: " . number_format($battleResult->defenderUnitsResult->getAmount()));

        $this->info("\n========================================================");
        $this->info("Battle Engine Performance Metrics:");
        $this->info("========================================================");
        $this->info("Execution time: " . number_format($executionTime, 2) . "ms");
        $this->info("Peak PHP memory usage: " . number_format($peakMemoryUsage, 2) . "MB");

        if ($engine === 'rust') {
            $this->info("Note: Rust memory usage cannot be measured from PHP. Debug Rust binary manually.");
        }

        $this->info("\n");
    }

    private function parseFleets(string $fleetJson): ?array
    {
        try {
            $data = json_decode($fleetJson, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($data['attacker']) || !isset($data['defender'])) {
                throw new InvalidArgumentException('Fleet data must contain "attacker" and "defender"');
            }

            $attackerUnitsData = $data['attacker']['units'] ?? $data['attacker'];
            $defenderUnitsData = $data['defender']['units'] ?? $data['defender'];

            // Create fleets
            $fleets = [
                'attacker' => $this->createUnitCollection($attackerUnitsData),
                'defender' => $this->createUnitCollection($defenderUnitsData),
            ];

            // Reset player and planet research context
            $this->resetResearchLevels();

            // Apply attacker research
            $this->applyResearch($data['attacker']['research'] ?? [], 'attacker');

            // Apply defender research (must be applied after attacker due to shared service)
            // In future, consider using separate context objects
            $this->applyResearch($data['defender']['research'] ?? [], 'defender');

            return $fleets;
        } catch (Exception $e) {
            $this->error('Invalid fleet JSON: ' . $e->getMessage());
            return null;
        }
    }

    private function applyResearch(array $research, string $side): void
    {
        foreach ($research as $tech => $level) {
            if (!in_array($tech, $this->validResearchTechnologies)) {
                $this->warn("Invalid research technology ignored in {$side}: {$tech}");
                continue;
            }

            if (!is_int($level) || $level < 0 || $level > 20) {
                $this->warn("Invalid level for {$tech} in {$side}: {$level}. Using level 10.");
                $level = 10;
            }

            $this->playerService->setResearchLevel($tech, $level);
        }
    }

    private function resetResearchLevels(): void
    {
        foreach ($this->validResearchTechnologies as $tech) {
            $this->playerService->setResearchLevel($tech, 0);
        }
    }

    private function createUnitCollection(array $units): UnitCollection
    {
        $fleet = new UnitCollection();
        foreach ($units as $unitType => $amount) {
            $unit = ObjectService::getUnitObjectByMachineName($unitType);
            if (!$unit) {
                throw new InvalidArgumentException("Unknown unit type: {$unitType}");
            }
            $fleet->addUnit($unit, $amount);
        }
        return $fleet;
    }
}
