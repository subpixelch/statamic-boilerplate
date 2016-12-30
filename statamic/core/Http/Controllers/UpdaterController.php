<?php

namespace Statamic\Http\Controllers;

use Statamic\API\Zip;
use GuzzleHttp\Client;
use Statamic\API\File;
use Statamic\API\Path;
use Statamic\API\Addon;
use Statamic\API\Cache;
use Statamic\API\Event;
use Statamic\API\Config;
use Statamic\API\Folder;
use Statamic\API\Stache;

class UpdaterController extends CpController
{
    /**
     * Show the available updates and changelogs
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $this->access('updater');

        $client = new Client();
        $response = $client->get('https://outpost.statamic.com/v2/changelog');
        $releases = json_decode($response->getBody());

        return view('updater.index', [
            'title' => 'Updater',
            'releases' => $releases,
            'latest' => $releases[0]
        ]);
    }

    /**
     * Show and confirm the specific update
     *
     * @param string $version  The version number
     * @return \Illuminate\View\View
     */
    public function confirmUpdate($version)
    {
        $this->access('updater:update');

        $title = version_compare($version, STATAMIC_VERSION, '>') ? "Upgrade" : "Downgrade";

        return view('updater.confirm', [
            'title' => $title,
            'version' => $version,
            'license_key' => Config::get('system.license_key', false)
        ]);
    }

    /**
     * Create a backup
     *
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function backup()
    {
        $this->authorize('updater:update');

        $zip_path = Path::makeRelative(temp_path('backup/statamic-' . STATAMIC_VERSION . '-' . time() . '.zip'));

        try {
            Folder::make(temp_path('backup'));
        } catch (\Exception $e) {
            return $this->fail("Couldn't create the backup folder.");
        }

        try {
            $zip = Zip::make($zip_path);

            foreach (Folder::getFilesRecursively(statamic_path()) as $path) {
                $zip->put($path, File::get($path));
            }

            Zip::write($zip);
        } catch (\Exception $e) {
            return $this->fail("Couldn't create the backup zip.", $e);
        }

        return $this->okay("Backup created and saved to <code>$zip_path</code>");
    }

    /**
     * Download Statamic
     *
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function download()
    {
        $this->authorize('updater:update');

        $version = $this->request->input('version');

        $zip_path = Path::makeRelative(temp_path('updates/statamic-'.$version.'.zip'));

        $client = new Client();

        if (File::exists($zip_path)) {
            return $this->okay("Download skipped. Using previously downloaded zip detected at <code>$zip_path</code>.");
        }

        try {
            $response = $client->get('https://outpost.statamic.com/v2/get/' . $version);
        } catch (\Exception $e) {
            return $this->fail("Couldn't get the latest release of Statamic from the server.");
        }

        try {
            File::put($zip_path, $response->getBody());
        } catch (\Exception $e) {
            return $this->fail("Couldn't write the new Statamic zip to file.");
        }

        return $this->okay("Statamic has been downloaded to <code>$zip_path</code>.");
    }

    /**
     * Unzip the Statamic download
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function unzip()
    {
        $this->authorize('updater:update');

        $version = $this->request->input('version');

        try {
            $zip = Path::makeRelative(temp_path('updates/statamic-'.$version.'.zip'));

            $target = temp_path('update-unzipped');

            Zip::extract($zip, $target);
        } catch (\Exception $e) {
            return $this->fail("Couldn't extract contents of the the zip.", $e);
        }
    }

    /**
     * Install composer dependencies
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function composer()
    {
        $this->authorize('updater:update');

        try {
            $temp_addons_path = temp_path('update-unzipped/statamic/site/addons/');

            Folder::delete($temp_addons_path);

            Folder::copy(addons_path(), $temp_addons_path);
        } catch (\Exception $e) {
            return $this->fail("Couldn't copy addons folder.", $e);
        }

        try {
            $manager = Addon::manager();

            $manager->composer()->path(temp_path('update-unzipped/statamic/statamic/composer.json'));

            $manager->updateDependencies();
        } catch (\Exception $e) {
            return $this->fail("Couldn't install dependencies.", $e);
        }
    }

    /**
     * Swap the statamic folder
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function swap()
    {
        $this->authorize('updater:update');

        $new_statamic = temp_path('update-unzipped/statamic/statamic');

        // Out with the old...
        try {
            foreach (Folder::getFolders(statamic_path()) as $folder) {
                Folder::delete($folder);
            }
            foreach (Folder::getFiles(statamic_path()) as $file) {
                File::delete($file);
            }
        } catch (\Exception $e) {
            return $this->fail("Couldn't delete the statamic folder.", $e);
        }

        // In with the new.
        try {
            Folder::copy($new_statamic, statamic_path());
        } catch (\Exception $e) {
            return $this->fail("Couldn't copy the new statamic folder.", $e);
        }

        return $this->okay("Statamic folder swapped.");
    }

    /**
     * Clean up
     *
     * This step is performed _after_ the update is completed.
     * It will be performed on the new version's code.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function cleanUp()
    {
        $version = $this->request->input('version');

        // Keep track of errors. These are not showstoppers.
        $errors = [];

        // Get rid of the extracted zip
        try {
            Folder::delete(temp_path('update-unzipped'));
        } catch (\Exception $e) {
            $errors[] = ['message' => "Couldn't delete the unzipped contents.", 'e' => $e];
        }

        // Get rid of the zip itself
        // try {
        //     $zip_path = Path::makeRelative(temp_path('updates/statamic-'.$version.'.zip'));
        //     File::delete($zip_path);
        // } catch (\Exception $e) {
        //     $errors[] = ['message' => "Couldn't delete the zip.", 'e' => $e]);
        // }

        // Clear the cache
        try {
            Cache::clear();
        } catch (\Exception $e) {
            $errors[] = ['message' => "Couldn't clear the cache.", 'e' => $e];
        }

        // Fire the event, devs can do their thang
        try {
            Event::fire('system.updated');
        } catch (\Exception $e) {
            $errors[] = ['message' => "There was a problem while running the system.updated event.", 'e' => $e];
        }

        if (! empty($errors)) {
            return $this->fail($errors);
        }

        return $this->okay("Clean up successful.");
    }

    /**
     * Generate a success response
     *
     * @param string $message
     * @return array
     */
    private function okay($message)
    {
        return ['success' => true, 'message' => $message];
    }

    /**
     * Generate a failure response
     *
     * @param string|array    $error  Either a message, or an array of messages and exceptions.
     * @param \Exception|null $e      An exception when passing a single error message.
     * @return \Illuminate\Http\JsonResponse
     */
    private function fail($data, $e = null)
    {
        $errors = [];

        if (is_string($data)) {
            $data = [
                ['message' => $data, 'e' => $e]
            ];
        }

        foreach ($data as $error) {
            $message = $error['message'];
            $e = ($error['e'] instanceof \Exception) ? $error['e']->getMessage() : null;

            $errors[] = compact('message', 'e');
        }

        return response()->json([
            'success' => false,
            'errors'  => $errors
        ], 500);
    }
}
