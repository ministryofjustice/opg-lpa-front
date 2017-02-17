<?php

namespace Application\Controller;

use Application\Form\Lpa\AbstractActorForm;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\User\Dob;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

abstract class AbstractLpaActorController extends AbstractLpaController
{
    /**
     * Return an array of actor details that can be utilised in a "reuse" scenario
     *
     * @param bool $trustOnly - when true, only return trust corporation details
     * @return array
     */
    private function getActorReuseDetails($trustOnly = false)
    {
        //  Initialise the reuse details details array
        $actorReuseDetails = [];

        //  If this is not a request to get trust data add the current user data to the details
        if (!$trustOnly) {
            $actorReuseDetails[] = [
                'label' => (string)$this->getServiceLocator()->get('UserDetailsSession')->user->name . ' (myself)',
                'data'  => $this->getUserDetailsAsArray(),
            ];
        }

        //  Get any seed details for this LPA
        $seedDetails = $this->getSeedDetails($this->getLpa());

        foreach ($seedDetails as $type => $actorData) {
            //  Trusts can only be attorneys
            if ($trustOnly && !in_array($type, ['primaryAttorneys', 'replacementAttorneys'])) {
                continue;
            }

            switch ($type) {
                case 'donor':
                    $actorReuseDetails[] = $this->getActorDetails($actorData, '(was the donor)');
                    break;
                case 'correspondent':
                    if ($actorData['who'] == 'other') {
                        $actorReuseDetails[] = $this->getActorDetails($actorData, '(was the correspondent)');
                    }
                    break;
                case 'certificateProvider':
                    $actorReuseDetails[] = $this->getActorDetails($actorData, '(was the certificate provider)');
                    break;
                case 'primaryAttorneys':
                    foreach ($actorData as $singleActorData) {
                        if ($singleActorData['type'] == 'trust' xor $trustOnly) {
                            continue;
                        }

                        $actorReuseDetails[] = $this->getActorDetails($singleActorData, '(was a primary attorney)');
                    }
                    break;
                case 'replacementAttorneys':
                    foreach ($actorData as $singleActorData) {
                        if ($singleActorData['type'] == 'trust' xor $trustOnly) {
                            continue;
                        }
                    }
                    break;
                case 'peopleToNotify':
                    foreach ($actorData as $singleActorData) {
                        $actorReuseDetails[] = $this->getActorDetails($singleActorData, '(was a person to be notified)');
                    }
                    break;
                default:
                    break;
            }
        }

        return $actorReuseDetails;
    }

    /**
     * Return the user details of the current user in an array
     *
     * @return array
     */
    private function getUserDetailsAsArray()
    {
        $userDetailsObj = $this->getUserDetails();
        $userDetails = $userDetailsObj->flatten();
        $dateOfBirth = $userDetailsObj->dob;

        //  If a date of birth is present then replace it as an array of day, month and year
        if ($dateOfBirth instanceof Dob) {
            $userDetails['dob-date'] = [
                'day'   => $dateOfBirth->date->format('d'),
                'month' => $dateOfBirth->date->format('m'),
                'year'  => $dateOfBirth->date->format('Y'),
            ];
        }

        return $userDetails;
    }

    /**
     * Simple function to get the seed details from the backend or from the user session if already retrieved
     *
     * @param  Lpa $lpa
     * @return array
     */
    private function getSeedDetails(Lpa $lpa)
    {
        $seedDetails = [];
        $seedId = $lpa->seed;

        if (!is_null($seedId)) {
            $cloneContainer = new Container('clone');

            if (!$cloneContainer->offsetExists($seedId)) {
                //  The data isn't in the session - get it now
                $cloneContainer->$seedId = $this->getLpaApplicationService()->getSeedDetails($lpa->id);
            }

            if (is_array($cloneContainer->$seedId)) {
                $seedDetails = $cloneContainer->$seedId;
            }
        }

        return $seedDetails;
    }

    /**
     * Simple function to return filtered actor details
     *
     * @param array $actorData
     * @param string $suffixText
     * @return array
     */
    private function getActorDetails(array $actorData, $suffixText = '')
    {
        //  Initialise the label text - this will be the value if the actor is a trust
        $label = $actorData['name'];

        if (!isset($actorData['type']) || $actorData['type'] != 'trust') {
            $label = $actorData['name']['first'] . ' ' . $actorData['name']['last'];
        }

        //  Filter the actor data
        foreach ($actorData as $actorDataKey => $actorDataValue) {
            if (!in_array($actorDataKey, ['name', 'number', 'otherNames', 'address', 'dob', 'email', 'case', 'phone'])) {
                unset($actorData[$actorDataKey]);
            }
        }

        return [
            'label' => trim($label . ' ' . $suffixText),
            'data'  => $this->flattenData($actorData),
        ];
    }

    /**
     * Add the seed data selector if appropriate
     *
     * @param ViewModel $viewModel
     * @param AbstractActorForm $mainForm
     * @param bool $trustOnly
     * @return void|JsonModel
     */
    protected function seedDataSelector(ViewModel $viewModel, AbstractActorForm $mainForm, $trustOnly = false)
    {
        //  Attempt to get the seed details
        $actorReuseDetails = $this->getActorReuseDetails($trustOnly);

        if (is_array($actorReuseDetails)) {
            //  Get the seed details picker form and add an appropriate action
            $reuseDetailsForm = $this->getServiceLocator()
                                     ->get('FormElementManager')
                                     ->get('Application\Form\Lpa\SeedDetailsPickerForm', [
                                         'seedDetails' => $actorReuseDetails,
                                         'trustOnly'   => $trustOnly,
                                     ]);

            //  Set the action for the form
            $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
            $reuseDetailsForm->setAttribute('action', $this->url()->fromRoute($currentRouteName, ['lpa-id' => $this->getLpa()->id]));

            //  Get the reuse-details index from the query parameters if it is present
            $reuseDetailsIndex = $this->params()->fromQuery('reuse-details');

            //  If a valid reuse-details query param has been passed then try to retrieve the appropriate details
            if (is_numeric($reuseDetailsIndex) && array_key_exists($reuseDetailsIndex, $actorReuseDetails)) {
                //  Get the actor data
                $actorData = $actorReuseDetails[$reuseDetailsIndex]['data'];

                //  If this is an AJAX request then just return the data as json
                if ($this->getRequest()->isXmlHttpRequest()) {
                    return new JsonModel($actorData);
                }

                //  This is not an AJAX request so just bind the data to the main form
                $mainForm->bind($actorData);
            }

            $viewModel->reuseDetailsForm = $reuseDetailsForm;
        }
    }
}
