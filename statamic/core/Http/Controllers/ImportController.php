<?php

namespace Statamic\Http\Controllers;

use Exception;
use Statamic\API\Str;
use GuzzleHttp\Client;
use Statamic\API\Cache;

class ImportController extends CpController
{
    public function index()
    {
        $this->access('importer');

        return view('import.index', ['title' => t('nav_import')]);
    }

    public function ui($name)
    {
        $this->access('importer');

        $importer = $this->importer($name);

        $title = t('nav_import');

        return view('import.import', compact('importer', 'title'));
    }

    public function details($name)
    {
        $this->access('importer');

        $importer = $this->importer($name);

        $instructions = markdown($importer->instructions());

        $json = Cache::get("importer.$name.json");

        return compact('instructions', 'json');
    }

    public function export($name)
    {
        $this->access('importer');

        $importer = $this->importer($name);

        $export_url = $importer->exportUrl($this->request->input('url'));

        try {
            $client = new Client;
            $response = $client->get($export_url);
            $json = $response->getBody()->getContents();
        } catch (\Exception $e) {
            return response(['success' => false, 'error' => $e->getMessage()], 500);
        }

        Cache::put("importer.$name.json", $json);

        try {
            $importer->prepare($json);
        } catch (Exception $e) {
            return response(['success' => false, 'error' => $e->getMessage()], 500);
        }

        return ['success' => true, 'summary' => $importer->summary()];
    }

    public function import($name)
    {
        $importer = $this->importer($name);

        $importer->import($this->request->input('summary'));

        return ['success' => true];
    }

    private function importer($name)
    {
        $this->access('importer');

        $studly = Str::studly($name);
        $class = "Statamic\\Importing\\$studly\\{$studly}Importer";

        return new $class;
    }
}
