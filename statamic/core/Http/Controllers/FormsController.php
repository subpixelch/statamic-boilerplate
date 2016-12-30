<?php

namespace Statamic\Http\Controllers;

use Statamic\API\Str;
use Statamic\API\Form;
use Statamic\API\File;
use Statamic\API\YAML;
use Statamic\API\Folder;
use Statamic\API\Metrics;
use Statamic\Exceptions\FatalException;
use Statamic\Forms\Presenters\UploadedFilePresenter;

class FormsController extends CpController
{
    public function index()
    {
        $this->access('forms');

        $data = [
            'title' => 'Forms',
            'forms' => Form::all()
        ];

        return view('forms.index', $data);
    }

    public function get()
    {
        $this->access('forms');

        $forms = Form::all();

        return ['items' => $forms];
    }

    public function show($form)
    {
        $this->access('forms');

        if (! $form = Form::get($form)) {
            return $this->pageNotFound();
        }

        return view('forms.show', compact('form'));
    }

    public function getFormSubmissions($form)
    {
        $this->access('forms');

        $form = Form::get($form);

        $columns = collect($form->columns())->map(function ($val, $column) {
            return ['label' => $column, 'field' => $column, 'translation' => $val];
        })->values()->reverse()->push([
            'label' => 'datestring',
            'field' => 'datestamp'
        ])->reverse();

        $items = collect($form->submissions()->each(function ($submission) {
            $this->replaceFileUploadFields($submission);
        })->toArray())->map(function ($submission) use ($form) {
            $submission['datestring'] = (string) $submission['date'];
            $submission['datestamp'] = $submission['date']->timestamp;
            $submission['edit_url'] = route('form.submission.show', [$form->name(), $submission['id']]);
            $submission['delete_url'] = route('form.submission.delete', [$form->name(), $submission['id']]);
            return $submission;
        });

        return compact('columns', 'items');
    }

    private function replaceFileUploadFields($submission)
    {
        collect($submission->data())->each(function ($value, $field) use ($submission) {
            if ($submission->formset()->isUploadableField($field)) {
                $submission->set($field, UploadedFilePresenter::render($submission, $field));
            }
        });
    }

    public function getForm($form)
    {
        $this->access('forms');

        $form = Form::get($form);

        $array = $form->toArray();

        $array['honeypot'] = $form->honeypot();

        $array['columns'] = array_keys($form->columns());

        $array['metrics'] = $this->preProcessMetrics($form);
        $array['email'] = $form->email();

        foreach ($form->fields() as $name => $field) {
            $field['name'] = $name;
            $array['fields'][] = $field;
        }

        return $array;
    }

    /**
     * Get the metrics array ready to be injected into a Grid field.
     *
     * @param  Form $form
     * @return array
     */
    private function preProcessMetrics($form)
    {
        $metrics = [];

        foreach ($form->formset()->get('metrics', []) as $params) {
            $metric = [
                'type' => $params['type'],
                'label' => $params['label']
            ];
            unset($params['type'], $params['label']);

            foreach ($params as $key => $value) {
                $metric['params'][] = [
                    'value' => $key,
                    'text' => $value
                ];
            }

            $metrics[] = $metric;
        }

        return $metrics;
    }

    public function create()
    {
        $this->access('super');

        return view('forms.create', [
            'title' => t('creating_formset')
        ]);
    }

    public function store()
    {
        $this->authorize('super');

        $slug = ($this->request->has('slug'))
                ? $this->request->input('slug')
                : Str::slug($this->request->input('formset.title'), '_');

        $form = Form::create($slug);

        $form->title($this->request->input('formset.title'));
        $form->honeypot($this->request->input('formset.honeypot'));
        $form->fields($this->prepareFields());
        $form->metrics($this->prepareMetrics());
        $form->email($this->prepareEmail());

        $form->save();

        $this->success(translate('cp.form_created'));

        return [
            'success' => true,
            'redirect' => route('form.edit', $form->name())
        ];
    }

    public function edit($form)
    {
        $this->access('super');

        $form = Form::get($form);

        return view('forms.edit', compact('form'));
    }

    public function update($form)
    {
        $this->access('super');

        $form = Form::get($form);

        $form->title($this->request->input('formset.title'));
        $form->honeypot($this->request->input('formset.honeypot'));
        $form->columns($this->request->input('formset.columns'));
        $form->metrics($this->prepareMetrics());
        $form->email($this->prepareEmail());
        $form->fields($this->prepareFields());

        $form->save();

        $this->success(translate('cp.form_updated'));

        return [
            'success' => true,
            'redirect' => route('form.edit', $form->name())
        ];
    }

    public function deleteSubmission($form, $id)
    {

        $this->access('super');

        $form = Form::get($form);

        $form->deleteSubmission($id);

        $this->success(t('form_submission_deleted'));

        return redirect()->back();
    }

    /**
     * Clean up the metric values from the Grid + Array field
     *
     * @return array
     */
    private function prepareMetrics()
    {
        $metrics = [];

        foreach ($this->request->input('formset.metrics') as $metric) {
            foreach ($metric['params'] as $param) {
                $metric[$param['value']] = $param['text'];
            }

            unset($metric['params']);

            $metrics[] = $metric;
        }

        return $metrics;
    }

    /**
     * Clean up the email values from the Grid field
     *
     * @return array
     */
    private function prepareEmail()
    {
        $emails = [];

        foreach ($this->request->input('formset.email') as $email) {
            $emails[] = array_filter($email);
        }

        return $emails;
    }

    /**
     * Get an array of submitted fields, keyed by the field names
     *
     * @return array
     */
    private function prepareFields()
    {
        $fields = [];

        foreach ($this->request->input('formset.fields') as $field) {
            $field_name = $field['name'];
            unset($field['name']);
            $fields[$field_name] = $field;
        }

        return $fields;
    }

    public function export($form, $type)
    {
        $this->access('forms');

        $form = Form::get($form);

        $exporter = 'Statamic\Forms\Exporters\\' . Str::studly($type) . 'Exporter';

        if (! class_exists($exporter)) {
            throw new FatalException("Exporter of type [$type] does not exist.");
        }

        $exporter = new $exporter;
        $exporter->form($form);

        $content = $exporter->export();

        if ($this->request->has('download')) {
            $path = temp_path('forms/'.$form->name().'-'.time().'.'.$type);
            File::put($path, $content);
            $response = response()->download($path)->deleteFileAfterSend(true);
        } else {
            $response = response($content)->header('Content-Type', $exporter->contentType());
        }

        return $response;
    }

    public function submission($form, $submission)
    {
        $this->access('forms');

        $form = Form::get($form);

        if (! $submission = $form->submission($submission)) {
            return $this->pageNotFound();
        }

        return view('forms.submission', compact('form', 'submission'));
    }
}
