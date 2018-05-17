<?php

namespace Application\Model\Service\Mail\Transport;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;
use SendGrid as SendGridClient;
use Twig_Environment;
use RuntimeException;

class MailTransportFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $emailConfig = $container->get('Config')['email'];
        $sendGridConfig = $emailConfig['sendgrid'];

        if (!isset($sendGridConfig['key'])) {
            throw new RuntimeException('Sendgrid settings not found');
        }

        $client = new SendGridClient($sendGridConfig['key']);

        /** @var Twig_Environment $emailRenderer */
        $emailRenderer = $container->get('TwigEmailRenderer');

        return new MailTransport($client, $emailRenderer, $emailConfig);
    }
}
