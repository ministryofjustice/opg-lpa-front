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
use Application\Form\Lpa\WhenReplacementAttorneyStepInForm;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;

class WhenReplacementAttorneyStepInController extends AbstractLpaController
{
    
    protected $contentHeader = 'creation-partial.phtml';
    
    public function indexAction()
    {
        $form = new WhenReplacementAttorneyStepInForm();
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            // set data for validation
            $form->setData($postData);
            
            if($form->isValid()) {
                
                $lpaId = $this->getLpa()->id;
                $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
                
                $replacementAttorneyDecisions = new ReplacementAttorneyDecisions(['when' => $form->get('when')->getValue()]);
                
                // persist data
                if(!$this->getLpaApplicationService()->setReplacementAttorneyDecisions($lpaId, $replacementAttorneyDecisions)) {
                    throw new \RuntimeException('API client failed to set replacement step in decisions for id: '.$lpaId);
                }
                
                $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
            }
        }
        else {
            if($this->getLpa()->document->replacementAttorneyDecisions instanceof ReplacementAttorneyDecisions) {
                $form->bind($this->getLpa()->document->replacementAttorneyDecisions->flatten());
            }
        }
        
        return new ViewModel(['form'=>$form]);
    }
}
