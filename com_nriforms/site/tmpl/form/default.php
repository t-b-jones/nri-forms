<?php

/**
 * @package     NRI.Component
 * @subpackage  com_nriforms
 *
 * Deliberately plain markup. Copy to
 * templates/<yourtemplate>/html/com_nriforms/form/default.php
 * to restyle per site.
 *
 * "Section Heading" fields (spacers) open a new <section> block; field
 * ordering in the backend defines section membership.
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \NRI\Component\Nriforms\Site\View\Form\HtmlView $this */

$itemid = (int) $this->params->get('page_itemid', 0) ?: (int) \Joomla\CMS\Factory::getApplication()->getInput()->getInt('Itemid', 0);
$action = Route::_('index.php?option=com_nriforms&task=form.submit&Itemid=' . $itemid);

$hasRequired = false;

foreach ($this->form->getGroup('com_fields') as $field) {
    if ($field->required) {
        $hasRequired = true;
        break;
    }
}
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

    <?php if ($hasRequired) : ?>
        <p class="nriform__required-note"><?php echo Text::_('COM_NRIFORMS_REQUIRED_NOTE'); ?></p>
    <?php endif; ?>

    <form action="<?php echo $action; ?>" method="post" class="form-validate nriform__form" novalidate>
        <?php $inSection = false; ?>
        <?php foreach ($this->form->getGroup('com_fields') as $field) : ?>
            <?php if (strtolower((string) $field->type) === 'spacer') : ?>
                <?php if ($inSection) : ?>
                    </section>
                <?php endif; $inSection = true; ?>
                <?php $hTag = 'h' . min(6, max(1, (int) $field->getAttribute('heading', 2))); ?>
                <section class="nriform__section">
                    <<?php echo $hTag; ?> class="nriform__section-heading"><?php echo $this->escape(Text::_($field->getAttribute('label') ?: $field->getAttribute('title') ?: $field->name)); ?></<?php echo $hTag; ?>>
            <?php else : ?>
                <?php echo $field->renderField(); ?>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if ($inSection) : ?>
            </section>
        <?php endif; ?>

        <?php if ($this->captchaEnabled) : ?>
            <div class="nriform__captcha">
                <?php echo $this->form->renderFieldset('captcha'); ?>
            </div>
        <?php endif; ?>

        <?php foreach (
            [
                ['consent', $this->settings->consent_enabled ?? 0, $this->settings->consent_label ?? '', $this->settings->consent_article_id ?? 0],
                ['terms', $this->settings->terms_enabled ?? 0, $this->settings->terms_label ?? '', $this->settings->terms_article_id ?? 0],
            ] as [$key, $enabled, $label, $articleId]
        ) : ?>
            <?php if ((int) $enabled === 1) :
                $label   = $label !== '' ? $label : Text::_('COM_NRIFORMS_' . strtoupper($key) . '_DEFAULT_LABEL');
                $fieldId = 'nri_' . $key . '_' . (int) $this->group->id;
            ?>
                <div class="nriform__consent form-check">
                    <input
                        type="checkbox"
                        class="form-check-input"
                        name="nri_<?php echo $key; ?>"
                        id="<?php echo $fieldId; ?>"
                        value="1"
                        required
                    >

                    <label class="form-check-label" for="<?php echo $fieldId; ?>">
                        <?php if ((int) $articleId > 0) : ?>
                            <a
                                href="<?php echo Route::_('index.php?option=com_content&view=article&id=' . (int) $articleId); ?>"
                                target="_blank"
                                rel="noopener"
                            >
                                <?php echo $this->escape($label); ?>
                            </a>
                        <?php else : ?>
                            <?php echo $this->escape($label); ?>
                        <?php endif; ?>
                    </label>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

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
