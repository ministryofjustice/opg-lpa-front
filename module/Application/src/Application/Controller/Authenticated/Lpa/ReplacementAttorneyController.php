<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaActorController;
use Application\Model\Service\Lpa\Metadata;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Zend\View\Model\ViewModel;

class ReplacementAttorneyController extends AbstractLpaActorController
{

    public function indexAction()
    {
        $lpaId = $this->getLpa()->id;

        // set hidden form for saving empty array to replacement attorneys.
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\BlankForm');

        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                // set user has confirmed if there are replacement attorneys
                $this->getServiceLocator()->get('Metadata')->setReplacementAttorneysConfirmed($this->getLpa());

                return $this->moveToNextRoute();
            }
        }

        // list replacement attorneys on the landing page if they've been added.
        $attorneysParams = [];
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();

        foreach ($this->getLpa()->document->replacementAttorneys as $idx => $attorney) {
            $params = [
                'attorney' => [
                    'address'   => $attorney->address
                ],
                'editRoute'     => $this->url()->fromRoute($currentRouteName . '/edit', ['lpa-id' => $lpaId, 'idx' => $idx]),
                'confirmDeleteRoute'   => $this->url()->fromRoute($currentRouteName . '/confirm-delete', ['lpa-id' => $lpaId, 'idx' => $idx]),
                'deleteRoute'   => $this->url()->fromRoute($currentRouteName . '/delete', ['lpa-id' => $lpaId, 'idx' => $idx]),
            ];

            if ($attorney instanceof Human) {
                $params['attorney']['name'] = $attorney->name;
            } else {
                $params['attorney']['name'] = $attorney->name;
            }

            $attorneysParams[] = $params;
        }

        return new ViewModel([
            'addRoute'  => $this->url()->fromRoute($currentRouteName . '/add', ['lpa-id' => $lpaId]),
            'lpaId'     => $lpaId,
            'attorneys' => $attorneysParams,
            'form'      => $form,
        ]);
    }

    public function addAction()
    {
        $viewModel = new ViewModel();
        $viewModel->setTemplate('application/replacement-attorney/person-form.twig');

        if ($this->isPopup()) {
            $viewModel->setTerminal(true);
            $viewModel->isPopup = true;
        }

        //  Execute the parent check function to determine what reuse options might be available and what should happen
        $reuseRedirect = $this->checkReuseDetailsOptions($viewModel);

        if (!is_null($reuseRedirect)) {
            return $reuseRedirect;
        }

        $lpa = $this->getLpa();
        $lpaId = $lpa->id;

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\AttorneyForm');
        $form->setAttribute('action', $this->url()->fromRoute('lpa/replacement-attorney/add', ['lpa-id' => $lpaId]));
        $form->setExistingActorNamesData($this->getActorsList());

        if ($this->request->isPost() && !$this->reuseActorDetails($form)) {
            //  Set the post data
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                // persist to the api
                $attorney = new Human($form->getModelDataFromValidatedForm());
                if (!$this->getLpaApplicationService()->addReplacementAttorney($lpaId, $attorney)) {
                    throw new \RuntimeException('API client failed to add a replacement attorney for id: '.$lpaId);
                }

                // set REPLACEMENT_ATTORNEYS_CONFIRMED flag in metadata
                if (!array_key_exists(Metadata::REPLACEMENT_ATTORNEYS_CONFIRMED, $lpa->metadata)) {
                        $this->getServiceLocator()->get('Metadata')->setReplacementAttorneysConfirmed($lpa);
                }

                $this->cleanUpReplacementAttorneyDecisions();

                return $this->moveToNextRoute();
            }
        }

        $this->addReuseDetailsBackButton($viewModel);

        $viewModel->form = $form;

        //  If appropriate add an add trust link route
        if ($this->allowTrust()) {
            $viewModel->switchAttorneyTypeRoute = 'lpa/replacement-attorney/add-trust';
        }

        //  Add a cancel URL for this action
        $this->addCancelUrlToView($viewModel, 'lpa/replacement-attorney');

        return $viewModel;
    }

    public function editAction()
    {
        $viewModel = new ViewModel();

        if ($this->isPopup()) {
            $viewModel->setTerminal(true);
            $viewModel->isPopup = true;
        }

        $lpa = $this->getLpa();
        $lpaId = $lpa->id;

        $attorneyIdx = $this->params()->fromRoute('idx');

        if (array_key_exists($attorneyIdx, $lpa->document->replacementAttorneys)) {
            $attorney = $lpa->document->replacementAttorneys[$attorneyIdx];
        }

        // if attorney idx does not exist in lpa, return 404.
        if (!isset($attorney)) {
            return $this->notFoundAction();
        }

        if ($attorney instanceof Human) {
            $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\AttorneyForm');
            $form->setExistingActorNamesData($this->getActorsList($attorneyIdx));
            $viewModel->setTemplate('application/replacement-attorney/person-form.twig');
        } else {
            $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\TrustCorporationForm');
            $viewModel->setTemplate('application/replacement-attorney/trust-form.twig');
        }

        $form->setAttribute('action', $this->url()->fromRoute('lpa/replacement-attorney/edit', ['lpa-id' => $lpaId, 'idx' => $attorneyIdx]));

        if ($this->request->isPost()) {
            $postData = $this->request->getPost();
            $form->setData($postData);

            if ($form->isValid()) {

                //  Update the attorney with new details
                $attorney->populate($form->getModelDataFromValidatedForm());

                // persist to the api
                if (!$this->getLpaApplicationService()->setReplacementAttorney($lpaId, $attorney, $attorney->id)) {
                    throw new \RuntimeException('API client failed to update replacement attorney ' . $attorney->id . ' for id: ' . $lpaId);
                }

                return $this->moveToNextRoute();
            }
        } else {
            $flattenAttorneyData = $attorney->flatten();

            if ($attorney instanceof Human) {
                $dob = $attorney->dob->date;
                $flattenAttorneyData['dob-date'] = [
                    'day'   => $dob->format('d'),
                    'month' => $dob->format('m'),
                    'year'  => $dob->format('Y'),
                ];
            }

            $form->bind($flattenAttorneyData);
        }

        $viewModel->form = $form;

        //  Add a cancel URL for this action
        $this->addCancelUrlToView($viewModel, 'lpa/replacement-attorney');

        return $viewModel;
    }

    public function confirmDeleteAction()
    {
        $lpaId = $this->getLpa()->id;
        $lpaDocument = $this->getLpa()->document;

        $attorneyIdx = $this->params()->fromRoute('idx');

        if (array_key_exists($attorneyIdx, $lpaDocument->replacementAttorneys)) {
            $attorney = $lpaDocument->replacementAttorneys[$attorneyIdx];
        }

        // if attorney idx does not exist in lpa, return 404.
        if (!isset($attorney)) {
            return $this->notFoundAction();
        }

        // Setting the trust flag
        $isTrust = isset($attorney->number);

        $viewModel = new ViewModel([
            'deleteRoute' => $this->url()->fromRoute('lpa/replacement-attorney/delete', ['lpa-id' => $lpaId, 'idx' => $attorneyIdx]),
            'attorneyName' => $attorney->name,
            'attorneyAddress' => $attorney->address,
            'isTrust' => $isTrust,
        ]);

        if ($this->isPopup()) {
            $viewModel->setTerminal(true);
            $viewModel->isPopup = true;
        }

        //  Add a cancel URL for this action
        $this->addCancelUrlToView($viewModel, 'lpa/replacement-attorney');

        return $viewModel;
    }

    public function deleteAction()
    {
        $lpaId = $this->getLpa()->id;
        $attorneyIdx = $this->getEvent()->getRouteMatch()->getParam('idx');

        if (array_key_exists($attorneyIdx, $this->getLpa()->document->replacementAttorneys)) {
            // persist data to the api
            if (!$this->getLpaApplicationService()->deleteReplacementAttorney($lpaId, $this->getLpa()->document->replacementAttorneys[$attorneyIdx]->id)) {
                throw new \RuntimeException('API client failed to delete replacement attorney ' . $attorneyIdx . ' for id: ' . $lpaId);
            }

            $this->cleanUpReplacementAttorneyDecisions();
        } else {
            // if attorney idx does not exist in lpa, return 404.
            return $this->notFoundAction();
        }

        return $this->moveToNextRoute();
    }

    public function addTrustAction()
    {
        $viewModel = new ViewModel();
        $viewModel->setTemplate('application/replacement-attorney/trust-form.twig');

        if ($this->isPopup()) {
            $viewModel->setTerminal(true);
            $viewModel->isPopup = true;
        }

        $lpaId = $this->getLpa()->id;

        //  Redirect to human add attorney if trusts are not allowed
        if (!$this->allowTrust()) {
            return $this->redirect()->toRoute('lpa/replacement-attorney/add', ['lpa-id' => $lpaId]);
        }

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\TrustCorporationForm');
        $form->setAttribute('action', $this->url()->fromRoute('lpa/replacement-attorney/add-trust', ['lpa-id' => $lpaId]));

        if ($this->request->isPost() && !$this->reuseActorDetails($form)) {
            //  Set the post data
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                // persist data to the api
                $attorney = new TrustCorporation($form->getModelDataFromValidatedForm());
                if (!$this->getLpaApplicationService()->addReplacementAttorney($lpaId, $attorney)) {
                    throw new \RuntimeException('API client failed to add trust corporation replacement attorney for id: '.$lpaId);
                }

                // set REPLACEMENT_ATTORNEYS_CONFIRMED flag in metadata
                if (!array_key_exists(Metadata::REPLACEMENT_ATTORNEYS_CONFIRMED, $this->getLpa()->metadata)) {
                    $this->getServiceLocator()->get('Metadata')->setReplacementAttorneysConfirmed($this->getLpa());
                }

                $this->cleanUpReplacementAttorneyDecisions();

                return $this->moveToNextRoute();
            }
        }

        $this->addReuseDetailsBackButton($viewModel);

        $viewModel->form = $form;
        $viewModel->switchAttorneyTypeRoute = 'lpa/replacement-attorney/add';

        //  Add a cancel URL for this action
        $this->addCancelUrlToView($viewModel, 'lpa/replacement-attorney');

        return $viewModel;
    }
}
