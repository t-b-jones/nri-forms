<?php
namespace NRI\Component\Nriforms\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

class FormController extends BaseController
{
    public function save(): void
    {
        $this->checkToken();

        $data = $this->input->post->get('jform', [], 'array');

        /** @var \NRI\Component\Nriforms\Administrator\Model\FormModel $model */
        $model = $this->getModel('Form', 'Administrator', ['ignore_request' => true]);

        if ($model->save($data)) {
            $this->setMessage(\Joomla\CMS\Language\Text::_('COM_NRIFORMS_SETTINGS_SAVED'));
        } else {
            $this->setMessage($model->getError(), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_nriforms&view=forms', false));
    }

    public function cancel(): void
    {
        $this->setRedirect(Route::_('index.php?option=com_nriforms&view=forms', false));
    }
}
