<?php

namespace OGame\GameMissions;

use OGame\Enums\FleetMissionStatus;
use OGame\Enums\FleetSpeedType;
use OGame\GameMessages\FleetDeployment;
use OGame\GameMessages\FleetDeploymentWithResources;
use OGame\GameMissions\Abstracts\GameMission;
use OGame\GameMissions\Models\MissionPossibleStatus;
use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Models\Enums\PlanetType;
use OGame\Models\FleetMission;
use OGame\Models\Planet\Coordinate;
use OGame\Services\PlanetService;

class DeploymentMission extends GameMission
{
    protected static string $name = 'Deployment';
    protected static int $typeId = 4;
    protected static bool $hasReturnMission = false;
    protected static FleetSpeedType $fleetSpeedType = FleetSpeedType::peaceful;
    protected static FleetMissionStatus $friendlyStatus = FleetMissionStatus::Neutral;

    /**
     * @inheritdoc
     */
    public function isMissionPossible(PlanetService $planet, Coordinate $targetCoordinate, PlanetType $targetType, UnitCollection $units): MissionPossibleStatus
    {
        $parentCheck = parent::isMissionPossible($planet, $targetCoordinate, $targetType, $units);
        if (!$parentCheck->possible) {
            return $parentCheck;
        }

        // Deployment mission is only possible for planets and moons.
        if (!in_array($targetType, [PlanetType::Planet, PlanetType::Moon])) {
            return new MissionPossibleStatus(false);
        }

        // If target planet does not exist, the mission is not possible.
        $targetPlanet = $this->planetServiceFactory->makeForCoordinate($targetCoordinate, true, $targetType);
        if ($targetPlanet === null) {
            return new MissionPossibleStatus(false);
        }

        // If target player is not the same as current player, this mission is not possible.
        if (!$planet->getPlayer()->equals($targetPlanet->getPlayer())) {
            return new MissionPossibleStatus(false);
        }

        // If all checks pass, the mission is possible.
        return new MissionPossibleStatus(true);
    }

    /**
     * @inheritdoc
     */
    protected function processArrival(FleetMission $mission): void
    {
        $target_planet = $this->planetServiceFactory->make($mission->planet_id_to, true);
        // Add resources to the target planet
        $resources = $this->fleetMissionService->getResources($mission);

        $target_planet->addResources($resources);

        // Add units to the target planet
        $target_planet->addUnits($this->fleetMissionService->getFleetUnits($mission));

        // Send a message to the player that the mission has arrived
        if ($resources->any()) {
            $this->messageService->sendSystemMessageToPlayer($target_planet->getPlayer(), FleetDeploymentWithResources::class, [
                'from' => '[planet]' . $mission->planet_id_from . '[/planet]',
                'to' => '[planet]' . $mission->planet_id_to . '[/planet]',
                'metal' => (string)$mission->metal,
                'crystal' => (string)$mission->crystal,
                'deuterium' => (string)($mission->deuterium +  ($mission->deuterium_consumption / 2)), //if mission deployment: Add half of the consumed deuterium
            ]);
        } else {
            $this->messageService->sendSystemMessageToPlayer($target_planet->getPlayer(), FleetDeployment::class, [
                'from' => '[planet]' . $mission->planet_id_from . '[/planet]',
                'to' => '[planet]' . $mission->planet_id_to . '[/planet]',
            ]);
        }

        // Mark the arrival mission as processed
        $mission->processed = 1;
        $mission->save();
    }

    /**
     * @inheritdoc
     */
    protected function processReturn(FleetMission $mission): void
    {
        $target_planet = $mission->planet_id_to !== null
            ? $this->planetServiceFactory->make($mission->planet_id_to, true)
            : null;

        // If planet_id_to is null (e.g. origin moon was destroyed after departure and the return
        // mission was created before this was handled), fall back to the planet at the origin coords.
        if ($target_planet === null && $mission->galaxy_to !== null) {
            $originCoord = new Coordinate($mission->galaxy_to, $mission->system_to, $mission->position_to);
            $originType = PlanetType::tryFrom($mission->type_to ?? 1);
            $target_planet = $originType !== null
                ? $this->planetServiceFactory->makeForCoordinate($originCoord, false, $originType)
                : null;
            if ($target_planet === null) {
                $target_planet = $this->planetServiceFactory->makePlanetForCoordinate($originCoord, false);
            }
        }

        if ($target_planet === null) {
            // Should not happen in practice: the parent planet always exists because a planet
            // cannot be abandoned while fleet missions are active. Guard here for safety.
            $mission->processed = 1;
            $mission->save();
            return;
        }

        // Transport return trip: add back the units to the source planet. Then we're done.
        $target_planet->addUnits($this->fleetMissionService->getFleetUnits($mission));

        // Add resources to the origin planet (if any).
        $return_resources = $this->fleetMissionService->getResources($mission);
        if ($return_resources->any()) {
            $target_planet->addResources($return_resources);
        }

        // Send message to player that the return mission has arrived.
        $this->sendFleetReturnMessage($mission, $target_planet->getPlayer());

        // Mark the return mission as processed
        $mission->processed = 1;
        $mission->save();
    }
}
