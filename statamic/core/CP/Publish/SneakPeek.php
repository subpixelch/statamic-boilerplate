<?php

namespace Statamic\CP\Publish;

use Statamic\API\Str;
use Statamic\API\Page;
use Statamic\API\Term;
use Statamic\API\Entry;
use Statamic\API\Fieldset;
use Illuminate\Http\Request;
use Statamic\Contracts\Data\Entries\Entry as EntryObject;

class SneakPeek
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var Statamic\Contracts\Data\Content\Content
     */
    private $content;

    /**
     * @var bool
     */
    private $new;

    /**
     * Create a new sneak peek
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Get the content object
     *
     * @return Statamic\Contracts\Data\Content\Content
     */
    public function content()
    {
        $this->new = true;

        $type = $this->request->input('type');

        $method = 'create' . ucfirst($type);

        return $this->$method();
    }

    /**
     * Get the updated data to be used to render the page
     *
     * @param  Statamic\Contacts\Data\Content\Content $content
     * @return array
     */
    public function update($content)
    {
        $this->content = $content;

        $fields = array_get($this->request->all(), 'fields', []);

        $fields = $this->processFields($fields);

        $data = $this->request->all();

        // For entries...
        if ($this->content instanceof EntryObject) {
            // For date-based entries...
            // Modify the date/order.
            if ($this->content->orderType() === 'date') {
                $date = array_get($data, 'extra.datetime');
                if (strlen($date) > 10) {
                    $date = str_replace(':', '', $date);
                    $date = str_replace(' ', '-', $date);
                }
                $this->content->order($date);
            }
        }

        unset($data['fields'], $data['extra']);

        $data = array_merge($data, $fields);

        return array_merge($this->content->data(), $data);
    }

    /**
     * Create a new, temporary entry object
     *
     * @return Statamic\Contracts\Data\Entries\Entry
     */
    private function createEntry()
    {
        $req = $this->request;

        $order = null;

        if ($req->input('extra.order_type') === 'date') {
            $order = Str::replace($req->input('extra.datetime'), ' ', '-');
            $order = Str::replace($order, ':', '');
        }

        $slug = $req->has('slug') ? $req->input('slug') : 'new-entry';

        $entry = Entry::create($slug)->collection($req->input('extra.collection'));

        if ($order) {
            $entry->order($order);
        }

        return $entry->get();
    }

    /**
     * Create a new, temporary taxonomy term object
     *
     * @return Statamic\Contracts\Data\Taxonomies\Term
     */
    private function createTaxonomy()
    {
        $req = $this->request;

        $slug = $req->has('slug') ? $req->input('slug') : 'new-term';

        $term = Term::create($slug)->taxonomy($req->input('extra.group'));

        return $term->get();
    }

    /**
     * Create a new, temporary page object
     *
     * @return Statamic\Contracts\Data\Pages\Page
     */
    private function createPage()
    {
        return Page::create('/'.$this->request->path())->get();
    }

    /**
     * Process the submitted fields
     *
     * @param  array $fields
     * @return array
     */
    private function processFields($fields)
    {
        // Existing pages will have their own fieldset, but new ones will have theirs
        // passed through with the POST request.
        $fieldset = (! $this->new)
            ? $this->content->fieldset()
            : Fieldset::get($this->request->input('fieldset'));

        foreach ($fieldset->fieldtypes() as $field) {
            if (! in_array($field->getName(), array_keys($fields))) {
                continue;
            }

            $fields[$field->getName()] = $field->process($fields[$field->getName()]);
        }

        // Get rid of null fields
        $fields = array_filter($fields);

        return $fields;
    }
}
