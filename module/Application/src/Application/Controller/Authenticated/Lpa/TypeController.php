<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Zend\View\Model\ViewModel;
use RuntimeException;

class TypeController extends AbstractLpaController
{
    public function indexAction()
    {
        $form = $this->getServiceLocator()
                     ->get('FormElementManager')
                     ->get('Application\Form\Lpa\TypeForm');

        $isChangeAllowed = true;

        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                $lpaId = $this->getLpa()->id;
                $lpaType = $form->getData()['type'];

                if ($lpaType != $this->getLpa()->document->type) {
                    if (!$this->getLpaApplicationService()->setType($lpaId, $lpaType)) {
                        throw new RuntimeException('API client failed to set LPA type for id: ' . $lpaId);
                    }
                }

                return $this->moveToNextRoute();
            }
        } elseif ($this->getLpa()->document instanceof Document) {
            $form->bind($this->getLpa()->document->flatten());

            if ($this->getLpa()->document->donor instanceof Donor) {
                $isChangeAllowed = false;
            }
        }

        $analyticsDimensions = [];

        if (empty($this->getLpa()->document->type)) {
            $analyticsDimensions = [
                'dimension2' => date('Y-m-d'),
                'dimension3' => 0,
            ];
        }

        return new ViewModel([
            'form'                => $form,
            'cloneUrl'            => $this->url()->fromRoute('user/dashboard/create-lpa', ['lpa-id' => $this->getLpa()->id]),
            'nextUrl'             => $this->url()->fromRoute('lpa/donor', ['lpa-id' => $this->getLpa()->id]),
            'isChangeAllowed'     => $isChangeAllowed,
            'analyticsDimensions' => $analyticsDimensions,
        ]);
    }

}

