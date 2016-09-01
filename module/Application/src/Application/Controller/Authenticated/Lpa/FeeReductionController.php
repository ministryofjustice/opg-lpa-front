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
use Opg\Lpa\DataModel\Lpa\Payment\Calculator;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Zend\Form\Element;

class FeeReductionController extends AbstractLpaController
{
    
    protected $contentHeader = 'registration-partial.phtml';
    
    public function indexAction()
    {
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\FeeReductionForm');
        $lpa = $this->getLpa();
        
        $reduction = $form->get('reductionOptions');
        
        $reductionOptions = [];
        
        $reductionOptions['reducedFeeReceivesBenefits'] = new Element('reductionOptions', [
            'label' => "The donor currently claims one of <a class=\"js-guidance\" href=\"/guide#topic-fees-and-reductions\" data-journey-click=\"stageprompt.lpa:help:fees-and-reductions\">these means-tested benefits</a>, and has not been awarded personal injury damages of more than £16,000"
        ]);
        $reductionOptions['reducedFeeReceivesBenefits']->setAttributes([
            'type' => 'radio',
            'id' => 'reducedFeeReceivesBenefits',
            'value' => $reduction->getOptions()['value_options']['reducedFeeReceivesBenefits']['value'],
            'checked' => (($reduction->getValue() == 'reducedFeeReceivesBenefits')? 'checked':null),
        ]);
        
        $reductionOptions['reducedFeeUniversalCredit'] = new Element('reductionOptions', [
            'label' => "The donor receives Universal Credit"
        ]);
        $reductionOptions['reducedFeeUniversalCredit']->setAttributes([
            'type' => 'radio',
            'id' => 'reducedFeeUniversalCredit',
            'value' => $reduction->getOptions()['value_options']['reducedFeeUniversalCredit']['value'],
            'checked' => (($reduction->getValue() == 'reducedFeeUniversalCredit')? 'checked':null),
        ]);
        
        $reductionOptions['reducedFeeLowIncome'] = new Element('reductionOptions', [
            'label' => "The donor currently has an income of less than £12,000 a year before tax",
        ]);
        $reductionOptions['reducedFeeLowIncome']->setAttributes([
            'type' => 'radio',
            'id' => 'reducedFeeLowIncome',
            'value' => $reduction->getOptions()['value_options']['reducedFeeLowIncome']['value'],
            'checked' => (($reduction->getValue() == 'reducedFeeLowIncome')? 'checked':null),
        ]);
        
        $reductionOptions['notApply'] = new Element('reductionOptions', [
            'label' => "I'm not applying for a reduced fee",
        ]);
        $reductionOptions['notApply']->setAttributes([
            'type' => 'radio',
            'id' => 'notApply',
            'value' => $reduction->getOptions()['value_options']['notApply']['value'],
            'checked' => (($reduction->getValue() == 'notApply')? 'checked':null),
        ]);
        
        if($this->request->isPost()) {
            $postData = $this->request->getPost();
            
            // set data for validation
            $form->setData($postData);
            
            if($form->isValid()) {
                
                $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
                
                // if no applying reduced fee, set payment in LPA including amount.
                switch($form->getData()['reductionOptions']) {
                    case 'reducedFeeReceivesBenefits':
                        $lpa->payment = new Payment([
                            'reducedFeeReceivesBenefits' => true,
                            'reducedFeeAwardedDamages'  => true,
                            'reducedFeeLowIncome'       => null,
                            'reducedFeeUniversalCredit' => null,
                        ]);
                        // payment date will be set by the API when setPayment() is called.
                        break;
                    case 'reducedFeeUniversalCredit':
                        $lpa->payment = new Payment([
                            'reducedFeeReceivesBenefits' => false,
                            'reducedFeeAwardedDamages'  => null,
                            'reducedFeeLowIncome'       => false,
                            'reducedFeeUniversalCredit' => true,
                        ]);
                        // payment date will be set by the API when setPayment() is called.
                        break;
                    case 'reducedFeeLowIncome':
                        $lpa->payment = new Payment([
                            'reducedFeeReceivesBenefits' => false,
                            'reducedFeeAwardedDamages'  => null,
                            'reducedFeeLowIncome'       => true,
                            'reducedFeeUniversalCredit' => false,
                        ]);
                        break;
                    case 'notApply':
                        $lpa->payment = new Payment([
                            'reducedFeeReceivesBenefits' => null,
                            'reducedFeeAwardedDamages'  => null,
                            'reducedFeeLowIncome'       => null,
                            'reducedFeeUniversalCredit' => null,
                        ]);
                        break;
                }
                
                // calculate payment amount and set payment in LPA
                Calculator::calculate($lpa);

                if(!$this->getLpaApplicationService()->setPayment($lpa->id, $lpa->payment)) {
                    throw new \RuntimeException('API client failed to set payment details for id: '.$lpa->id . ' in FeeReductionController');
                }
                
                return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpa->id]);
            }
        }
        else {
            if($lpa->payment instanceof Payment) {
                if($lpa->payment->reducedFeeReceivesBenefits && $lpa->payment->reducedFeeAwardedDamages) {
                    $reductionOptionsValue = 'reducedFeeReceivesBenefits';
                }
                elseif($lpa->payment->reducedFeeUniversalCredit) {
                    $reductionOptionsValue = 'reducedFeeUniversalCredit';
                }
                elseif($lpa->payment->reducedFeeLowIncome) {
                    $reductionOptionsValue = 'reducedFeeLowIncome';
                }
                else {
                    $reductionOptionsValue = 'notApply';
                }
                
                $form->bind([
                        'reductionOptions' => $reductionOptionsValue,
                ]);
            }
        }
        
        return new ViewModel([
                'form'=>$form,
                'reductionOptions' => $reductionOptions,
        ]);
    }
}
