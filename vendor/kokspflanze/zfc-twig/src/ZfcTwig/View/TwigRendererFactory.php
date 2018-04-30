<?php

namespace ZfcTwig\View;

use Interop\Container\ContainerInterface;
use Twig\Environment;
use Twig\Loader\ChainLoader;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\View\View;
use ZfcTwig\ModuleOptions;

class TwigRendererFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return TwigRenderer
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var ModuleOptions $options */
        $options = $container->get(ModuleOptions::class);

        $renderer = new TwigRenderer(
            $container->get(View::class),
            $container->get(ChainLoader::class),
            $container->get(Environment::class),
            $container->get(TwigResolver::class)
        );

        $renderer->setCanRenderTrees($options->getDisableZfmodel());
        $renderer->setHelperPluginManager($container->get(HelperPluginManager::class));
        $renderer->setZendHelperPluginManager($container->get('ViewHelperManager'));

        return $renderer;
    }

}