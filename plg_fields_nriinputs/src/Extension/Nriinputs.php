<?php

/**
 * @package     NRI.Plugin
 * @subpackage  Fields.nriinputs
 *
 * Provides "email" and "tel" custom field types by delegating to
 * Joomla's core EmailField/TelField and EmailRule/TelRule. No
 * validation logic lives here - core owns and maintains it.
 */

namespace NRI\Plugin\Fields\Nriinputs\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\Component\Fields\Administrator\Plugin\FieldsPlugin;

final class Nriinputs extends FieldsPlugin
{
    /**
     * Load the plugin language automatically, so the custom validation
     * messages translate at Form::validate() time on the site.
     *
     * @var boolean
     */
    protected $autoloadLanguage = true;

    /**
     * Transforms the field into a DOM XML element and appends it as a child on the given parent.
     *
     * @param   \stdClass    $field   The field.
     * @param   \DOMElement  $parent  The field node parent.
     * @param   Form         $form    The form.
     *
     * @return  \DOMElement|null
     */
    public function onCustomFieldsPrepareDom($field, \DOMElement $parent, Form $form)
    {
        $fieldNode = parent::onCustomFieldsPrepareDom($field, $parent, $form);

        if (!$fieldNode) {
            return $fieldNode;
        }

        // $field->type is 'email' or 'tel' - both are core JForm field
        // types and both have a matching core validation rule.
        $fieldNode->setAttribute('type', $field->type);
        $fieldNode->setAttribute('validate', $field->type);

        if ($field->type === 'email') {
            // Friendly server-side error instead of the generic
            // "Invalid field: <label>" (JForm's native mechanism).
            $fieldNode->setAttribute('message', 'PLG_FIELDS_NRIINPUTS_EMAIL_MESSAGE');
        }

        if ($field->type === 'tel') {
            $fieldNode->setAttribute('message', 'PLG_FIELDS_NRIINPUTS_TEL_MESSAGE');

            // Browsers do not natively validate type="tel" (unlike email),
            // so give them a pattern for client-side feedback before submit.
            // Deliberately permissive: digits, spaces, + - ( ), 7-20 chars.
            if (!$fieldNode->getAttribute('pattern')) {
                $fieldNode->setAttribute('pattern', '[0-9+\-()\s]{7,20}');
            }

            // Browsers show this text in the pattern-mismatch bubble,
            // giving tel the same friendly pre-submit feedback email
            // gets natively.
            $fieldNode->setAttribute('title', Text::_('PLG_FIELDS_NRIINPUTS_TEL_MESSAGE'));
        }

        return $fieldNode;
    }
}
