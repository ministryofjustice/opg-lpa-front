<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\DateCheckController;
use Application\Form\Lpa\DateCheckForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class DateCheckControllerTest extends AbstractControllerTest
{
    /**
     * @var DateCheckController
     */
    private $controller;
    /**
     * @var MockInterface|DateCheckForm
     */
    private $form;
    private $postData = [
        'sign-date-donor'                 => ['day' => 1, 'month' => 2, 'year' => 2016],
        'sign-date-donor-life-sustaining' => ['day' => 1, 'month' => 2, 'year' => 2016],
        'sign-date-attorney-0'  => ['day' => 1, 'month' => 2, 'year' => 2016],
        'sign-date-attorney-1'  => ['day' => 1, 'month' => 2, 'year' => 2016],
        'sign-date-replacement-attorney-0'  => ['day' => 1, 'month' => 2, 'year' => 2016],
        'sign-date-replacement-attorney-1'  => ['day' => 1, 'month' => 2, 'year' => 2016],
        'sign-date-certificate-provider'  => ['day' => 1, 'month' => 2, 'year' => 2016]
    ];

    public function setUp()
    {
        $this->controller = parent::controllerSetUp(DateCheckController::class);

        $this->form = Mockery::mock(DateCheckForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\DateCheckForm', ['lpa' => $this->lpa]])->andReturn($this->form);
    }

    public function testIndexActionGet()
    {
        $this->params->shouldReceive('fromPost')->withArgs(['return-route', null])->andReturn(null)->once();
        $currentRouteName = 'lpa/date-check/complete';
        $this->setMatchedRouteName($this->controller, $currentRouteName);
        $this->setFormAction($this->form, $this->lpa, $currentRouteName);
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals('lpa/complete', $result->getVariable('returnRoute'));
    }

    public function testIndexActionPostInvalid()
    {
        $this->params->shouldReceive('fromPost')->withArgs(['return-route', null])->andReturn(null)->once();
        $currentRouteName = 'lpa/date-check/complete';
        $this->setMatchedRouteName($this->controller, $currentRouteName);
        $this->setFormAction($this->form, $this->lpa, $currentRouteName);
        $this->setPostInvalid($this->form);

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals('lpa/complete', $result->getVariable('returnRoute'));
    }

    public function testIndexActionPostInvalidDates()
    {
        //Donor must be the first to sign
        $postData = $this->postData;
        $postData['sign-date-donor']['year'] = 2017;

        $this->params->shouldReceive('fromPost')->withArgs(['return-route', null])->andReturn(null)->once();
        $currentRouteName = 'lpa/date-check/complete';
        $this->setMatchedRouteName($this->controller, $currentRouteName);
        $this->setFormAction($this->form, $this->lpa, $currentRouteName);
        $this->setPostValid($this->form, $postData);
        $this->form->shouldReceive('getData')->andReturn($postData)->once();
        $this->form->shouldReceive('setMessages')->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals('lpa/complete', $result->getVariable('returnRoute'));
        $this->assertEquals(null, $result->dateError);
    }

    public function testIndexActionPostValidDates()
    {
        $response = new Response();
        $postData = $this->postData;

        $this->params->shouldReceive('fromPost')->withArgs(['return-route', null])->andReturn(null)->once();
        $currentRouteName = 'lpa/date-check/complete';
        $this->setMatchedRouteName($this->controller, $currentRouteName);
        $this->setFormAction($this->form, $this->lpa, $currentRouteName);
        $this->setPostValid($this->form, $postData);
        $this->form->shouldReceive('getData')->andReturn($postData)->once();
        $this->url->shouldReceive('fromRoute')->withArgs([
            'lpa/date-check/valid',
            ['lpa-id' => $this->lpa->id],
            ['query' => ['return-route' => 'lpa/complete']]
        ])->andReturn("lpa/{$this->lpa->id}/date-check/valid")->once();
        $this->redirect->shouldReceive('toUrl')
            ->withArgs(["lpa/{$this->lpa->id}/date-check/valid"])->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testValidActionNoReturnRoute()
    {
        $this->params->shouldReceive('fromQuery')->withArgs(['return-route', null])->andReturn(null)->once();

        /** @var ViewModel $result */
        $result = $this->controller->validAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('user/dashboard', $result->returnRoute);
    }

    public function testValidActionReturnRoute()
    {
        $this->params->shouldReceive('fromQuery')->withArgs(['return-route', null])->andReturn('lpa/complete')->once();

        /** @var ViewModel $result */
        $result = $this->controller->validAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('lpa/complete', $result->returnRoute);
    }
}
