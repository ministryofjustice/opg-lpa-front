<?php
namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;

class IndexController extends AbstractLpaController
{
    
    public function indexAction()
    {
        $destinationRoute = $this->getFlowChecker()->check('lpa/view-docs');
        $this->redirect()->toRoute($destinationRoute, ['lpa-id'=>$this->getLpa()->id]);
    }
}
