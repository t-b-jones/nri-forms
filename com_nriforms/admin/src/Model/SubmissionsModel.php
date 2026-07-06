<?php

/**
 * @package     NRI.Component
 * @subpackage  com_nriforms
 */

namespace NRI\Component\Nriforms\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

class SubmissionsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'group_id', 'a.group_id',
                'group_title', 'a.group_title',
                'mail_sent', 'a.mail_sent',
                'created', 'a.created',
            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.created', $direction = 'DESC')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
        $this->setState('filter.search', $search);

        $groupId = $this->getUserStateFromRequest($this->context . '.filter.group_id', 'filter_group_id', '', 'string');
        $this->setState('filter.group_id', $groupId);

        parent::populateState($ordering, $direction);
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.group_id');

        return parent::getStoreId($id);
    }

    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('a') . '.*')
            ->from($db->quoteName('#__nriforms_submissions', 'a'));

        // Filter by form (field group)
        $groupId = $this->getState('filter.group_id');

        if (is_numeric($groupId)) {
            $query->where($db->quoteName('a.group_id') . ' = :groupId')
                ->bind(':groupId', $groupId, \Joomla\Database\ParameterType::INTEGER);
        }

        // Search in submitted data
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            $search = '%' . str_replace(' ', '%', trim($search)) . '%';
            $query->where($db->quoteName('a.data') . ' LIKE :search')
                ->bind(':search', $search);
        }

        $orderCol  = $this->state->get('list.ordering', 'a.created');
        $orderDirn = $this->state->get('list.direction', 'DESC');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}
