<?php
namespace NRI\Component\Nriforms\Administrator\View\Forms;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    public $items;

    public function display($tpl = null): void
    {
        $this->items = $this->get('Items');
        ToolbarHelper::title(Text::_('COM_NRIFORMS_MANAGER_FORMS'), 'checkbox-partial');
        parent::display($tpl);
    }
}
