<?php

namespace Application\Form\Lpa;

use Zend\Form\Form;

class SeedDetailsPickerForm extends Form
{
    public function __construct($name, $options)
    {
        //  Populate the pick details value options from the seed details
        $pickDetailsValueOptions = [];

        if (array_key_exists('seedDetails', $options)) {
            foreach ($options['seedDetails'] as $idx => $actor) {
                $pickDetailsValueOptions[$idx] = $actor['label'];
            }

            unset($options['seedDetails']);
        }

        //  Trigger the parent constructor now
        parent::__construct('form-seed-details-picker', $options);

        //  Set the method to GET
        $this->setAttribute('method', 'GET');

        //  Add the required inputs
        $this->add([
            'name' => 'pick-details',
            'type' => 'Select',
            'required' => true,
            'options' => [
                'value_options' => $pickDetailsValueOptions,
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'Submit',
        ]);
    }
}
