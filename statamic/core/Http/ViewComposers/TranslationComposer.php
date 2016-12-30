<?php

namespace Statamic\Http\ViewComposers;

use Illuminate\Contracts\View\View;

class TranslationComposer
{
    public function compose(View $view)
    {
        $view->with('translations', app('translator')->all());
    }
}
