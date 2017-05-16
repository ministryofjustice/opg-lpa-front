<?php

namespace ApplicationTest\Form;

use Application\Form\AbstractForm;
use Mockery as m;

trait FormTestSetupTrait
{
    /**
     * Form to test
     *
     * @var AbstractForm
     */
    protected $form;

    /**
     * Set up the form to test
     */
    protected function setUpForm(AbstractForm $form)
    {
        //  Mock the input filter - the filter validation should pass to allow the validate by model to execute
        $inputFilter = m::mock('Zend\InputFilter\InputFilter')->makePartial();
        $inputFilter->shouldReceive('isValid')
            ->andReturn(true);

        $form->setInputFilter($inputFilter);

        //  Mock the form element manager and config
        $config = [
            'csrf' => [
                'salt' => 'Rando_Calrissian'
            ]
        ];

        $sl = m::mock('Zend\ServiceManager\ServiceLocatorInterface');
        $sl->shouldReceive('get')
            ->with('Config')
            ->andReturn($config);

        $fem = m::mock('Zend\Form\FormElementManager');
        $fem->shouldReceive('getServiceLocator')
            ->andReturn($sl);

        $form->setServiceLocator($fem);
        $form->init();

        $this->form = $form;
    }
}
