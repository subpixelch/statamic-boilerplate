<?php

namespace Statamic\View;

use Exception;
use ArrayIterator;
use Statamic\API\Str;
use Statamic\API\Helper;
use Statamic\Extend\Management\Loader;
use Statamic\Exceptions\ModifierException;
use Statamic\Exceptions\NativeModifierNotFoundException;

class Modify implements \IteratorAggregate
{
    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var array
     */
    protected $context = [];

    /**
     * @var \Statamic\Extend\Management\Loader
     */
    private $loader;

    public function __construct(Loader $loader)
    {
        $this->loader = $loader;
    }

    /**
     * Specify a value to start the modification chain
     *
     * @param mixed $value
     * @return \Statamic\View\Modify
     */
    public static function value($value)
    {
        $instance = app(self::class);

        $instance->value = $value;

        return $instance;
    }

    /**
     * Set the context
     *
     * @param array $context
     * @return $this
     */
    public function context($context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Get the raw value
     *
     * @return mixed
     */
    public function fetch()
    {
        return $this->value;
    }

    /**
     * Get the value as a string
     *
     * @return string
     * @throws \Statamic\Exceptions\ModifierException
     */
    public function __toString()
    {
        if (! is_string($this->value) && ! method_exists($this->value, '__toString')) {
            throw new ModifierException(
                'Attempted to access modified value as a string, but encountered ['.get_class($this->value).']'
            );
        }

        return (string) $this->value;
    }

    /**
     * Get the value as an array
     *
     * @return \Traversable
     * @throws \Statamic\Exceptions\ModifierException
     */
    public function getIterator()
    {
        if (! is_array($this->value)) {
            throw new ModifierException(sprintf(
                'Attempted to access modified value as an array, but encountered [%s]',
                is_string($this->value) ? 'string' : get_class($this->value)
            ));
        }

        return new ArrayIterator($this->fetch());
    }

    /**
     * Allow calls to modifiers via method names
     *
     * @param  string $method Modifier name
     * @param  array  $args   Any parameters as arguments
     * @return $this
     */
    public function __call($method, $args)
    {
        $this->value = $this->modify($method, array_get($args, 0));

        return $this;
    }

    /**
     * Modify a value
     *
     * @param string $modifier
     * @param array  $params
     * @return mixed
     * @throws \Statamic\Exceptions\ModifierException
     */
    public function modify($modifier, $params = [])
    {
        // Blade and/or raw PHP usage might pass a single parameter.
        // We should make sure it's always an array.
        $params = Helper::ensureArray($params);

        // Templates will use snake_case to specify modifiers, so we'll
        // convert them to the correct PSR-2 modifier method name.
        $modifier = Str::camel($modifier);

        try {
            // Attempt to run the modifier. If it worked, awesome,
            // we'll have successfully returned a modified value.
            return $this->runModifier($modifier, $params);

        } catch (ModifierException $e) {
            // If this class explicitly raised an exception, it would've
            // been a ModifierException, so we'll just rethrow it since
            // we'll be catching it on the view side of things.
            $e->setModifier($modifier);
            throw $e;

        } catch (Exception $e) {
            // If a modifier's code raised an exception, we'll just
            // catch it here and rethrow it as a ModifierException.
            $e = new ModifierException($e->getMessage());
            $e->setModifier($modifier);
            throw $e;
        }
    }

    /**
     * Run the modifier
     *
     * We keep all the native bundled modifiers in one big juicy class
     * rather than a million separate files. First, we'll check there
     * then attempt to load a modifier in a regular addon location.
     *
     * @param string $modifier
     * @param array $params
     * @return mixed
     * @throws \Exception
     */
    protected function runModifier($modifier, $params)
    {
        try {
            return $this->modifyNatively($modifier, $params);
        } catch (NativeModifierNotFoundException $e) {
            return $this->modifyThirdParty($modifier, $params);
        }
    }

    /**
     * Attempt to modify using the native modifiers
     *
     * @param string $modifier
     * @param array  $params
     * @return mixed
     * @throws \Statamic\Exceptions\NativeModifierNotFoundException
     */
    protected function modifyNatively($modifier, $params)
    {
        $base_modifiers = app('Statamic\View\BaseModifiers');

        $method = $this->resolveAlias($modifier);

        if (! method_exists($base_modifiers, $method)) {
            throw new NativeModifierNotFoundException;
        }

        return $base_modifiers->$method($this->value, $params, $this->context);
    }

    /**
     * Modify using third party addons
     *
     * @param string $modifier
     * @param array  $params
     * @return mixed
     * @throws \Exception
     */
    protected function modifyThirdParty($modifier, $params)
    {
        $class = $this->loader->loadModifier($modifier);

        if (! method_exists($class, 'index')) {
            throw new Exception("Modifier [$modifier] is missing index method.");
        }

        return $class->index($this->value, $params, $this->context);
    }

    /**
     * Resolve a modifier alias
     *
     * @param string $modifier
     * @return string
     */
    protected function resolveAlias($modifier)
    {
        switch (Str::camel($modifier)) {
            case "+":
                return "add";

            case "-":
                return "subtract";

            case "*":
                return "multiply";

            case "/":
                return "divide";

            case "%":
                return "mod";

            case "^":
                return "exponent";

            case "dd":
                return "dump";

            case "ago":
            case "until":
            case "since":
                return "relative";

            case "specialchars":
            case "htmlspecialchars":
                return "sanitize";

            case "striptags":
                return "stripTags";

            case "join":
            case "implode":
            case "list":
                return "joinplode";

            case "piped":
                return "optionList";

            case "json":
                return "toJson";

            case "email":
                return "obfuscateEmail";

            case "l10n":
                return "formatLocalized";

            case "85":
                return "slackEasterEgg";

            case "tz":
                return "timezone";

            case "inFuture":
                return "isFuture";

            case "inPast":
                return "isPast";

            default:
                return $modifier;
        }
    }
}
