<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\AuthController;
use Application\Form\User\Login;
use Application\Model\Service\Authentication\Identity\User;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Zend\Http\Header\Cookie;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class AuthControllerCookieTest extends AbstractControllerTest
{
    /**
     * @var AuthController
     */
    private $controller;
    /**
     * @var User
     */
    private $identity;

    public function setUp()
    {
        $this->controller = new AuthController();
        parent::controllerSetUp($this->controller);

        $this->identity = Mockery::mock(User::class);
    }

    public function testIndexActionAlreadySignedIn()
    {
        $response = new Response();

        $this->authenticationService->shouldReceive('getIdentity')->andReturn($this->identity)->once();
        $this->redirect->shouldReceive('toRoute')->with('user/dashboard')->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionCheckCookieFails()
    {
        $response = new Response();

        $this->authenticationService->shouldReceive('getIdentity')->andReturn(null)->once();
        $this->request->shouldReceive('getMethod')->andReturn('GET');
        $this->request->shouldReceive('getCookie')->andReturn(false)->once();
        $this->params->shouldReceive('fromQuery')->with('cookie')->andReturn(1)->once();
        $this->redirect->shouldReceive('toRoute')->with('enable-cookie')->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionCheckCookieRedirect()
    {
        $response = new Response();

        $this->authenticationService->shouldReceive('getIdentity')->andReturn(null)->once();
        $this->request->shouldReceive('getMethod')->andReturn('GET');
        $this->request->shouldReceive('getCookie')->andReturn(false)->once();
        $this->params->shouldReceive('fromQuery')->with('cookie')->andReturn(null)->once();
        $this->redirect->shouldReceive('toRoute')->with('login', array(), ['query' => ['cookie' => '1']])->andReturn($response)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionCheckCookieExistsFalse()
    {
        $cookie = Mockery::mock(Cookie::class);
        $response = new Response();

        $this->authenticationService->shouldReceive('getIdentity')->andReturn(null)->once();
        $this->params->shouldReceive('fromQuery')->with('cookie')->andReturn(null)->once();
        $this->redirect->shouldReceive('toRoute')->with('login', array(), ['query' => ['cookie' => '1']])->andReturn($response)->once();

        $cookie->shouldReceive('offsetExists')->with('lpa')->andReturn(false)->once();

        $this->request->shouldReceive('getMethod')->andReturn('GET')->once();
        $this->request->shouldReceive('getCookie')->andReturn($cookie)->once();

        $result = $this->controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testIndexActionCheckCookieExists()
    {
        $cookie = Mockery::mock(Cookie::class);
        $loginForm = new Login();

        $this->authenticationService->shouldReceive('getIdentity')->andReturn(null)->once();
        $this->url->shouldReceive('fromRoute')->with('login')->andReturn('login')->once();
        $this->formElementManager->shouldReceive('get')->with('Application\Form\User\Login')->andReturn($loginForm)->once();

        $cookie->shouldReceive('offsetExists')->with('lpa')->andReturn(true)->once();

        $this->request->shouldReceive('getMethod')->andReturn('GET')->once();
        $this->request->shouldReceive('getCookie')->andReturn($cookie)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals($loginForm, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('authError'));
        $this->assertEquals(false, $result->getVariable('isTimeout'));
    }

    public function testIndexActionCheckCookiePost()
    {
        $loginForm = new Login();

        $this->authenticationService->shouldReceive('getIdentity')->andReturn(null)->once();
        $this->url->shouldReceive('fromRoute')->with('login')->andReturn('login')->once();
        $this->formElementManager->shouldReceive('get')->with('Application\Form\User\Login')->andReturn($loginForm)->once();

        $this->request->shouldReceive('getMethod')->andReturn('POST')->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals($loginForm, $result->getVariable('form'));
        $this->assertEquals(null, $result->getVariable('authError'));
        $this->assertEquals(false, $result->getVariable('isTimeout'));
    }
}