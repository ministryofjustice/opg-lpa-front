<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
class PeopleToNotifyForm extends AbstractActorForm
{
    protected $formElements = [
            'name-title' => [
                    'type' => 'Zend\Form\Element\Select',
            ],
            'name-first' => [
                    'type' => 'Text',
            ],
            'name-last' => [
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
    
    public function __construct ($formName = 'people-to-notify')
    {
        
        parent::__construct($formName);
        
    }
    
    public function validateByModel()
    {
        $this->actor = new NotifiedPerson();
        
        return parent::validateByModel();
    }
}
