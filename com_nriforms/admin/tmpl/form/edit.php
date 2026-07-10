<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

/** @var \NRI\Component\Nriforms\Administrator\View\Form\HtmlView $this */

$this->getDocument()->getWebAssetManager()->useScript('keepalive')->useScript('showon');
?>
<form action="<?php echo Route::_('index.php?option=com_nriforms&view=form&layout=edit&group_id=' . (int) $this->item->group_id); ?>" method="post" name="adminForm" id="adminForm">
    <div class="main-card p-4">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'nriform', ['active' => 'mail']); ?>
        <?php foreach (['mail', 'storage', 'consent', 'protection', 'aftersubmit'] as $set) : ?>
            <?php $fs = $this->form->getFieldsets()[$set] ?? null; ?>
            <?php if ($fs) : ?>
                <?php echo HTMLHelper::_('uitab.addTab', 'nriform', $set, \Joomla\CMS\Language\Text::_($fs->label)); ?>
                <?php echo $this->form->renderFieldset($set); ?>
                <?php echo HTMLHelper::_('uitab.endTab'); ?>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>
    <?php echo $this->form->renderField('id'); ?>
    <?php echo $this->form->renderField('group_id'); ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
