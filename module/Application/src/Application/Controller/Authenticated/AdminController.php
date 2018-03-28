<?php

namespace Application\Controller\Authenticated;

use Application\Controller\AbstractAuthenticatedController;
use Application\Form\Admin\UserSearchForm;
use Application\Model\Service\Admin\Admin as AdminService;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\ViewModel;

class AdminController extends AbstractAuthenticatedController
{
    /**
     * @var AdminService
     */
    private $adminService;

    /**
     * Ensure user is allowed to access admin functions
     *
     * @param  MvcEvent $event
     * @return mixed
     */
    public function onDispatch(MvcEvent $event)
    {
        $userEmail = (string)$this->getUserDetails()->email;

        if ($userEmail != '') {
            $adminAccounts = $this->config()['admin']['accounts'];

            $isAdmin = in_array($userEmail, $adminAccounts);

            if ($isAdmin) {
                return parent::onDispatch($event);
            }
        }

        return $this->redirect()->toRoute('home');
    }

    public function systemMessageAction()
    {
        $form = $this->getFormElementManager()
                     ->get('Application\Form\Admin\SystemMessageForm');

        if ($this->request->isPost()) {
            $post = $this->request->getPost();

            $form->setData($post);

            if ($form->isValid()) {
                if (empty($post['message'])) {
                    $this->cache()->removeItem('system-message');
                } else {
                    $this->cache()->setItem('system-message', $post['message']);
                }

                return $this->redirect()->toRoute('home');
            }
        } else {
            $messageElement = $form->get('message');
            $currentMessage = $this->cache()->getItem('system-message');
            $messageElement->setValue($currentMessage);
        }

        return new ViewModel([
            'form' => $form
        ]);
    }

    public function userSearchAction()
    {
        /** @var UserSearchForm $form */
        $form = $this->getFormElementManager()
                     ->get('Application\Form\Admin\UserSearchForm');

        $user = false;

        if ($this->request->isPost()) {
            $post = $this->request->getPost();

            $form->setData($post);

            if ($form->isValid()) {
                $email = $post['email'];

                $result = $this->adminService->searchUsers($email);

                if ($result === false) {
                    // Set error message
                    $messages = array_merge($form->getMessages(), [
                        'email' => ['No user found for email address']
                    ]);
                    $form->setMessages($messages);
                } else {
                    $user = $result;
                }
            }
        }

        return new ViewModel([
            'form' => $form,
            'user' => $user
        ]);
    }

    public function setAdminService(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }
}
