<?php

/**
 * @package     NRI.Component
 * @subpackage  com_nriforms
 */

namespace NRI\Component\Nriforms\Administrator\View\Submission;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    public $item;

    public function display($tpl = null): void
    {
        $this->item = $this->get('Item');

        $this->addToolbar();

        parent::display($tpl);
    }

    private function addToolbar(): void
    {
        ToolbarHelper::title(
            Text::sprintf('COM_NRIFORMS_MANAGER_SUBMISSION', (int) $this->item->id),
            'checkbox-partial'
        );
        ToolbarHelper::back('JTOOLBAR_BACK', Route::_('index.php?option=com_nriforms&view=submissions', false));
    }
}
