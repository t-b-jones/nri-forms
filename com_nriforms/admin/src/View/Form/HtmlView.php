<?php
namespace NRI\Component\Nriforms\Administrator\View\Form;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    public $form;
    public $item;
    public $groupTitle;

    public function display($tpl = null): void
    {
        $groupId = Factory::getApplication()->getInput()->getInt('group_id');

        /** @var \NRI\Component\Nriforms\Administrator\Model\FormModel $model */
        $model = $this->getModel();

        $this->item       = $model->getItemByGroup($groupId);
        $this->groupTitle = $model->getGroupTitle($groupId);
        $this->form       = $model->getForm((array) $this->item, false);
        $this->form->bind($this->item);

        ToolbarHelper::title(Text::sprintf('COM_NRIFORMS_MANAGER_FORM_SETTINGS', $this->groupTitle), 'checkbox-partial');
        ToolbarHelper::save('form.save');
        ToolbarHelper::cancel('form.cancel');

        parent::display($tpl);
    }
}
