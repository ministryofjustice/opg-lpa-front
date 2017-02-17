<?php

namespace Application\Form\Lpa;

use Zend\Form\Form;

class SeedDetailsPickerForm extends Form
{
    /**
     * Flag to indicate if the form contains only trust data
     *
     * @var bool
     */
    private $trustDataOnly = false;

    public function __construct($name, $options)
    {
        //  Populate the reuse details value options from the seed details
        $reuseDetailsValueOptions = [];

        if (array_key_exists('seedDetails', $options)) {
            foreach ($options['seedDetails'] as $idx => $actor) {
                $reuseDetailsValueOptions[$idx] = $actor['label'];
            }

            unset($options['seedDetails']);
        }

        if (array_key_exists('trustOnly', $options)) {
            $this->trustDataOnly = (bool) $options['trustOnly'];

            unset($options['trustOnly']);
        }

        //  Trigger the parent constructor now
        parent::__construct($name, $options);

        //  Set the method to GET
        $this->setAttribute('method', 'GET');

        //  Add the required inputs
        $this->add([
            'name' => 'reuse-details',
            'type' => 'Select',
            'required' => true,
            'options' => [
                'value_options' => $reuseDetailsValueOptions,
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'Submit',
        ]);
    }

    /**
     * Simple function to indicate if the form contains trust data only
     *
     * @return bool
     */
    public function hasTrustDataOnly()
    {
        return (bool) $this->trustDataOnly;
    }
}
