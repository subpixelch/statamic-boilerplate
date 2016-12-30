<?php

namespace Statamic\CP;

use Statamic\Exceptions\FatalException;
use Statamic\Exceptions\ResourceNotFoundException;

/**
 * Creates a Fieldtype instance
 */
class FieldtypeFactory
{
    /**
     * Create a new fieldtype
     *
     * @param string $type
     * @param array  $config
     * @return \Statamic\Extend\Fieldtype
     * @throws \Statamic\Exceptions\FatalException
     */
    public static function create($type, array $config = [])
    {
        try {
            $fieldtype = resource_loader()->loadFieldtype($type, $config);
        } catch (ResourceNotFoundException $e) {
            $message = "Fieldtype [$type] does not exist.";

            if ($suggestion = self::getSuggestion($type)) {
                $message .= " Try [$suggestion].";
            }

            throw new FatalException($message);
        }

        return $fieldtype;
    }

    /**
     * Get a suggestion for the unknown fieldtype, if they've used a deprecated one from v1.
     *
     * @param  string $type
     * @return null|string
     */
    private static function getSuggestion($type)
    {
        switch ($type) {
            case 'file':
                return 'assets';
            case 'markitup':
                return 'markdown';
        }
    }
}
