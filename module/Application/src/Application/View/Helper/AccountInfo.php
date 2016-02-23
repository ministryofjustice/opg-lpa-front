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

            $details = $this->view->getHelperPluginManager()->getServiceLocator()->get('UserDetailsSession');
            
            // do not show user names and links to account details and dashboard if user is logging in the first time.
            if($details->user->name !== null) { 
                
                if( $details->user->name->first != null && $details->user->name->last != null ){
                    $params['name'] = "{$details->user->name->first} {$details->user->name->last}";
                }
    
                $params['links'][] = array(
                    'text' => 'Your details',
                    'url' => $this->view->url('user/about-you'),
                );
                $params['links'][] = array(
                    'text' => 'Your LPAs',
                    'url' => $this->view->url('user/dashboard'),
                );
            }
            
            $params['links'][] = array(
                'text' => 'Sign out',
                'url' => $this->view->url('logout'),
            );

            $params['lastLogin'] = $auth->getIdentity()->lastLogin();
            
        } else {
            $params['email'] = '';
            $params['links'][] = array(
                'text' => 'Sign in',
                'url' => '/user/login'
            );
        }
        
        if ($auth->hasIdentity()) {
            $template = $this->view->getHelperPluginManager()->getServiceLocator()->get('TwigViewRenderer')->loadTemplate('account-info/account-info.twig');
            $content = $template->render($params);
        
            echo $content;
        }
    }
}