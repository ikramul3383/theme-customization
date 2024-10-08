<?php

namespace WebinaneCommerce\Fields;

use WebinaneCommerce\Helpers\Metable;
use WebinaneCommerce\Helpers\Rulable;
use Closure;
// use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use WebinaneCommerce\Helpers\Macroable;
use JsonSerializable;

abstract class Field implements JsonSerializable
{
    use Macroable, Metable, Rulable;

    public $panel;
    public $default;
    /**
     * The displayable name of the field.
     *
     * @var string
     */
    public $name;

    /**
     * The attribute / column name of the field.
     *
     * @var string
     */
    public $attribute;

    /**
     * The field's resolved value.
     *
     * @var mixed
     */
    public $value;

    /**
     * The callback to be used to resolve the field's display value.
     *
     * @var \Closure
     */
    public $displayCallback;

    /**
     * The callback to be used to resolve the field's value.
     *
     * @var \Closure
     */
    public $resolveCallback;

    /**
     * The callback to be used to hydrate the model attribute.
     *
     * @var callable
     */
    public $fillCallback;

    /**
     * The callback to be used for computed field.
     *
     * @var callable
     */
    protected $computedCallback;

    /**
     * The callback to be used for the field's default value.
     *
     * @var callable
     */
    protected $defaultCallback;

    /**
     * The validation rules for creation and updates.
     *
     * @var array
     */
    public $rules = [];

    /**
     * The validation rules for creation.
     *
     * @var array
     */
    public $creationRules = [];

    /**
     * The validation rules for updates.
     *
     * @var array
     */
    public $updateRules = [];

    /**
     * Indicates if the field should be sortable.
     *
     * @var bool
     */
    public $sortable = false;

    /**
     * Indicates if the field is nullable.
     *
     * @var bool
     */
    public $nullable = false;

    /**
     * Values which will be replaced to null.
     *
     * @var array
     */
    public $nullValues = [''];

    /**
     * Indicates if the field was resolved as a pivot field.
     *
     * @var bool
     */
    public $pivot = false;

    /**
     * The text alignment for the field's text in tables.
     *
     * @var string
     */
    public $textAlign = 'left';

    /**
     * Indicates if the field label and form element should sit on top of each other.
     *
     * @var bool
     */
    public $stacked = false;

    /**
     * The custom components registered for fields.
     *
     * @var array
     */
    public static $customComponents = [];

    /**
     * The callback used to determine if the field is readonly.
     *
     * @var Closure
     */
    public $readonlyCallback;

    /**
     * The callback used to determine if the field is required.
     *
     * @var Closure
     */
    public $requiredCallback;

    /**
     * The resource associated with the field.
     *
     * @var \Laravel\Nova\Resource
     */
    public $resource;

    /**
     * The resource associated with the field.
     *
     * @var \Laravel\Nova\Resource
     */
    public $size = 24;

    /**
     * Help Text.
     *
     * @var string
     */
    public $help_text = '';

    /**
     * Dependencies
     *
     * @var array
     */
    protected $dependencies = [];
    /**
     * Create a new field.
     *
     * @param  string  $name
     * @param  string|callable|null  $attribute
     * @param  callable|null  $resolveCallback
     * @return void
     */
    public function __construct($name, $attribute = null, callable $resolveCallback = null)
    {
        $this->name = $name;
        $this->resolveCallback = $resolveCallback;

        $this->default(null);

        if ($attribute instanceof Closure ||
            (is_callable($attribute) && is_object($attribute))) {
            $this->computedCallback = $attribute;
            $this->attribute = 'ComputedField';
        } else {
            $this->attribute = $attribute ?? str_replace(' ', '_', Str::lower($name));
        }
    }

    /**
     * Stack the label above the field.
     *
     * @param bool $stack
     *
     * @return $this
     */
    public function stacked($stack = true)
    {
        $this->stacked = $stack;

        return $this;
    }

    /**
     * Resolve the field's value for display.
     *
     * @param  mixed  $resource
     * @param  string|null  $attribute
     * @return void
     */
    public function resolveForDisplay($resource, $attribute = null)
    {
        $this->resource = $resource;

        $attribute = $attribute ?? $this->attribute;

        if ($attribute === 'ComputedField') {
            $this->value = call_user_func($this->computedCallback, $resource);
        }


        if (! $this->displayCallback) {
            $this->resolve($resource, $attribute);
        } elseif (is_callable($this->displayCallback)) {
            tap($this->value ?? $this->resolveAttribute($resource, $attribute), function ($value) use ($resource, $attribute) {
                $this->value = call_user_func($this->displayCallback, $value, $resource, $attribute);
            });
        }
    }

    /**
     * Resolve the field's value.
     *
     * @param  mixed  $resource
     * @param  string|null  $attribute
     * @return void
     */
    public function resolve($resource, $attribute = null)
    {
        $this->resource = $resource;

        $attribute = $attribute ?? $this->attribute;


        if ($attribute === 'ComputedField') {
            $this->value = call_user_func($this->computedCallback, $resource);

            return;
        }
        if (! $this->resolveCallback) {
            $this->value = $this->resolveAttribute($resource, $attribute);
        } elseif (is_callable($this->resolveCallback)) {
        // exit('sdfsd');
            $this->value = call_user_func($this->resolveCallback, $resource, $attribute);
        }
    }

    /**
     * Resolve the given attribute from the given resource.
     *
     * @param  mixed  $resource
     * @param  string  $attribute
     * @return mixed
     */
    protected function resolveAttribute($resource, $attribute)
    {
        return data_get($resource, str_replace('->', '.', $attribute));
    }

    /**
     * Define the callback that should be used to display the field's value.
     *
     * @param  callable  $displayCallback
     * @return $this
     */
    public function displayUsing(callable $displayCallback)
    {
        $this->displayCallback = $displayCallback;

        return $this;
    }

    /**
     * Define the callback that should be used to resolve the field's value.
     *
     * @param  callable  $resolveCallback
     * @return $this
     */
    public function resolveUsing(callable $resolveCallback)
    {
        $this->resolveCallback = $resolveCallback;

        return $this;
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  object  $model
     * @return mixed
     */
    public function fill(Request $request, $model)
    {
        return $this->fillInto($request, $model, $this->attribute);
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  object  $model
     * @return mixed
     */
    public function fillForAction(Request $request, $model)
    {
        return $this->fill($request, $model);
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  object  $model
     * @param  string  $attribute
     * @param  string|null  $requestAttribute
     * @return mixed
     */
    public function fillInto(Request $request, $model, $attribute, $requestAttribute = null)
    {
        return $this->fillAttribute($request, $requestAttribute ?? $this->attribute, $model, $attribute);
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  string  $requestAttribute
     * @param  object  $model
     * @param  string  $attribute
     * @return void
     */
    protected function fillAttribute(Request $request, $requestAttribute, $model, $attribute)
    {
        if (isset($this->fillCallback)) {
            return call_user_func(
                $this->fillCallback, $request, $model, $attribute, $requestAttribute
            );
        }

        return $this->fillAttributeFromRequest(
            $request, $requestAttribute, $model, $attribute
        );
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  string  $requestAttribute
     * @param  object  $model
     * @param  string  $attribute
     * @return mixed
     */
    protected function fillAttributeFromRequest(Request $request, $requestAttribute, $model, $attribute)
    {
        if ($request->exists($requestAttribute)) {
            $value = $request[$requestAttribute];

            $model->{$attribute} = $this->isNullValue($value) ? null : $value;
        }
    }

    /**
     * Check value for null value.
     *
     * @param  mixed $value
     * @return bool
     */
    protected function isNullValue($value)
    {
        if (! $this->nullable) {
            return false;
        }

        return is_callable($this->nullValues)
            ? ($this->nullValues)($value)
            : in_array($value, (array) $this->nullValues);
    }

    /**
     * Specify a callback that should be used to hydrate the model attribute for the field.
     *
     * @param  callable  $fillCallback
     * @return $this
     */
    public function fillUsing($fillCallback)
    {
        $this->fillCallback = $fillCallback;

        return $this;
    }

    /**
     * Set the validation rules for the field.
     *
     * @param  callable|array|string  $rules
     * @return $this
     */
    public function rules($rules)
    {
        $this->rules = ($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules;

        return $this;
    }

    /**
     * Get the validation rules for this field.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function getRules()
    {
        return [$this->attribute => is_callable($this->rules)
                            ? call_user_func($this->rules)
                            : $this->rules, ];
    }

    /**
     * Get the creation rules for this field.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array|string
     */
    public function getCreationRules(Request $request)
    {
        $rules = [$this->attribute => is_callable($this->creationRules)
                            ? call_user_func($this->creationRules, $request)
                            : $this->creationRules, ];

        return array_merge_recursive(
            $this->getRules($request), $rules
        );
    }

    /**
     * Set the creation validation rules for the field.
     *
     * @param  callable|array|string  $rules
     * @return $this
     */
    public function creationRules($rules)
    {
        $this->creationRules = ($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules;

        return $this;
    }

    /**
     * Get the update rules for this field.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function getUpdateRules(Request $request)
    {
        $rules = [$this->attribute => is_callable($this->updateRules)
                            ? call_user_func($this->updateRules, $request)
                            : $this->updateRules, ];

        return array_merge_recursive(
            $this->getRules($request), $rules
        );
    }

    /**
     * Set the creation validation rules for the field.
     *
     * @param  callable|array|string  $rules
     * @return $this
     */
    public function updateRules($rules)
    {
        $this->updateRules = ($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules;

        return $this;
    }

    /**
     * Get the validation attribute for the field.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return string
     */
    public function getValidationAttribute(Request $request)
    {
        return $this->validationAttribute ?? Str::singular($this->attribute);
    }

    /**
     * Specify that this field should be sortable.
     *
     * @param  bool  $value
     * @return $this
     */
    public function sortable($value = true)
    {
        if (! $this->computed()) {
            $this->sortable = $value;
        }

        return $this;
    }

    /**
     * Return the sortable uri key for the field.
     *
     * @return string
     */
    public function sortableUriKey()
    {
        return $this->attribute;
    }

    /**
     * Indicate that the field should be nullable.
     *
     * @param  bool  $nullable
     * @param  array|Closure  $values
     * @return $this
     */
    public function nullable($nullable = true, $values = null)
    {
        $this->nullable = $nullable;

        if ($values !== null) {
            $this->nullValues($values);
        }

        return $this;
    }

    /**
     * Specify nullable values.
     *
     * @param  array|Closure  $values
     * @return $this
     */
    public function nullValues($values)
    {
        $this->nullValues = $values;

        return $this;
    }

    /**
     * Determine if the field is computed.
     *
     * @return bool
     */
    public function computed()
    {
        return (is_callable($this->attribute) && ! is_string($this->attribute)) ||
               $this->attribute == 'ComputedField';
    }

    /**
     * Get the component name for the field.
     *
     * @return string
     */
    public function component()
    {
        if (isset(static::$customComponents[get_class($this)])) {
            return static::$customComponents[get_class($this)];
        }

        return $this->component;
    }

    /**
     * Set the component that should be used by the field.
     *
     * @param  string  $component
     * @return void
     */
    public static function useComponent($component)
    {
        static::$customComponents[get_called_class()] = $component;
    }

    /**
     * Set the callback used to determine if the field is readonly.
     *
     * @param  Closure|bool  $callback
     * @return $this
     */
    public function readonly($callback = true)
    {
        $this->readonlyCallback = $callback;

        return $this;
    }

    /**
     * Determine if the field is readonly.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return bool
     */
    public function isReadonly($request)
    {
        $callback = $this->readonlyCallback;
        
        if ($callback === true || (is_callable($callback) && call_user_func($callback, $request))) {
            $this->setReadonlyAttribute();

            return true;
        }

        return false;
        
    }

    /**
     * Set the field to a readonly field.
     *
     * @return $this
     */
    protected function setReadonlyAttribute()
    {
        $this->withMeta(['extraAttributes' => ['readonly' => true]]);

        return $this;
    }

    /**
     * Set the text alignment of the field.
     *
     * @param  string  $alignment
     * @return $this
     */
    public function textAlign($alignment)
    {
        $this->textAlign = $alignment;

        return $this;
    }

    /**
     * Set the callback used to determine if the field is required.
     *
     * @param  Closure|bool  $callback
     * @return $this
     */
    public function required($callback = true)
    {
        $this->requiredCallback = $callback;

        return $this;
    }

    /**
     * Determine if the field is required.
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return bool
     */
    public function isRequired($request)
    {
        $callback = $this->requiredCallback;
        if ($callback === true || (is_callable($callback) && call_user_func($callback, $request))) {
            return true;
        }
        return false;
    }

    /**
     * Return the validation key for the field.
     *
     * @return string
     */
    public function validationKey()
    {
        return $this->attribute;
    }

    /**
     * Set the width for the help text tooltip.
     *
     * @param  string
     * @return $this
     * @throws \Exception
     */
    public function helpWidth($helpWidth)
    {
        throw new \Exception('Help width is not supported on fields.');
    }

    /**
     * Return the width of the help text tooltip.
     *
     * @return string
     * @throws \Exception
     */
    public function getHelpWidth()
    {
        throw new \Exception('Help width is not supported on fields.');
    }

    /**
     * Set the callback to be used for determining the field's default value.
     *
     * @param $callback
     * @return $this
     */
    public function default($callback)
    {
        $this->default = $callback;

        return $this;
    }

    /**
     * Resolve the default value for the field.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return callable|mixed
     */
    protected function resolveDefaultValue($request)
    {
        if($this->default) {
            return $this->default;
        }
        return '';
    }

    public function setDependency($rule)
    {
        $this->dependencies[] = $rule;

        return $this;
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        $request = new \stdClass;
        return array_merge([
                'id' => $this->attribute,
                'type' => $this->component(),
                'help' => $this->getHelpText(),
                'default' => $this->default,
                'indexName' => $this->name,
                'label' => $this->name,
                'nullable' => $this->nullable,
                'panel' => $this->panel,
                'prefixComponent' => true,
                'readonly' => $this->isReadonly($request),
                'required' => $this->isRequired($request),
                // 'sortable' => $this->sortable,
                // 'sortableUriKey' => $this->sortableUriKey(),
                'stacked' => $this->stacked,
                'textAlign' => $this->textAlign,
                'validationKey' => $this->validationKey(),
                'value' => $this->value ?? $this->default,
                'col'   => $this->size,
                'vshow' => $this->dependencies ? $this->dependencies : null, 
            ], ['props' => $this->meta(), 'rules' => $this->getRules()]
        );
        
    }

    /**
     * Create a new element.
     *
     * @return static
     */
    public static function make(...$arguments)
    {
        return new static(...$arguments);
    }

    public function setHelp($help) {
        $this->help_text = $help;
        return $this;
    }
    /**
     * [getHelpText description]
     * @return [type] [description]
     */
    public function getHelpText() {
        return $this->help_text;
    }

    /**
     * Set the column Size.
     */
    public function size($size = 24) {
        $this->size = $size;

        return $this;
    }
}
