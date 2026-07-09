<?php

/**
 * @package     NRI.Plugin
 * @subpackage  System.nriforms
 *
 * Listens for com_fields saves in the com_nriforms.form context:
 * - Field group saved  -> create the form's mail template (if missing)
 * - Field saved        -> refresh the template's placeholder tag list
 */

namespace NRI\Plugin\System\Nriforms\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use NRI\Component\Nriforms\Site\Helper\MailHelper;

final class Nriforms extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onContentAfterSave' => 'onContentAfterSave',
        ];
    }

    public function onContentAfterSave(Event $event): void
    {
        // Template housekeeping is a convenience; it must NEVER cause a
        // field or group save to report failure.
        try {
            $this->handleAfterSave($event);
        } catch (\Throwable $e) {
            // Swallow silently: worst case is a stale tag list, fixed on
            // the next successful save or lazily at submission time.
        }
    }

    private function handleAfterSave(Event $event): void
    {
        if (!class_exists(MailHelper::class)) {
            return;
        }

        // Tolerate both concrete (named-argument) and legacy event shapes.
        $args    = $event->getArguments();
        $context = $args['context'] ?? $args[0] ?? '';
        $item    = $args['subject'] ?? $args['item'] ?? $args[1] ?? null;

        if (!$item) {
            return;
        }

        if ($context === 'com_fields.group' && ($item->context ?? '') === 'com_nriforms.form') {
            // A form was created or saved: make sure its template exists
            // (creation only - existing templates are never overwritten).
            MailHelper::ensureTemplate(
                (int) $item->id,
                (string) $item->title,
                $this->fieldNames((int) $item->id)
            );

            return;
        }

        if ($context === 'com_fields.field' && ($item->context ?? '') === 'com_nriforms.form') {
            $groupId = (int) ($item->group_id ?? 0);

            if (!$groupId) {
                return;
            }

            // A field was added/saved: ensure the group's template exists
            // and refresh its placeholder tag list so the new {FIELD_...}
            // tag shows on the template edit screen.
            $group = $this->groupTitle($groupId);

            if ($group !== null) {
                MailHelper::ensureTemplate($groupId, $group, $this->fieldNames($groupId));
                MailHelper::refreshTags($groupId, $this->fieldNames($groupId));
            }
        }
    }

    /**
     * Names of the fields currently in a group.
     *
     * @return string[]
     */
    private function fieldNames(int $groupId): array
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName('name'))
            ->from($db->quoteName('#__fields'))
            ->where($db->quoteName('context') . ' = ' . $db->quote('com_nriforms.form'))
            ->where($db->quoteName('group_id') . ' = :gid')
            ->where($db->quoteName('state') . ' >= 0')
            ->where($db->quoteName('type') . ' != ' . $db->quote('section'))
            ->order($db->quoteName('ordering') . ' ASC')
            ->bind(':gid', $groupId, ParameterType::INTEGER);

        return $db->setQuery($query)->loadColumn() ?: [];
    }

    private function groupTitle(int $groupId): ?string
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName('title'))
            ->from($db->quoteName('#__fields_groups'))
            ->where($db->quoteName('id') . ' = :gid')
            ->bind(':gid', $groupId, ParameterType::INTEGER);

        $title = $db->setQuery($query)->loadResult();

        return $title !== null ? (string) $title : null;
    }
}
