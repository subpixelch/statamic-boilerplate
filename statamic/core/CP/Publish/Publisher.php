<?php

namespace Statamic\CP\Publish;

use Statamic\API\Event;
use Statamic\API\Config;
use Statamic\API\Helper;
use Statamic\API\Page;
use Statamic\API\Stache;
use Statamic\API\Str;
use Statamic\API\Fieldset;
use Illuminate\Http\Request;
use Statamic\Contracts\Data\Users\User;
use Statamic\Exceptions\PublishException;

abstract class Publisher
{
    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var \Statamic\Contracts\Data\Content\Content
     */
    protected $content;

    /**
     * @var array
     */
    protected $fields;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var string
     */
    protected $slug;

    /**
     * @var string
     */
    protected $order;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var string
     */
    protected $id;

    /**
     * Create a new Publisher
     *
     * @param \Illuminate\Http\Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;

        $this->fields = $this->request->input('fields');

        $this->locale = $this->request->has('locale') ? $this->request->input('locale') : site_locale();
    }

    /**
     * Publish the content
     *
     * @return \Statamic\Contracts\Data\Content\Content
     */
    public function publish()
    {
        $this->initialValidation();

        // We'll get and prepare the content object. This means we'll retrieve or create it, whatever
        // the case may be. We'll also update the essentials like status and order.
        $this->prepare();

        // Fieldtypes may modify the values submitted by the user.
        $this->processFields();

        // Update the submission with the modified data
        $submission = array_merge($this->request->all(), ['fields' => $this->fields]);
        $this->validateSubmission($submission);

        // Add the fieldset to the data if it was specified on the fly.
        $this->appendFieldset();

        // Commit any changes made by the user and/or the fieldtype processors back to the content object.
        $this->updateContent();

        // Save the file and any run any supplementary tasks like updating the cache, firing events, etc.
        $this->save();

        return $this->content;
    }

    /**
     * Perform initial validation
     *
     * @throws \Statamic\Exceptions\PublishException
     */
    abstract protected function initialValidation();

    /**
     * Prepare the content object
     *
     * Retrieve, update, and/or create a Page, depending on the situation.
     */
    abstract protected function prepare();

    /**
     * Append the fieldset to the data if its different from what's in the cascade
     */
    protected function appendFieldset()
    {
        if ($this->content instanceof User || $this->content->contentType() !== 'page') {
            return;
        }

        $parent = Page::whereUri($this->request->input('extra.parent_url'));

        $fieldset = $this->request->input('fieldset');

        if ($fieldset !== $parent->fieldset()->name()) {
            $this->fields['fieldset'] = $fieldset;
        }
    }

    /**
     * Get the slug from the submission
     *
     * @return string
     */
    protected function getSubmittedSlug()
    {
        // If there's a slug, use it. Otherwise make one from the title field.
        // If there's no title field, an error should be thrown elsewhere.
        return ($this->request->has('slug'))
               ? $this->request->input('slug')
               : Str::slug($this->request->input('fields.title'));
    }

    /**
     * Get the order key from the submission
     *
     * @return string|null
     */
    protected function getSubmittedOrderKey()
    {
        return ($this->request->has('order'))
            ? $this->request->input('order')
            : null;
    }

    /**
     * Get the status from the submission
     *
     * @return string|null
     */
    protected function getSubmittedStatus()
    {
        return $this->request->input('status');
    }

    /**
     * Get the 'display' name of the title field from the fieldset
     *
     * @return string
     */
    protected function getTitleDisplayName()
    {
        if (! $this->request->has('fieldset')) {
            return trans('cp.title');
        }

        $fieldset = Fieldset::get($this->request->input('fieldset'))->contents();

        $title = array_get($fieldset, 'fields.title.display', trans('cp.title'));

        return $title;
    }

    /**
     * Run field data through fieldtypes processors
     */
    protected function processFields()
    {
        foreach ($this->content->fieldset()->fieldtypes() as $field) {
            if (! in_array($field->getName(), array_keys($this->fields))) {
                continue;
            }

            $this->fields[$field->getName()] = $field->process($this->fields[$field->getName()]);
        }

        // Get rid of null fields
        $this->fields = array_filter($this->fields);
    }

    /**
     * Perform validation with provided rules
     *
     * @param  array $rules
     * @param  array $messages
     * @throws PublishException
     */
    protected function validate($rules, $messages = [], $attributes = [])
    {
        $validator = app('validator')->make($this->request->all(), $rules, $messages, $attributes);

        if ($validator->fails()) {
            $e = new PublishException;
            $e->setErrors($validator->errors()->toArray());
            throw $e;
        }
    }

    /**
     * Get the required fields from the fieldset
     *
     * @return array
     */
    protected function requiredFields()
    {
        $fieldset = Fieldset::get($this->request->input('fieldset'));

        return collect($fieldset->fields())->filter(function ($field) {
            return array_get($field, 'required');
        })->keys()->map(function ($field) {
            return 'fields.' . $field;
        })->all();
    }

    /**
     * Validate the submission and redirect on failure
     *
     * @param array $submission
     */
    protected function validateSubmission($submission)
    {
        $validation = new ValidationBuilder($this->fields, $this->content->fieldset());
        $validation->build();

        $this->validate($validation->rules(), [], $validation->attributes());
    }

    /**
     * Update the content object with the data from the submission
     *
     * @throws \Exception
     */
    protected function updateContent()
    {
        $this->fields['id'] = $this->id;

        $this->content->dataForLocale($this->locale, $this->getIsolatedLocalizedData());

        if ($this->slug) {
            $this->content->slug($this->slug);
        }
    }

    /**
     * Get the data for the locale.
     *
     * If its localized, remove any fields that are the same as the default.
     *
     * @return array
     */
    protected function getIsolatedLocalizedData()
    {
        // Default locale doesn't need any filtering
        if ($this->locale === default_locale()) {
            return $this->fields;
        }

        $default = $this->content->defaultData();

        $data = $this->fields;

        foreach ($data as $key => $value) {
            if ($key === 'id') {
                continue;
            }

            if ($value === array_get($default, $key)) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    /**
     * Save the content
     */
    protected function save()
    {
        // Save the content
        $this->content->save();

        // Fire events that may be useful. We'll fire a generic content published
        // event as well as a specific one for the type of content published.
        Event::fire('cp.published', $this->content);

        if ($this->content instanceof \Statamic\Contracts\Data\Pages\Page) {
            Event::fire('cp.page.published', $this->content);
        } elseif ($this->content instanceof \Statamic\Contracts\Data\Entries\Entry) {
            Event::fire('cp.entry.published', $this->content);
        } elseif ($this->content instanceof \Statamic\Contracts\Data\Taxonomies\Term) {
            Event::fire('cp.term.published', $this->content);
        } elseif ($this->content instanceof \Statamic\Contracts\Data\Globals\GlobalSet) {
            Event::fire('cp.globals.published', $this->content);
        } elseif ($this->content instanceof \Statamic\Contracts\Data\Users\User) {
            Event::fire('cp.user.published', $this->content);
        }
    }

    /**
     * Is this content new?
     *
     * @return bool
     */
    protected function isNew()
    {
        return bool($this->request->input('new'));
    }

    /**
     * Is this content localized? (ie. not the default locale)
     *
     * @return bool
     */
    protected function isLocalized()
    {
        return $this->locale !== Config::getDefaultLocale();
    }
}
