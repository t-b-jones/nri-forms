<?php

/**
 * @package     NRI.Component
 * @subpackage  com_nriforms
 */

namespace NRI\Component\Nriforms\Site\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\DatabaseInterface;

/**
 * Lists com_fields field groups in the com_nriforms.form context.
 * A field group IS a form in this component.
 */
class FormgroupField extends ListField
{
    protected $type = 'Formgroup';

    protected function getOptions()
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'title', 'state']))
            ->from($db->quoteName('#__fields_groups'))
            ->where($db->quoteName('context') . ' = ' . $db->quote('com_nriforms.form'))
            ->where($db->quoteName('state') . ' >= 0')
            ->order($db->quoteName('title') . ' ASC');

        $groups  = $db->setQuery($query)->loadObjectList();
        $options = [];

        foreach ($groups as $group) {
            $text = $group->title . ((int) $group->state === 0 ? ' [' . \Joomla\CMS\Language\Text::_('JUNPUBLISHED') . ']' : '');
            $options[] = HTMLHelper::_('select.option', $group->id, $text);
        }

        return array_merge(parent::getOptions(), $options);
    }
}
