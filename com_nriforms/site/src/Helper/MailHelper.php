<?php

/**
 * @package     NRI.Component
 * @subpackage  com_nriforms
 */

namespace NRI\Component\Nriforms\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Mail\MailTemplate;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

abstract class MailHelper
{
    /**
     * The mail template key for a form (field group).
     */
    public static function templateId(int $groupId): string
    {
        return 'com_nriforms.submission.' . $groupId;
    }

    /**
     * Create the form's mail template if it does not exist yet, seeded
     * with a sensible default. Idempotent - safe to call on every render.
     *
     * @param   int       $groupId      The field group (form) id
     * @param   string    $groupTitle   The form title (used in the seed)
     * @param   string[]  $fieldNames   Names of the form's fields, for the tag list
     * @param   string    $seedSubject  Optional subject seed (menu item Subject param)
     */
    public static function ensureTemplate(int $groupId, string $groupTitle, array $fieldNames, string $seedSubject = ''): void
    {
        static $ensured = [];

        if (isset($ensured[$groupId])) {
            return;
        }

        $ensured[$groupId] = true;

        // Keep the Mail Templates list human-readable: com_mails titles
        // rows via per-key language constants, which we satisfy through
        // language overrides. Runs before the existence check so form
        // renames update the displayed title.
        self::syncLanguageOverrides($groupId, $groupTitle);

        $templateId = self::templateId($groupId);
        $db         = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__mail_templates'))
            ->where($db->quoteName('template_id') . ' = :tid')
            ->bind(':tid', $templateId, ParameterType::STRING);

        if ((int) $db->setQuery($query)->loadResult() > 0) {
            return;
        }

        // Placeholder tags shown as buttons on the template edit screen.
        $tags = array_merge(
            ['sitename', 'siteurl', 'form_title', 'date', 'fields', 'fields_html'],
            array_map(static fn (string $name): string => 'field_' . $name, $fieldNames)
        );

        $subject = $seedSubject !== ''
            ? $seedSubject
            : 'New enquiry: ' . $groupTitle . ' — {SITENAME}';

        MailTemplate::createTemplate(
            $templateId,
            $subject,
            "New submission from the '{FORM_TITLE}' form on {SITEURL} ({DATE})\r\n\r\n{FIELDS}",
            $tags,
            '<h2>New submission: {FORM_TITLE}</h2>'
            . '<p>Sent from {SITEURL} on {DATE}.</p>'
            . '{FIELDS_HTML}'
        );
    }

    /**
     * Write language override strings for the template's Title and
     * Description as shown in System > Mail Templates, using the same
     * core helpers as com_languages' override manager. Idempotent, and
     * re-run on every group save so renamed forms update their title.
     */
    public static function syncLanguageOverrides(int $groupId, string $groupTitle): void
    {
        // com_mails derives its constants from the template id:
        // com_nriforms.submission.2 -> COM_NRIFORMS_MAIL_SUBMISSION_2_*
        $parts    = explode('.', self::templateId($groupId), 2);
        $base     = strtoupper($parts[0] . '_MAIL_' . str_replace('.', '_', $parts[1] ?? ''));
        $titleKey = $base . '_TITLE';
        $descKey  = $base . '_DESC';

        foreach (LanguageHelper::getInstalledLanguages(1) as $language) {
            $tag  = $language->element;
            $path = JPATH_ADMINISTRATOR . '/language/overrides/' . $tag . '.override.ini';

            try {
                $strings = is_file($path) ? LanguageHelper::parseIniFile($path) : [];

                $newTitle = 'Form: ' . $groupTitle;
                $newDesc  = 'Notification email sent when this form is submitted. Edit the subject and body here; placeholder tags insert the submitted values.';

                if (($strings[$titleKey] ?? null) === $newTitle && ($strings[$descKey] ?? null) === $newDesc) {
                    continue;
                }

                $strings[$titleKey] = $newTitle;
                $strings[$descKey]  = $newDesc;

                LanguageHelper::saveToIniFile($path, $strings);
            } catch (\Throwable $e) {
                // Cosmetic feature: never let it break template creation.
                continue;
            }
        }
    }

    /**
     * Update an existing template's placeholder tag list to match the
     * form's current fields. Subject and bodies are NEVER touched - only
     * the params.tags array that drives the tag buttons on the template
     * edit screen.
     */
    public static function refreshTags(int $groupId, array $fieldNames): void
    {
        $templateId = self::templateId($groupId);
        $db         = Factory::getContainer()->get(DatabaseInterface::class);

        $tags = array_merge(
            ['sitename', 'siteurl', 'form_title', 'date', 'fields', 'fields_html'],
            array_map(static fn (string $name): string => 'field_' . $name, $fieldNames)
        );

        $query = $db->getQuery(true)
            ->select($db->quoteName(['template_id', 'language', 'params']))
            ->from($db->quoteName('#__mail_templates'))
            ->where($db->quoteName('template_id') . ' = :tid')
            ->bind(':tid', $templateId, ParameterType::STRING);

        $rows = $db->setQuery($query)->loadObjectList();

        foreach ($rows as $row) {
            $params         = json_decode($row->params ?: '{}', true) ?: [];
            $params['tags'] = $tags;

            // Composite primary key: (template_id, language). There is no id column.
            $update = $db->getQuery(true)
                ->update($db->quoteName('#__mail_templates'))
                ->set($db->quoteName('params') . ' = :params')
                ->where($db->quoteName('template_id') . ' = :utid')
                ->where($db->quoteName('language') . ' = :ulang')
                ->bind(':params', json_encode($params))
                ->bind(':utid', $row->template_id, ParameterType::STRING)
                ->bind(':ulang', $row->language, ParameterType::STRING);

            $db->setQuery($update)->execute();
        }
    }
}
