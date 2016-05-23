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
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\DataModel\Lpa\Elements\Name;

class CompleteController extends AbstractLpaController
{
    
    protected $contentHeader = 'complete-partial.phtml';
    
    public function indexAction()
    {
        $viewModel = new ViewModel(
            $this->getViewParams()
        );
        
        $viewModel->setTemplate('application/complete/complete.twig');
        
        $lpa = $this->getLpa();
        
        if (property_exists($lpa, 'metadata')) {
            
            if ($lpa->startedAt && $lpa->startedAt instanceof \DateTime) {
                $analyticsDimensions = [
                    'dimension2' => $lpa->startedAt->format('Y-m-d'),
                ];
            }
            
            if (isset($lpa->metadata['updateSessionCount'])) {
                $analyticsDimensions['dimension3'] = $lpa->metadata['updateSessionCount'];
            }
            
            $this->layout()->setVariable('analyticsDimensions', json_encode($analyticsDimensions));
        }
        
        return $viewModel;
    }
    
    public function viewDocsAction()
    {
        $this->layout()->contentHeader = 'blank-header-partial.phtml';
        return new ViewModel($this->getViewParams());
    }
    
    private function getViewParams()
    {
        $lpa = $this->getLpa();

        $payment = $this->getLpa()->payment;
        
        $isPaymentSkipped = 
            (($payment->reducedFeeUniversalCredit === true) ||
            (($payment->reducedFeeReceivesBenefits === true) && ($payment->reducedFeeAwardedDamages === true)) ||
            ($payment->method == Payment::PAYMENT_TYPE_CHEQUE));
        
        $viewParams = [
                'lp1Url'             => $this->url()->fromRoute('lpa/download', ['lpa-id'=>$lpa->id, 'pdf-type'=>'lp1']),
                'cloneUrl'           => $this->url()->fromRoute('user/dashboard/create-lpa', ['lpa-id'=>$lpa->id]),
                'dateCheckUrl'       => $this->url()->fromRoute('lpa/date-check', ['lpa-id'=>$lpa->id, 'from-page'=>'complete']),
                'correspondentName'  => (($lpa->document->correspondent->name instanceof Name)?$lpa->document->correspondent->name:$lpa->document->correspondent->company),
                'paymentAmount'      => $lpa->payment->amount,
                'paymentReferenceNo' => $lpa->payment->reference,
                'hasRemission'       => ($this->getFlowChecker()->isEligibleForFeeReduction()),
                'isPaymentSkipped'   => $isPaymentSkipped,
        ];
        
        if(count($lpa->document->peopleToNotify) > 0) {
            $viewParams['lp3Url'] = $this->url()->fromRoute('lpa/download', ['lpa-id'=>$lpa->id, 'pdf-type'=>'lp3']);
            $viewParams['peopleToNotify'] = $lpa->document->peopleToNotify;
        }
        
        if($this->getFlowChecker()->isEligibleForFeeReduction()) {
            $viewParams['lpa120Url'] = $this->url()->fromRoute('lpa/download', ['lpa-id'=>$lpa->id, 'pdf-type'=>'lpa120']);
        }
        
        
        return $viewParams;
    }
}
