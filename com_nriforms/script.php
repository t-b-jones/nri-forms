<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

return new class () {
    public function postflight($type, $adapter): bool
    {
        if (!\in_array($type, ['install', 'update'], true)) {
            return true;
        }

        $db   = Factory::getContainer()->get(DatabaseInterface::class);
        $cols = $db->getTableColumns('#__nriforms_submissions');

        if (!isset($cols['expires'])) {
            $db->setQuery('ALTER TABLE `#__nriforms_submissions` ADD `expires` datetime NULL DEFAULT NULL AFTER `created`')->execute();
        }

        return true;
    }
};
