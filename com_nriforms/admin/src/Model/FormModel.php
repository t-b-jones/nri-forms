<?php
namespace NRI\Component\Nriforms\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Database\ParameterType;

class FormModel extends AdminModel
{
    protected $text_prefix = 'COM_NRIFORMS';

    public function getTable($type = 'Form', $prefix = 'Administrator', $config = [])
    {
        return parent::getTable($type, $prefix, $config);
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_nriforms.settings', 'settings', ['control' => 'jform', 'load_data' => $loadData]);

        return $form ?: false;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_nriforms.edit.form.data', []);

        return $data ?: $this->getItemByGroup((int) Factory::getApplication()->getInput()->getInt('group_id'));
    }

    /** Settings row for a group, or a stub if none exists yet. */
    public function getItemByGroup(int $groupId): object
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__nriforms_forms'))
            ->where($db->quoteName('group_id') . ' = :gid')
            ->bind(':gid', $groupId, ParameterType::INTEGER);

        $item = $db->setQuery($query)->loadObject();

        if (!$item) {
            $item = (object) ['id' => 0, 'group_id' => $groupId, 'replyto_field' => 'email', 'save_submissions' => 1];
        }

        return $item;
    }

    public function getGroupTitle(int $groupId): string
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('title'))
            ->from($db->quoteName('#__fields_groups'))
            ->where($db->quoteName('id') . ' = :gid')
            ->bind(':gid', $groupId, ParameterType::INTEGER);

        return (string) $db->setQuery($query)->loadResult();
    }
}
