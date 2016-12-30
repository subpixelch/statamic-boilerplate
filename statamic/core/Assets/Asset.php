<?php

namespace Statamic\Assets;

use Carbon\Carbon;
use Statamic\API\AssetContainer as AssetContainerAPI;
use Statamic\API\Str;
use Statamic\API\URL;
use Statamic\API\Path;
use Statamic\API\YAML;
use Statamic\API\Event;
use Statamic\API\Image;
use Statamic\Data\Data;
use Statamic\API\Config;
use Statamic\API\Helper;
use Statamic\API\Storage;
use Statamic\API\Fieldset;
use Statamic\API\File;
use Statamic\Exceptions\UuidExistsException;
use Statamic\Contracts\Assets\Asset as AssetContract;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Asset extends Data implements AssetContract
{
    /**
     * Get the driver this asset's container uses
     *
     * @return string
     */
    public function driver()
    {
        return $this->container()->driver();
    }

    /**
     * Get the container's filesystem disk instance
     *
     * @return \Statamic\Filesystem\FileAccessor
     */
    public function disk()
    {
        return File::disk('assets:' . $this->container()->uuid());
    }

    /**
     * Get or set the ID
     *
     * @param mixed $id
     * @return mixed
     * @throws \Statamic\Exceptions\UuidExistsException
     */
    public function id($id = null)
    {
        if (is_null($id)) {
            return array_get($this->attributes, 'id');
        }

        if ($this->id()) {
            throw new UuidExistsException('Data already has an ID');
        }

        // If true is passed in, we'll generate a UUID. Otherwise just use what was passed.
        $id = ($id === true) ? Helper::makeUuid() : $id;
        $this->attributes['id'] = $id;

        return $this;
    }

    /**
     * @param string|null $folder
     * @return \Statamic\Contracts\Assets\AssetFolder
     */
    public function folder($folder = null)
    {
        if (is_null($folder)) {
            return $this->container()->folder($this->attributes['folder']);
        }

        $this->attributes['folder'] = $folder;
    }

    /**
     * @return string
     */
    public function filename()
    {
        return pathinfo($this->basename())['filename'];
    }

    /**
     * @param string|null $basename
     * @return string
     */
    public function basename($basename = null)
    {
        if (is_null($basename)) {
            return $this->attributes['basename'];
        }

        $this->attributes['basename'] = $basename;
    }

    /**
     * Get or set the path to the data
     *
     * @param string|null $path Path to set
     * @return mixed
     */
    public function path($path = null)
    {
        if (is_null($path)) {
            return $this->getPath();
        }

        $this->attributes['path'] = $path;
    }

    /**
     * @return string
     */
    protected function getPath()
    {
        if (isset($this->attributes['path'])) {
            return $this->attributes['path'];
        }

        return ltrim(Path::assemble($this->folder()->path(), $this->basename()), '/');
    }

    /**
     * Get the path to a localized version
     *
     * @param string $locale
     * @return string
     */
    public function localizedPath($locale)
    {
        // @todo
        dd('todo asset@localizedpath');
    }

    public function resolvedPath()
    {
        return Path::tidy($this->folder()->resolvedPath() . '/' . $this->basename());
    }

    /**
     * Get the asset's URL
     *
     * @return string
     */
    public function uri()
    {
        return URL::assemble($this->container()->url(), $this->path());
    }

    /**
     * Get the asset's URL, and encode it
     *
     * @return string
     */
    public function url()
    {
        return URL::encode($this->uri());
    }

    /**
     * Get the asset's absolute URL
     *
     * @return string
     * @throws \RuntimeException
     */
    public function absoluteUrl()
    {
        return URL::makeAbsolute($this->url());
    }

    /**
     * Get either a image URL builder instance, or a URL if passed params.
     *
     * @param null|array $params Optional manipulation parameters to return a string right away
     * @return \Statamic\Contracts\Imaging\UrlBuilder|string
     * @throws \Exception
     */
    public function manipulate($params = null)
    {
        return Image::manipulate($this->id(), $params);
    }

    /**
     * Is this asset an image?
     *
     * @return bool
     */
    public function isImage()
    {
        return (in_array(strtolower($this->extension()), ['jpg', 'jpeg', 'png', 'gif']));
    }

    /**
     * @return string
     */
    public function extension()
    {
        return Path::extension($this->path());
    }

    /**
     * @return \Carbon\Carbon
     */
    public function lastModified()
    {
        return Carbon::createFromTimestamp($this->disk()->lastModified($this->path()));
    }

    /**
     * Save the file
     */
    public function save()
    {
        $this->ensureId();

        $this->folder()->addAsset($this->id(), $this);

        $this->folder()->save();

        event('asset.saved', $this);
    }

    /**
     * Delete the data
     *
     * @return mixed
     */
    public function delete()
    {
        // First we need to remove the asset from the array in folder.yaml
        // and the corresponding localized versions, if applicable.
        $this->folder()->removeAsset($this->id());
        $this->folder()->save();

        // Also, delete the actual file
        $this->disk()->delete($this->path());
    }

    /**
     * Get or set the container where this asset is located
     *
     * @param string $id  ID of the container
     * @return \Statamic\Contracts\Assets\AssetContainer
     */
    public function container($id = null)
    {
        if (is_null($id)) {
            return AssetContainerAPI::find($this->attributes['container']);
        }

        $this->attributes['container'] = $id;
    }

    /**
     * Rename the data
     */
    protected function rename()
    {
        // TODO: Implement delete() method.
    }

    /**
     * Get the asset's dimensions
     *
     * @return array  An array in the [width, height] format
     */
    public function dimensions()
    {
        if (! $this->isImage()) {
            return [null, null];
        }

        if ($this->driver() === 'local') {
            $path = Path::assemble($this->disk()->filesystem()->getAdapter()->getPathPrefix(), $this->path());
            return getimagesize($path);
        } elseif ($this->driver() === 's3') {
            return getimagesize($this->url());
        }
    }

    /**
     * Get the asset's width
     *
     * @return int|null
     */
    public function width()
    {
        return array_get($this->dimensions(), 0);
    }

    /**
     * Get the asset's height
     *
     * @return int|null
     */
    public function height()
    {
        return array_get($this->dimensions(), 1);
    }

    /**
     * Convert to an array
     *
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();

        unset($array['content'], $array['content_raw']);

        $extra = [
            'uuid'      => $this->id(), // @todo remove
            'id'        => $this->id(),
            'title'     => $this->get('title', $this->filename()),
            'url'       => $this->url(),
            'permalink' => $this->absoluteUrl(),
            'path'      => $this->path(),
            'filename'  => $this->filename(),
            'basename'  => $this->basename(),
            'extension' => $this->extension(),
            'is_image'  => $this->isImage(),
            'is_asset'  => true,
            'fieldset' => $this->fieldset()->name()
        ];

        if ($exists = $this->disk()->exists($this->path())) {
            $size = $this->disk()->size($this->path());
            $kb = number_format($size / 1024, 2);
            $mb = number_format($size / 1048576, 2);
            $gb = number_format($size / 1073741824, 2);

            $extra = array_merge($extra, [
                'size'           => $this->disk()->sizeHuman($this->path()),
                'size_bytes'     => $size,
                'size_kilobytes' => $kb,
                'size_megabytes' => $mb,
                'size_gigabytes' => $gb,
                'size_b'         => $size,
                'size_kb'        => $kb,
                'size_mb'        => $mb,
                'size_gb'        => $gb,
                'width'          => $this->width(),
                'height'          => $this->height(),
                'last_modified'  => (string) $this->lastModified(),
                'last_modified_timestamp' => $this->lastModified()->timestamp,
                'last_modified_instance'  => $this->lastModified(),
            ]);
        }

        return array_merge($array, $extra);
    }

    /**
     * Add supplemental data to the attributes
     *
     * @return void
     */
    public function supplement()
    {
        // The Asset object implements its own toArray method,
        // which negates the need for a supplement method.
    }

    /**
     * Upload a file
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile $file
     * @return void
     */
    public function upload(UploadedFile $file)
    {
        $basename  = $file->getClientOriginalName();
        $filename  = pathinfo($basename)['filename'];
        $ext       = $file->getClientOriginalExtension();

        $directory = $this->folder()->path();
        $path      = Path::tidy($directory . '/' . $filename . '.' . $ext);

        // If the file exists, we'll append a timestamp to prevent overwriting.
        if ($this->disk()->exists($path)) {
            $basename = $filename . '-' . time() . '.' . $ext;
            $path = Str::removeLeft(Path::assemble($directory, $basename), '/');
        }

        $this->performUpload($file, $path);

        Event::fire('asset.uploaded', $path);

        $this->basename($basename);
    }

    /**
     * Actually perform the file upload.
     *
     * Saves the file to a temporary location on the local filesystem, then moves it to the
     * right place. This is a workaround for needing to know the file extension or mime
     * type when uploading to Amazon S3. Temporary files don't have file extensions
     * so sending directly to S3 causes it to appear with the wrong mime type.
     *
     * @param UploadedFile $file
     * @param string $path
     */
    private function performUpload(UploadedFile $file, $path)
    {
        // Get the underlying root flysystem driver instance
        $temp_disk = File::disk()->filesystem()->getDriver();

        // Build up a path where the file will be temporarily stored
        $temp = Path::makeRelative(
            temp_path('uploads/'.md5($file->getRealPath().microtime(true)).'.'.$file->getClientOriginalExtension())
        );

        // Upload to a temporary location
        $stream = fopen($file->getRealPath(), 'r+');
        $temp_disk->putStream($temp, $stream);
        fclose($stream);

        // Move from the temporary location to the real container location
        $this->disk()->put($path, $temp_disk->readStream($temp));

        // Delete the temporary file
        $temp_disk->delete($temp);
    }

    /**
     * Get or set the fieldset
     *
     * @param string|null $fieldset
     * @return \Statamic\CP\Fieldset
     * @throws \Exception
     * @throws \Statamic\Exceptions\FileNotFoundException
     */
    public function fieldset($fieldset = null)
    {
        if (! is_null($fieldset)) {
            throw new \Exception('You cannot set an asset fieldset.');
        }

        // Check the container
        if ($fieldset = $this->container()->fieldset()) {
            return $fieldset;
        }

        // Then the default asset fieldset
        return Fieldset::get(Config::get('theming.default_asset_fieldset'));
    }

    /**
     * Get the path before the object was modified.
     *
     * @return string
     */
    public function originalPath()
    {
        // @todo
        dd('todo: extend data@originalPath');
    }

    /**
     * Get the path to a localized version before the object was modified.
     *
     * @param string $locale
     * @return string
     */
    public function originalLocalizedPath($locale)
    {
        // @todo
        dd('todo: extend data@localizedPath');
    }

    /**
     * The URL to edit it in the CP
     *
     * @return mixed
     */
    public function editUrl()
    {
        return cp_route('asset.edit', $this->id());
    }
}
