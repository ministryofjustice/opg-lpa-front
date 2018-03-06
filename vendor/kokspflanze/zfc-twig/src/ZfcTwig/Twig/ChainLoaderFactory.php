<?php

namespace ZfcTwig\Twig;

use Interop\Container\ContainerInterface;
use InvalidArgumentException;
use Twig_Loader_Chain;
use Zend\ServiceManager\Factory\FactoryInterface;
use ZfcTwig\ModuleOptions;

class ChainLoaderFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return Twig_Loader_Chain
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var ModuleOptions $options */
        $options = $container->get(ModuleOptions::class);

        // Setup loader
        $chain = new Twig_Loader_Chain();

        foreach ($options->getLoaderChain() as $loader) {
            if (!is_string($loader) || !$container->has($loader)) {
                throw new InvalidArgumentException('Loaders should be a service manager alias.');
            }
            $chain->addLoader($container->get($loader));
        }

        return $chain;
    }

}