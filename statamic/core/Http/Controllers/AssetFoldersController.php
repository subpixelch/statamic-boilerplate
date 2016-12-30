<?php

namespace Statamic\Http\Controllers;

use Statamic\API\AssetContainer;
use Statamic\API\Folder;
use Statamic\API\Helper;
use Statamic\API\Path;
use Statamic\API\Stache;

class AssetFoldersController extends CpController
{
    public function store()
    {
        $container = AssetContainer::find($this->request->input('container'));

        $parent = $container->folder($this->request->input('parent'));

        $basename = $this->request->input('basename');

        $folder = $parent->createFolder($basename);

        $folder->set('title', $this->request->input('title'));

        $folder->save();

        Stache::update();

        return ['success' => true, 'message' => 'Folder created', 'folder' => $folder->toArray()];
    }

    public function edit($container, $folder = '/')
    {
        $container = AssetContainer::find($container);

        $folder = $container->folder($folder);

        return $folder->toArray();
    }

    public function update($container, $folder = '/')
    {
        $container = AssetContainer::find($container);

        $folder = $container->folder($folder);

        $folder->set('title', $this->request->input('title'));

        $folder->save();

        Stache::update();

        return ['success' => true, 'message' => 'Folder updated', 'folder' => $folder->toArray()];
    }

    public function delete()
    {
        $container = AssetContainer::find($this->request->input('container'));

        $folders = Helper::ensureArray($this->request->input('folders'));

        foreach ($folders as $folder) {
            $folder = $container->folder($folder)->delete();
        }

        return ['success' => true];
    }
}
