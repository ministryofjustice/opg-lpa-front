<?php
namespace Application\Controller;

use DateTime;
use RuntimeException;

use Zend\Mvc\MvcEvent;
use Zend\Session\Container as SessionContainer;
use Application\Model\Service\Authentication\Identity\User as Identity;
use Zend\Session\Container;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

abstract class AbstractAuthenticatedController extends AbstractBaseController
{
    /**
     * @var Identity The Identity of the current authenticated user.
     */
    private $user;

    /**
     * If a controller is excluded from the ABout You check, this should be overridden to TRUE.
     *
     * @var bool Is this controller excluded form checking if the About You section is complete.
     */
    protected $excludeFromAboutYouCheck = false;


    /**
     * Do some pre-dispatch checks...
     *
     * @param  MvcEvent $e
     * @return mixed
     */
    public function onDispatch(MvcEvent $e){

        // Before the user can access any actions that extend this controller...

        //----------------------------------------------------------------------
        // Check we have a user set, thus ensuring an authenticated user

        if( ($authenticated = $this->checkAuthenticated()) !== true ){
            return $authenticated;
        }

        $identity = $this->getServiceLocator()->get('AuthenticationService')->getIdentity();

        $this->log()->info('Request to ' . get_class($this), $identity->toArray());

        //----------------------------------------------------------------------
        // Check if they've singed in since the T&C's changed...

        /*
         * We check here if the terms have changed since the user last logged in.
         * We also use a session to record whether the user has seen the 'Terms have changed' page since logging in.
         *
         * If the terms have changed and they haven't seen the 'Terms have changed' page
         * in this session, we redirect them to it.
         */

        $termsUpdated = new DateTime($this->config()['terms']['lastUpdated']);

        if( $identity->lastLogin() < $termsUpdated ){

            $termsSession = new SessionContainer('TermsAndConditionsCheck');

            if( !isset($termsSession->seen) ){

                // Flag that the 'Terms have changed' page will now have been seen...
                $termsSession->seen = true;

                return $this->redirect()->toRoute( 'user/dashboard/terms-changed' );

            } // if

        } // if


        //----------------------------------------------------------------------
        // Load the user's details and ensure the required details are included

        $detailsContainer = $this->getServiceLocator()->get('UserDetailsSession');

        if( !isset($detailsContainer->user) || is_null($detailsContainer->user->name) ){

            $userDetails = $this->getServiceLocator()->get('AboutYouDetails')->load();

            // If the user details do not at least have a name
            // And we're not trying to set the details via the AboutYouController...
            if( is_null($userDetails->name) && $this->excludeFromAboutYouCheck !== true ) {

                // Redirect to the About You page.
                return $this->redirect()->toRoute( 'user/about-you/new' );

            }

            // Store the details in the session...
            $detailsContainer->user = $userDetails;

        } // if

        //---

        // inject lpa into view
        $view = parent::onDispatch($e);

        if(($view instanceof ViewModel) && !($view instanceof JsonModel)) {
            $view->setVariable('signedInUser', $this->getUserDetails());
        }

        return $view;

    } // function

    /**
     * Return the Identity of the current authenticated user.
     *
     * @return Identity
     */
    public function getUser ()
    {
        if( !( $this->user instanceof Identity ) ){
            throw new RuntimeException('A valid Identity has not been set');
        }
        return $this->user;
    }

    /**
     * Set the Identity of the current authenticated user.
     *
     * @param Identity $user
     */
    public function setUser( Identity $user )
    {
        $this->user = $user;
    }

    /**
     * Returns extra details about the user.
     *
     * @return mixed|null
     */
    public function getUserDetails(){

        $detailsContainer = $this->getServiceLocator()->get('UserDetailsSession');

        if( !isset($detailsContainer->user) ){
            return null;
        }

        return $detailsContainer->user;

    }

    /**
     * Returns an instance of the LPA Application Service.
     *
     * @return object
     */
    protected function getLpaApplicationService(){
        return $this->getServiceLocator()->get('LpaApplicationService');
    }


    /**
     * Check there is a user authenticated.
     *
     * @return bool|\Zend\Http\Response
     */
    protected function checkAuthenticated( $allowRedirect = true ){

        if( !( $this->user instanceof Identity ) ){

            if( $allowRedirect ){

                $preAuthRequest = new Container('PreAuthRequest');

                $preAuthRequest->url = (string)$this->getRequest()->getUri();

            }

            //---

            // Redirect to the About You page.
            return $this->redirect()->toRoute( 'login', [ 'state'=>'timeout' ] );

        } // if

        return true;

    } // function

    /**
     * delete cloned data for this seed id from session container if it exists.
     * to make sure clone data will be loaded freshly when actor form is rendered.
     *
     * @param int $seedId
     */
    protected function resetSessionCloneData($seedId)
    {
        $cloneContainer = new Container('clone');
        if($cloneContainer->offsetExists($seedId)) {
            unset($cloneContainer->$seedId);
        }
    }

} // class
