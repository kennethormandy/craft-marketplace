<?php

namespace kennethormandy\marketplace\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\elements\User;
use kennethormandy\marketplace\Marketplace;
use Stripe\Account;
use Stripe\Stripe;

// TODO Investigated extending these Users or
//      BaseRelationField, but wasn’t clear
//      if it is intended.
// use craft\fields\Users;
// use craft\fields\BaseRelationField;
// use craft\elements\db\ElementQueryInterface;
// use craft\db\Table as DbTable;


/**
 * MarketplacePayee Field.
 *
 * Whenever someone creates a new field in Craft, they must specify what
 * type of field it is. The system comes with a handful of field types baked in,
 * and we’ve made it extremely easy for plugins to add new ones.
 *
 * https://craftcms.com/docs/plugins/field-types
 *
 * @author    Kenneth Ormandy
 * @since     0.1.0
 */
class MarketplacePayee extends Field

{
    // /**
    //  * @inheritdoc
    //  */
    // protected static function elementType(): string
    // {
    //     return User::class;
    // }

    // /**
    //  * @inheritdoc
    //  */
    // public static function valueType(): string
    // {
    //     return ElementQueryInterface::class;
    // }

    // public $sortable = false;
    // public $allowMultipleSources = false;
    // public $targetSiteId;
    // public $source;
    // public $allowLimit = true;
    // public $limit = 1;

    // public function init()
    // {
    //     parent::init();
    //     // $this->handle = '';
    //     // $this->handlesService->getButtonHandle($userObject)
    // }

    // public function modifyElementIndexQuery(ElementQueryInterface $query)
    // {
    //     // if ($this instanceof EagerLoadingFieldInterface && isset($this->handle) && !empty($this->handle)) {
    //     //     $query->andWithout($this->handle);
    //     // }
    // }

    // Public Properties
    // =========================================================================

    /**
     * Some attribute.
     *
     * @var string
     */
    // public $someAttribute = 'Some Default';

    // Static Methods
    // =========================================================================

    /**
     * Returns the display name of this class.
     *
     * @return string The display name of this class.
     */
    public static function displayName(): string
    {
        return Craft::t('marketplace', 'Marketplace Payee');
    }

    // Public Methods
    // =========================================================================

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules()
    {
        $rules = parent::rules();
        // $rules = array_merge($rules, [
        //     ['someAttribute', 'string'],
        //     ['someAttribute', 'default', 'value' => 'Some Default'],
        // ]);
        return $rules;
    }

    // Value might have been incorrectly stored as an array as a JSON string in
    // the database. In serializeValue, we now provide a single item as a string.
    //   Relevant, especially if changing to relation later:
    // https://github.com/craftcms/cms/blob/develop/src/fields/BaseRelationField.php#L398
    public function normalizeValue($value, ElementInterface $element = null)
    {
        if (is_string($value)) {
            // Handles an issue in very early versions of the plugin, it was possible for
            // the value to be saved as a string of an array, ex. `"["8"]"` 
            // This is covered by the FieldPayeeTest
            if (str_contains($value, '"[') && $value[0] === '"' && $value[strlen($value) - 1] === '"') {
                $value = trim($value, '"');
                $value = rtrim($value, '"');
            }

            if (str_contains($value, '[') && $value[0] === '[' && $value[strlen($value) - 1] === ']') {
                $value = json_decode($value);
                if (is_array($value)) {
                    return $value[0];
                }
            }
        }

        if (is_integer($value)) {
            return strval($value);
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function serializeValue($value, ElementInterface $element = null)
    {

        if (is_array($value)) {
            $value = $value[0];
        }

        if (is_string($value)) {
            if (
                (str_contains($value, '[') && $value[0] === '[' && $value[strlen($value) - 1] === ']')
            ) {
                $arr = json_decode($value);
                return $arr[0];
            }
            
            return $value;
        }

        if (is_integer($value)) {
            return $value;
        }

        return null;
    }

    /**
     * Returns the field’s input HTML.
     *
     * An extremely simple implementation would be to directly return some HTML:
     *
     * ```php
     * return '<textarea name="'.$name.'">'.$value.'</textarea>';
     * ```
     *
     * For more complex inputs, you might prefer to create a template, and render it via
     * [[\craft\web\View::renderTemplate()]]. For example, the following code would render a template located at
     * craft/plugins/myplugin/templates/_fieldinput.html, passing the $name and $value variables to it:
     *
     * ```php
     * return Craft::$app->getView()->renderTemplate('myplugin/_fieldinput', [
     *     'name'  => $name,
     *     'value' => $value
     * ]);
     * ```
     *
     * If you need to tie any JavaScript code to your input, it’s important to know that any `name=` and `id=`
     * attributes within the returned HTML will probably get [[\craft\web\View::namespaceInputs() namespaced]],
     * however your JavaScript code will be left untouched.
     *
     * For example, if getInputHtml() returns the following HTML:
     *
     * ```html
     * <textarea id="foo" name="foo"></textarea>
     *
     * <script type="text/javascript">
     *     var textarea = document.getElementById('foo');
     * </script>
     * ```
     *
     * …then it might actually look like this before getting output to the browser:
     *
     * ```html
     * <textarea id="namespace-foo" name="namespace[foo]"></textarea>
     *
     * <script type="text/javascript">
     *     var textarea = document.getElementById('foo');
     * </script>
     * ```
     *
     * As you can see, that JavaScript code will not be able to find the textarea, because the textarea’s `id=`
     * attribute was changed from `foo` to `namespace-foo`.
     *
     * Before you start adding `namespace-` to the beginning of your element ID selectors, keep in mind that the actual
     * namespace is going to change depending on the context. Often they are randomly generated. So it’s not quite
     * that simple.
     *
     * Thankfully, [[\craft\web\View]] provides a couple handy methods that can help you deal with this:
     *
     * - [[\craft\web\View::namespaceInputId()]] will give you the namespaced version of a given ID.
     * - [[\craft\web\View::namespaceInputName()]] will give you the namespaced version of a given input name.
     * - [[\craft\web\View::formatInputId()]] will format an input name to look more like an ID attribute value.
     *
     * So here’s what a getInputHtml() method that includes field-targeting JavaScript code might look like:
     *
     * ```php
     * public function getInputHtml($value, $element)
     * {
     *     // Come up with an ID value based on $name
     *     $id = Craft::$app->getView()->formatInputId($name);
     *
     *     // Figure out what that ID is going to be namespaced into
     *     $namespacedId = Craft::$app->getView()->namespaceInputId($id);
     *
     *     // Render and return the input template
     *     return Craft::$app->getView()->renderTemplate('myplugin/_fieldinput', [
     *         'name'         => $name,
     *         'id'           => $id,
     *         'namespacedId' => $namespacedId,
     *         'value'        => $value
     *     ]);
     * }
     * ```
     *
     * And the _fieldinput.html template might look like this:
     *
     * ```twig
     * <textarea id="{{ id }}" name="{{ name }}">{{ value }}</textarea>
     *
     * <script type="text/javascript">
     *     var textarea = document.getElementById('{{ namespacedId }}');
     * </script>
     * ```
     *
     * The same principles also apply if you’re including your JavaScript code with
     * [[\craft\web\View::registerJs()]].
     *
     * @param mixed                 $value           The field’s value. This will either be the [[normalizeValue() normalized value]],
     *                                               raw POST data (i.e. if there was a validation error), or null
     * @param ElementInterface|null $element         The element the field is associated with, if there is one
     *
     * @return string The input HTML.
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        // Get our id and namespace
        $id = Craft::$app->getView()->formatInputId($this->handle);
        $namespacedId = Craft::$app->getView()->namespaceInputId($id);

        $payee = null;
        $payeeHandle = Marketplace::$plugin->handlesService->getPayeeHandle();

        if ($payeeHandle && isset($element[$payeeHandle]) && $element[$payeeHandle]) {
            $payeeId = $element[$payeeHandle];
            $payee = User::find()->id($payeeId)->one();
        }

        // Render the input template
        return Craft::$app->getView()->renderTemplate(
            'marketplace/_components/fields/MarketplacePayee_input',
            [
                'name' => $this->handle,
                'value' => $value,
                'field' => $this,
                'id' => $id,
                'namespacedId' => $namespacedId,

                'userElementType' => User::class,

                'payee' => $payee,

                // TODO Filter out users without a Stripe account ID set
                'userOptionCriteria' => [
                  'can' => 'accessCp',
                ],
            ]
        );
    }
}
