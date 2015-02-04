<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class PageTitle extends AbstractHelper
{
    public function __invoke()
    {
        switch ($this->view->routeName()) {
            case 'login': return 'Sign in';
            case 'register': return 'Create an account';
            case 'home' : return 'Make a lasting power of attorney';
            default: '@Todo - page title unknown';
        }
    }
}