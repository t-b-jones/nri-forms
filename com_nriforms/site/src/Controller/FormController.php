<?php

/**
 * @package     NRI.Component
 * @subpackage  com_nriforms
 */

namespace NRI\Component\Nriforms\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Mail\MailTemplate;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\String\PunycodeHelper;
use Joomla\CMS\Uri\Uri;
use NRI\Component\Nriforms\Administrator\Helper\CryptoHelper;
use NRI\Component\Nriforms\Site\Helper\MailHelper;

class FormController extends BaseController
{
    /**
     * Handle a form submission.
     */
    public function submit(): void
    {
        $this->checkToken();

        $app    = $this->app;
        $input  = $app->getInput();
        $itemid = $input->getInt('Itemid');

        $menu = $app->getMenu()->getItem($itemid);

        if (!$menu || $menu->component !== 'com_nriforms') {
            throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'), 404);
        }

        $returnUrl = Route::_('index.php?Itemid=' . $itemid, false);
        $groupId   = (int) ($menu->query['group_id'] ?? 0) ?: (int) $menu->getParams()->get('group_id', 0);

        // Honeypot: bots fill it, humans never see it. Pretend success.
        if ($input->post->getString('nri_hp', '') !== '') {
            $app->enqueueMessage($this->successMessage(), 'message');
            $this->setRedirect($returnUrl);

            return;
        }

        /** @var \NRI\Component\Nriforms\Site\Model\FormModel $model */
        $model = $this->getModel('Form', 'Site', ['ignore_request' => true]);
        $group = $model->getGroup($groupId);

        if (!$group) {
            throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'), 404);
        }

        $settings = $model->getSettings($groupId);

        $form = $model->getFormObject($groupId);

        // Captcha: mirror the view's setup so validate() runs the rule.
        $captcha = $settings->captcha ?? '';
        $captcha = $captcha !== '' ? $captcha : (string) $app->get('captcha', '0');

        if ($captcha && $captcha !== '0' && \Joomla\CMS\Plugin\PluginHelper::isEnabled('captcha', $captcha)) {
            $form->load(
                '<form><fieldset name="captcha"><field name="captcha" type="captcha" label="COM_NRIFORMS_CAPTCHA_LABEL" validate="captcha" plugin="' . htmlspecialchars($captcha, ENT_QUOTES) . '" /></fieldset></form>'
            );
        }

        $data = $input->post->get('jform', [], 'array');

        // Consent / terms: required checkboxes when enabled.
        foreach ([['consent', 'nri_consent'], ['terms', 'nri_terms']] as [$key, $field]) {
            if ((int) ($settings->{$key . '_enabled'} ?? 0) === 1 && $input->post->getInt($field, 0) !== 1) {
                $app->enqueueMessage(Text::sprintf('COM_NRIFORMS_ERR_CONSENT_REQUIRED', $settings->{$key . '_label'} ?: Text::_('COM_NRIFORMS_' . strtoupper($key) . '_DEFAULT_LABEL')), 'warning');
                $app->setUserState('com_nriforms.form.' . $groupId . '.data', $data);
                $this->setRedirect($returnUrl);

                return;
            }
        }

        // Server-side conditionality: hidden fields cannot be required.
        $model->relaxConditionalFields($form, $data);

        $data   = $form->filter($data);
        $result = $form->validate($data);

        if ($result === false) {
            foreach ($form->getErrors() as $error) {
                $app->enqueueMessage(
                    $error instanceof \Exception ? $error->getMessage() : (string) $error,
                    'warning'
                );
            }

            // Preserve what the user typed for re-display.
            $app->setUserState('com_nriforms.form.' . $groupId . '.data', $data);
            $this->setRedirect($returnUrl);

            return;
        }

        $app->setUserState('com_nriforms.form.' . $groupId . '.data', null);

        // Pair values with labels, in field order.
        $labelled    = [];
        $groupFields = $model->getGroupFields($groupId);

        foreach ([['consent', 'nri_consent'], ['terms', 'nri_terms']] as [$key, $field]) {
            if ((int) ($settings->{$key . '_enabled'} ?? 0) === 1) {
                $labelled[] = [
                    'name'  => $field,
                    'label' => $settings->{$key . '_label'} ?: Text::_('COM_NRIFORMS_' . strtoupper($key) . '_DEFAULT_LABEL'),
                    'value' => Text::_('JYES'),
                ];
            }
        }

        foreach ($groupFields as $field) {
            $value = $data['com_fields'][$field->name] ?? null;

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $labelled[] = [
                'name'  => $field->name,
                'label' => $field->label,
                'value' => $value,
            ];
        }

        $params = ComponentHelper::getParams('com_nriforms');

        // Save the submission first: it is the safety net if mail fails.
        $submissionId = 0;

        $saveEnabled = $settings
            ? (int) $settings->save_submissions === 1
            : (int) $params->get('save_submissions', 1) === 1;

        if ($saveEnabled) {
            $table = $app->bootComponent('com_nriforms')
                ->getMVCFactory()
                ->createTable('Submission', 'Administrator');

            $retention = $settings ? (int) $settings->retention_days : 0;
            $dataJson  = json_encode($labelled);

            $encrypt = $settings ? (int) $settings->encrypt === 1 : (int) $params->get('encrypt_submissions', 0) === 1;

            if ($encrypt) {
                $dataJson = CryptoHelper::encrypt($dataJson);
            }

            $table->save(
                [
                    'group_id'    => $groupId,
                    'group_title' => $group->title,
                    'data'        => $dataJson,
                    'mail_sent'   => 0,
                    'state'       => 1,
                    'expires'     => $retention > 0 ? Factory::getDate('+' . $retention . ' days')->toSql() : null,
                ]
            );
            $submissionId = (int) $table->id;
        }

        $mailError = $this->sendMail($menu, $group, $labelled, $params, $groupFields, $settings);
        $mailOk    = ($mailError === null);

        if (!$mailOk) {
            Log::addLogger(['text_file' => 'com_nriforms.mail.php'], Log::ALL, ['com_nriforms.mail']);
            Log::add(
                'Form "' . $group->title . '" (menu #' . $itemid . '): ' . $mailError,
                Log::ERROR,
                'com_nriforms.mail'
            );

            $user = $app->getIdentity();

            if (JDEBUG || ($user && $user->authorise('core.admin'))) {
                $app->enqueueMessage(Text::sprintf('COM_NRIFORMS_MAIL_ERROR_ADMIN', $mailError), 'warning');
            }
        }

        if ($mailOk && $submissionId) {
            $table->mail_sent = 1;
            $table->store();
        }

        if (!$mailOk && !$submissionId) {
            // Nothing was captured anywhere: be honest with the user.
            $app->enqueueMessage(Text::_('COM_NRIFORMS_SUBMIT_FAILED'), 'error');
            $this->setRedirect($returnUrl);

            return;
        }

        $app->enqueueMessage($this->successMessage($settings), 'message');

        $redirect   = trim((string) ($settings->redirect_url ?? ''));
        $isRelative = str_starts_with($redirect, '/') && !str_starts_with($redirect, '//');
        $ok         = $redirect !== '' && ($isRelative || \Joomla\CMS\Uri\Uri::isInternal($redirect));

        $this->setRedirect($ok ? $redirect : $returnUrl);
    }

    private function successMessage(?object $settings = null): string
    {
        $message = trim((string) ($settings->success_message ?? ''));

        return $message !== '' ? $message : Text::_('COM_NRIFORMS_SUBMIT_SUCCESS');
    }

    /**
     * Send the notification email via the form's own mail template
     * (com_nriforms.submission.{groupId}), editable in System > Mail
     * Templates. Falls back to a code-built plain-text mail if the
     * template path fails.
     *
     * @return string|null  Null on success, otherwise the failure reason.
     */
    private function sendMail($menu, object $group, array $labelled, $params, array $groupFields, ?object $settings = null): ?string
    {
        $recipients = $this->resolveRecipients($params, $settings);

        if ($recipients === []) {
            return Text::_('COM_NRIFORMS_MAIL_ERR_NO_RECIPIENT');
        }

        try {
            $app = $this->app;

            MailHelper::ensureTemplate(
                (int) $group->id,
                $group->title,
                array_map(
                    static fn ($f) => (string) $f->name,
                    array_filter($groupFields, static fn ($f) => $f->type !== 'section')
                )
            );

            $mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
            $this->applyReplyTo($mailer, $labelled, $settings);

            $template = new MailTemplate(
                MailHelper::templateId((int) $group->id),
                $app->getLanguage()->getTag(),
                $mailer
            );

            foreach ($recipients as $recipient) {
                $template->addRecipient($recipient);
            }

            $template->addTemplateData($this->buildTemplateData($group, $labelled, $groupFields));

            return $template->send() ? null : Text::_('COM_NRIFORMS_MAIL_ERR_SEND_FAILED');
        } catch (\Throwable $e) {
            // Template path failed: fall back to the plain built-in mail.
            $fallback = $this->sendPlainMail($group, $labelled, $recipients, $settings);

            if ($fallback === null) {
                return null;
            }

            return $e->getMessage() . ' / ' . $fallback;
        }
    }

    /**
     * All placeholder values for the mail template: globals, the two
     * ready-made field blocks, and one {FIELD_<NAME>} tag per field so
     * templates can place, omit and reorder fields freely.
     */
    private function buildTemplateData(object $group, array $labelled, array $groupFields): array
    {
        $plain = [];
        $html  = [];

        foreach ($labelled as $entry) {
            $value = \is_array($entry['value']) ? implode(', ', $entry['value']) : (string) $entry['value'];

            $plain[] = $entry['label'] . ': ' . $value;
            $html[]  = '<tr><th align="left" style="padding:4px 12px 4px 0;vertical-align:top;">'
                . htmlspecialchars($entry['label'], ENT_QUOTES, 'UTF-8')
                . '</th><td style="padding:4px 0;">'
                . nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'))
                . '</td></tr>';
        }

        $data = [
            'sitename'    => $this->app->get('sitename'),
            'siteurl'     => Uri::root(),
            'form_title'  => $group->title,
            'date'        => Factory::getDate()->format(Text::_('DATE_FORMAT_LC2')),
            'fields'      => implode("\r\n", $plain),
            'fields_html' => '<table cellspacing="0" cellpadding="0" border="0">' . implode('', $html) . '</table>',
        ];

        // Every field gets a tag; absent/empty ones resolve to '' so no
        // literal {FIELD_X} placeholders leak into sent mail.
        foreach ($groupFields as $field) {
            $data['field_' . $field->name] = '';
        }

        foreach ($labelled as $entry) {
            $data['field_' . $entry['name']] = \is_array($entry['value'])
                ? implode(', ', $entry['value'])
                : (string) $entry['value'];
        }

        return $data;
    }

    /**
     * Resolve recipient addresses: menu item first, component default second.
     *
     * @return string[]
     */
    private function resolveRecipients($params, ?object $settings = null): array
    {
        $recipients = trim((string) ($settings->recipient ?? ''));

        if ($recipients === '') {
            $recipients = trim((string) $params->get('default_recipient', ''));
        }

        if ($recipients === '') {
            return [];
        }

        $list = [];

        foreach (array_map('trim', explode(',', $recipients)) as $recipient) {
            if ($recipient !== '') {
                $list[] = PunycodeHelper::emailToPunycode($recipient);
            }
        }

        return $list;
    }

    /**
     * Reply-To: the value of the configured field, if present and sane.
     */
    private function applyReplyTo($mailer, array $labelled, ?object $settings = null): void
    {
        $replyToField = trim((string) ($settings->replyto_field ?? '')) ?: 'email';

        foreach ($labelled as $entry) {
            if ($entry['name'] === $replyToField && \is_string($entry['value']) && filter_var($entry['value'], FILTER_VALIDATE_EMAIL)) {
                // The submitter is the sole reply target: drop the global
                // Reply-To the mailer factory pre-applied.
                $mailer->clearReplyTos();
                $mailer->addReplyTo(PunycodeHelper::emailToPunycode($entry['value']));
                break;
            }
        }
    }

    /**
     * Fallback plain-text mail, built in code - used only when the mail
     * template path fails.
     *
     * @return string|null  Null on success, otherwise the failure reason.
     */
    private function sendPlainMail(object $group, array $labelled, array $recipients, ?object $settings = null): ?string
    {
        $subject = Text::sprintf('COM_NRIFORMS_MAIL_SUBJECT', $group->title, Uri::getInstance()->toString(['host']));

        $lines = [];

        foreach ($labelled as $entry) {
            $value   = \is_array($entry['value']) ? implode(', ', $entry['value']) : (string) $entry['value'];
            $lines[] = $entry['label'] . ': ' . $value;
        }

        $body = Text::sprintf('COM_NRIFORMS_MAIL_INTRO', $group->title) . "\r\n\r\n" . implode("\r\n", $lines);

        try {
            $mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();

            foreach ($recipients as $recipient) {
                $mailer->addRecipient($recipient);
            }

            $this->applyReplyTo($mailer, $labelled, $settings);

            $mailer->setSubject($subject);
            $mailer->setBody($body);

            $result = $mailer->Send();

            // Legacy Mail::Send can return an Exception instead of throwing.
            if ($result instanceof \Exception) {
                return $result->getMessage();
            }

            return $result === true ? null : Text::_('COM_NRIFORMS_MAIL_ERR_SEND_FAILED');
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }
}
