<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Zend\View\Model\ViewModel;

class StyleguideController extends AbstractBaseController
{
    public function indexAction(){
        return new ViewModel();
    }

    public function iconsAction(){
        return new ViewModel();
    }
}
