<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Zend\View\Model\ViewModel;
use RuntimeException;

class LifeSustainingController extends AbstractLpaController
{
    public function indexAction()
    {
        $lpa = $this->getLpa();

        $form = $this->getFormElementManager()
                     ->get('Application\Form\Lpa\LifeSustainingForm', [
                         'lpa' => $lpa,
                     ]);

        $primaryAttorneyDecisions = $lpa->document->primaryAttorneyDecisions;

        if ($this->request->isPost()) {
            $postData = $this->request->getPost();

            $form->setData($postData);

            if ($form->isValid()) {
                if (!$primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
                    $primaryAttorneyDecisions = new PrimaryAttorneyDecisions();
                    $lpa->document->primaryAttorneyDecisions = $primaryAttorneyDecisions;
                }

                $canSustainLife = (bool) $form->getData()['canSustainLife'];

                if ($primaryAttorneyDecisions->canSustainLife !== $canSustainLife) {
                    $primaryAttorneyDecisions->canSustainLife = $canSustainLife;

                    // persist data
                    if (!$this->getLpaApplicationService()->setPrimaryAttorneyDecisions($lpa, $primaryAttorneyDecisions)) {
                        throw new RuntimeException('API client failed to set life sustaining for id: ' . $lpa->id);
                    }
                }

                return $this->moveToNextRoute();
            }
        } else {
            if ($lpa->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
                $form->bind($lpa->document->primaryAttorneyDecisions->flatten());
            }
        }

        return new ViewModel([
            'form' => $form
        ]);
    }
}
