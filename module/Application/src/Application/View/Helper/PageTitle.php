<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class PageTitle extends AbstractHelper
{
    public function __invoke()
    {
        switch ($this->view->routeName()) {
            case 'login': return 'Sign in';
            case 'enable-cookie': return 'Enable Cookies';
            case 'register': return 'Create an account';
            case 'home' : return 'Make a lasting power of attorney';
            case 'user/dashboard' : return 'Dashboard'; 
            default: return '@Todo - page title unknown';
        }
    }
}