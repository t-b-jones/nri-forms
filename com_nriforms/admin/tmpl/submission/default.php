<?php

/**
 * @package     NRI.Component
 * @subpackage  com_nriforms
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/** @var \NRI\Component\Nriforms\Administrator\View\Submission\HtmlView $this */
?>
<div class="main-card p-4">
    <dl class="row mb-4">
        <dt class="col-sm-3"><?php echo Text::_('COM_NRIFORMS_FIELD_FORM_LABEL'); ?></dt>
        <dd class="col-sm-9"><?php echo $this->escape($this->item->group_title); ?></dd>

        <dt class="col-sm-3"><?php echo Text::_('JDATE'); ?></dt>
        <dd class="col-sm-9"><?php echo HTMLHelper::_('date', $this->item->created, Text::_('DATE_FORMAT_LC2')); ?></dd>

        <dt class="col-sm-3"><?php echo Text::_('COM_NRIFORMS_HEADING_MAIL_SENT'); ?></dt>
        <dd class="col-sm-9"><?php echo Text::_((int) $this->item->mail_sent === 1 ? 'JYES' : 'JNO'); ?></dd>
    </dl>

    <h2 class="h4"><?php echo Text::_('COM_NRIFORMS_SUBMISSION_DATA'); ?></h2>
    <table class="table">
        <tbody>
        <?php foreach ($this->item->fields as $entry) : ?>
            <tr>
                <th scope="row" class="w-25"><?php echo $this->escape($entry['label'] ?? ''); ?></th>
                <td>
                    <?php
                    $value = $entry['value'] ?? '';
                    echo nl2br($this->escape(\is_array($value) ? implode(', ', $value) : (string) $value));
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
