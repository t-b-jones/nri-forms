<?php

/**
 * @package     NRI.Component
 * @subpackage  com_nriforms
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \NRI\Component\Nriforms\Administrator\View\Submissions\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo Route::_('index.php?option=com_nriforms&view=submissions'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table itemList" id="submissionList">
                        <caption class="visually-hidden"><?php echo Text::_('COM_NRIFORMS_MANAGER_SUBMISSIONS'); ?></caption>
                        <thead>
                            <tr>
                                <td class="w-1 text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </td>
                                <th scope="col">
                                    <?php echo Text::_('COM_NRIFORMS_HEADING_SUMMARY'); ?>
                                </th>
                                <th scope="col" class="w-20 d-none d-md-table-cell">
                                    <?php echo Text::_('COM_NRIFORMS_FIELD_FORM_LABEL'); ?>
                                </th>
                                <th scope="col" class="w-10 text-center d-none d-md-table-cell">
                                    <?php echo Text::_('COM_NRIFORMS_HEADING_MAIL_SENT'); ?>
                                </th>
                                <th scope="col" class="w-15 d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JDATE', 'a.created', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-3 d-none d-lg-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($this->items as $i => $item) :
                            $data    = json_decode($item->data ?? '', true) ?: [];
                            $summary = '';
                            $first   = '';

                            foreach ($data as $entry) {
                                $value = $entry['value'] ?? '';

                                if (!\is_string($value) || trim($value) === '') {
                                    continue;
                                }

                                if ($first === '') {
                                    $first = trim($value);
                                }

                                // Prefer the sender's email: the one value
                                // that identifies a submission on any form.
                                if (filter_var(trim($value), FILTER_VALIDATE_EMAIL)) {
                                    $summary = trim($value);
                                    break;
                                }
                            }

                            if ($summary === '') {
                                $summary = $first;
                            }

                            if (mb_strlen($summary) > 60) {
                                $summary = mb_substr($summary, 0, 57) . '…';
                            }

                            if ($summary === '') {
                                $summary = Text::sprintf('COM_NRIFORMS_SUBMISSION_NUMBER', (int) $item->id);
                            }
                        ?>
                            <tr class="row<?php echo $i % 2; ?>">
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', (string) $item->id); ?>
                                </td>
                                <th scope="row">
                                    <a href="<?php echo Route::_('index.php?option=com_nriforms&view=submission&id=' . (int) $item->id); ?>">
                                        <?php echo $this->escape($summary); ?>
                                    </a>
                                </th>
                                <td class="d-none d-md-table-cell">
                                    <?php echo $this->escape($item->group_title); ?>
                                </td>
                                <td class="text-center d-none d-md-table-cell">
                                    <?php if ((int) $item->mail_sent === 1) : ?>
                                        <span class="icon-publish" aria-hidden="true"></span>
                                        <span class="visually-hidden"><?php echo Text::_('JYES'); ?></span>
                                    <?php else : ?>
                                        <span class="icon-unpublish" aria-hidden="true"></span>
                                        <span class="visually-hidden"><?php echo Text::_('JNO'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC5')); ?>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <?php echo (int) $item->id; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php echo $this->pagination->getListFooter(); ?>
                <?php endif; ?>

                <input type="hidden" name="task" value="">
                <input type="hidden" name="boxchecked" value="0">
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>
