<?php

/**
 * @package     NRI.Component
 * @subpackage  com_nriforms
 */

namespace NRI\Component\Nriforms\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use NRI\Component\Nriforms\Administrator\Helper\CryptoHelper;

class SubmissionModel extends AdminModel
{
    protected $text_prefix = 'COM_NRIFORMS';

    public function getTable($type = 'Submission', $prefix = 'Administrator', $config = [])
    {
        return parent::getTable($type, $prefix, $config);
    }

    /**
     * Submissions are read-only; a minimal form exists only to satisfy AdminModel.
     */
    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_nriforms.submission', 'submission', ['control' => 'jform', 'load_data' => false]);

        return $form ?: false;
    }

    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        if ($item && !empty($item->data)) {
            $item->data = CryptoHelper::decrypt((string) $item->data);
            $registry   = json_decode($item->data, true);
            $item->fields = \is_array($registry) ? $registry : [];
        }

        return $item;
    }
}
