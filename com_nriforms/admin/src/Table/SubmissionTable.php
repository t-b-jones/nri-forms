<?php

/**
 * @package     NRI.Component
 * @subpackage  com_nriforms
 */

namespace NRI\Component\Nriforms\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;

class SubmissionTable extends Table
{
    public function __construct(DatabaseDriver $db, ?DispatcherInterface $dispatcher = null)
    {
        parent::__construct('#__nriforms_submissions', 'id', $db, $dispatcher);
    }

    public function store($updateNulls = true)
    {
        if (empty($this->id) && empty($this->created)) {
            $this->created = Factory::getDate()->toSql();
        }

        return parent::store($updateNulls);
    }
}
