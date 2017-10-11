<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\AboutYouController;
use Application\Form\User\AboutYou;
use Application\Model\Service\User\Details;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Common\Name;
use Opg\Lpa\DataModel\User\User;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class AboutYouControllerTest extends AbstractControllerTest
{
    /**
     * @var AboutYouController
     */
    private $controller;
    /**
     * @var MockInterface|AboutYou
     */
    private $form;
    /**
     * @var MockInterface|Details
     */
    private $aboutYouDetails;
    private $postData = [

    ];

    public function setUp()
    {
        $this->controller = new AboutYouController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(AboutYou::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\User\AboutYou')->andReturn($this->form);

        $this->aboutYouDetails = Mockery::mock(Details::class);
        $this->serviceLocator->shouldReceive('get')->with('AboutYouDetails')->andReturn($this->aboutYouDetails);
    }

    public function testIndexActionGet()
    {
        $user = $this->getUserDetails();

        //  Set up any route or request parameters
        $this->params->shouldReceive('fromRoute')->with('new', null)->andReturn(null)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        //  Set up helpers and services
        $this->url->shouldReceive('fromRoute')->with('user/about-you', [])->andReturn('/user/about-you')->once();
        $this->aboutYouDetails->shouldReceive('load')->andReturn($user)->once();

        //  Set up the form
        $this->form->shouldReceive('setAttribute')->with('action', '/user/about-you')->once();
        $this->form->shouldReceive('setData')->with($user->flatten())->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionPostInvalid()
    {
        //  Set up any route or request parameters
        $this->params->shouldReceive('fromRoute')->with('new', null)->andReturn(null)->once();

        //  Set up helpers and service
        $this->url->shouldReceive('fromRoute')->with('user/about-you', [])->andReturn('/user/about-you')->once();

        //  Set up form
        $this->setPostInvalid($this->form, $this->postData);
        $this->form->shouldReceive('setAttribute')->with('action', '/user/about-you')->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testIndexActionPostValid()
    {
        $response = new Response();

        //  Set up any route or request parameters
        $this->params->shouldReceive('fromRoute')->with('new', null)->andReturn(null)->once();

        //  Set up helpers and service
        $this->url->shouldReceive('fromRoute')->with('user/about-you', [])->andReturn('/user/about-you')->once();
        $this->aboutYouDetails->shouldReceive('updateAllDetails')->with($this->form)->once();
        $this->flashMessenger->shouldReceive('addSuccessMessage')->with('Your details have been updated.')->once();
        $this->redirect->shouldReceive('toRoute')->with('user/dashboard')->andReturn($response)->once();

        //  Set up form
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('setAttribute')->with('action', '/user/about-you')->once();

        $result = $this->controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
    }

    public function testNewActionGet()
    {
        $user = $this->getUserDetails(true);

        //  Set up any route or request parameters
        $this->params->shouldReceive('fromRoute')->with('new', null)->andReturn('new')->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        //  Set up helpers and service
        $this->url->shouldReceive('fromRoute')->with('user/about-you', [
            'new' => 'new',
        ])->andReturn('/user/about-you/new')->once();
        $this->aboutYouDetails->shouldReceive('load')->andReturn($user)->once();

        //  Set up form
        $this->form->shouldReceive('setAttribute')->with('action', '/user/about-you/new')->once();
        $this->form->shouldReceive('setData')->with($user->flatten())->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testNewActionPostValid()
    {
        $response = new Response();

        //  Set up any route or request parameters
        $this->params->shouldReceive('fromRoute')->with('new', null)->andReturn('new')->once();


        //  Set up helpers and service
        $this->url->shouldReceive('fromRoute')->with('user/about-you', [
            'new' => 'new',
        ])->andReturn('/user/about-you/new')->once();
        $this->aboutYouDetails->shouldReceive('updateAllDetails')->with($this->form)->once();
        $this->redirect->shouldReceive('toRoute')->with('user/dashboard')->andReturn($response)->once();

        //  Set up form
        $this->form->shouldReceive('setAttribute')->with('action', '/user/about-you/new')->once();
        $this->setPostValid($this->form, $this->postData);

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    /**
     * Get sample user details
     *
     * @param bool $newDetails
     * @return User
     */
    private function getUserDetails($newDetails = false)
    {
        $user = new User();

        if (!$newDetails) {
            //  Just set a name for the user details to be considered existing
            $user->name = new Name([
                'title' => 'Mrs',
                'first' => 'New',
                'last'  => 'User',
            ]);
        }

        return $user;
    }
}
