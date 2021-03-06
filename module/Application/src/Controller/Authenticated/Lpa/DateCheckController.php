<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Application\Model\Service\Signatures\DateCheck;
use Zend\View\Model\ViewModel;

class DateCheckController extends AbstractLpaController
{
    public function indexAction()
    {
        $lpa = $this->getLpa();

        //  If the return route has been submitted in the post then just use it
        $returnRoute = $this->params()->fromPost('return-route', null);

        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();

        if (is_null($returnRoute)) {
            //  If we came from the "LPA complete" route then set the return target back there
            if ($currentRouteName == 'lpa/date-check/complete') {
                $returnRoute = 'lpa/complete';
            }
        }

        //  Create the date check form and set the action
        $form = $this->getFormElementManager()->get('Application\Form\Lpa\DateCheckForm', [
            'lpa' => $lpa,
        ]);
        $form->setAttribute('action', $this->url()->fromRoute($currentRouteName, [
            'lpa-id' => $lpa->id
        ]));

        if ($this->request->isPost()) {
            //  Set the post data in the form and validate it
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                $data = $form->getData();

                //  Extract the attorney dates from the post data
                $attorneySignatureDates = [];

                foreach ($data as $name => $date) {
                    if (preg_match('/sign-date-(attorney|replacement-attorney)-\d/', $name)) {
                        $attorneySignatureDates[] = $date;
                    }
                }

                //  Extract the applicant dates from the post data
                $applicantSignatureDates = [];

                foreach ($data as $name => $date) {
                    if (preg_match('/sign-date-applicant-\d/', $name)) {
                        $applicantSignatureDates[] = $date;
                    }
                }

                $result = DateCheck::checkDates([
                    'sign-date-donor'                 => $this->dateArrayToTime($data['sign-date-donor']),
                    'sign-date-donor-life-sustaining' => isset($data['sign-date-donor-life-sustaining']) ? $this->dateArrayToTime($data['sign-date-donor-life-sustaining']) : null,
                    'sign-date-certificate-provider'  => $this->dateArrayToTime($data['sign-date-certificate-provider']),
                    'sign-date-attorneys'             => array_map([$this, 'dateArrayToTime'], $attorneySignatureDates),
                    'sign-date-applicants'            => array_map([$this, 'dateArrayToTime'], $applicantSignatureDates),
                ], empty($lpa->completedAt));

                if ($result === true) {
                    $queryParams = [];

                    if (!empty($returnRoute)) {
                        $queryParams['return-route'] = $returnRoute;
                    }

                    $validUrl = $this->url()->fromRoute('lpa/date-check/valid', [
                        'lpa-id' => $lpa->id,
                    ], [
                        'query' => $queryParams
                    ]);

                    return $this->redirect()->toUrl($validUrl);
                } else {
                    $form->setMessages($result);
                }
            }
        }

        $applicants = [];

        if ($lpa->completedAt !== null) {
            if ($lpa->document->whoIsRegistering === 'donor') {
                $applicants[0] = [
                    'name' => $lpa->document->donor->name,
                    'isHuman' => true,
                ];
            } elseif (is_array($lpa->document->whoIsRegistering)) {
                //Applicant is one or more primary attorneys
                foreach ($lpa->document->whoIsRegistering as $id) {
                    foreach ($lpa->document->primaryAttorneys as $primaryAttorney) {
                        if ($id == $primaryAttorney->id) {
                            $applicants[] = [
                                'name' => $primaryAttorney->name,
                                'isHuman' => isset($primaryAttorney->dob),
                            ];
                            break;
                        }
                    }
                }
            }
        }

        return new ViewModel([
            'form'        => $form,
            'returnRoute' => $returnRoute,
            'applicants'  => $applicants,
        ]);
    }


    public function validAction()
    {
        //  Generate the return target from the route
        //  If there is no route then return to the dashboard
        $returnRoute = $this->params()->fromQuery('return-route', null);

        if (is_null($returnRoute)) {
            $returnRoute = 'user/dashboard';
        }

        return new ViewModel([
            'returnRoute' => $returnRoute,
        ]);
    }

    private function dateArrayToTime(array $dateArray)
    {
        $day = $dateArray['day'];
        $month = $dateArray['month'];
        $year = $dateArray['year'];
        return strtotime("$day-$month-$year");
    }
}
