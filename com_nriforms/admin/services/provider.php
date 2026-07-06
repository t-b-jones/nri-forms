<?php

/**
 * @package     NRI.Component
 * @subpackage  com_nriforms
 */

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use NRI\Component\Nriforms\Administrator\Extension\NriformsComponent;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCFactory('\\NRI\\Component\\Nriforms'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\NRI\\Component\\Nriforms'));
        $container->registerServiceProvider(new RouterFactory('\\NRI\\Component\\Nriforms'));

        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new NriformsComponent(
                    $container->get(ComponentDispatcherFactoryInterface::class)
                );
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                $component->setRouterFactory($container->get(RouterFactoryInterface::class));

                return $component;
            }
        );
    }
};
