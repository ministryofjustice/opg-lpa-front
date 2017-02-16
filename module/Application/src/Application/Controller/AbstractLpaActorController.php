<?php

namespace Application\Controller;

use Application\Form\Lpa\AbstractActorForm;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

abstract class AbstractLpaActorController extends AbstractLpaController
{
    /**
     * Return clone source LPA details from API and then store in a session container for subsequent calls
     *
     * @param bool $trustOnly - when true, only return trust corporation details
     * @return array|null;
     */
    private function getSeedDetails($trustOnly = false)
    {
        //  Get the LPA to check the seed value
        $lpa = $this->getLpa();
        $seedId = $lpa->seed;

        if (is_null($seedId)) {
            return null;
        }

        //  Get the seed data and store it in the user session - if it isn't already there
        $cloneContainer = new Container('clone');

        if (!$cloneContainer->offsetExists($seedId)) {
            //  The data isn't in the session - get it now
            $seedData = $this->getLpaApplicationService()->getSeedDetails($lpa->id);

            if (!$seedData) {
                return null;
            }

            $cloneContainer->$seedId = $seedData;
        }

        //  Get seed data from session container
        $seedData = $cloneContainer->$seedId;

        //  Initialise the seed details array
        $seedDetails = [];

        //  If this is not an attempt to get trust details then add the details of the session user
        if (!$trustOnly) {
            $seedDetails[] = [
                'label' => (string)$this->getServiceLocator()->get('UserDetailsSession')->user->name . ' (myself)',
                'data'  => $this->getUserDetailsAsArray(),
            ];
        }

        foreach ($seedData as $type => $actorData) {
            //  Trusts can only be attorneys
            if ($trustOnly && !in_array($type, ['primaryAttorneys', 'replacementAttorneys'])) {
                continue;
            }

            switch ($type) {
                case 'donor':
                    $seedDetails[] = $this->getActorSeedDetails($actorData, '(was the donor)');
                    break;
                case 'correspondent':
                    if ($actorData['who'] == 'other') {
                        $seedDetails[] = $this->getActorSeedDetails($actorData, '(was the correspondent)');
                    }
                    break;
                case 'certificateProvider':
                    $seedDetails[] = $this->getActorSeedDetails($actorData, '(was the certificate provider)');
                    break;
                case 'primaryAttorneys':
                    foreach ($actorData as $singleActorData) {
                        if ($singleActorData['type'] == 'trust' xor $trustOnly) {
                            continue;
                        }

                        $seedDetails[] = $this->getActorSeedDetails($singleActorData, '(was a primary attorney)');

                        if ($trustOnly) {
                            return $seedDetails;
                        }
                    }
                    break;
                case 'replacementAttorneys':
                    foreach ($actorData as $singleActorData) {
                        if ($singleActorData['type'] == 'trust' xor $trustOnly) {
                            continue;
                        }

                        $seedDetails[] = $this->getActorSeedDetails($singleActorData, '(was a replacement attorney)');

                        if ($trustOnly) {
                            return $seedDetails;
                        }
                    }
                    break;
                case 'peopleToNotify':
                    foreach ($actorData as $singleActorData) {
                        $seedDetails[] = $this->getActorSeedDetails($singleActorData, '(was a person to be notified)');
                    }
                    break;
                default:
                    break;
            }
        }

        return $seedDetails;
    }

    /**
     * Simple function to return filtered actor details for seeding
     *
     * @param array $actorData
     * @param string $suffixText
     * @return array
     */
    private function getActorSeedDetails(array $actorData, $suffixText = '')
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
        //  If the user details aren't being used already (determined by the query params) then allow the link to be displayed
        if (!$trustOnly && !$this->params()->fromQuery('use-my-details')) {
            $viewModel->allowUseMyDetails = true;
        }

        $seedDetails = $this->getSeedDetails($trustOnly);

        //  If there are no seed details then exit early
        if ($seedDetails == null) {
            return;
        }

        //  Get the seed details picker form and add an appropriate action
        $reuseDetailsForm = $this->getServiceLocator()
                                 ->get('FormElementManager')
                                 ->get('Application\Form\Lpa\SeedDetailsPickerForm', ['seedDetails' => $seedDetails]);

        //  If this is from an add trust screen then populate the trust details
        if ($trustOnly) {
            if (!$this->params()->fromQuery('use-trust-details')) {
                $viewModel->reuseTrustName = $seedDetails[0]['label'];
            }

            //  Don't show the allow my details link to be displayed on the trust forms
            $viewModel->allowUseMyDetails = false;
        } else {
            $viewModel->reuseDetailsForm = $reuseDetailsForm;

            //  As we are going to display the reuse details form - don't allow the link to be displayed
            $viewModel->allowUseMyDetails = false;
        }

        //  Initialise the actor data
        $actorData = null;

        //  If this is a post request then it may be a request to utilise some of the seed data
        if ($this->request->isPost()) {
            $postData = $this->request->getPost();

            //  Exit if this is NOT a post from the seed form
            if (!$postData->offsetExists('is-seed-form')) {
                return;
            }

            //  Load the data into the form so it can be validated
            $reuseDetailsForm->setData($postData);

            if (!$reuseDetailsForm->isValid()) {
                return;
            }

            //  Only continue if the index selected is in the seed data
            $pickIdx = $this->request->getPost('pick-details');

            if (is_array($seedDetails) && array_key_exists($pickIdx, $seedDetails)) {
                $actorData = $seedDetails[$pickIdx]['data'];

                //  If this is an AJAX request then just return the data as json
                if ($this->getRequest()->isXmlHttpRequest()) {
                    return new JsonModel($actorData);
                }
            }
        } elseif ($this->params()->fromQuery('use-trust-details')) {
            //  If we're trying to use the trust details then just use the first array item - there should only be one
            $actorData = $seedDetails[0]['data'];
        }

        //  This was not an AJAX request so bind the actor data to the main form
        if (!is_null($actorData)) {
            $mainForm->bind($actorData);
        }
    }

    protected function getUserDetailsAsArray()
    {
        $userDetails = $this->getUserDetails()->flatten();
        if(array_key_exists('dob-date', $userDetails)) {
            $userDetails['dob-date'] = [
                'day'   => $this->getUserDetails()->dob->date->format('d'),
                'month' => $this->getUserDetails()->dob->date->format('m'),
                'year'  => $this->getUserDetails()->dob->date->format('Y'),
            ];
        }

        return $userDetails;
    }
}
