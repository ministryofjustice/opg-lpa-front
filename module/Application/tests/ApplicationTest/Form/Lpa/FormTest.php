<?php

namespace ApplicationTest\Form\Lpa;

use Application\Form\Lpa\AbstractLpaForm;
use ApplicationTest\Form\FormTestSetupTrait;
use Opg\Lpa\DataModel\Lpa\Lpa;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class FormTest extends TestCase
{
    use FormTestSetupTrait;

    public function testAllFormsHaveCsrfCheck()
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/pf.json'));

        foreach (glob(__DIR__ . '/../../../../src/Application/Form/Lpa/*.php') as $filepath) {
            $pathInfo = pathinfo(realpath($filepath));
            $className = 'Application\\Form\\Lpa\\' . $pathInfo['filename'];
            $reflectionClass = new ReflectionClass($className);

            if (class_exists($className) && !$reflectionClass->isAbstract() && $reflectionClass->isSubclassOf(AbstractLpaForm::class)) {
                //  Instantiate the form object and test
                $form = new $className('name', [
                    'lpa' => $lpa
                ]);

                $this->setUpForm($form);

                foreach ($form->getElements() as $key => $value) {
                    if (strpos($key, 'secret') === 0) {
                        $secretKeys = $value;
                        break;
                    }
                }

                $this->assertInstanceOf('Zend\Form\Element\Csrf', $secretKeys);
            }
        }
    }
}
