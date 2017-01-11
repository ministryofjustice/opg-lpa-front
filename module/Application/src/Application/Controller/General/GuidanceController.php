<?php

namespace Application\Controller\General;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;

class GuidanceController extends AbstractBaseController
{
    public function indexAction()
    {
        $guidanceService = $this->getServiceLocator()->get('Guidance');

        $model = new ViewModel($guidanceService->parseMarkdown());


        if ($this->request->isXmlHttpRequest()) {

            // if this is accessed via ajax request, disable layout, and return the core text content
            $model->setTemplate('guidance/opg-help-content.twig');

        } else {

            // Otherwise include the layout.
            $model->setTemplate('guidance/opg-help-with-layout.twig');

        }

        $model->setTerminal(true);

        return $model;

    }

    public function indexNonTwigAction()
    {
        $guidanceService = $this->getServiceLocator()->get('Guidance');

        $model = new ViewModel($guidanceService->parseMarkdown());

        $model->setTemplate('guidance/opg-help-system.phtml');

        if ($this->request->isXmlHttpRequest()) {
            // if this is accessed via ajax request, disable layout, and return the core text content
            $model->setTerminal(true);
        }

       return $model;

    }
}
