<?php
namespace Application;

use DateTime;

use Zend\Stdlib\ArrayUtils;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

use Zend\Session\Container;

use Zend\ServiceManager\ServiceLocatorInterface;

use Application\Model\Service\Authentication\Adapter\LpaApiClient as LpaApiClientAuthAdapter;

use Opg\Lpa\Api\Client\Client as ApiClient;

use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Zend\ModuleManager\ModuleManager;

class Module{
    
    public function init(ModuleManager $manager)
    {
        $events = $manager->getEventManager();
        $sharedEvents = $events->getSharedManager();
        $sharedEvents->attach(__NAMESPACE__, 'dispatch', function($e) {
            $controller = $e->getTarget();
            
            // choose correct layout template.
            if(in_array(get_class($controller), [
                    'Application\Controller\Authenticated\Lpa\CertificateProviderController',
                    'Application\Controller\Authenticated\Lpa\CreatedController',
                    'Application\Controller\Authenticated\Lpa\DonorController',
                    'Application\Controller\Authenticated\Lpa\HowPrimaryAttorneysMakeDecisionController',
                    'Application\Controller\Authenticated\Lpa\HowReplacementAttorneysMakeDecisionController',
                    'Application\Controller\Authenticated\Lpa\InstructionsController',
                    'Application\Controller\Authenticated\Lpa\LifeSustainingController',
                    'Application\Controller\Authenticated\Lpa\PeopleToNotifyController',
                    'Application\Controller\Authenticated\Lpa\PrimaryAttorneyController',
                    'Application\Controller\Authenticated\Lpa\ReplacementAttorneyController',
                    'Application\Controller\Authenticated\Lpa\TypeController',
                    'Application\Controller\Authenticated\Lpa\WhatIsMyRoleController',
                    'Application\Controller\Authenticated\Lpa\WhenLpaStartsController',
                    'Application\Controller\Authenticated\Lpa\WhenReplacementAttorneyStepInController',
            ])) {
                $controller->layout('layout/creation');
            }
                    if(in_array(get_class($controller), [
                    'Application\Controller\Authenticated\Lpa\ApplicantController',
                    'Application\Controller\Authenticated\Lpa\CompleteController',
                    'Application\Controller\Authenticated\Lpa\CorrespondentController',
                    'Application\Controller\Authenticated\Lpa\FeeController',
                    'Application\Controller\Authenticated\Lpa\PaymentController',
            ])) {
                $controller->layout('layout/registration');
            }
        }, 100);
    }

    public function onBootstrap(MvcEvent $e){
        
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        //Only bootstrap the session if it's *not* PHPUnit.
        if(!strstr($e->getApplication()->getServiceManager()->get('Request')->getServer('SCRIPT_NAME'), 'phpunit')) {
            $this->bootstrapSession($e);
            $this->bootstrapIdentity($e);
        }
        
    }

    /**
     * Sets up and starts global sessions.
     *
     * @param MvcEvent $e
     */
    private function bootstrapSession(MvcEvent $e){

        $session = $e->getApplication()->getServiceManager()->get('SessionManager');

        // Always starts the session.
        $session->start();

        // Ensures this SessionManager is used for all Session Containers.
        Container::setDefaultManager($session);

        //---

        $container = new Container('initialised');

        // If it's a new session, regenerate the id.
        if (!isset($container->init)) {
            $session->regenerateId(true);
            $container->init = true;
        }

    } // function

    /**
     * This performs lazy checking of the user's auth token (if there is one).
     *
     * It works by only checking if the token is invalid if once we've gone past our recorded (in session)
     * 'tokenExpiresAt' time. Before then we assume the token is valid (leaving the API to verify this).
     *
     * If we're past 'tokenExpiresAt', then we query the Auth service to check the token's state. If it's still
     * valid we update 'tokenExpiresAt'. Otherwise we clear the user's identity form the session.
     *
     * We don't deal with forcing the user to re-authenticate here as they
     * may be accessing a page that does not require authentication.
     *
     * @param MvcEvent $e
     */
    private function bootstrapIdentity(MvcEvent $e){

        $sm = $e->getApplication()->getServiceManager();

        $auth = $sm->get('AuthenticationService');

        // If we have an identity...
        if ( ($identity = $auth->getIdentity()) != null ) {

            // If we're beyond the original time we expected the token to expire...
            if( (new DateTime) > $identity->tokenExpiresAt() ){

                // Get the tokens details...
                $info = $sm->get('ApiClient')->getTokenInfo( $identity->token() );

                // If the token has not expired...
                if( isset($info['expires_in']) ){

                    // update the time the token expires in the session
                    $identity->tokenExpiresIn( $info['expires_in'] );

                } else {

                    // else the user will need to re-login, so remove the current identity.
                    $auth->clearIdentity();

                }

            } // if we're beyond tokenExpiresAt

        } // if we have an identity

    } // function

    //-------------------------------------------

    public function getServiceConfig(){

        return [
            'invokables' => [
                'AuthenticationService' => 'Zend\Authentication\AuthenticationService',
            ],
            'factories' => [
                'SessionManager'    => 'Application\Model\Service\Session\SessionFactory',
                'ApiClient'         => 'Application\Model\Service\Lpa\ApiClientFactory',

                'LpaApplicationService' => function( ServiceLocatorInterface $sm ){
                    return new LpaApplicationService( $sm->get('ApiClient') );
                },

                'LpaApiClientAuthAdapter' => function( ServiceLocatorInterface $sm ){
                    return new LpaApiClientAuthAdapter( $sm->get('ApiClient') );
                },

            ], // factories
        ];

    } // function

    public function getControllerConfig(){

        return [
            'initializers' => [
                'UserAwareInitializer' => 'Application\ControllerFactory\UserAwareInitializer',
                'LpaAwareInitializer' => 'Application\ControllerFactory\LpaAwareInitializer',
            ]
        ];

    }

    //-------------------------------------------

    public function getAutoloaderConfig(){
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getConfig(){

        $configFiles = [
            __DIR__ . '/config/module.config.php',
            __DIR__ . '/config/module.routes.php',
        ];

        //---

        $config = array();

        // Merge all module config options
        foreach($configFiles as $configFile) {
            $config = ArrayUtils::merge( $config, include($configFile) );
        }

        return $config;

    }

} // class
