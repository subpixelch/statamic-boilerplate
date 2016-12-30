<?php

namespace Statamic\Importing\Statamic;

use Exception;
use Statamic\API\URL;
use Statamic\API\Cache;
use Statamic\Importing\Importer;

class StatamicImporter extends Importer
{
    public function name()
    {
        return 'statamic';
    }

    public function title()
    {
        return 'Statamic v1';
    }

    public function instructions()
    {
        return "
Download the `exporter` addon from
[http://github.com/statamic/exporter](http://github.com/statamic/exporter) and install
it into your v1 site.";

    }

    public function prepare($data)
    {
        if (! $data = json_decode($data, true)) {
            throw new Exception('Invalid export data format.');
        }

        $data = (new Preparer)->prepare($data);

        Cache::put('importer.statamic.prepared', $data);

        return true;
    }

    public function summary()
    {
        $prepared = Cache::get('importer.statamic.prepared');

        $summary = [];

        foreach ($prepared['pages'] as $page_url => $page) {
            $summary['pages'][$page_url] = ['url' => $page_url, 'title' => array_get($page, 'title')];
        }

        foreach ($prepared['entries'] as $collection => $entries) {
            $collection_entries = [];

            foreach ($entries as $slug => $entry) {
                $collection_entries[$slug] = compact('slug');
            }

            $summary['collections'][$collection] = [
                'title' => $collection,
                'route' => $prepared['collections'][$collection]['route'],
                'entries' => $collection_entries
            ];
        }

        foreach ($prepared['terms'] as $taxonomy => $terms) {
            $taxonomy_terms = [];

            foreach ($terms as $slug => $term) {
                $taxonomy_terms[$slug] = compact('slug');
            }

            $summary['taxonomies'][$taxonomy] = [
                'title' => $taxonomy,
                'route' => $prepared['taxonomies'][$taxonomy]['route'],
                'terms' => $taxonomy_terms
            ];
        }

        foreach ($prepared['globals'] as $set => $vars) {
            $variables = [];

            foreach ($vars as $key => $value) {
                $variables[$key] = compact('key');
            }

            $summary['globals'][$set] = compact('set', 'variables');
        }

        return $summary;
    }

    public function import($summary)
    {
        $prepared = Cache::get('importer.statamic.prepared');

        (new Migrator)->migrate($prepared, $summary);
    }

    public function exportUrl($url)
    {
        return URL::assemble($url, 'TRIGGER/exporter/export');
    }
}
