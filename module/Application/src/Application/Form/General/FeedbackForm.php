<?php

namespace Application\Form\General;

use Application\Form\AbstractForm;
use Zend\Validator\NotEmpty;
use Zend\Validator\StringLength;

/**
 * To send feedback to the OPG
 *
 * Class Feedback
 * @package Application\Form\General
 */
class FeedbackForm extends AbstractForm
{
    private $maxFeedbackLength = 2000;

    public function init()
    {
        $this->setName('send-feedback');

        $this->add([
            'name'    => 'rating',
            'type'    => 'Radio',
            'options' => [
                'value_options' => [
                    'very-satisfied' => [
                        'value' => 'very-satisfied',
                    ],
                    'satisfied' => [
                        'value' => 'satisfied',
                    ],
                    'neither-satisfied-or-dissatisfied' => [
                        'value' => 'neither-satisfied-or-dissatisfied',
                    ],
                    'dissatisfied' => [
                        'value' => 'dissatisfied',
                    ],
                    'very-dissatisfied' => [
                        'value' => 'very-dissatisfied',
                    ],
                ],
                'disable_inarray_validator' => true,
            ],
        ]);

        $this->add([
            'name' => 'details',
            'type' => 'Textarea',
        ]);

        $this->add([
            'name' => 'email',
            'type' => 'Email',
        ]);

        $this->add([
            'name' => 'phone',
            'type' => 'Text',
        ]);

        //  Add data to the input filter
        $this->addToInputFilter([
            'name'          => 'rating',
            'error_message' => 'cannot-be-empty',
        ]);

        $this->addToInputFilter([
            'name'     => 'details',
            'validators' => [
                [
                    'name'    => 'NotEmpty',
                    'break_chain_on_failure' => true,
                    'options' => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => 'cannot-be-empty',
                        ],
                    ],
                ],
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'max' => $this->maxFeedbackLength,
                        'messages' => [
                             StringLength::TOO_LONG => 'max-' . $this->maxFeedbackLength . '-chars',
                         ],
                    ],
                ],
            ],
        ]);

        $this->addToInputFilter([
            'name'     => 'email',
            'required' => false,
            'validators' => [
                [
                    'name' => 'EmailAddress'
                ],
            ],
        ]);

        $this->addToInputFilter([
            'name'     => 'phone',
            'required' => false,
        ]);

        parent::init();
    }
}
