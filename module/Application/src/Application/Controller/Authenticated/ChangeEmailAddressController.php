<?php

namespace Application\Controller\Authenticated;

use Application\Controller\AbstractAuthenticatedController;
use Zend\View\Model\ViewModel;

class ChangeEmailAddressController extends AbstractAuthenticatedController
{
    public function indexAction()
    {
        $currentAddress = (string)$this->getUserDetails()->email;

        $form = $this->getFormElementManager()->get('Application\Form\User\ChangeEmailAddress');
        $form->setAttribute('action', $this->url()->fromRoute('user/change-email-address'));

        $error = null;

        // This form needs to check the user's current password,
        // thus we pass it the Authentication Service
        $authentication =   $this->getAuthenticationService();
        $adapter =          $this->getAuthenticationAdapter();

        // Pass the user's current email address...
        $adapter->setEmail($currentAddress);

        $authentication->setAdapter($adapter);

        $form->setAuthenticationService($authentication);

        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                //  Get the user ID and email address
                $userId = $this->getUser()->id();
                $email = $form->getData()['email'];

                $service = $this->getUserService();

                $result = $service->requestEmailUpdate($userId, $email, $currentAddress);

                if ($result === true) {
                    return (new ViewModel([
                        'email' => $form->getData()['email']
                    ]))->setTemplate('application/authenticated/change-email-address/email-sent.twig');
                } else {
                    $error = $result;
                }
            }
        }

        return new ViewModel(compact('form', 'error', 'currentAddress'));
    }
}
