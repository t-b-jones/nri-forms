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

$value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

echo '<a href="mailto:' . $value . '">' . $value . '</a>';
