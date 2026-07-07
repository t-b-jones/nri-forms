<?php

/**
 * @package     NRI.Component
 * @subpackage  com_nriforms
 *
 * Implements FieldsServiceInterface so com_fields serves this component:
 * Field Groups in the "com_nriforms.form" context are our Forms, and
 * Fields assigned to those groups are our inputs.
 */

namespace NRI\Component\Nriforms\Administrator\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Factory;
use Joomla\CMS\Fields\FieldsServiceInterface;
use Joomla\CMS\Language\Text;

class NriformsComponent extends MVCComponent implements FieldsServiceInterface, RouterServiceInterface
{
    use RouterServiceTrait;

    /**
     * Returns valid contexts.
     *
     * Note: this is called during com_fields' own dispatch, when this
     * component's language files are not yet loaded - so load them
     * explicitly (same pattern as core com_contact/com_content).
     *
     * @return  string[]
     */
    public function getContexts(): array
    {
        $language = Factory::getApplication()->getLanguage();
        $basePath = JPATH_ADMINISTRATOR . '/components/com_nriforms';

        $language->load('com_nriforms', JPATH_ADMINISTRATOR)
            || $language->load('com_nriforms', $basePath);
        $language->load('com_nriforms.sys', JPATH_ADMINISTRATOR)
            || $language->load('com_nriforms.sys', $basePath);

        return [
            'com_nriforms.form' => Text::_('COM_NRIFORMS_CONTEXT_FORM'),
        ];
    }

    /**
     * Returns a valid section for the given section. If it is not valid then null is returned.
     *
     * @param   string  $section  The section to get the mapping for
     * @param   object  $item     Optional associated item
     *
     * @return  string|null
     */
    public function validateSection($section, $item = null)
    {
        if ($section === 'form') {
            return 'form';
        }

        return null;
    }
}
