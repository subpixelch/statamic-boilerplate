<?php

namespace Statamic\Contracts\Importing;

interface Importer
{
    public function name();
    public function title();
    public function instructions();
    public function prepare($data);
    public function summary();
    public function import($data);
    public function exportUrl($url);
}
