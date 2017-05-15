<?php

namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

class WhenLpaStartsForm extends AbstractLpaForm
{
    protected $formElements = [
        'when' => [
            'type'      => 'Radio',
            'required'  => true,
            'options'   => [
                'value_options' => [
                    'now' => [
                        'value' => 'now',
                    ],
                    'no-capacity' => [
                        'value' => 'no-capacity',
                    ],
                ],
            ],
        ],
        'submit' => [
            'type' => 'Submit',
        ],
    ];

    public function init()
    {
        $this->setName('form-when-lpa-starts');

        parent::init();
    }

    /**
     * Validate form input data through model validators
     *
     * @return array
     */
    public function validateByModel()
    {
        $decisions = new PrimaryAttorneyDecisions($this->data);

        $validation = $decisions->validate(['when']);

        $messages = [];

        if ($validation->hasErrors()) {
            $messages = $this->modelValidationMessageConverter($validation);
        }

        return [
            'isValid'  => !$validation->hasErrors(),
            'messages' => $messages,
        ];
    }
}
