<?php

namespace Statamic\Addons\Suggest;

use Statamic\API\Str;
use Statamic\Exceptions\FatalException;
use Statamic\Extend\Controller;

class SuggestController extends Controller
{
    /**
     * The mode class
     *
     * @var \Statamic\Addons\Suggest\Mode
     */
    protected $mode;

    /**
     * Associative array of suggestions with label and value keys.
     *
     * @var array
     */
    protected $suggestions;

    /**
     * Get the suggestions
     *
     * @return array
     * @throws \Statamic\Exceptions\FatalException
     */
    public function suggestions()
    {
        return $this->mode()->suggestions();
    }

    /**
     * Get the suggestion mode
     *
     * @return \Statamic\Addons\Suggest\Mode
     * @throws \Statamic\Exceptions\FatalException
     */
    private function mode()
    {
        $mode = $this->request->input('type');

        if ($mode === 'suggest') {
            $mode = Str::studly($this->request->input('mode', 'options'));
        }

        $class = 'Statamic\Addons\Suggest\Modes\\' . Str::studly($mode) . 'Mode';

        if (! class_exists($class)) {
            $class = "Statamic\\Addons\\{$mode}\\{$mode}SuggestMode";
        }

        if (! class_exists($class)) {
            throw new FatalException("Suggest mode [$mode] does not exist.");
        }

        return new $class($this->request);
    }
}
