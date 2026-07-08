<?php

namespace NRI\Plugin\Task\Nriforms\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\SubscriberInterface;

final class Nriforms extends CMSPlugin implements SubscriberInterface
{
    use TaskPluginTrait;

    protected const TASKS_MAP = [
        'nriforms.purge' => [
            'langConstPrefix' => 'PLG_TASK_NRIFORMS_PURGE',
            'method'          => 'purgeExpired',
        ],
    ];

    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return [
            'onTaskOptionsList' => 'advertiseRoutines',
            'onExecuteTask'     => 'standardRoutineHandler',
        ];
    }

    private function purgeExpired(ExecuteTaskEvent $event): int
    {

        try {
            $db  = Factory::getContainer()->get(DatabaseInterface::class);
            $now = Factory::getDate()->toSql();

            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__nriforms_submissions'))
                ->where($db->quoteName('expires') . ' IS NOT NULL')
                ->where($db->quoteName('expires') . ' < :now')
                ->bind(':now', $now);

            $db->setQuery($query)->execute();

            $this->logTask(sprintf('Purged %d expired form submissions.', $db->getAffectedRows()));

            return Status::OK;
        } catch (\Throwable $e) {
            $this->logTask(sprintf('Error purging expired form submissions: %s', $e->getMessage()));
            return Status::NO_RUN;
        }
    }
}
