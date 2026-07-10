<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \NRI\Component\Nriforms\Administrator\View\Forms\HtmlView $this */
?>
<div id="j-main-container" class="j-main-container">
    <table class="table itemList">
        <thead>
            <tr>
                <th scope="col"><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
                <th scope="col" class="w-25"><?php echo Text::_('COM_NRIFORMS_FIELD_RECIPIENT_LABEL'); ?></th>
                <th scope="col" class="w-10 text-center"><?php echo Text::_('COM_NRIFORMS_FIELD_RETENTION_LABEL'); ?></th>
                <th scope="col" class="w-10 text-center"><?php echo Text::_('COM_NRIFORMS_HEADING_CONFIGURED'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($this->items as $item) : ?>
            <tr>
                <th scope="row">
                    <a href="<?php echo Route::_('index.php?option=com_nriforms&view=form&layout=edit&group_id=' . (int) $item->id); ?>">
                        <?php echo $this->escape($item->title); ?>
                    </a>
                </th>
                <td><?php echo $this->escape($item->recipient ?? ''); ?></td>
                <td class="text-center"><?php echo $item->settings_id ? (int) $item->retention_days : '—'; ?></td>
                <td class="text-center">
                    <span class="icon-<?php echo $item->settings_id ? 'publish' : 'unpublish'; ?>" aria-hidden="true"></span>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
