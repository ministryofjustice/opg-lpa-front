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

use Omnipay\Omnipay;
use Zend\View\Helper\ServerUrl;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Application\Model\Service\Payment\Helper\LpaIdHelper;

use GuzzleHttp\Client as GuzzleClient;

class PaymentController extends AbstractLpaController
{
    protected $contentHeader = 'registration-partial.phtml';
    
    /**
     * Gathers the LPA information and forwards the payment request to Worldpay
     * Uses the Omnipay purchase interface to obtain a URL to which to redirect
     * the user for payment.
     */
    public function indexAction()
    {
        $lpa = $this->getLpa();
        
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        
        // session container for storing online payment email address 
        $container = new Container('paymentEmail');
        
        // make payment by cheque
        if($this->params()->fromQuery('pay-by-cheque')) {
        
            $lpa->payment->method = Payment::PAYMENT_TYPE_CHEQUE;
        
            if(!$this->getLpaApplicationService()->setPayment($lpa->id, $lpa->payment)) {
                throw new \RuntimeException('API client failed to set payment details for id: '.$lpa->id . ' in FeeReductionController');
            }
        
            // send email
            $communicationService = $this->getServiceLocator()->get('Communication');
            $communicationService->sendRegistrationCompleteEmail($lpa, $this->url()->fromRoute('lpa/view-docs', ['lpa-id' => $lpa->id], ['force_canonical' => true]));
        
            // to complete page
            return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpa->id]);
            
        }
        elseif($this->params()->fromQuery('retry') && 
            ($lpa->payment->method = Payment::PAYMENT_TYPE_CARD) && 
            ($container->email != null)) {
            
            return $this->payOnline($lpa);
        }
        
        // Payment form page
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\PaymentForm');
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            // set data for validation
            $form->setData($postData);
            
            if($form->isValid()) {
                
                $lpa->payment->method = Payment::PAYMENT_TYPE_CARD;
                
                // persist data
                if(!$this->getLpaApplicationService()->setPayment($lpa->id, $lpa->payment)) {
                    throw new \RuntimeException('API client failed to set repeat case number for id: '.$lpa->id);
                }
                
                // set paymentEmail in session container.
                $container->email = $form->getData()['email'];
                
                return $this->payOnline($lpa);
                
            } // if($form->isValid())
        }
        else {
            // when landing on payment page, show the payment form
            
            $data = [];
            if($this->getLpa()->payment instanceof Payment) {
                $data['method'] =  $this->getLpa()->payment->method;
            }
            
            $container = new Container('paymentEmail');
            if(isset($container->email)) {
                $data['email'] = $container->email;
            }
            
            $form->bind($data);
        }
        
        return new ViewModel([
                'form'=>$form,
                'payByChequeRoute' => $this->url()->fromRoute('lpa/payment', ['lpa-id'=>$this->getLpa()->id], ['query'=>['pay-by-cheque'=>true]]),
        ]);
        
    }


    public function responseAction(){

        $container = new Container('Payment');

        $client = new GuzzleClient();

        $response = $client->get( $container->details['_links']['self']['href'] ,[
            'headers' => [
                'accept' => 'application/json',
                'authorization' => 'Bearer 020ec61b-2be1-4d13-86e9-00a55ae05463',
            ]
        ]);

        $json = $response->json();

        //-----------------------

        $lpa = $this->getLpa();

        if( $json['status'] !== 'SUCCEEDED' ) {

            // FAILED
            return $this->forward()->dispatch('Authenticated\Lpa\PaymentController', array('action' => 'failure'));

        }

        //---

        $payment = $lpa->payment;
        $payment->reference = $json['reference'];
        $payment->date = new \DateTime( $json['created_date'] );

        $this->getServiceLocator()->get('ApiClient')->setPayment($lpa->id, $payment);

        // send email
        $communicationService = $this->getServiceLocator()->get('Communication');
        $communicationService->sendRegistrationCompleteEmail($lpa, $this->url()->fromRoute('lpa/view-docs', ['lpa-id' => $lpa->id], ['force_canonical' => true]));

        return $this->redirect()->toRoute('lpa/complete', ['lpa-id'=>$this->getLpa()->id]);

    }
    
    public function successAction()
    {
        $paymentService = $this->getServiceLocator()->get('Payment');
        
        $params = $this->getSuccessParams();
        
        $lpa = $this->getLpa();
        
        $paymentService->verifyMacString($params, $lpa->id);
        $paymentService->verifyOrderKey($params, $lpa->id);
        
        $paymentService->updateLpa($params, $lpa);
        
        // send email
        $communicationService = $this->getServiceLocator()->get('Communication');
        $communicationService->sendRegistrationCompleteEmail($lpa, $this->url()->fromRoute('lpa/view-docs', ['lpa-id' => $lpa->id], ['force_canonical' => true]));
        
        return $this->redirect()->toRoute('lpa/complete', ['lpa-id'=>$this->getLpa()->id]);
    }
    

    public function failureAction()
    {
        return new ViewModel([
                'retryUrl' => $this->url()->fromRoute('lpa/payment', ['lpa-id'=>$this->getLpa()->id], ['query'=>['retry'=>true]]),
                'paymentUrl' => $this->url()->fromRoute('lpa/payment', ['lpa-id'=>$this->getLpa()->id]),
        ]);
    }
    
    public function cancelAction()
    {
        return new ViewModel([
                'retryUrl' => $this->url()->fromRoute('lpa/payment', ['lpa-id'=>$this->getLpa()->id], ['query'=>['retry'=>true]]),
                'paymentUrl' => $this->url()->fromRoute('lpa/payment', ['lpa-id'=>$this->getLpa()->id]),
        ]);
    }

    private function payOnline(Lpa $lpa)
    {

        $baseUri = (new ServerUrl())->__invoke(false);

        $callback =  $baseUri . $this->url()->fromRoute(
            'lpa/payment/response',
            ['lpa-id' => $this->getLpa()->id]
        );

        //---

        $lpa = $this->getLpa();
        $donorName = (string)$lpa->document->donor->name;

        //---

        $client = new GuzzleClient();


        $response = $client->post( 'https://publicapi.pymnt.uk/v1/payments' ,[
            'headers' => [
                'accept' => 'application/json',
                'authorization' => 'Bearer 020ec61b-2be1-4d13-86e9-00a55ae05463',
                'content-type' => 'application/json',
            ],
            'json' => [
                'return_url' => $callback,
                'description' => 'LPA for ' . $donorName,
                'reference' => LpaIdHelper::constructWorldPayTransactionId($lpa->id),
                'amount' => (int)($lpa->payment->amount * 100), // amount in pence
            ]
        ]);

        //---

        $container = new Container('Payment');

        $container->details = $json = $response->json();

        $redirect = $json['_links']['next_url']['href'];

        $this->redirect()->toUrl($redirect);

        return $this->getResponse();

    } // function
}
