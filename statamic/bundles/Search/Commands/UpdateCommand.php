<?php

namespace Statamic\Addons\Search\Commands;

use Mmanos\Search\Index;
use Statamic\API\Config;
use Statamic\API\Content;
use Statamic\API\Search;
use Statamic\Extend\Command;

class UpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the search index';

    /**
     * @var string
     */
    private $driver;

    /**
     * @var Index
     */
    protected $index;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->driver = Config::get('search.driver');
        $this->index = Search::in(Config::get('search.default_index'));

        $this->deleteIndex();
        $this->updateIndex();

        $this->info('Search index updated!');
    }

    /**
     * Normalize content
     *
     * @param mixed $content
     * @return array
     */
    private function normalizeContent($content)
    {
        $content = $content->toArray();

        // Nested arrays aren't supported by Zend so we'll convert them to dot notation.
        // For example, ['foo' => ['bar' => ['baz' => 'qux']]] will be converted to
        // ['foo.bar.baz' => 'qux']. Other drivers will continue to use arrays.
        if ($this->driver === 'zend') {
            $content = array_dot($content);
        }

        return $content;
    }

    /**
     * Delete the index
     *
     * @return void
     */
    private function deleteIndex()
    {
        $this->index->deleteIndex();

        $this->info('Search index cleared.');
    }

    /**
     * Update the index
     *
     * @return void
     */
    private function updateIndex()
    {
        $this->line('Updating index. Please wait...');

        $items = Content::all();

        $bar = $this->output->createProgressBar($items->count());

        foreach ($items as $id => $content) {
            $this->index->insert($id, $this->normalizeContent($content));
            $bar->advance();
        }

        $bar->finish();
        $this->line('');
    }
}
