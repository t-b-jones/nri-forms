<?php

/**
 * @package     NRI.Plugin
 * @subpackage  Fields.nriinputs
 */

defined('_JEXEC') or die;

$value = $field->value;

if ($value == '') {
    return;
}

$display = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$href    = htmlspecialchars(preg_replace('/[^+0-9]/', '', (string) $value), ENT_QUOTES, 'UTF-8');

echo '<a href="tel:' . $href . '">' . $display . '</a>';
