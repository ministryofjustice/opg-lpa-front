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
use Application\Form\WhenLpaStartsForm;
use Zend\View\Model\ViewModel;

class WhenLpaStartsController extends AbstractLpaController
{
    
    protected $contentHeader = 'creation-partial.phtml';
    
    public function indexAction()
    {
        $form = new WhenLpaStartsForm();
        
        $form->bind(new \ArrayObject(['whenLpaStarts'=>$this->getLpa()->document->primaryAttorneyDecisions->when]));
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            $form->setData($postData);
            
            if($form->isValid()) {
                
//                 $this->redirect('lpa/donor', ['lpa-id'=>$this->request->getPost('lpa-id')]);
            }
        }
        
        return new ViewModel(['form'=>$form]);
    }
}
