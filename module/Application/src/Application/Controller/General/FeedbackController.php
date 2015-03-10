<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller\General;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;
use Application\Form\General\FeedbackForm;
use Zend\Session\Container;

class FeedbackController extends AbstractBaseController
{
    public function indexAction()
    {
        $container = new Container('feedback');
        
        $form = new FeedbackForm();
        
        $model = new ViewModel([
            'form'=>$form,
            'pageTitle' => 'Send Feedback'
        ]);
        
        $model->setTemplate('application/feedback/index.phtml');
        
        $request = $this->getRequest();
        
        if ($request->isPost()) {
        
            $form->setData($request->getPost());
        
            if ($form->isValid()) {
                
                $feedbackService = $this->getServiceLocator()->get('Feedback');
                $data = $form->getData();
                
                $feedbackService->sendMail([
                    'rating' => $data['rating'],
                    'details' => $data['details'],
                    'email' => $data['email'],
                    'fromPage' => $container->feedbackLinkClickedFromPage,
                ]);
                
                $model->setTemplate('application/feedback/thankyou.phtml');
            }
        } else {
            $container->setExpirationHops(1);
            $container->feedbackLinkClickedFromPage = $this->getRequest()->getHeader('Referer')->uri()->getPath();
        }
        
        return $model;
    }
}
