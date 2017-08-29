<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\TypeController;
use Application\Form\Lpa\TypeForm;
use Application\Model\Service\ApiClient\Response\Lpa;
use ApplicationTest\Controller\AbstractControllerTest;
use DateTime;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\View\Model\ViewModel;

class TypeControllerTest extends AbstractControllerTest
{
    /**
     * @var TypeController
     */
    private $controller;
    /**
     * @var MockInterface|TypeForm
     */
    private $form;
    private $postData = [
        'type' => 'property-and-financial'
    ];

    public function setUp()
    {
        $this->controller = new TypeController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(TypeForm::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\TypeForm')->andReturn($this->form);
    }

    public function testIndexActionGet()
    {
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/type/index', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(true, $result->getVariable('isChangeAllowed'));
        $this->assertEquals([
            'dimension2' => (new DateTime())->format('Y-m-d'),
            'dimension3' => 0
        ], $result->getVariable('analyticsDimensions'));
    }

    public function testIndexActionPostInvalid()
    {
        $this->setPostInvalid($this->form, $this->postData);

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/type/index', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
        $this->assertEquals(true, $result->getVariable('isChangeAllowed'));
        $this->assertEquals([
            'dimension2' => (new DateTime())->format('Y-m-d'),
            'dimension3' => 0
        ], $result->getVariable('analyticsDimensions'));
    }

    public function testIndexActionPostCreationError()
    {
        $response = new Response();

        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->request->shouldReceive('getPost')->andReturn($this->postData)->once();
        $this->form->shouldReceive('setData')->with($this->postData)->once();
        $this->form->shouldReceive('isValid')->andReturn(true)->once();
        $this->lpaApplicationService->shouldReceive('createApplication')->andReturn(null)->once();
        $this->flashMessenger->shouldReceive('addErrorMessage')->with('Error creating a new LPA. Please try again.')->once();
        $this->redirect->shouldReceive('toRoute')->with('user/dashboard')->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to set LPA type for id: 5531003156
     */
    public function testIndexActionPostSetTypeException()
    {
        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->request->shouldReceive('getPost')->andReturn($this->postData)->once();
        $this->form->shouldReceive('setData')->with($this->postData)->once();
        $this->form->shouldReceive('isValid')->andReturn(true)->once();
        $lpa = FixturesData::getHwLpa();
        $this->lpaApplicationService->shouldReceive('createApplication')->andReturn($lpa)->once();
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setType')->andReturn($lpa->id, $this->postData['type'])->andReturn(false)->once();

        $this->controller->indexAction();
    }

    public function testIndexAction()
    {
        $response = new Response();

        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->request->shouldReceive('getPost')->andReturn($this->postData)->once();
        $this->form->shouldReceive('setData')->with($this->postData)->once();
        $this->form->shouldReceive('isValid')->andReturn(true)->once();
        $lpa = new Lpa();
        $lpa->id = 123;
        $lpa->document = new Document();
        $lpa->document->type = $this->postData['type'];
        $this->lpaApplicationService->shouldReceive('createApplication')->andReturn($lpa)->once();
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setType')->andReturn($lpa->id, $this->postData['type'])->andReturn(true)->once();

        $this->setMatchedRouteName($this->controller, 'lpa/form-type');
        $this->redirect->shouldReceive('toRoute')->with('lpa/donor', ['lpa-id' => $lpa->id], ['fragment' => 'current'])->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

}