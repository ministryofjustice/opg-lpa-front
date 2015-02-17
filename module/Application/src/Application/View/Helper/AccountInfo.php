<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class AccountInfo extends AbstractHelper
{
    public function __invoke()
    {
        $auth = $this->view->getHelperPluginManager()->getServiceLocator()->get('AuthenticationService');
        $params = [
            'links' => [],
        ];
        
        if ($auth->hasIdentity()) {
            $identity = $auth->getIdentity();
            
            $params['name'] = '@todo Firstname Lastname';
            
            $params['links'][] = array(
                'text' => 'Your details',
                'url' => $this->view->url('user/about-you'),
            );
            $params['links'][] = array(
                'text' => 'Your LPAs',
                'url' => $this->view->url('user/dashboard'),
            );
            $params['links'][] = array(
                'text' => 'Sign out',
                'url' => $this->view->url('logout'),
            );
        } else {
            $params['email'] = '';
            $params['links'][] = array(
                'text' => 'Sign in',
                'url' => '/user/login'
            );
        }
        
        echo $this->view->partial('application/account-info/account-info.phtml', $params);
    }
}