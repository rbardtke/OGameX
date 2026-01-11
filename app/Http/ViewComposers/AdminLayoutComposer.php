<?php

namespace OGame\Http\ViewComposers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use OGame\Services\PlayerService;
use OGame\Services\SettingsService;
use OGame\Services\TicketService;

/**
 * Class AdminLayoutComposer
 * @package OGame\Http\Composers
 *
 * Contains all preprocessor logic for parsing the ingame.layouts.admin
 * blade theme file.
 */
class AdminLayoutComposer
{
    /**
     * AdminLayoutComposer constructor.
     *
     * Construct view composer and get all required data via dependency
     * injection.
     *
     * @param Request $request
     * @param PlayerService $player
     * @param SettingsService $settingsService
     * @param TicketService $ticketService
     */
    public function __construct(
        private Request $request,
        private PlayerService $player,
        private SettingsService $settingsService,
        private TicketService $ticketService
    ) {
    }

    /**
     * Compose the view and pass any required variables.
     *
     * @param View $view
     */
    public function compose(View $view): void
    {
        // Include body_id, which might have been set in the controller.
        $body_id = $this->request->attributes->get('body_id');

        // Get current locale
        $locale = App::getLocale();

        // Get active admin tab from request attributes
        $activeAdminTab = $this->request->attributes->get('activeAdminTab', 'developershortcuts');

        // Get open ticket count for badge (with error handling)
        try {
            $openTicketCount = $this->ticketService->getOpenTicketCount();
        } catch (\Exception $e) {
            $openTicketCount = 0;
        }

        $view->with([
            'currentPlayer' => $this->player,
            'settings' => $this->settingsService,
            'body_id' => $body_id,
            'locale' => $locale,
            'activeAdminTab' => $activeAdminTab,
            'openTicketCount' => $openTicketCount,
        ]);
    }
}
