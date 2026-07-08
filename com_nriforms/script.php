<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\Database\DatabaseInterface;

return new class () implements InstallerScriptInterface {
    public function install(InstallerAdapter $adapter): bool { return true; }
    public function update(InstallerAdapter $adapter): bool { return true; }
    public function uninstall(InstallerAdapter $adapter): bool { return true; }
    public function preflight(string $type, InstallerAdapter $adapter): bool { return true; }

    public function postflight(string $type, InstallerAdapter $adapter): bool
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