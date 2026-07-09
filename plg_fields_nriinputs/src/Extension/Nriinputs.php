<?php

namespace NRI\Plugin\Fields\Nriinputs\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\Component\Fields\Administrator\Plugin\FieldsPlugin;

final class Nriinputs extends FieldsPlugin
{
    protected $autoloadLanguage = true;

    public function onCustomFieldsPrepareDom($field, \DOMElement $parent, Form $form)
    {
        $fieldNode = parent::onCustomFieldsPrepareDom($field, $parent, $form);

        if (!$fieldNode) {
            return $fieldNode;
        }

        if ($field->type === 'section') {
            // Presentation-only heading: core spacer field, posts no value.
            // The form layout detects spacers and opens a new <section>.
            $fieldNode->setAttribute('type', 'spacer');
            $fieldNode->setAttribute('class', 'nriform-section');

            return $fieldNode;
        }

        $fieldNode->setAttribute('type', $field->type);
        $fieldNode->setAttribute('validate', $field->type);

        if ($field->type === 'email') {
            $fieldNode->setAttribute('message', 'PLG_FIELDS_NRIINPUTS_EMAIL_MESSAGE');
        }

        if ($field->type === 'tel') {
            $fieldNode->setAttribute('message', 'PLG_FIELDS_NRIINPUTS_TEL_MESSAGE');

            if (!$fieldNode->getAttribute('pattern')) {
                $fieldNode->setAttribute('pattern', '[0-9+\-()\s]{7,20}');
            }

            $fieldNode->setAttribute('title', Text::_('PLG_FIELDS_NRIINPUTS_TEL_MESSAGE'));
        }

        return $fieldNode;
    }
}
