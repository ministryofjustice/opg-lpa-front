<?php

namespace Application\Controller\Authenticated\Lpa;

use Opg\Lpa\DataModel\Lpa\Payment\Payment;

use Application\Controller\AbstractLpaController;
use Zend\View\Model\ViewModel;
use Zend\View\Helper\ServerUrl;

use Zend\Http\Response as HttpResponse;

class CheckoutController extends AbstractLpaController {

    public function indexAction(){

        $worldPayForm = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\PaymentForm');

        $response = $this->processWorldPayForm( $worldPayForm );

        if( $response instanceof HttpResponse ){
            return $response;
        }

        //---

        $lpa = $this->getLpa();

        return new ViewModel([
            // If it's not a while number, use money_format
            'paymentAmount' => ( floor( $lpa->payment->amount ) == $lpa->payment->amount ) ? $lpa->payment->amount : money_format('%i', $lpa->payment->amount),
            'worldpayForm' => $worldPayForm,
        ]);

    }

    public function chequeAction(){

        $lpa = $this->getLpa();

        $lpa->payment->method = Payment::PAYMENT_TYPE_CHEQUE;

        if(!$this->getLpaApplicationService()->setPayment($lpa->id, $lpa->payment)) {
            throw new \RuntimeException('API client failed to set payment details for id: '.$lpa->id . ' in CheckoutController');
        }

        //---

        return $this->finishCheckout();

    }

    public function payAction(){

        die('GDS pay');

    }

    public function confirmAction(){

        $lpa = $this->getLpa();

        // Sanity check; making sure this method isn't called if there's something to pay.
        if( $lpa->payment->amount != 0 ){
            throw new \RuntimeException('Invalid option');
        }

        //---

        return $this->finishCheckout();

    }

    private function finishCheckout(){

        $lpa = $this->getLpa();

        //---

        // Lock the LPA form future changes.
        $this->getLpaApplicationService()->lockLpa( $this->getLpa()->id );

        //---

        // Send confirmation email.
        $this->getServiceLocator()->get('Communication')
            ->sendRegistrationCompleteEmail($lpa, $this->url()
                ->fromRoute('lpa/view-docs', ['lpa-id' => $lpa->id], ['force_canonical' => true]));


        return $this->getNextSectionRedirect();

    }

    //------------------------------------------------------------------------------
    // GDS Pay



    //------------------------------------------------------------------------------
    // WorldPay

    public function worldpaySuccessAction(){

        $params = [
            'paymentStatus' => null,
            'orderKey' => null,
            'paymentAmount' => null,
            'paymentCurrency' => null,
            'mac' => null
        ];

        foreach ($params as $key => &$value) {
            if ($this->request->getQuery($key) == null) {
                throw new \Exception(
                    'Invalid success response from Worldpay. ' .
                    'Expected ' . $key . ' parameter was not found. ' .
                    $_SERVER["REQUEST_URI"]
                );
            }
            $value = $this->request->getQuery($key);
        }

        if ($params['paymentStatus'] != 'AUTHORISED') {
            throw new \Exception(
                'Invalid success response from Worldpay. ' .
                'paymentStatus was ' . $params['paymentStatus'] . ' (expected AUTHORISED)'
            );
        }

        //--------

        $paymentService = $this->getServiceLocator()->get('Payment');

        $paymentService->verifyMacString($params, $this->getLpa()->id);
        $paymentService->verifyOrderKey($params, $this->getLpa()->id);

        // The above functions throw fatal exceptions if there are any issues.

        $paymentService->updateLpa($params, $this->getLpa());

        //---

        return $this->finishCheckout();

    }

    public function worldpayCancelAction(){

        $worldPayForm = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\PaymentForm');

        $response = $this->processWorldPayForm( $worldPayForm );

        if( $response instanceof HttpResponse ){
            return $response;
        }

        //---

        // Shows cancel page
        return new ViewModel([
            'worldpayForm' => $worldPayForm,
        ]);

    }

    public function worldpayFailureAction(){

        $worldPayForm = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\PaymentForm');

        $response = $this->processWorldPayForm( $worldPayForm );

        if( $response instanceof HttpResponse ){
            return $response;
        }

        //---

        // Shows failure page
        return new ViewModel([
            'worldpayForm' => $worldPayForm,
        ]);

    }

    public function worldpayPendingAction(){

    }

    //---

    private function getWorldpayRedirect( $emailAddress ){

        $paymentService = $this->getServiceLocator()->get('Payment');

        $options = $paymentService->getOptions( $this->getLpa(), $emailAddress );

        $response = $paymentService
            ->getGateway()
            ->purchase($options)
            ->send();

        $redirectUrl = $response->getData()->reference;

        foreach( [ 'success', 'failure', 'cancel' ] as $type ){
            $redirectUrl .= "&{$type}URL=" . $this->getWorldpayRedirectCallbackEndpoint($type);
        }

        $this->redirect()->toUrl($redirectUrl);

        return $this->getResponse();

    }

    private function getWorldpayRedirectCallbackEndpoint($type) {

        $baseUri = (new ServerUrl())->__invoke(false);

        return $baseUri . $this->url()->fromRoute(
            'lpa/checkout/worldpay/return/' . $type,
            ['lpa-id' => $this->getLpa()->id]
        );

    }

    //-------------------------------------

    private function processWorldPayForm( $worldPayForm ){

        // If POST, it's a worldpay payment...
        if($this->request->isPost()) {

            $worldPayForm->setData( $this->request->getPost() );

            if($worldPayForm->isValid()) {

                $lpa = $this->getLpa();

                $lpa->payment->method = Payment::PAYMENT_TYPE_CARD;

                if(!$this->getLpaApplicationService()->setPayment($lpa->id, $lpa->payment)) {
                    throw new \RuntimeException('API client failed to set payment details for id: '.$lpa->id . ' in CheckoutController');
                }

                //---

                return $this->getWorldpayRedirect( $worldPayForm->getData()['email'] );

            }

        }

    } // processWorldPayForm

}