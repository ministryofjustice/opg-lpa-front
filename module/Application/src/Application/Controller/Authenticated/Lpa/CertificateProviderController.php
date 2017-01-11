<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaActorController;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class CertificateProviderController extends AbstractLpaActorController
{
    public function indexAction()
    {
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        $lpaId = $this->getLpa()->id;

        $cp = $this->getLpa()->document->certificateProvider;

        if ($cp instanceof CertificateProvider) {
            return new ViewModel([
                'certificateProvider' => [
                    'name' => $cp->name,
                    'address' => $cp->address,
                ],
                'editRoute' => $this->url()->fromRoute($currentRouteName.'/edit', ['lpa-id' => $lpaId]),
                'nextRoute' => $this->url()->fromRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]),
            ]);
        } else {
            return new ViewModel(['addRoute' => $this->url()->fromRoute($currentRouteName . '/add', ['lpa-id' => $lpaId])]);
        }
    }

    public function addAction()
    {
        if ($this->getLpa()->document->certificateProvider instanceof CertificateProvider) {
            return $this->redirect()->toRoute('lpa/certificate-provider', ['lpa-id' => $lpaId]);
        }

        $routeMatch = $this->getEvent()->getRouteMatch();
        $isPopup = $this->getRequest()->isXmlHttpRequest();

        $viewModel = new ViewModel(['routeMatch' => $routeMatch, 'isPopup' => $isPopup]);
        $viewModel->setTemplate('application/certificate-provider/form.twig');
        if ($isPopup) {
            $viewModel->setTerminal(true);
        }

        $lpaId = $this->getLpa()->id;

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\CertificateProviderForm');
        $form->setAttribute('action', $this->url()->fromRoute($routeMatch->getMatchedRouteName(), ['lpa-id' => $lpaId]));

        $seedSelection = $this->seedDataSelector($viewModel, $form);

        if ($seedSelection instanceof JsonModel) {
            return $seedSelection;
        }

        if ($this->request->isPost()) {
            $postData = $this->request->getPost();

            if (!$postData->offsetExists('pick-details')) {
                // handle certificate provider form submission
                $form->setData($postData);

                if ($form->isValid()) {
                    // persist data
                    $cp = new CertificateProvider($form->getModelDataFromValidatedForm());

                    if (!$this->getLpaApplicationService()->setCertificateProvider($lpaId, $cp)) {
                        throw new \RuntimeException('API client failed to save certificate provider for id: '.$lpaId);
                    }

                    if ($this->getRequest()->isXmlHttpRequest()) {
                        return new JsonModel(['success' => true]);
                    } else {
                        return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($routeMatch->getMatchedRouteName()), ['lpa-id' => $lpaId]);
                    }
                }
            }
        } else {
            // load user's details into the form
            if ($this->params()->fromQuery('use-my-details')) {
                $form->bind($this->getUserDetailsAsArray());
            }
        }

        $viewModel->form = $form;

        // show user my details link (if the link has not been clicked and seed dropdown is not set in the view)
        if (($viewModel->seedDetailsPickerForm==null) && !$this->params()->fromQuery('use-my-details')) {
            $viewModel->useMyDetailsRoute = $this->url()->fromRoute('lpa/certificate-provider/add', ['lpa-id' => $lpaId]) . '?use-my-details=1';
        }

        return $viewModel;
    }

    public function editAction()
    {
        $routeMatch = $this->getEvent()->getRouteMatch();
        $isPopup = $this->getRequest()->isXmlHttpRequest();
        $viewModel = new ViewModel(['routeMatch' => $routeMatch, 'isPopup' => $isPopup]);

        $viewModel->setTemplate('application/certificate-provider/form.twig');

        if ($isPopup) {
            $viewModel->setTerminal(true);
        }

        $lpaId = $this->getLpa()->id;

        $currentRouteName = $routeMatch->getMatchedRouteName();

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\CertificateProviderForm');
        $form->setAttribute('action', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $lpaId]));

        if ($this->request->isPost()) {
            $postData = $this->request->getPost();

            $form->setData($postData);

            if ($form->isValid()) {
                // persist data
                $cp = new CertificateProvider($form->getModelDataFromValidatedForm());

                if (!$this->getLpaApplicationService()->setCertificateProvider($lpaId, $cp)) {
                    throw new \RuntimeException('API client failed to update certificate provider for id: '.$lpaId);
                }

                if ($this->getRequest()->isXmlHttpRequest()) {
                    return new JsonModel(['success' => true]);
                } else {
                    return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
                }
            }
        } else {
            $cp = $this->getLpa()->document->certificateProvider->flatten();
            $form->bind($cp);
        }

        $viewModel->form = $form;

        return $viewModel;
    }
}
