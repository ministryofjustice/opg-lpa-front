<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\WhoAreYou\WhoAreYou;
use Opg\Lpa\DataModel\Validator\ValidatorResponse;
class WhoAreYouForm extends AbstractForm
{
    protected $formElements = [
            'who' => [
                    'type' => 'Zend\Form\Element\Radio',
                    'options' => [
                            'value_options' => [
                                    'donor' => [
                                            'value' => 'donor',
                                    ],
                                    'friendOrFamily' => [
                                            'value' => 'friendOrFamily',
                                    ],
                                    'professional'   => [
                                            'value' => 'professional',
                                    ],
                                    'digitalPartner'      => [
                                            'value' => 'digitalPartner',
                                    ],
                                    'organisation'  => [
                                            'value' => 'organisation',
                                    ],
                                    'notSaid'  => [
                                            'value' => 'notSaid'
                                    ],
                            ],
                    ],
            ],
            'professional' => [
                    'type' => 'Zend\Form\Element\Radio',
                    'options' => [
                            'value_options' => [
                                    'solicitor'      => [
                                            'value' => 'solicitor',
                                    ],
                                    'will-writer'      => [
                                            'value' => 'will-writer',
                                    ],
                                    'other'      => [
                                            'value' => 'other',
                                    ],
                            ],
                    ],
            ],
            'digitalPartner' => [
                    'type' => 'Zend\Form\Element\Radio',
                    'options' => [
                            'value_options' => [
                                    'Age-Uk'      => [
                                            'value' => 'Age-Uk',
                                    ],
                                    'Alzheimer-Society'      => [
                                            'value' => 'Alzheimer-Society',
                                    ],
                                    'Citizens-Advice-Bureau'      => [
                                            'value' => 'Citizens-Advice-Bureau',
                                    ],
                            ],
                    ],
            ],
            'professional-other' => [
                    'type' => 'Text'
            ],
            'organisation' => [
                    'type' => 'Text'
            ],
            'submit' => [
                    'type' => 'Zend\Form\Element\Submit',
            ],
    ];
    
    public function __construct ()
    {
        
        parent::__construct('who-are-you');
        
    }
    
   /**
    * Validate form input data through model validators.
    * 
    * @return [isValid => bool, messages => [<formElementName> => string, ..]]
    */
    public function validateByModel()
    {
        $whoAreYou = new WhoAreYou($this->convertFormDataForModel($this->data));
        
        $validation = $whoAreYou->validate();
        
        if(count($validation) == 0) {
            return ['isValid'=>true, 'messages' => []];
        }
        else {
            return [
                    'isValid'=>false,
                    'messages' => $this->modelValidationMessageConverter($validation, $this->data),
            ];
        }
    }
    
    /**
     * Convert form data to model-compatible input data format.
     * 
     * @param array $formData. e.g. ['who'=>'professional','professional'=>'solicitor', 'professional-other'=>null, 'digitalPartner'=>null, 'orgaisation'=>null]
     * 
     * @return array. e.g. ['who'=>'prefessiona', 'subquestion'=>'solicitor', 'qualifier'=>null]
     */
    protected function convertFormDataForModel($formData)
    {
        $modelData = [];
        if(array_key_exists($formData['who'], WhoAreYou::options())) {
            $modelData['who'] = $formData['who'];
            switch($formData['who']) {
                case 'professional':
                    $modelData['subquestion'] = $formData['professional'];
                    if($formData['professional'] == 'other') {
                        $modelData['qualifier'] = $formData['professional-other'];
                    }
                    else {
                        $modelData['qualifier'] = null;
                    }
                    break;
                case 'digitalPartner' :
                    $modelData['subquestion'] = $formData['digitalPartner'];
                    $modelData['qualifier'] = null;
                    break;
                case 'organisation' :
                    $modelData['subquestion'] = null;
                    $modelData['qualifier'] = $formData['organisation'];
                    break;
                default:
                    $modelData['subquestion'] = null;
                    $modelData['qualifier'] = null;
            }
        }
        
        return $modelData;
    }
    
    /**
     * Convert model validation response to Zend Form validation messages format.
     */
    protected function modelValidationMessageConverter(ValidatorResponse $validation, $context=null)
    {
        $messages = [];
        $linkIdx = 1;
        
        // loop through all form elements.
        foreach($validation as $validationErrorKey => $validationErrors) {
            if($validationErrorKey == 'subquestion') {
                switch($context['who']) {
                    case 'professional':
                        $fieldName = 'professional';
                        break;
                    case 'digitalPartner' :
                        $fieldName = 'digitalPartner';
                        break;
                    case 'organisation' :
                        break;
                    default:
                }
                
            }
            elseif($validationErrorKey == 'qualifier') {
                switch($context['who']) {
                    case 'professional':
                        $fieldName = 'professional-other';
                        break;
                    case 'organisation' :
                        $fieldName = 'organisation';
                        break;
                    default:
                }
                
            }
            else {
                $fieldName = $validationErrorKey;
            }
            
            $messages[$fieldName] = $validationErrors['messages'];
        }
        
        return $messages;
    }
}
