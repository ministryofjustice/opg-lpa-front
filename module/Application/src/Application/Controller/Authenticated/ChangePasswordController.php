<?php

namespace Application\Controller\Authenticated;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractAuthenticatedController;

use Application\Form\User\ChangePassword as ChangePasswordForm;

class ChangePasswordController extends AbstractAuthenticatedController
{
    public function indexAction(){

        $currentAddress = (string)$this->getUserDetails()->email;

        //----------------------

        $form = new ChangePasswordForm();
        $form->setAttribute( 'action', $this->url()->fromRoute('user/change-password') );

        $error = null;

        //----------------------

        // This form needs to check the user's current password,
        // thus we pass it the Authentication Service

        $authentication =   $this->getServiceLocator()->get('AuthenticationService');
        $adapter =          $this->getServiceLocator()->get('AuthenticationAdapter');

        // Pass the user's current email address...
        $adapter->setEmail( $currentAddress );

        $authentication->setAdapter( $adapter );

        $form->setAuthenticationService( $authentication );

        //----------------------

        $request = $this->getRequest();

        if ($request->isPost()) {

            //---

            $form->setData($request->getPost());

            //---

            if ($form->isValid()) {

                $service = $this->getServiceLocator()->get('AboutYouDetails');

                $result = $service->updatePassword( $form );

                //---

                if( $result === true ){

                    $this->flashMessenger()->addSuccessMessage('Your new password has been saved. Please remember to use this new password to sign in from now on.');

                    return $this->redirect()->toRoute( 'user/about-you' );

                } else {
                    $error = $result;
                }

            } // if

        } // if

        //----------------------

        $pageTitle = 'Change your password';

        return new ViewModel( compact( 'form', 'error', 'pageTitle' ) );

    }
}
