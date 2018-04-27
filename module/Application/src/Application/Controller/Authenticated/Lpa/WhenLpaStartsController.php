<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Zend\View\Model\ViewModel;
use RuntimeException;

class WhenLpaStartsController extends AbstractLpaController
{
    public function indexAction()
    {
        $lpa = $this->getLpa();

        $form = $this->getFormElementManager()
                     ->get('Application\Form\Lpa\WhenLpaStartsForm', [
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

                $whenToStart = $form->getData()['when'];

                if ($primaryAttorneyDecisions->when !== $whenToStart) {
                    $primaryAttorneyDecisions->when = $whenToStart;

                    // persist data
                    if (!$this->getLpaApplicationService()->setPrimaryAttorneyDecisions($this->getIdentity()->id(), $lpa->id, $primaryAttorneyDecisions)) {
                        throw new RuntimeException('API client failed to set when LPA starts for id: ' . $lpa->id);
                    }
                }

                return $this->moveToNextRoute();
            }
        } else {
            if ($primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
                $form->bind($primaryAttorneyDecisions->flatten());
            }
        }

        return new ViewModel(['form' => $form]);
    }
}
