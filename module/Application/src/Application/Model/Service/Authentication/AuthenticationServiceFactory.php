<?php

namespace Application\Model\Service\Authentication;

use Application\Model\Service\Authentication\Adapter\AdapterInterface;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\Authentication\Storage\Session as SessionStorage;

class AuthenticationServiceFactory implements FactoryInterface
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
        $storage = new SessionStorage('UserDetails', 'identity');

        /** @var AdapterInterface $adapter */
        $adapter = $container->get('LpaAuthAdapter');

        return new AuthenticationService($storage, $adapter);
    }
}
