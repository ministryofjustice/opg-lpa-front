<?php

return array(

    'router' => [
        'routes' => [

            /*
             * /forward
             * /service
             * /create
             * /register
             * /pdf
             * /help
             * /postcode
             * /user/is-logged-in'
             *
             */

            'proxy-dashboard' => [
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => [
                    'route'    => '/old-dashboard',
                    'defaults' => [
                        'controller' => 'V1Proxy\Controller\Access',
                        'action'     => 'index',
                    ],
                ],
            ],

            'proxy-forward' => [
                'type' => 'Zend\Mvc\Router\Http\Regex',
                'options' => [
                    'regex'    => '^/forward(.)*',
                    'spec'     => '/forward',
                    'defaults' => [
                        'controller' => 'V1Proxy\Controller\Access',
                        'action'     => 'index',
                    ],
                ],
            ],

            'proxy-help' => [
                'type' => 'Zend\Mvc\Router\Http\Regex',
                'options' => [
                    'regex'    => '^/help(.)*',
                    'spec'     => '/help',
                    'defaults' => [
                        'controller' => 'V1Proxy\Controller\Access',
                        'action'     => 'index',
                    ],
                ],
            ],

            'proxy-loggedin' => [
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => [
                    'route'    => '/user/is-logged-in',
                    'defaults' => [
                        'controller' => 'V1Proxy\Controller\Access',
                        'action'     => 'index',
                    ],
                ],
            ],

            'proxy-service' => [
                'type' => 'Zend\Mvc\Router\Http\Regex',
                'options' => [
                    'regex'    => '^/service(.)*',
                    'spec'     => '/service',
                    'defaults' => [
                        'controller' => 'V1Proxy\Controller\Access',
                        'action'     => 'index',
                    ],
                ],
            ],

            'proxy-create' => [
                'type' => 'Zend\Mvc\Router\Http\Regex',
                'options' => [
                    'regex'    => '^/create(.)*',
                    'spec'     => '/create',
                    'defaults' => [
                        'controller' => 'V1Proxy\Controller\Access',
                        'action'     => 'index',
                    ],
                ],
            ],

            'proxy-postcode' => [
                'type' => 'Zend\Mvc\Router\Http\Regex',
                'options' => [
                    'regex'    => '^/postcode(.)*',
                    'spec'     => '/postcode',
                    'defaults' => [
                        'controller' => 'V1Proxy\Controller\Access',
                        'action'     => 'index',
                    ],
                ],
            ],

            'proxy-address' => [
                'type' => 'Zend\Mvc\Router\Http\Regex',
                'options' => [
                    'regex'    => '^/address/lookup(.)*',
                    'spec'     => '/address/lookup',
                    'defaults' => [
                        'controller' => 'V1Proxy\Controller\Access',
                        'action'     => 'index',
                    ],
                ],
            ],

            'proxy-register' => [
                'type' => 'Zend\Mvc\Router\Http\Regex',
                'options' => [
                    'regex'    => '^/register(.)*',
                    'spec'     => '/register',
                    'defaults' => [
                        'controller' => 'V1Proxy\Controller\Access',
                        'action'     => 'index',
                    ],
                ],
            ],

            'proxy-pdf' => [
                'type' => 'Zend\Mvc\Router\Http\Regex',
                'options' => [
                    'regex'    => '^/pdf(.)*',
                    'spec'     => '/pdf',
                    'defaults' => [
                        'controller' => 'V1Proxy\Controller\Access',
                        'action'     => 'index',
                    ],
                ],
            ],

        ]
    ], // router

    'controllers' => [
        'invokables' => [
            'V1Proxy\Controller\Access' => 'V1Proxy\Controller\AccessController',
        ],
    ], // controllers

);
