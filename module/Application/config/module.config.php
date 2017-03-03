<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

return [

    /* ------------------------------------------------------------- */
    /* ------------ All routes are in module.routes.php ------------ */
    /* ------------------------------------------------------------- */

    'controllers' => [
        'initializers' => [
            'UserAwareInitializer' => 'Application\ControllerFactory\UserAwareInitializer',
            'LpaAwareInitializer' => 'Application\ControllerFactory\LpaAwareInitializer',
        ],
        'abstract_factories' => [
            'Application\ControllerFactory\ControllerAbstractFactory'
        ],
    ],

    'service_manager' => [
        'abstract_factories' => [
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'Zend\Log\LoggerAbstractServiceFactory',
        ],
        'aliases' => [
            //'translator' => 'MvcTranslator',
        ],
    ],

    /*
    'translator' => [
        'locale' => 'en_US',
        'translation_file_patterns' => [
            [
                'type'     => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
            ],
        ],
    ],
    */

    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/500'               => __DIR__ . '/../view/error/500.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],

    'email_view_manager' => array(
        'template_path_stack' => array(
            'emails' => __DIR__ . '/../view/email',
        ),
    ),

    'view_helpers' => [
        'invokables' => [
            'accordion'             => 'Application\View\Helper\Accordion',
            'accountInfo'           => 'Application\View\Helper\AccountInfo',
            'pageHeaders'           => 'Application\View\Helper\PageHeaders',
            'elementGroupClass'     => 'Application\View\Helper\ElementGroupClass',
            'routeName'             => 'Application\View\Helper\RouteName',
            'formElementErrors'     => 'Application\View\Helper\FormElementErrors',
            'formElementErrorsV2'   => 'Application\View\Helper\FormElementErrorsV2',
            'formErrorList'         => 'Application\View\Helper\FormErrorList',
            'formLinkedErrorList'   => 'Application\View\Helper\FormLinkedErrorList',
            'formLinkedErrorListV2' => 'Application\View\Helper\FormLinkedErrorListV2',
            'formErrorTextExchange' => 'Application\View\Helper\FormErrorTextExchange',
            'concatNames'           => 'Application\View\Helper\ConcatNames',
            'cellStyles'            => 'Application\View\Helper\CellStyles',
            'systemMessage'         => 'Application\View\Helper\SystemMessage',
            'formatLpaId'           => 'Application\View\Helper\FormatLpaId',
            'ordinalSuffix'         => 'Application\View\Helper\OrdinalSuffix',
            'applicantNames'        => 'Application\View\Helper\ApplicantNames',
            'moneyFormat'           => 'Application\View\Helper\MoneyFormat',
            'formRadio'             => 'Application\Form\View\Helper\FormRadio',
            'reuseDetailsForm'      => 'Application\Form\View\Helper\ReuseDetailsForm',
        ],
    ],

    // Placeholder for console routes
    'console' => [
        'router' => [
            'routes' => [

                'dynamo-session-gc' => [
                    'type'    => 'simple',
                    'options' => [
                        'route'    => 'dynamo-session-gc',
                        'defaults' => [
                            'controller' => 'Console\SessionsController',
                            'action'     => 'gc'
                        ],
                    ],
                ],

            ],
        ],
    ],

];
