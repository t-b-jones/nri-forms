<?php

/**
 * @package     NRI.Component
 * @subpackage  com_nriforms
 *
 * Deliberately plain markup. Copy to
 * templates/<yourtemplate>/html/com_nriforms/form/default.php
 * to restyle per site.
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \NRI\Component\Nriforms\Site\View\Form\HtmlView $this */

$itemid = (int) $this->params->get('page_itemid', 0) ?: (int) \Joomla\CMS\Factory::getApplication()->getInput()->getInt('Itemid', 0);
$action = Route::_('index.php?option=com_nriforms&task=form.submit&Itemid=' . $itemid);
?>
<div class="com-nriforms nriform nriform--<?php echo (int) $this->group->id; ?>">
    <?php if ($this->params->get('show_page_heading')) : ?>
        <h1><?php echo $this->escape($this->params->get('page_heading') ?: $this->group->title); ?></h1>
    <?php endif; ?>

    <?php if (trim((string) $this->group->description) !== '') : ?>
        <div class="nriform__description">
            <?php echo $this->group->description; ?>
        </div>
    <?php endif; ?>

    <form action="<?php echo $action; ?>" method="post" class="form-validate nriform__form" novalidate>
        <?php foreach ($this->form->getGroup('com_fields') as $field) : ?>
            <?php echo $field->renderField(); ?>
        <?php endforeach; ?>

        <?php // Honeypot: visually hidden, ignored by humans, filled by bots. ?>
        <div class="nriform__hp" aria-hidden="true" style="position:absolute;left:-9999px;top:auto;height:1px;overflow:hidden;">
            <label for="nri_hp_<?php echo (int) $this->group->id; ?>"><?php echo Text::_('COM_NRIFORMS_HP_LABEL'); ?></label>
            <input type="text" name="nri_hp" id="nri_hp_<?php echo (int) $this->group->id; ?>" value="" tabindex="-1" autocomplete="off">
        </div>

        <div class="nriform__actions">
            <button type="submit" class="btn btn-primary nriform__submit">
                <?php echo $this->escape($this->params->get('submit_label') ?: Text::_('COM_NRIFORMS_SUBMIT')); ?>
            </button>
        </div>

        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>
