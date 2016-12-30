<?php

namespace Statamic\Http\ViewComposers;

use Statamic\API\URL;
use Illuminate\Contracts\View\View;

class JavascriptComposer
{
    public function compose(View $view)
    {
        $view->with('scripts', $this->scripts());
    }

    private function scripts()
    {
        // Don't bother doing anything on the login screen.
        if (\Route::current() && \Route::current()->getName() === 'login') {
            return '';
        }

        $scripts = addon_repo()->filter('scripts.js')
            ->getFiles()
            ->filterByRegex('/^site\/addons\/[\w_-]+\/resources\/assets\/js\/scripts\.js$/');

        $str = '';

        foreach ($scripts as $path) {
            $dir = pathinfo($path)['dirname'];
            $parts = explode('/', $dir);

            $str .= '<script src="' . URL::prependSiteRoot(URL::assemble(RESOURCES_ROUTE, 'addons', $parts[2], 'js/scripts.js')) . '"></script>';
        }

        return $str;
    }
}
