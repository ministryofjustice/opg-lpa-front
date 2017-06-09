<?php

namespace ApplicationTest\Form\Lpa;

use Application\Form\Lpa\TypeForm;
use ApplicationTest\Form\FormTestSetupTrait;
use Opg\Lpa\DataModel\Lpa\Document\Document;

class TypeFormTest extends \PHPUnit_Framework_TestCase
{
    use FormTestSetupTrait;

    /**
     * Set up the form to test
     */
    public function setUp()
    {
        $this->setUpForm(new TypeForm());
    }

    public function testNameAndInstances()
    {
        $this->assertInstanceOf('Application\Form\Lpa\TypeForm', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractLpaForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractCsrfForm', $this->form);
        $this->assertEquals('form-type', $this->form->getName());
    }

    public function testElements()
    {
        $this->assertInstanceOf('Application\Form\Element\Type', $this->form->get('type'));
        $this->assertInstanceOf('Zend\Form\Element\Submit', $this->form->get('submit'));
    }

    public function testValidateByModelOK()
    {
        $this->form->setData([
            'type' => Document::LPA_TYPE_HW,
        ]);

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidateByModelInvalid()
    {
        $this->form->setData([
            'type' => 'invalid-lpa-type',
        ]);

        $this->assertFalse($this->form->isValid());
        $this->assertEquals([
            'type' => [
                0 => 'allowed-values:property-and-financial,health-and-welfare'
            ]
        ], $this->form->getMessages());
    }
}
