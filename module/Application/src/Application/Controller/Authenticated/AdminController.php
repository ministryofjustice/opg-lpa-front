<?php

namespace Application\Controller\Authenticated;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractAuthenticatedController;
use Zend\Mvc\MvcEvent;
use Application\Form\Admin\SystemMessageForm;

class AdminController extends AbstractAuthenticatedController
{
    /**
     * Ensure user is allowed to access admin functions
     *
     * @param  MvcEvent $e
     * @return mixed
     */
    public function onDispatch(MvcEvent $event)
    {
        $userEmail = (string)$this->getUserDetails()->email;
        
        if ($userEmail != '') {
            $adminAccounts = $this->getServiceLocator()->get('config')['admin']['accounts'];
        
            $isAdmin = in_array($userEmail, $adminAccounts);
        
            if ($isAdmin) {
                return parent::onDispatch( $event );
            }
        }
        
        return $this->redirect()->toRoute('home');
    }
    
    public function statsAction()
    {
        $apiClient = $this->getServiceLocator()->get('ApiClient');
        
        $lpaUserStats = $apiClient->getApiStats('lpasperuser');
        
        switch ($this->params()->fromQuery('by')) {
            case 'user' :
                $columns = ['Number of Users', 'Number of LPAs'];
                $byStats = $lpaUserStats['byUserCount'];
                break;
            case 'lpa' :
            default:
                $columns = ['Number of LPAs', 'Number of Users'];
                $byStats = $lpaUserStats['byLpaCount'];
                break;
        }
        
        $authStats = $apiClient->getAuthStats();
        
        return new ViewModel([
            'columns' => $columns,
            'api_stats' => $byStats,
            'auth_stats' => $authStats,
            'pageTitle' => 'Admin stats',
        ]);
    }
    
    public function systemMessageAction()
    {        
        $form = new SystemMessageForm();
        
        if ($this->request->isPost()) {
            $post = $this->request->getPost();
            
            $form->setData($post);
            
            if ($form->isValid()) {
                $this->cache()->setItem('system-message', $post['message']);
                
                return $this->redirect()->toRoute('home');
            }
        } else {
            $messageElement = $form->get('message');
            $currentMessage = $this->cache()->getItem('system-message');
            $messageElement->setValue($currentMessage);
        }
        
        return new ViewModel(['form'=>$form]);

    }
}
