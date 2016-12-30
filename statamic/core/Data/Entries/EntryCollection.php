<?php

namespace Statamic\Data\Entries;

use Carbon\Carbon;
use Statamic\Data\Content\ContentCollection;

class EntryCollection extends ContentCollection
{
    /**
     * Removes entries with a date in the future
     *
     * @return static
     */
    public function removeFuture()
    {
        return $this->reject(function($entry) {
            if ($entry->orderType() !== 'date') {
                return false;
            }

            return Carbon::now()->lt($entry->date());
        });
    }

    /**
     * Removes entries with a date in the past
     *
     * @return static
     */
    public function removePast()
    {
        return $this->reject(function($entry) {
            if ($entry->orderType() !== 'date') {
                return false;
            }

            return Carbon::now()->gt($entry->date());
        });
    }

    /**
     * Removes entries before whose date is before a given date
     *
     * @param mixed $before
     * @return static
     */
    public function removeBefore($before)
    {
        return $this->reject(function($entry) use ($before) {
            if ($entry->orderType() !== 'date') {
                return false;
            }

            return $entry->date()->lt(Carbon::parse($before));
        });
    }

    /**
     * Removes entries before whose date is after a given date
     *
     * @param mixed $after
     * @return static
     */
    public function removeAfter($after)
    {
        return $this->reject(function($entry) use ($after) {
            if ($entry->orderType() !== 'date') {
                return false;
            }

            return $entry->date()->gt(Carbon::parse($after));
        });
    }
}
