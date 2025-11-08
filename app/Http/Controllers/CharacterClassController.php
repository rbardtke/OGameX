<?php

namespace OGame\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use OGame\Services\PlayerService;

class CharacterClassController extends OGameController
{
    /**
     * Shows the character class selection page
     *
     * @param PlayerService $player
     * @return View
     */
    public function index(PlayerService $player): View
    {
        $this->setBodyId('characterclassselection');

        $currentClass = $player->getUser()->player_class;
        $canChangeClass = $player->getUser()->canChangeClass();
        $classChangedAt = $player->getUser()->class_changed_at;

        return view('ingame.characterclass.index')->with([
            'currentClass' => $currentClass,
            'canChangeClass' => $canChangeClass,
            'classChangedAt' => $classChangedAt,
        ]);
    }

    /**
     * Handle AJAX request to select a character class
     *
     * @param Request $request
     * @param PlayerService $player
     * @return JsonResponse
     * @throws Exception
     */
    public function selectClass(Request $request, PlayerService $player): JsonResponse
    {
        $classId = $request->input('characterClassId');

        // Map class IDs to class names (OGame uses numeric IDs: 1=Collector, 2=General, 3=Discoverer)
        $classMap = [
            '1' => 'collector',
            '2' => 'general',
            '3' => 'discoverer',
        ];

        if (!isset($classMap[$classId])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid character class',
            ], 400);
        }

        $className = $classMap[$classId];
        $user = $player->getUser();

        if (!$user->canChangeClass()) {
            $nextChangeDate = $user->class_changed_at->addWeek()->format('Y-m-d H:i:s');
            return response()->json([
                'status' => 'error',
                'message' => "You can only change your class once per week. Next change available: {$nextChangeDate}",
            ], 400);
        }

        if ($user->changeClass($className)) {
            return response()->json([
                'status' => 'success',
                'message' => "Successfully changed to {$className} class",
                'class' => $className,
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to change class',
        ], 500);
    }

    /**
     * Handle AJAX request to deselect a character class (set to null)
     *
     * @param Request $request
     * @param PlayerService $player
     * @return JsonResponse
     */
    public function deselectClass(Request $request, PlayerService $player): JsonResponse
    {
        $user = $player->getUser();

        if (!$user->canChangeClass()) {
            $nextChangeDate = $user->class_changed_at->addWeek()->format('Y-m-d H:i:s');
            return response()->json([
                'status' => 'error',
                'message' => "You can only change your class once per week. Next change available: {$nextChangeDate}",
            ], 400);
        }

        $user->player_class = null;
        $user->class_changed_at = now();
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully removed class selection',
        ]);
    }
}
