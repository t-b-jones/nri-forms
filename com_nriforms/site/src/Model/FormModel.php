<?php

/**
 * @package     NRI.Component
 * @subpackage  com_nriforms
 */

namespace NRI\Component\Nriforms\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

class FormModel extends BaseDatabaseModel
{
    /**
     * Get the field group (form) record.
     */
    public function getGroup(int $groupId): ?object
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'title', 'state', 'description']))
            ->from($db->quoteName('#__fields_groups'))
            ->where($db->quoteName('context') . ' = ' . $db->quote('com_nriforms.form'))
            ->where($db->quoteName('id') . ' = :id')
            ->where($db->quoteName('state') . ' = 1')
            ->bind(':id', $groupId, ParameterType::INTEGER);

        return $db->setQuery($query)->loadObject();
    }

    /**
     * Get the published com_fields fields belonging to a group, in order.
     *
     * @return \stdClass[]
     */
    public function getGroupFields(int $groupId): array
    {
        $fields = FieldsHelper::getFields('com_nriforms.form');

        return array_values(
            array_filter(
                $fields,
                static fn ($field) => (int) $field->group_id === $groupId
            )
        );
    }

    /**
     * Build a JForm object from the group's fields. com_fields does the
     * DOM construction via each field type plugin, so showon, required,
     * validation etc. all come through exactly as configured in the backend.
     *
     * prepareForm() takes the CONTEXT string and loads every field in it,
     * organised into fieldsets named "fields-{groupId}" - so we strip the
     * fieldsets belonging to other groups (other forms) afterwards.
     */
    public function getFormObject(int $groupId): Form
    {
        $form = new Form('com_nriforms.form.' . $groupId, ['control' => 'jform']);
        $form->load('<form />');

        $item = (object) ['id' => 0, 'language' => '*'];

        FieldsHelper::prepareForm('com_nriforms.form', $form, $item);

        foreach ($form->getFieldsets() as $fieldset) {
            if (strpos($fieldset->name, 'fields-') !== 0) {
                continue;
            }

            if ($fieldset->name === 'fields-' . $groupId) {
                // Our form's fields: clear the disabled flag prepareForm
                // applies when the current user (a site guest) lacks the
                // edit-value permission. This is a public-facing form.
                foreach ($form->getFieldset($fieldset->name) as $formField) {
                    $form->setFieldAttribute($formField->fieldname, 'disabled', 'false', 'com_fields');
                }

                continue;
            }

            // Another group's fields: remove them entirely so they are
            // neither rendered nor validated.
            foreach ($form->getFieldset($fieldset->name) as $formField) {
                $form->removeField($formField->fieldname, 'com_fields');
            }
        }

        return $form;
    }

    /**
     * Evaluate each field's showon condition against submitted data and
     * relax "required" for any field that is currently hidden, so the
     * server never demands a value the user could not see.
     *
     * Supports the core syntax: field:value[,value2][AND|OR]field2:value
     * with the same semantics as core showon (equality, ! for negation).
     */
    public function relaxConditionalFields(Form $form, array $data): void
    {
        $values = $data['com_fields'] ?? [];

        foreach ($form->getGroup('com_fields') as $formField) {
            $showOn = $formField->showon;

            if (!$showOn) {
                continue;
            }

            if (!$this->isShown($showOn, $values)) {
                $name = $formField->fieldname;
                $form->setFieldAttribute($name, 'required', 'false', 'com_fields');
                $form->setFieldAttribute($name, 'validate', '', 'com_fields');
            }
        }
    }

    /**
     * Evaluate a showon expression against submitted values.
     */
    private function isShown(string $showOn, array $values): bool
    {
        $conditions = FormHelper::parseShowOnConditions($showOn);
        $result     = null;

        foreach ($conditions as $condition) {
            // Core gives us the full form control path; reduce to the field name.
            $parts   = preg_split('/[\[\]]+/', $condition['field'], -1, PREG_SPLIT_NO_EMPTY);
            $name    = end($parts);
            $current = $values[$name] ?? '';
            $current = \is_array($current) ? $current : [$current];

            $match = \count(array_intersect($current, $condition['values'])) > 0;

            if (!empty($condition['sign']) && $condition['sign'] === '!=') {
                $match = !$match;
            }

            if ($result === null) {
                $result = $match;
            } elseif (\in_array('OR', $condition['op'] ?? [], true)) {
                $result = $result || $match;
            } else {
                $result = $result && $match;
            }
        }

        return $result ?? true;
    }
}
