<?php

namespace Statamic\Http\Controllers;

use Statamic\API\Config;
use Statamic\API\URL;
use Statamic\API\User;
use Statamic\API\Email;
use Statamic\API\Helper;
use Statamic\API\Fieldset;
use Statamic\Addons\User\PasswordReset;

class UsersController extends CpController
{
    /**
     * @var \Statamic\Contracts\Data\Users\User|\Statamic\Contracts\Permissions\Permissible
     */
    private $user;

    /**
     * Redirect to the current user's edit page
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function account()
    {
        return redirect()->route('user.edit', User::getCurrent()->username());
    }

    /**
     * List users
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $this->access('users:edit');

        $data = [
            'title' => 'Users'
        ];

        return view('users.index', $data);
    }

    /**
     * Get users as JSON
     *
     * @return array
     */
    public function get()
    {
        $users = User::all()->supplement('checked', function () {
            return false;
        })->values()->toArray();

        return [
            'items' => $users,
            'columns' => ['name', 'username', 'email']
        ];
    }

    /**
     * Create a new user
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $this->authorize('users:create');

        $fieldset = 'user';

        $data = $this->populateWithBlanks($fieldset);

        return view('publish', [
            'extra'             => [],
            'is_new'            => true,
            'content_data'      => $data,
            'content_type'      => 'user',
            'fieldset'          => $fieldset,
            'title'             => trans('cp.create_a_user'),
            'uuid'              => null,
            'url'               => null,
            'parent_url'        => null,
            'slug'              => null,
            'status'            => null,
            'uri'               =>  null,
            'locale'            => default_locale(),
            'is_default_locale' => true,
            'locales'           => []
        ]);
    }

    /**
     * Edit a user
     *
     * @param string $username
     * @return \Illuminate\View\View
     */
    public function edit($username)
    {
        $this->user = User::whereUsername($username);

        // Users can always manage their data
        if ($this->user !== User::getCurrent()) {
            $this->authorize('users:edit');
        }

        $data = $this->populateWithBlanks($this->user);

        if (Config::get('users.login_type') === 'email') {
            $data['email'] = $this->user->email();
        } else {
            $data['username'] = $this->user->username();
        }

        $data['roles'] = $this->user->roles()->keys();
        $data['user_groups'] = $this->user->groups()->keys();
        $data['status'] = $this->user->status();

        return view('publish', [
            'extra'             => [],
            'is_new'            => false,
            'content_data'      => $data,
            'content_type'      => 'user',
            'fieldset'          => $this->user->fieldset()->name(),
            'title'             => $this->user->username(),
            'uuid'              => $this->user->id(),
            'url'               => null,
            'uri'               => null,
            'parent_url'        => null,
            'slug'              => $this->user->username(),
            'status'            => $this->user->status(),
            'locale'            => default_locale(),
            'is_default_locale' => true,
            'locales'           => []
        ]);
    }

    /**
     * Delete a user
     *
     * @return array
     */

    public function delete()
    {
        $this->authorize('users:delete');

        $ids = Helper::ensureArray($this->request->input('ids'));

        foreach ($ids as $id) {
            User::find($id)->delete();
        }

        return ['success' => true];
    }

    /**
     * Create the data array, populating it with blank values for all fields in
     * the fieldset, then overriding with the actual data where applicable.
     *
     * @param string|\Statamic\Contracts\Data\Users\User $arg
     * @return array
     */
    private function populateWithBlanks($arg)
    {
        // Get a fieldset and data
        if ($arg instanceof \Statamic\Contracts\Data\Users\User) {
            $fieldset = $arg->fieldset();
            $data = $arg->processedData();
        } else {
            $fieldset = Fieldset::get($arg);
            $data = [];
        }

        // Get the fieldtypes
        $fieldtypes = collect($fieldset->fieldtypes())->keyBy(function ($ft) {
            return $ft->getName();
        });

        // Build up the blanks
        $blanks = [];
        foreach ($fieldset->fields() as $name => $config) {
            if (! $default = array_get($config, 'default')) {
                $default = $fieldtypes->get($name)->blank();
            }

            $blanks[$name] = $default;
        }

        return array_merge($blanks, $data);
    }

    public function getResetUrl($username)
    {
        $resetter = new PasswordReset;

        $resetter->user(User::whereUsername($username));

        return [
            'success' => true,
            'url' => $resetter->url()
        ];
    }

    public function sendResetEmail($username)
    {
        $user = User::whereUsername($username);

        if (! $user->email()) {
            return ['success' => false];
        }

        $resetter = new PasswordReset;

        $resetter->user($user);

        $resetter->send();

        return ['success' => true];
    }
}
