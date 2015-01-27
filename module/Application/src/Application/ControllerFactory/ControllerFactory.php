<?php
namespace Application\ControllerFactory;

use RuntimeException;

use Zend\Stdlib\DispatchableInterface as Dispatchable;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Creates a controller based on the passed 'controllerName'.
 *
 * Class ControllerFactory
 * @package Application\ControllerFactory
 */
class ControllerFactory implements FactoryInterface
{
    /**
     * @param ServiceLocatorInterface $controllerManager
     * @return Dispatchable
     */
    public function createService(ServiceLocatorInterface $controllerManager)
    {

        $locator = $controllerManager->getServiceLocator();

        //---

        $controllerName = $locator->get('Application')->getMvcEvent()->getRouteMatch()->getParam('controllerName');

        // Check the class exists...
        if( !class_exists($controllerName) ){
            throw new RuntimeException( 'Unknown controller name' );
        }

        $controller = new $controllerName;

        // Ensure it's Dispatchable...
        if( !( $controller instanceof Dispatchable ) ){
            throw new RuntimeException( 'Passed controller class is not Dispatchable' );
        }

        return $controller;

    } // function

} // class
