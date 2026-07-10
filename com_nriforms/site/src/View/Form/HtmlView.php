<?php

/**
 * @package     NRI.Component
 * @subpackage  com_nriforms
 */

namespace NRI\Component\Nriforms\Site\View\Form;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

class HtmlView extends BaseHtmlView
{
    /** @var object The field group (form) record */
    public $group;

    /** @var \Joomla\CMS\Form\Form */
    public $form;

    /** @var \Joomla\Registry\Registry Menu item params */
    public $params;

    /** @var object|null Per-form settings row */
    public $settings;

    /** @var bool */
    public $captchaEnabled = false;

    public function display($tpl = null): void
    {
        $app    = \Joomla\CMS\Factory::getApplication();
        $menu   = $app->getMenu()->getActive();
        $params = $app->getParams();

        $groupId = $app->getInput()->getInt('group_id', 0) ?: (int) $params->get('group_id', 0);

        /** @var \NRI\Component\Nriforms\Site\Model\FormModel $model */
        $model = $this->getModel();
        $group = $model->getGroup($groupId);

        if (!$group) {
            throw new \RuntimeException(Text::_('JERROR_PAGE_NOT_FOUND'), 404);
        }

        $this->group  = $group;
        $this->params = $params;
        $this->form   = $model->getFormObject($groupId);

        $this->settings = $model->getSettings($groupId);

        // Captcha, com_contact-style: per-form setting, '' = Use Global,
        // '0' = disabled, otherwise a captcha plugin name.
        $captcha = $this->settings->captcha ?? '';
        $captcha = $captcha !== '' ? $captcha : (string) $app->get('captcha', '0');

        if ($captcha && $captcha !== '0' && \Joomla\CMS\Plugin\PluginHelper::isEnabled('captcha', $captcha)) {
            $this->captchaEnabled = true;
            $this->form->load(
                '<form><fieldset name="captcha"><field name="captcha" type="captcha" label="COM_NRIFORMS_CAPTCHA_LABEL" validate="captcha" plugin="' . htmlspecialchars($captcha, ENT_QUOTES) . '" /></fieldset></form>'
            );
        }

        // Alternative layout selected on the menu item (componentlayout
        // field). "template:layout" syntax is handled by setLayout().
        $layout = (string) $params->get('form_layout', '');

        if ($layout !== '' && $layout !== '_:default') {
            $this->setLayout($layout);
        }

        // Re-populate after a failed validation round trip.
        $stored = $app->getUserState('com_nriforms.form.' . $groupId . '.data');

        if (\is_array($stored) && $stored) {
            $this->form->bind($stored);
        }

        // Core conditional-display behaviour on the front end.
        $this->getDocument()->getWebAssetManager()
            ->useScript('showon')
            ->useScript('form.validate');

        parent::display($tpl);
    }
}
