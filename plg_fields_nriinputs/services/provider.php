<?php

/**
 * @package     NRI.Plugin
 * @subpackage  Fields.nriinputs
 */

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use NRI\Plugin\Fields\Nriinputs\Extension\Nriinputs;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin = new Nriinputs(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('fields', 'nriinputs')
                );
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
