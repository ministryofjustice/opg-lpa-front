<?php

namespace Application\Controller\General;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;

class LoginController extends AbstractBaseController
{
    public function indexAction()
    {
        return new ViewModel();
    }
}
