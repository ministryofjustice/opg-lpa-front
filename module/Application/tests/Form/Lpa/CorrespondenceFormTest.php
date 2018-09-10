<?php

namespace ApplicationTest\Form\Lpa;

use Application\Form\Lpa\CorrespondenceForm;
use ApplicationTest\Form\FormTestSetupTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class CorrespondenceFormTest extends MockeryTestCase
{
    use FormTestSetupTrait;

    /**
     * Set up the form to test
     */
    public function setUp()
    {
        $this->setUpMainFlowForm(new CorrespondenceForm());
    }

    public function testNameAndInstances()
    {
        $this->assertInstanceOf('Application\Form\Lpa\CorrespondenceForm', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractMainFlowForm', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractLpaForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractCsrfForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractForm', $this->form);
        $this->assertEquals('form-correspondence', $this->form->getName());
    }

    public function testElements()
    {
        $this->assertInstanceOf('Zend\Form\Element\Radio', $this->form->get('contactInWelsh'));
        $this->assertInstanceOf('Application\Form\Lpa\CorrespondenceFieldset', $this->form->get('correspondence'));
        $this->assertInstanceOf('Zend\Form\Element\Submit', $this->form->get('save'));
    }

    public function testValidateByModelOK()
    {
        $this->form->setData(array_merge([
            'contactInWelsh' => '0',
            'correspondence' => [
                'contactByEmail' => 'a@b.com',
                'contactByPhone' => '',
                'contactByPost' => '',
                'email-address' => '',
                'phone-number' => '',
            ],
        ], $this->getCsrfData()));

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidateByModelInvalid()
    {
        $this->form->setData(array_merge([
            'contactInWelsh' => '',
            'correspondence' => [
                'contactByEmail' => '',
                'contactByPhone' => '',
                'contactByPost' => '',
                'email-address' => '',
                'phone-number' => '',
            ],
        ], $this->getCsrfData()));

        $this->assertFalse($this->form->isValid());
        $this->assertEquals([
            'contactInWelsh' => [
                'isEmpty' => 'Value is required and can\'t be empty'
            ],
            'correspondence' => [
                'at-least-one-option-needs-to-be-selected' => 'at-least-one-option-needs-to-be-selected',
            ]
        ], $this->form->getMessages());
    }
}
