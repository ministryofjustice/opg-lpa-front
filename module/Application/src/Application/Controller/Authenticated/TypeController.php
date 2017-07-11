<?php

namespace Application\Controller\Authenticated;

use Application\Controller\AbstractAuthenticatedController;
use Application\Model\FormFlowChecker;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Zend\View\Model\ViewModel;
use RuntimeException;

class TypeController extends AbstractAuthenticatedController
{
    public function indexAction()
    {
        $form = $this->getServiceLocator()
                     ->get('FormElementManager')
                     ->get('Application\Form\Lpa\TypeForm');

        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                $lpa = $this->getLpaApplicationService()->createApplication();

                if (!$lpa instanceof Lpa) {
                    $this->flashMessenger()->addErrorMessage('Error creating a new LPA. Please try again.');

                    return $this->redirect()->toRoute('user/dashboard');
                }

                $lpaType = $form->getData()['type'];

                if (!$this->getLpaApplicationService()->setType($lpa->id, $lpaType)) {
                    throw new RuntimeException('API client failed to set LPA type for id: ' . $lpa->id);
                }

                $formFlowChecker = new FormFlowChecker();
                $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();

                return $this->redirect()->toRoute($formFlowChecker->nextRoute($currentRouteName), ['lpa-id' => $lpa->id]);
            }
        }

        $analyticsDimensions = [
            'dimension2' => date('Y-m-d'),
            'dimension3' => 0,
        ];

        $view = new ViewModel([
            'form'                => $form,
            'isChangeAllowed'     => true,
            'analyticsDimensions' => $analyticsDimensions,
        ]);

        $view->setTemplate('application/type/index');

        return $view;
    }
}
