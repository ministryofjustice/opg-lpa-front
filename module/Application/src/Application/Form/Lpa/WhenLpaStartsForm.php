<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

class WhenLpaStartsForm extends AbstractForm
{
    protected $formElements = [
            'whenLpaStarts' => [
                    'type' => 'Zend\Form\Element\Radio',
                    'options' => [
                            'value_options' => [
                                    'now' => [
                                            'label' => "as soon as it's registered (with my consent)",
                                            'value' => 'now',
                                    ],
                                    'no-capacity' => [
                                            'label' => "only if I don't have mental capacity",
                                            'value' => 'no-capacity',
                                    ],
                            ],
                    ],
            ],
            'submit' => [
                    'type' => 'Zend\Form\Element\Submit',
                    'attributes' => [
                            'value' => 'Save and continue'
                    ],
                    
            ],
    ];
    
    public function __construct ($formName = 'whenLpaStarts')
    {
        
        parent::__construct($formName);
        
    }
    
    public function modelValidation()
    {
        $decisions = new PrimaryAttorneyDecisions($this->modelization($this->data));
        
        $validation = $decisions->validate(['when']);
        
        if(count($validation) == 0) {
            return ['isValid'=>true, 'messages' => []];
        }
        else {
            return [
                    'isValid'=>false,
                    'messages' => $this->modelValidationMessageConverter($validation),
            ];
        }
    }
}
