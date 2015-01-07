<?php
namespace Application\Model\Service\Session;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

use Zend\Session\SessionManager;
use Zend\Session\SaveHandler\Cache as CacheSaveHandler;
use Zend\Session\Exception\RuntimeException;

use Zend\Cache\StorageFactory as CacheStorageFactory;

/**
 * Create the SessionManager for use throughout the LPA frontend.
 *
 * Class SessionFactory
 * @package Application\Model\Service\Session
 */
class SessionFactory implements FactoryInterface {

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return SessionManager
     */
    public function createService(ServiceLocatorInterface $serviceLocator){

        $config = $serviceLocator->get('Config');

        if( !isset( $config['session'] ) ){
            throw new RuntimeException('Session configuration setting not found ');
        }

        $config = $config['session'];

        //----------------------------------------
        // Apply any native PHP level settings

        if( isset($config['native_settings']) && is_array($config['native_settings']) ){

            foreach( $config['native_settings'] as $k => $v ){
                ini_set( 'session.'.$k, $v );
            }

        }

        //----------------------------------------

        $manager = new SessionManager();

        //----------------------------------------
        // Setup Redis as the save handler

        $redis = CacheStorageFactory::factory([
            'adapter' => [
                'name' => 'redis',
                'options' => $config['redis'],
            ]
        ]);

        //----------------------------------------
        // Setup the encryption save handler

        $key = $config['encryption']['key'];

        //$saveHandler = new SaveHandler\EncryptedCache( $redis, $key );
        $saveHandler = new CacheSaveHandler( $redis );

        $manager->setSaveHandler($saveHandler);

        //----------------------------------------

        return $manager;

    } // function

} // class
