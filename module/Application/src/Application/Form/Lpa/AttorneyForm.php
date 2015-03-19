<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;

class AttorneyForm extends AbstractActorForm
{
    protected $formElements = [
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
                    'type' => 'Date',
            ],
            'email-address' => [
                    'type' => 'Email',
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
    
    public function __construct ($formName = 'primary-attorney')
    {
        
        parent::__construct($formName);
        
    }
    
    public function validateByModel()
    {
        $this->actorModel = new Human();
        
        return parent::validateByModel();
    }
}
