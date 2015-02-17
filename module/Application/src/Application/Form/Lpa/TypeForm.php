<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Document;

class TypeForm extends AbstractForm
{
    protected $formElements = [
            'type' => [
                    'type' => 'Zend\Form\Element\Radio',
                    'options' => [
                            'value_options' => [
                                    'property-and-financial' => [
                                            'value' => 'property-and-financial',
                                    ],
                                    'health-and-welfare' => [
                                            'value' => 'health-and-welfare',
                                    ]
                            ],
                    ],
            ],
            'submit' => [
                    'type' => 'Zend\Form\Element\Submit',
            ],
    ];
    
    public function __construct ($formName = 'type-form')
    {
        
        parent::__construct($formName);
        
    }
    
    public function modelValidation()
    {
        $document = new Document($this->modelization($this->data));
        
        $validation = $document->validate(['type']);
        
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
