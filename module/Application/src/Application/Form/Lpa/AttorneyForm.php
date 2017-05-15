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
            'type' => 'Application\Form\Fieldset\Dob',
            'validators' => [
                [
                    'name' => 'Application\Form\Validator\Date',
                ],
            ],
        ],
        'email-address' => [
            'type' => 'Email',
            'validators' => [
                [
                    'name' => 'EmailAddress',
                ]
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
        'submit' => [
            'type' => 'Submit',
        ],
    ];

    public function init()
    {
        $this->setName('form-attorney');

        //  Set the actor model so it can be used during validation
        $this->actorModel = new Human();

        parent::init();
    }
}
