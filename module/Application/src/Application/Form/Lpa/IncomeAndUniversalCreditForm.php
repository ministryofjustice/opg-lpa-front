<?php

namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Payment\Payment;

class IncomeAndUniversalCreditForm extends AbstractLpaForm
{
    protected $formElements = [
        'reducedFeeUniversalCredit' => [
            'type'      => 'Radio',
            'required'  => true,
            'options'   => [
                'value_options' => [
                    'yes' => [
                        'value' => 1,
                    ],
                    'no' => [
                        'value' => 0,
                    ],
                ],
            ],
        ],
        'reducedFeeLowIncome' => [
            'type'      => 'Radio',
            'required'  => true,
            'options'   => [
                'value_options' => [
                    'yes' => [
                        'value' => 1,
                    ],
                    'no' => [
                        'value' => 0,
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
        $this->setName('form-income-and-universal-credit');

        parent::init();
    }

    /**
     * Validate form input data through model validators
     *
     * @return array
     */
    public function validateByModel()
    {
        $lpa = new Payment([
            'reducedFeeLowIncome'       => (bool)$this->data['reducedFeeLowIncome'],
            'reducedFeeUniversalCredit' => array_key_exists('reducedFeeUniversalCredit', $this->data)?(bool)$this->data['reducedFeeUniversalCredit']:null,
        ]);

        $validation = $lpa->validate(['reducedFeeLowIncome', 'reducedFeeUniversalCredit']);

        $isValid = true;
        $messages = [];

        if (count($validation) != 0) {
            $isValid = false;
            $messages = $this->modelValidationMessageConverter($validation);
        }

        return [
            'isValid'  => $isValid,
            'messages' => $messages,
        ];
    }
}
