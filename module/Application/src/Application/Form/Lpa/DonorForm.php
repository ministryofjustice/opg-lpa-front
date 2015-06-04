<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Donor;

class DonorForm extends AbstractActorForm
{
    protected $formElements;
    
    public function init ()
    {
        $this->formElements = [
            'name-title' => [
                    'type' => 'Text',
            ],
            'name-first' => [
                    'type' => 'Text',
            ],
            'name-last' => [
                    'type' => 'Text',
            ],
            'otherNames' => [
                    'type' => 'Text',
            ],
            'dob-date' => [
                    'type' => 'Application\Form\Fieldset\Dob',
                    'validators' => [
                        [
                            'name'    => 'Application\Form\Validator\Date',
                        ],
                    ],
            ],
            'email-address' => [
                    'type' => 'Text',
                    'validators' => [
                        [
                            'name'    => 'EmailAddress',
                        ],
                    ],
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
            'canSign' => [
                    'type' => 'CheckBox',
                    'options' => [
                            'checked_value' => false,
                            'unchecked_value' => true,
                    ],
            ],
            'submit' => [
                    'type' => 'Zend\Form\Element\Submit',
                    
            ],
        ];
        
        $this->setName('donor');
        
        parent::init();
    }
    
   /**
    * Validate form input data through model validators.
    * 
    * @return [isValid => bool, messages => [<formElementName> => string, ..]]
    */
    public function validateByModel()
    {
        $this->actorModel = new Donor();
        
        return parent::validateByModel();
    }
}
