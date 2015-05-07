<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Zend\View\Model\ViewModel;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

class WhenLpaStartsController extends AbstractLpaController
{
    
    protected $contentHeader = 'creation-partial.phtml';
    
    public function indexAction()
    {
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\WhenLpaStartsForm');
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            $form->setData($postData);
            
            if($form->isValid()) {
                
                $lpaId = $this->getLpa()->id;
                $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
                
                if($this->getLpa()->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
                    $primaryAttorneyDecisions = $this->getLpa()->document->primaryAttorneyDecisions;
                }
                else {
                    $primaryAttorneyDecisions = $this->getLpa()->document->primaryAttorneyDecisions = new PrimaryAttorneyDecisions();
                }
                
                $primaryAttorneyDecisions->when = $form->get('when')->getValue();
                
                // persist data
                if(!$this->getLpaApplicationService()->setPrimaryAttorneyDecisions($lpaId, $primaryAttorneyDecisions)) {
                    throw new \RuntimeException('API client failed to set when LPA starts for id: '.$lpaId);
                }
                
                return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
            }
        }
        else {
            if($this->getLpa()->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
                $form->bind($this->getLpa()->document->primaryAttorneyDecisions->flatten());
            }
        }
        
        return new ViewModel(['form'=>$form]);
    }
}
