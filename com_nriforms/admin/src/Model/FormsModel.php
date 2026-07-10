<?php
namespace NRI\Component\Nriforms\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

class FormsModel extends ListModel
{
    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['g.id', 'g.title', 'g.state']))
            ->select($db->quoteName(['f.id'], ['settings_id']))
            ->select($db->quoteName(['f.recipient', 'f.retention_days', 'f.captcha', 'f.consent_enabled', 'f.terms_enabled', 'f.save_submissions']))
            ->from($db->quoteName('#__fields_groups', 'g'))
            ->join('LEFT', $db->quoteName('#__nriforms_forms', 'f') . ' ON ' . $db->quoteName('f.group_id') . ' = ' . $db->quoteName('g.id'))
            ->where($db->quoteName('g.context') . ' = ' . $db->quote('com_nriforms.form'))
            ->where($db->quoteName('g.state') . ' >= 0')
            ->order($db->quoteName('g.title') . ' ASC');

        return $query;
    }
}
