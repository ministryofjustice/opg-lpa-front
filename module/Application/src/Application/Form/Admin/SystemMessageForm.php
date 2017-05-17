<?php

namespace Application\Form\Admin;

use Application\Form\AbstractForm;
use Zend\Validator\StringLength;

/**
 * For an admin to set the system message
 *
 * Class SystemMessageForm
 * @package Application\Form\Admin
 */
class SystemMessageForm extends AbstractForm
{
    private $maxMessageLength = 8000;

    public function init()
    {
        $this->setName('admin-system-message');

        $this->add([
            'name' => 'message',
            'type' => 'Textarea',
        ]);

        //  Add data to the input filter
        $this->addToInputFilter([
            'name'     => 'message',
            'required' => false,
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'max' => $this->maxMessageLength,
                        'messages' => [
                             StringLength::TOO_LONG => 'Please limit the message to ' . $this->maxMessageLength . ' chars.',
                         ],
                    ],
                ],
            ],
        ]);

        parent::init();
    }
}
