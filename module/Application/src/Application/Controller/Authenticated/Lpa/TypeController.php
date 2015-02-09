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
use Application\Form\Lpa\TypeForm;
use Zend\View\Model\ViewModel;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Document;

class TypeController extends AbstractLpaController
{
    
    protected $contentHeader = 'creation-partial.phtml';
    
    public function indexAction()
    {
        $form = new TypeForm();
        
        if(($this->getLpa() instanceof Lpa) && ($this->getLpa()->document instanceof Document)) {
            $form->bind($this->getLpa()->document);
        }
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            // set data for validation
            $form->setData($postData);
            
            if($form->isValid()) {
                
                $lpaId = $this->getEvent()->getRouteMatch()->getParam('lpa-id');
                
                // persist data
                if(!$this->lpaService->setType($lpaId, $form->get('type')->getValue())) {
                    throw new \RuntimeException('API client failed to set LPA type for id: '.$lpaId);
                }
                
                $this->redirect()->toRoute('lpa/donor', ['lpa-id' => $lpaId]);
            }
        }
        
        return new ViewModel(['form'=>$form]);
    }
}
