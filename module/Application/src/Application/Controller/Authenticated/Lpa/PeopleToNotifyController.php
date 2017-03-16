<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaActorController;
use Application\Model\Service\Lpa\Metadata;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use Zend\View\Model\ViewModel;

class PeopleToNotifyController extends AbstractLpaActorController
{
    public function indexAction()
    {
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        $lpaId = $this->getLpa()->id;

        // set hidden form for saving empty array to peopleToNotify.
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\BlankForm');

        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                // set user has confirmed if there are people to notify
                $this->getServiceLocator()->get('Metadata')->setPeopleToNotifyConfirmed($this->getLpa());

                return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpaId]);
            }
        }

        // list notified persons on the landing page if they've been added.
        $peopleToNotifyParams = [];

        foreach ($this->getLpa()->document->peopleToNotify as $idx => $peopleToNotify) {
            $peopleToNotifyParams[] = [
                'notifiedPerson' => [
                    'name'      => $peopleToNotify->name,
                    'address'   => $peopleToNotify->address
                ],
                'editRoute'     => $this->url()->fromRoute($currentRouteName . '/edit', ['lpa-id' => $lpaId, 'idx' => $idx]),
                'deleteRoute'   => $this->url()->fromRoute($currentRouteName . '/delete', ['lpa-id' => $lpaId, 'idx' => $idx]),
            ];
        }

        $view = new ViewModel(['form' => $form, 'peopleToNotify' => $peopleToNotifyParams]);

        if (count($this->getLpa()->document->peopleToNotify) < 5) {
            $view->addRoute  = $this->url()->fromRoute($currentRouteName . '/add', ['lpa-id' => $lpaId]);
        }

        return $view;
    }

    public function addAction()
    {
        $viewModel = new ViewModel();
        $viewModel->setTemplate('application/people-to-notify/form.twig');

        if ($this->isPopup()) {
            $viewModel->setTerminal(true);
            $viewModel->isPopup = true;
        }

        $lpa = $this->getLpa();
        $lpaId = $lpa->id;

        if (count($lpa->document->peopleToNotify) >= 5) {
            return $this->redirect()->toRoute('lpa/people-to-notify', ['lpa-id'=>$lpaId]);
        }

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\PeopleToNotifyForm');
        $routeMatch = $this->getEvent()->getRouteMatch();
        $form->setAttribute('action', $this->url()->fromRoute($routeMatch->getMatchedRouteName(), ['lpa-id' => $lpaId]));
        $form->setExistingActorNamesData($this->getActorsList($routeMatch));

        if ($this->request->isPost()) {
            //  Set the post data
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                // persist data
                $np = new NotifiedPerson($form->getModelDataFromValidatedForm());
                if (!$this->getLpaApplicationService()->addNotifiedPerson($lpaId, $np)) {
                    throw new \RuntimeException('API client failed to add a notified person for id: '.$lpaId);
                }

                // remove metadata flag value if exists
                if (!array_key_exists(Metadata::PEOPLE_TO_NOTIFY_CONFIRMED, $lpa->metadata)) {
                        $this->getServiceLocator()->get('Metadata')->setPeopleToNotifyConfirmed($lpa);
                }

                return $this->moveToNextRoute();
            }
        } else {
            $this->addReuseDetailsForm($viewModel, $form);
        }

        $this->addReuseDetailsBackButton($viewModel);

        $viewModel->form = $form;

        //  Add a cancel URL for this action
        $this->addCancelUrlToView($viewModel, 'lpa/people-to-notify');

        return $viewModel;
    }

    public function editAction()
    {
        $viewModel = new ViewModel();
        $viewModel->setTemplate('application/people-to-notify/form.twig');

        if ($this->isPopup()) {
            $viewModel->setTerminal(true);
            $viewModel->isPopup = true;
        }

        $lpa = $this->getLpa();
        $lpaId = $lpa->id;

        $routeMatch = $this->getEvent()->getRouteMatch();
        $currentRouteName = $routeMatch->getMatchedRouteName();

        $personIdx = $routeMatch->getParam('idx');
        if (array_key_exists($personIdx, $lpa->document->peopleToNotify)) {
            $notifiedPerson = $lpa->document->peopleToNotify[$personIdx];
        }

        // if notified person idx does not exist in lpa, return 404.
        if (!isset($notifiedPerson)) {
            return $this->notFoundAction();
        }

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\PeopleToNotifyForm');
        $form->setAttribute('action', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $lpaId, 'idx' => $personIdx]));
        $form->setExistingActorNamesData($this->getActorsList($routeMatch));

        if ($this->request->isPost()) {
            $postData = $this->request->getPost();
            $form->setData($postData);

            if ($form->isValid()) {
                // update details
                $notifiedPerson->populate($form->getModelDataFromValidatedForm());

                // persist to the api
                if (!$this->getLpaApplicationService()->setNotifiedPerson($lpaId, $notifiedPerson, $notifiedPerson->id)) {
                    throw new \RuntimeException('API client failed to update notified person ' . $personIdx . ' for id: ' . $lpaId);
                }

                return $this->moveToNextRoute();
            }
        } else {
            $form->bind($notifiedPerson->flatten());
        }

        $viewModel->form = $form;

        //  Add a cancel URL for this action
        $this->addCancelUrlToView($viewModel, 'lpa/people-to-notify');

        return $viewModel;
    }

    public function deleteAction()
    {
        $lpaId = $this->getLpa()->id;
        $personIdx = $this->getEvent()->getRouteMatch()->getParam('idx');

        if (array_key_exists($personIdx, $this->getLpa()->document->peopleToNotify)) {
            // persist data to the api
            if (!$this->getLpaApplicationService()->deleteNotifiedPerson($lpaId, $this->getLpa()->document->peopleToNotify[$personIdx]->id)) {
                throw new \RuntimeException('API client failed to delete notified person ' . $personIdx . ' for id: ' . $lpaId);
            }
        } else {
            // if notified person idx does not exist in lpa, return 404.
            return $this->notFoundAction();
        }

        return $this->moveToNextRoute();
    }
}
