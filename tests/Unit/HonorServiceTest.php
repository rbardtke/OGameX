<?php

namespace Tests\Unit;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Models\Highscore;
use OGame\Models\User;
use OGame\Services\HonorService;
use OGame\Services\ObjectService;
use OGame\Services\PlayerService;
use Tests\UnitTestCase;

/**
 * Test that the HonorService calculates honor points correctly.
 */
class HonorServiceTest extends UnitTestCase
{
    use RefreshDatabase;

    private HonorService $honorService;

    /**
     * Set up common test components.
     * @throws BindingResolutionException
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->honorService = app(HonorService::class);
    }

    /**
     * Helper to create a second player for testing.
     *
     * @return PlayerService
     */
    private function createSecondPlayer(): PlayerService
    {
        $user = User::factory()->create();
        return resolve(PlayerService::class, ['player_id' => $user->id]);
    }

    /**
     * Test that honor points are calculated correctly for honorable combat.
     *
     * @throws BindingResolutionException
     */
    public function testHonorableTargetCombat(): void
    {
        // Create two players with similar military points
        $this->createAndSetPlanetModel([]);
        $attacker = $this->planetService->getPlayer();

        // Create second player as defender
        $defender = $this->createSecondPlayer();

        // Set equal military points in highscore
        Highscore::updateOrCreate(
            ['player_id' => $attacker->getId()],
            [
                'military' => 1000,
                'military_rank' => 50,
                'general' => 5000,
                'economy' => 2000,
                'research' => 1500,
                'general_rank' => 50,
                'economy_rank' => 50,
                'research_rank' => 50,
            ]
        );

        Highscore::updateOrCreate(
            ['player_id' => $defender->getId()],
            [
                'military' => 1000,
                'military_rank' => 51,
                'general' => 5000,
                'economy' => 2000,
                'research' => 1500,
                'general_rank' => 51,
                'economy_rank' => 51,
                'research_rank' => 51,
            ]
        );

        // Create unit collections
        $unitsDestroyed = new UnitCollection();
        $unitsDestroyed->addUnit(ObjectService::getShipObjectByMachineName('light_fighter'), 100);

        $totalDefenderUnits = new UnitCollection();
        $totalDefenderUnits->addUnit(ObjectService::getShipObjectByMachineName('light_fighter'), 100);

        // Calculate honor
        $honor = $this->honorService->calculateHonorPointsFromBattle(
            $unitsDestroyed,
            $totalDefenderUnits,
            $attacker,
            $defender
        );

        // Should gain honor for honorable combat
        $this->assertGreaterThan(0, $honor['attacker'], 'Attacker should gain honor for honorable target');
        $this->assertEquals(0, $honor['defender']);
    }

    /**
     * Test that honor points are negative for dishonorable combat.
     *
     * @throws BindingResolutionException
     */
    public function testDishonorableTargetCombat(): void
    {
        // Create two players with vastly different military points
        $this->createAndSetPlanetModel([]);
        $attacker = $this->planetService->getPlayer();

        $defender = $this->createSecondPlayer();

        // Set vastly different military points (attacker much stronger)
        Highscore::updateOrCreate(
            ['player_id' => $attacker->getId()],
            [
                'military' => 10000,
                'military_rank' => 1,
                'general' => 50000,
                'economy' => 20000,
                'research' => 15000,
                'general_rank' => 1,
                'economy_rank' => 1,
                'research_rank' => 1,
            ]
        );

        Highscore::updateOrCreate(
            ['player_id' => $defender->getId()],
            [
                'military' => 100, // Much weaker
                'military_rank' => 500, // Far behind in rank
                'general' => 500,
                'economy' => 200,
                'research' => 150,
                'general_rank' => 500,
                'economy_rank' => 500,
                'research_rank' => 500,
            ]
        );

        // Create unit collections
        $unitsDestroyed = new UnitCollection();
        $unitsDestroyed->addUnit(ObjectService::getShipObjectByMachineName('light_fighter'), 50);

        $totalDefenderUnits = new UnitCollection();
        $totalDefenderUnits->addUnit(ObjectService::getShipObjectByMachineName('light_fighter'), 50);

        // Calculate honor
        $honor = $this->honorService->calculateHonorPointsFromBattle(
            $unitsDestroyed,
            $totalDefenderUnits,
            $attacker,
            $defender
        );

        // Should lose honor for dishonorable combat
        $this->assertLessThan(0, $honor['attacker'], 'Attacker should lose honor for dishonorable target');
        $this->assertEquals(0, $honor['defender']);
    }

    /**
     * Test that civil ships are excluded from honor calculations.
     *
     * @throws BindingResolutionException
     */
    public function testCivilShipsExcluded(): void
    {
        $this->createAndSetPlanetModel([]);
        $attacker = $this->planetService->getPlayer();
        $defender = $this->createSecondPlayer();

        // Setup honorable combat scenario
        Highscore::updateOrCreate(['player_id' => $attacker->getId()], ['military' => 1000, 'military_rank' => 50, 'general' => 5000, 'economy' => 2000, 'research' => 1500, 'general_rank' => 50, 'economy_rank' => 50, 'research_rank' => 50]);
        Highscore::updateOrCreate(['player_id' => $defender->getId()], ['military' => 1000, 'military_rank' => 51, 'general' => 5000, 'economy' => 2000, 'research' => 1500, 'general_rank' => 51, 'economy_rank' => 51, 'research_rank' => 51]);

        // Destroy only civil ships
        $unitsDestroyed = new UnitCollection();
        $unitsDestroyed->addUnit(ObjectService::getShipObjectByMachineName('small_cargo'), 100);
        $unitsDestroyed->addUnit(ObjectService::getShipObjectByMachineName('large_cargo'), 50);

        $totalDefenderUnits = new UnitCollection();
        $totalDefenderUnits->addUnit(ObjectService::getShipObjectByMachineName('small_cargo'), 100);
        $totalDefenderUnits->addUnit(ObjectService::getShipObjectByMachineName('large_cargo'), 50);

        $honor = $this->honorService->calculateHonorPointsFromBattle(
            $unitsDestroyed,
            $totalDefenderUnits,
            $attacker,
            $defender
        );

        // Should gain no honor for destroying only civil ships
        $this->assertEquals(0, $honor['attacker'], 'No honor should be awarded for civil ships');
    }

    /**
     * Test that 1% minimum threshold is enforced.
     *
     * @throws BindingResolutionException
     */
    public function testMinimumThreshold(): void
    {
        $this->createAndSetPlanetModel([]);
        $attacker = $this->planetService->getPlayer();
        $defender = $this->createSecondPlayer();

        // Setup honorable combat
        Highscore::updateOrCreate(['player_id' => $attacker->getId()], ['military' => 1000, 'military_rank' => 50, 'general' => 5000, 'economy' => 2000, 'research' => 1500, 'general_rank' => 50, 'economy_rank' => 50, 'research_rank' => 50]);
        Highscore::updateOrCreate(['player_id' => $defender->getId()], ['military' => 1000, 'military_rank' => 51, 'general' => 5000, 'economy' => 2000, 'research' => 1500, 'general_rank' => 51, 'economy_rank' => 51, 'research_rank' => 51]);

        // Destroy less than 1% of total units
        $unitsDestroyed = new UnitCollection();
        $unitsDestroyed->addUnit(ObjectService::getShipObjectByMachineName('light_fighter'), 1);

        $totalDefenderUnits = new UnitCollection();
        $totalDefenderUnits->addUnit(ObjectService::getShipObjectByMachineName('light_fighter'), 10000);

        $honor = $this->honorService->calculateHonorPointsFromBattle(
            $unitsDestroyed,
            $totalDefenderUnits,
            $attacker,
            $defender
        );

        // Should gain no honor due to minimum threshold
        $this->assertEquals(0, $honor['attacker'], 'No honor should be awarded below 1% threshold');
    }

    /**
     * Test isHonorableTarget method with various scenarios.
     *
     * @throws BindingResolutionException
     */
    public function testIsHonorableTarget(): void
    {
        $this->createAndSetPlanetModel([]);
        $attacker = $this->planetService->getPlayer();
        $defender = $this->createSecondPlayer();

        // Scenario 1: Equal military points (honorable)
        Highscore::updateOrCreate(['player_id' => $attacker->getId()], ['military' => 1000, 'military_rank' => 50, 'general' => 5000, 'economy' => 2000, 'research' => 1500, 'general_rank' => 50, 'economy_rank' => 50, 'research_rank' => 50]);
        Highscore::updateOrCreate(['player_id' => $defender->getId()], ['military' => 1000, 'military_rank' => 51, 'general' => 5000, 'economy' => 2000, 'research' => 1500, 'general_rank' => 51, 'economy_rank' => 51, 'research_rank' => 51]);

        $this->assertTrue($this->honorService->isHonorableTarget($attacker, $defender), 'Equal military points should be honorable');

        // Scenario 2: Defender has 50%+ of attacker's military (honorable)
        Highscore::where('player_id', $attacker->getId())->update(['military' => 2000]);
        Highscore::where('player_id', $defender->getId())->update(['military' => 1100]);

        $this->assertTrue($this->honorService->isHonorableTarget($attacker, $defender), 'Defender with 50%+ military should be honorable');

        // Scenario 3: Vast difference (dishonorable)
        Highscore::where('player_id', $attacker->getId())->update(['military' => 10000, 'military_rank' => 1]);
        Highscore::where('player_id', $defender->getId())->update(['military' => 100, 'military_rank' => 500]);

        $this->assertFalse($this->honorService->isHonorableTarget($attacker, $defender), 'Vast military difference should be dishonorable');
    }

    /**
     * Test honor rank calculation.
     */
    public function testGetHonorRank(): void
    {
        // Bandit ranks (negative honor)
        $this->assertEquals('rank_bandit1', $this->honorService->getHonorRank(-1000, 100));
        $this->assertEquals('rank_bandit2', $this->honorService->getHonorRank(-5000, 100));
        $this->assertEquals('rank_bandit3', $this->honorService->getHonorRank(-10000, 100));

        // Positive ranks require both points and rank
        $this->assertEquals('rank_starlord1', $this->honorService->getHonorRank(1000, 100));
        $this->assertEquals('rank_starlord2', $this->honorService->getHonorRank(5000, 50));
        $this->assertEquals('rank_starlord3', $this->honorService->getHonorRank(10000, 10));

        // Not enough rank position
        $this->assertNull($this->honorService->getHonorRank(10000, 500), 'High honor but low rank should return null');

        // Not enough honor points
        $this->assertNull($this->honorService->getHonorRank(500, 10), 'High rank but low honor should return null');
    }

    /**
     * Test awarding honor points to users.
     *
     * @throws BindingResolutionException
     */
    public function testAwardHonorPoints(): void
    {
        $this->createAndSetPlanetModel([]);
        $user = $this->planetService->getPlayer()->getUser();

        $initialHonor = $user->honor_points;

        // Award positive honor
        $this->honorService->awardHonorPoints($user, 100);
        $user->refresh();
        $this->assertEquals($initialHonor + 100, $user->honor_points);

        // Award negative honor
        $this->honorService->awardHonorPoints($user, -50);
        $user->refresh();
        $this->assertEquals($initialHonor + 50, $user->honor_points);

        // Award zero (should not change)
        $currentHonor = $user->honor_points;
        $this->honorService->awardHonorPoints($user, 0);
        $user->refresh();
        $this->assertEquals($currentHonor, $user->honor_points);
    }
}
