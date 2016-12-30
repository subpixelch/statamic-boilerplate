<?php

namespace Statamic\Http\Controllers;

use Statamic\API\User;
use Statamic\API\Config;

/**
 * Controller for the CP home/dashboard
 */
class DashboardController extends CpController
{
    /**
     * View for the CP dashboard
     */
    public function index()
    {
        $widgets = [];

        foreach (resource_loader()->loadWidgets(Config::get('cp.widgets', [])) as $widget) {
            $widgets[] = [
                'width' => $widget->getConfig('width', 100),
                'html' => (string) $widget->html(),
                'importance' => $widget->getConfig('importance', 2)
            ];
        }

        if (empty($widgets) && !User::getCurrent()->can('settings:cp')) {
            return redirect()->route('pages');
        }

        $data = [
            'title' => translate('cp.dashboard'),
            'sidebar' => false,
            'widgets' => $widgets
        ];

        return view('dashboard', $data);
    }
}
