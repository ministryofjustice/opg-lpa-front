<?php

namespace ZfcTwig;

use InvalidArgumentException;
use Twig\Environment;
use Zend\EventManager\EventInterface;
use Zend\ModuleManager\Feature\BootstrapListenerInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;

class Module implements
    BootstrapListenerInterface,
    ConfigProviderInterface
{
    /**
     * @param EventInterface $e
     * @throws InvalidArgumentException
     * @return array
     */
    public function onBootstrap(EventInterface $e)
    {
        /** @var \Zend\Mvc\MvcEvent $e*/
        $application    = $e->getApplication();
        $serviceManager = $application->getServiceManager();
        $environment    = $serviceManager->get(Environment::class);

        /** @var ModuleOptions $options */
        $options = $serviceManager->get(ModuleOptions::class);

        // Setup extensions
        foreach ($options->getExtensions() as $extension) {
            // Allows modules to override/remove extensions.
            if (empty($extension)) {
                continue;
            } elseif (is_string($extension)) {
                if ($serviceManager->has($extension)) {
                    $extension = $serviceManager->get($extension);
                } else {
                    $extension = new $extension();
                }
            } elseif (!is_object($extension)) {
                throw new InvalidArgumentException('Extensions should be a string or object.');
            }

            $environment->addExtension($extension);
        }
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/../../config/module.config.php';
    }
}
