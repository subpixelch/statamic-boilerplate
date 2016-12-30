<?php

namespace Statamic\Http\Controllers;

use Lang;
use Statamic\API\Str;
use Statamic\API\File;
use Statamic\API\YAML;
use Statamic\API\User;
use Statamic\API\Helper;
use Statamic\API\Config;
use Statamic\API\Folder;
use Illuminate\Http\Request;

/**
 * The base control panel controller
 */
class CpController extends Controller
{
    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Create a new CpController
     *
     * @param \Illuminate\Http\Request $request
     */
    public function __construct(Request $request)
    {
        $this->setLocale();

        $this->request = $request;
    }

    /**
     * 404
     */
    public function pageNotFound()
    {
        abort(404);
    }

    /**
     * Set the successful flash message
     *
     * @param string $message
     * @param null   $text
     * @return array
     */
    protected function success($message, $text = null)
    {
        $this->request->session()->flash('success', $message);

        if ($text) {
            $this->request->session()->flash('success_text', $text);
        }
    }

    /**
     * Get all the template names from the current theme
     *
     * @return array
     */
    public function templates()
    {
        $templates = [];

        foreach (Folder::disk('theme')->getFilesRecursively('templates') as $path) {
            $parts = explode('/', $path);
            array_shift($parts);
            $templates[] = Str::removeRight(join('/', $parts), '.html');
        }

        return $templates;
    }

    public function themes()
    {
        $themes = [];

        foreach (Folder::disk('themes')->getFolders('/') as $folder) {
            $name = $folder;

            // Get the name if one exists in a meta file
            if (File::disk('themes')->exists($folder.'/meta.yaml')) {
                $meta = YAML::parse(File::disk('themes')->get($folder.'/meta.yaml'));
                $name = array_get($meta, 'name', $folder);
            }

            $themes[] = compact('folder', 'name');
        }

        return $themes;
    }

    /**
     * Set the locale the translator will use within the control panel.
     *
     * Users can set their own locale in their files. If unspecified, it will fall back
     * to the locale setting in cp.yaml. Finally, it will fall back to the site locale.
     *
     * @return void
     */
    private function setLocale()
    {
        $user_locale = (User::loggedIn()) ? User::getCurrent()->get('locale') : null;

        $locale = Helper::pick(
            $user_locale,
            Config::get('cp.locale'),
            Config::getDefaultLocale()
        );

        Lang::setLocale($locale);
    }
}
