<?php

namespace Application\Controller\General;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;

class HomeController extends AbstractBaseController
{
    public function indexAciton()
    {
        return new ViewModel();
    }
    
    public function redirectAction()
    {
        return new ViewModel();
    }
}
