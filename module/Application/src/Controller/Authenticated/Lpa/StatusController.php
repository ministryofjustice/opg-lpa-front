<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Zend\View\Model\ViewModel;
use DateTime;

class StatusController extends AbstractLpaController
{
    public function indexAction()
    {
        $lpaId = $this->getEvent()->getRouteMatch()->getParam('lpa-id');
        $lpa = $this->getLpaApplicationService()->getApplication($lpaId);

        $lpaStatus = null;

        if ($lpa->getCompletedAt() instanceof DateTime) {
            $lpaStatus = 'completed';

            $trackFromDate = new DateTime($this->config()['processing-status']['track-from-date']);

            if ($trackFromDate <= new DateTime('now') && $trackFromDate <= $lpa->getCompletedAt()) {
                $lpaStatus = 'waiting';

                $lpaStatusDetails = $this->getLpaApplicationService()->getStatuses($lpaId);

                if ($lpaStatusDetails[$lpaId]['found'] == true) {
                    $lpaStatus = strtolower($lpaStatusDetails[$lpaId]['status']);
                }
            }
        }

        //  Keep these statues in workflow order
        $statuses = ['waiting', 'received', 'checking', 'returned', 'completed'];
        if (!in_array($lpaStatus, $statuses)) {
            return $this->redirect()->toRoute('user/dashboard');
        }

        //  Determine what statuses should trigger the current status to display as 'done'
        $doneStatuses = array_slice($statuses, 0, array_search($lpaStatus, $statuses));

        $viewModel = new ViewModel([
            'lpa'          => $lpa,
            'status'       => $lpaStatus,
            'doneStatuses' => $doneStatuses,
        ]);

        $viewModel->setTemplate('application/authenticated/lpa/status/status.twig');

        return $viewModel;
    }
}
