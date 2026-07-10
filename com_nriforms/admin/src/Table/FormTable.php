<?php
namespace NRI\Component\Nriforms\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;

class FormTable extends Table
{
    public function __construct(DatabaseDriver $db, ?DispatcherInterface $dispatcher = null)
    {
        parent::__construct('#__nriforms_forms', 'id', $db, $dispatcher);
    }
}
