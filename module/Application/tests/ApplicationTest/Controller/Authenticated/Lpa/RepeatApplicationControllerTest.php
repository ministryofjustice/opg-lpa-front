<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\RepeatApplicationController;
use Application\Form\Lpa\RepeatApplicationForm;
use Application\Model\Service\Authentication\Identity\User;
use ApplicationTest\Controller\AbstractControllerTest;
use DateTime;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class RepeatApplicationControllerTest extends AbstractControllerTest
{
    /**
     * @var RepeatApplicationController
     */
    private $controller;
    /**
     * @var MockInterface|RepeatApplicationForm
     */
    private $form;
    private $postDataNoRepeat = [
        'isRepeatApplication' => 'no-repeat'
    ];
    private $postDataRepeat = [
        'isRepeatApplication' => 'is-repeat',
        'repeatCaseNumber' => '12345'
    ];

    public function setUp()
    {
        $this->controller = parent::controllerSetUp(RepeatApplicationController::class);

        $this->user = FixturesData::getUser();
        $this->userIdentity = new User($this->user->id, 'token', 60 * 60, new DateTime());

        $this->form = Mockery::mock(RepeatApplicationForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\RepeatApplicationForm', ['lpa' => $this->lpa]])->andReturn($this->form);
    }

    public function testIndexActionGetNotRepeatApplication()
    {
        unset($this->lpa->metadata[Lpa::REPEAT_APPLICATION_CONFIRMED]);

        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(41, $result->getVariable('lpaRepeatFee'));
    }

    public function testIndexActionGet()
    {
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->form->shouldReceive('bind')->withArgs([[
            'isRepeatApplication' => 'is-new',
            'repeatCaseNumber'    => null,
        ]])->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(41, $result->getVariable('lpaRepeatFee'));
    }

    public function testIndexActionPostNoRepeatInvalid()
    {
        $this->setPostInvalid($this->form, $this->postDataNoRepeat);
        $this->form->shouldReceive('setValidationGroup')->withArgs(['isRepeatApplication'])->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(41, $result->getVariable('lpaRepeatFee'));
    }

    public function testIndexActionPostRepeatInvalid()
    {
        $this->setPostInvalid($this->form, $this->postDataRepeat);

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(41, $result->getVariable('lpaRepeatFee'));
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to set repeat case number for id: 91333263035
     */
    public function testIndexActionPostNoRepeatFailed()
    {
        $this->lpa->repeatCaseNumber = 12345;

        $this->setPostValid($this->form, $this->postDataNoRepeat);
        $this->form->shouldReceive('setValidationGroup')->withArgs(['isRepeatApplication'])->once();
        $this->form->shouldReceive('getData')->andReturn($this->postDataNoRepeat)->once();
        $this->lpaApplicationService->shouldReceive('deleteRepeatCaseNumber')
            ->withArgs([$this->lpa])->andReturn(false)->once();

        $this->controller->indexAction();
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to set repeat case number for id: 91333263035
     */
    public function testIndexActionPostRepeatFailed()
    {
        $this->setPostValid($this->form, $this->postDataRepeat);
        $this->form->shouldReceive('getData')->andReturn($this->postDataRepeat)->once();
        $this->lpaApplicationService->shouldReceive('setRepeatCaseNumber')
            ->withArgs([$this->lpa, $this->postDataRepeat['repeatCaseNumber']])->andReturn(false)->once();

        $this->controller->indexAction();
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to set payment details for id: 91333263035 in RepeatApplicationController
     */
    public function testIndexActionPostRepeatSetPaymentFailed()
    {
        $this->setPostValid($this->form, $this->postDataRepeat);
        $this->form->shouldReceive('getData')->andReturn($this->postDataRepeat)->once();
        $this->lpaApplicationService->shouldReceive('setRepeatCaseNumber')
            ->withArgs([$this->lpa, $this->postDataRepeat['repeatCaseNumber']])->andReturn(true)->once();
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs(function ($lpa, $payment) {
                return $lpa->id === $this->lpa->id
                    && $payment->amount === 41.0;
            })->andReturn(false)->once();

        $this->controller->indexAction();
    }

    public function testIndexActionPostNoRepeatSuccess()
    {
        $response = new Response();

        $this->lpa->repeatCaseNumber = 12345;

        $this->setPostValid($this->form, $this->postDataNoRepeat);
        $this->form->shouldReceive('setValidationGroup')->withArgs(['isRepeatApplication'])->once();
        $this->form->shouldReceive('getData')->andReturn($this->postDataNoRepeat)->once();
        $this->lpaApplicationService->shouldReceive('deleteRepeatCaseNumber')
            ->withArgs([$this->lpa])->andReturn(true)->once();
        $this->lpaApplicationService->shouldReceive('setPayment')
            ->withArgs(function ($lpa, $payment) {
                return $lpa->id === $this->lpa->id
                    && $payment->amount === 82.0;
            })->andReturn(true)->once();
        $this->metadata->shouldReceive('setRepeatApplicationConfirmed')->withArgs([$this->lpa])->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($this->controller, 'lpa/fee-reduction');
        $this->setRedirectToRoute('lpa/checkout', $this->lpa, $response);

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }
}
