<?php

namespace Application\Model\Service;

use Application\Model\Service\AddressLookup\PostcodeInfo;
use Application\Model\Service\ApiClient\ApiClientAwareInterface;
use Application\Model\Service\ApiClient\Client as ApiClient;
use Application\Model\Service\AuthClient\AuthClientAwareInterface;
use Application\Model\Service\AuthClient\Client as AuthClient;
use Application\Model\Service\Lpa\Communication;
use Application\Model\Service\Lpa\Metadata;
use Application\Model\Service\User\Details;
use Exception;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\ServiceLocatorInterface;

class ServiceAbstractFactory implements AbstractFactoryInterface
{
    /**
     * Any additional services to be injected into the requested service using the setter method specified
     *
     * @var array
     */
    private $additionalServices = [
        Communication::class => [
            'setUserDetailsSession' => 'UserDetailsSession'
        ],
        Details::class => [
            'setUserDetailsSession' => 'UserDetailsSession'
        ],
        Metadata::class => [
            'setLpaApplicationService' => 'LpaApplicationService',
        ],
        PostcodeInfo::class => [
            'setPostcodeInfoClient' => 'PostcodeInfoClient'
        ],
    ];

    /**
     * Can the factory create an instance for the service?
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        return class_exists($requestedName) && is_subclass_of($requestedName, AbstractService::class);
    }

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
     * @throws Exception if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (!$this->canCreate($container, $requestedName)) {
            throw new ServiceNotFoundException(sprintf(
                'Abstract factory %s can not create the requested service %s',
                get_class($this),
                $requestedName
            ));
        }

        $serviceName = $requestedName;

        if (is_subclass_of($serviceName, AbstractEmailService::class)) {
            $service = new $serviceName(
                $container->get('AuthenticationService'),
                $container->get('Config'),
                $container->get('TwigEmailRenderer'),
                $container->get('MailTransport')
            );
        } else {
            $service = new $serviceName(
                $container->get('AuthenticationService'),
                $container->get('Config')
            );
        }

        //  Inject the API and/or Auth clients if necessary
        if ($service instanceof ApiClientAwareInterface) {
            /** @var ApiClient $apiClient */
            $apiClient = $container->get('ApiClient');
            $service->setApiClient($apiClient);
        }

        if ($service instanceof AuthClientAwareInterface) {
            /** @var AuthClient $authClient */
            $authClient = $container->get('AuthClient');
            $service->setAuthClient($authClient);
        }

        //  If required load any additional services into the resource
        if (array_key_exists($serviceName, $this->additionalServices)
            && is_array($this->additionalServices[$serviceName])) {
            foreach ($this->additionalServices[$serviceName] as $setterMethod => $additionalService) {
                if (!method_exists($service, $setterMethod)) {
                    throw new Exception(sprintf(
                        'The setter method %s does not exist on the requested resource %s',
                        $setterMethod,
                        $serviceName
                    ));
                }

                $service->$setterMethod($container->get($additionalService));
            }
        }

        return $service;
    }
}
