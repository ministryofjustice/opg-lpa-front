<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Correspondence;

class CorrespondentForm extends AbstractActorForm
{
    protected $formElements = [
            'who'        => [
                    'type' => 'Select',
                    'options' => [
                        'value_options' => [
                            'donor' => [
                                'value' => 'donor',
                                'label' => 'Donor',
                            ],
                            'attorney' => [
                                'value' => 'attorney',
                                'label' => 'Attorney',
                            ],
                            'other' => [
                                'value' => 'other',
                                'label' => 'Other',
                            ]
                        ],
                    ],
            ],
            'name-title' => [
                    'type' => 'Text',
            ],
            'name-first' => [
                    'type' => 'Text',
            ],
            'name-last' => [
                    'type' => 'Text',
            ],
            'company' => [
                    'type' => 'Text',
            ],
            'email-address' => [
                    'type' => 'Email',
                    'validators' => [
                        [
                            'name' => 'EmailAddress',
                        ]
                    ],
            ],
            'phone-number' => [
                    'type' => 'Text',
            ],
            'address-address1' => [
                    'type' => 'Text',
            ],
            'address-address2' => [
                    'type' => 'Text',
            ],
            'address-address3' => [
                    'type' => 'Text',
            ],
            'address-postcode' => [
                    'type' => 'Text',
            ],
            'submit' => [
                    'type' => 'Zend\Form\Element\Submit',
            ],
    ];
    
    public function init ()
    {
        $this->setName('form-correspondent');
        
        parent::init();
    }
    
   /**
    * Validate form input data through model validators.
    * 
    * @return [isValid => bool, messages => [<formElementName> => string, ..]]
    */
    public function validateByModel()
    {
        $this->actorModel = new Correspondence();
        
        return parent::validateByModel();
    }
}
