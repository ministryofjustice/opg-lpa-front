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

    /**
     * SeedDetailsPickerForm constructor
     *
     * @param int|null|string $name
     * @param array $options
     */
    public function __construct($name, $options)
    {
        //  Populate the reuse details value options from the seed details
        $reuseDetailsValueOptions = [];

        if (array_key_exists('seedDetails', $options)) {
            foreach ($options['seedDetails'] as $idx => $actor) {
                $reuseDetailsValueOptions[] = $this->getValueOption($actor['label'], $idx);
            }

            //  If there is more than one value option then add a none of the above option also
            if (count($reuseDetailsValueOptions) > 1) {
                $reuseDetailsValueOptions[] = $this->getValueOption('None of the above', -1);
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
            'type' => 'Radio',
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
     * Simple function to create consistent value option arrays
     *
     * @param $label
     * @param $index
     * @return array
     */
    private function getValueOption($label, $index)
    {
        return [
            'label'            => $label,
            'value'            => $index,
            'label_attributes' => [
                'class' => 'text block-label flush--left',
            ],
        ];
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
