<?php
namespace Application;

use DateTime;

use Zend\Stdlib\ArrayUtils;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

use Zend\Session\Container;
use Zend\ServiceManager\ServiceLocatorInterface;

use Application\Model\Service\Authentication\Adapter\LpaApiClient as LpaApiClientAuthAdapter;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Opg\Lpa\Logger\Logger;
use Zend\Cache\StorageFactory;

use Zend\View\Model\ViewModel;
use Application\Adapter\DynamoDbKeyValueStore;

class Module{
    
    public function onBootstrap(MvcEvent $e){
        
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        
        // Register error handler for dispatch and render errors
        $eventManager->attach(\Zend\Mvc\MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'handleError'));
        $eventManager->attach(\Zend\Mvc\MvcEvent::EVENT_RENDER_ERROR, array($this, 'handleError'));
        
        register_shutdown_function(function () {
            $error = error_get_last();
            
            if ($error['type'] === E_ERROR) {
                // This is a fatal error, we have no exception and no nice view to render
                // The fatal error will have been logged already prior to writing this message
                echo 'An unknown server error has occurred.';
            }
        });

        //---

        $request = $e->getApplication()->getServiceManager()->get('Request');

        // Only bootstrap the session if it's *not* PHPUnit AND is not a healthcheck.
        if(
            !strstr( $request->getServer('SCRIPT_NAME'), 'phpunit' ) &&
            !in_array( $request->getUri()->getPath(), [ '/ping/elb', '/ping/json' ] ))
        {
            $this->bootstrapSession($e);
            $this->bootstrapIdentity($e);
        }
        
    } // function

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

        $session->initialise();

    } // function

    /**
     * This performs lazy checking of the user's auth token (if there is one).
     *
     * It works by only checking if the token is invalid once we've gone past our recorded (in session)
     * 'tokenExpiresAt' time. Before then we assume the token is valid (leaving the API to verify this).
     *
     * If we're past 'tokenExpiresAt', then we query the Auth service to check the token's state. If it's still
     * valid we update 'tokenExpiresAt'. Otherwise we clear the user's identity form the session.
     *
     * IGNORE ALL OF THE ABOVE ^^^ #TODO
     *
     * This now checks the token on every request otherwise we have no method of knowing if the user has
     * logged in on another browser. We need to find a new way of checking this, then hopefully we can
     * re-enable lazy checking.
     *
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
            //if( (new DateTime) > $identity->tokenExpiresAt() ){

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

            //} // if we're beyond tokenExpiresAt

        } // if we have an identity

    } // function

    //-------------------------------------------

    public function getServiceConfig(){

        return [
            'aliases' => [
                'MailTransport' => 'SendGridTransport',
                'AddressLookup' => 'PostcodeInfo',
                'AuthenticationAdapter' => 'LpaApiClientAuthAdapter',
                'Zend\Authentication\AuthenticationService' => 'AuthenticationService',
            ],
            'invokables' => [
                'AuthenticationService' => 'Application\Model\Service\Authentication\AuthenticationService',
                'PasswordReset'         => 'Application\Model\Service\User\PasswordReset',
                'Register'              => 'Application\Model\Service\User\Register',
                'AboutYouDetails'       => 'Application\Model\Service\User\Details',
                'DeleteUser'            => 'Application\Model\Service\User\Delete',
                'Payment'               => 'Application\Model\Service\Payment\Payment',
                'Feedback'              => 'Application\Model\Service\Feedback\Feedback',
                'Signatures'            => 'Application\Model\Service\Feedback\Signatures',
                'Guidance'              => 'Application\Model\Service\Guidance\Guidance',
                'ApplicationList'       => 'Application\Model\Service\Lpa\ApplicationList',
                'Metadata'              => 'Application\Model\Service\Lpa\Metadata',
                'Communication'         => 'Application\Model\Service\Lpa\Communication',
                'PostcodeInfo'          => 'Application\Model\Service\AddressLookup\PostcodeInfo',
                'SiteStatus'            => 'Application\Model\Service\System\Status',
            ],
            'factories' => [
                'SessionManager'        => 'Application\Model\Service\Session\SessionFactory',
                'ApiClient'             => 'Application\Model\Service\Lpa\ApiClientFactory',
                'PostcodeInfoClient'    => 'Application\Model\Service\PostcodeInfo\PostcodeInfoClientFactory',
                'EmailPhpRenderer'      => 'Application\Model\Service\Mail\View\Renderer\PhpRendererFactory',

                // Access via 'MailTransport'
                'SendGridTransport' => 'Application\Model\Service\Mail\Transport\SendGridFactory',

                // LPA access service
                'LpaApplicationService' => function( ServiceLocatorInterface $sm ){
                    return new LpaApplicationService( $sm->get('ApiClient') );
                },

                // Authentication Adapter. Access via 'AuthenticationAdapter'
                'LpaApiClientAuthAdapter' => function( ServiceLocatorInterface $sm ){
                    return new LpaApiClientAuthAdapter( $sm->get('ApiClient') );
                },

                // Generate the session container for a user's personal details
                'UserDetailsSession' => function(){
                    return new Container('UserDetails');
                },
                
                // Logger
                'Logger' => function ( ServiceLocatorInterface $sm ) {
                    $logger = new Logger();
                    $logConfig = $sm->get('config')['log'];
                    
                    $logger->setFileLogPath($logConfig['path']);
                    $logger->setSentryUri($logConfig['sentry-uri']);
                    
                    return $logger;
                    
                },
                
                'Cache' => function ( ServiceLocatorInterface $sm ) {
                    
                    $config = $sm->get('config')['admin']['dynamodb'];
                    
                    $dynamoDbAdapter = new DynamoDbKeyValueStore($config);
                    
                    return $dynamoDbAdapter;
                }

            ], // factories
        ];

    } // function

    //-------------------------------------------

    public function getViewHelperConfig(){

        return array(
            'factories' => array(
                'staticAssetPath' => function( $sm ){
                    $config = $sm->getServiceLocator()->get('Config');
                    return new \Application\View\Helper\StaticAssetPath( $config['version']['cache'] );
                },
            ),
        );

    } // function

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
    
    /**
     * Use our logger to send this exception to its various destinations
     * 
     * @param MvcEvent $e
     */
    public function handleError(MvcEvent $e)
    {

        $exception = $e->getResult()->exception;
        
        if ($exception) {
            $logger = $e->getApplication()->getServiceManager()->get('Logger');
            $logger->err($exception->getMessage());
            
            $viewModel = new ViewModel();
            $viewModel->setTemplate('error/500');
            
            $e->getViewModel()->addChild($viewModel);
            $e->stopPropagation();
             
            $e->getResponse()->setStatusCode(500);
            
            return $viewModel;
        }
        
    }
    
} // class
