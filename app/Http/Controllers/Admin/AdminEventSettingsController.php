<?php

namespace OGame\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\View\View;
use OGame\Http\Controllers\OGameController;

class AdminEventSettingsController extends OGameController
{
    /**
     * Shows the event settings page (placeholder).
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        // Set active admin tab for the layout
        $request->attributes->set('activeAdminTab', 'eventsettings');

        return view('ingame.admin.tabs.eventsettings');
    }
}
