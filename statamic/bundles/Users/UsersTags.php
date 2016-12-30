<?php

namespace Statamic\Addons\Users;

use Statamic\API\Role;
use Statamic\API\User;
use Statamic\API\UserGroup;
use Statamic\Addons\Collection\CollectionTags;

class UsersTags extends CollectionTags
{
    public function index()
    {
        $this->collection = collect_content(User::all());

        if ($group = $this->get('group')) {
            $this->filterByGroup($group);
        }

        if ($role = $this->get('role')) {
            $this->filterByRole($role);
        }

        $this->filter();

        if ($this->collection->isEmpty()) {
            return $this->parseNoResults();
        }

        return $this->output();
    }

    public function getSortOrder()
    {
        return $this->get('sort', 'username');
    }

    protected function filterByGroup($group)
    {
        $group = UserGroup::whereHandle($group);

        $this->collection = $this->collection->filter(function ($user) use ($group) {
            return $user->inGroup($group);
        });
    }

    protected function filterByRole($role)
    {
        $role = Role::whereHandle($role);

        $this->collection = $this->collection->filter(function ($user) use ($role) {
            return $user->hasRole($role);
        });
    }
}
