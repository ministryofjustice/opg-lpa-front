<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Correspondence;
use Opg\Lpa\DataModel\Lpa\Elements\EmailAddress;
use Opg\Lpa\DataModel\Lpa\Elements\PhoneNumber;

class CorrespondenceForm extends AbstractActorForm
{
    private $lpa;
    
    public function __construct($name, $options)
    {
        if(array_key_exists('lpa', $options)) {
            $this->lpa = $options['lpa'];
            unset($options['lpa']);
        }
    
        parent::__construct($name, $options);
    }
    
    public function init ()
    {
        $this->setName('correspondence');
        $this->formElements = [
                'correspondence' => [
                        'type' => 'Application\Form\Lpa\CorrespondenceFieldset',
                        'options' => [
                                'checked_value' => true,
                                'unchecked_value' => false,
                        ],
                        'validators' => array(
                                new \Application\Form\Lpa\Validator\Correspondence(),
                        ),
                ],
                'submit' => [
                        'type' => 'Zend\Form\Element\Submit',
                ],
        ];        
        parent::init();
    }
    
   /**
    * Validate form input data through model validators.
    * 
    * @return [isValid => bool, messages => [<formElementName> => string, ..]]
    */
    public function validateByModel()
    {
        $error = ['correspondence' => []];
        
        if($this->data['correspondence']['contactByEmail'] == "1") {
            if(($this->lpa->document->correspondent instanceof Correspondence) && !($this->lpa->document->correspondent->email instanceof EmailAddress)) {
                $error['correspondence']['contactByEmail'] = ["Email address is not provided"];
            }
        }
        
        if($this->data['correspondence']['contactByPhone'] == "1") {
            if(!($this->lpa->document->correspondent->phone instanceof PhoneNumber)) {
                $error['correspondence']['contactByPhone'] = ["Phone number is not provided"];
            }
        }
        
        $correspondent = new Correspondence([
                'contactByPost' => (bool)$this->data['correspondence']['contactByPost'],
                'contactByInWelsh' => (bool)$this->data['correspondence']['contactInWelsh'],
        ]);
        
        $modelValidation = $correspondent->validate(['contactByPost', 'contactInWelsh']);
        
        if(count($modelValidation) == 0) {
            if(count($error['correspondence']) == 0) {
                return ['isValid'=>true, 'messages' => []];
            }
            else {
                return [
                        'isValid'=>false,
                        'messages' => $error,
                ];
            }
        }
        else {
            return [
                    'isValid'=>false,
                    'messages' => array_merge(['correspondence' => $this->modelValidationMessageConverter($modelValidation)], $error),
            ];
        }
    }
    
}
