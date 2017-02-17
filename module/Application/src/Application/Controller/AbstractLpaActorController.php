<?php

namespace Application\Controller;

use Application\Form\Lpa\AbstractActorForm;
use Opg\Lpa\DataModel\AbstractData;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Opg\Lpa\DataModel\Lpa\Elements\Name;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\User\Dob;
use Zend\Mvc\Router\Http\RouteMatch;
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
    protected function getActorReuseDetails($trustOnly = false)
    {
        //  Initialise the reuse details details array
        $actorReuseDetails = [];

        //  If this is not a request to get trust data, and the session user data hasn't already been used, add it now
        if (!$trustOnly) {
            $this->addCurrentUserDetailsForReuse($actorReuseDetails);
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
                    $actorReuseDetails[] = $this->getReuseDetailsForActor($actorData, '(was the donor)');
                    break;
                case 'correspondent':
                    //  Only add the correspondent details if it is not the donor or an attorney
                    if ($actorData['who'] == 'other') {
                        $actorReuseDetails[] = $this->getReuseDetailsForActor($actorData, '(was the correspondent)');
                    }
                    break;
                case 'certificateProvider':
                    $actorReuseDetails[] = $this->getReuseDetailsForActor($actorData, '(was the certificate provider)');
                    break;
                case 'primaryAttorneys':
                    foreach ($actorData as $singleActorData) {
                        if ($singleActorData['type'] == 'trust' xor $trustOnly) {
                            continue;
                        }

                        $actorReuseDetails[] = $this->getReuseDetailsForActor($singleActorData, '(was a primary attorney)');
                    }
                    break;
                case 'replacementAttorneys':
                    foreach ($actorData as $singleActorData) {
                        if ($singleActorData['type'] == 'trust' xor $trustOnly) {
                            continue;
                        }

                        $actorReuseDetails[] = $this->getReuseDetailsForActor($singleActorData, '(was a replacement attorney)');
                    }
                    break;
                case 'peopleToNotify':
                    foreach ($actorData as $singleActorData) {
                        $actorReuseDetails[] = $this->getReuseDetailsForActor($singleActorData, '(was a person to be notified)');
                    }
                    break;
                default:
                    break;
            }
        }

        return $actorReuseDetails;
    }

    /**
     * Add the current user details to the reuse details array
     *
     * @param array $actorReuseDetails
     * @param bool $checkIfAlreadyUsed
     */
    protected function addCurrentUserDetailsForReuse(array &$actorReuseDetails, $checkIfAlreadyUsed = true)
    {
        //  Check that the current session user details have not already been used
        $currentUserDetailsUsedToBeAdded = true;
        $userDetailsObj = $this->getUserDetails();

        //  Check to see if the user details have already been used if necessary
        if ($checkIfAlreadyUsed) {
            foreach ($this->getActorsList() as $actorsListItem) {
                if (strtolower($userDetailsObj->name->first) == strtolower($actorsListItem['firstname'])
                    && strtolower($userDetailsObj->name->last) == strtolower($actorsListItem['lastname'])
                ) {
                    $currentUserDetailsUsedToBeAdded = false;
                    break;
                }
            }
        }

        if ($currentUserDetailsUsedToBeAdded) {
            //  Flatten the user details and reformat the DOB before adding the details to the reuse details array
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

            $actorReuseDetails[] = [
                'label' => $userDetailsObj->name . ' (myself)',
                'data'  => $userDetails,
            ];
        }
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
     * Simple function to return filtered actor details for reuse
     *
     * @param array $actorData
     * @param string $suffixText
     * @return array
     */
    protected function getReuseDetailsForActor(array $actorData, $suffixText = '')
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

            //  Get the reuse details index from the query parameters if it is present
            $reuseDetailsIndex = $this->params()->fromQuery('reuse-details');

            //  If a valid reuse details query param has been passed then try to retrieve the appropriate details
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

    /**
     * Generate a list of actors already associated with the current LPA
     *
     * @param Lpa $lpa
     * @param RouteMatch $routeMatch
     * @return array
     */
    protected function getActorsList(RouteMatch $routeMatch = null)
    {
        $actorsList = [];

        //  Get the route details
        $matchedRoute = null;
        $routeIndex = null;

        if ($routeMatch instanceof RouteMatch) {
            $matchedRoute = $routeMatch->getMatchedRouteName();
            $routeIndex = $routeMatch->getParam('idx');
        }

        $lpa = $this->getLpa();

        if (($matchedRoute != 'lpa/donor/edit') && ($lpa->document->donor instanceof Donor)) {
            $actorsList[] = $this->getActorDetails($lpa->document->donor, 'donor');
        }

        // when edit a cp or on np add/edit page, do not include this cp
        if (($lpa->document->certificateProvider instanceof CertificateProvider) && !in_array($matchedRoute, ['lpa/certificate-provider/edit','lpa/people-to-notify/add','lpa/people-to-notify/edit'])) {
            $actorsList[] = $this->getActorDetails($lpa->document->certificateProvider, 'certificate provider');
        }

        foreach ($lpa->document->primaryAttorneys as $idx => $attorney) {
            if ($matchedRoute == 'lpa/primary-attorney/edit' && $routeIndex == $idx) {
                continue;
            }

            if ($attorney instanceof Human) {
                $actorsList[] = $this->getActorDetails($attorney, 'attorney');
            }
        }

        foreach ($lpa->document->replacementAttorneys as $idx => $attorney) {
            if ($matchedRoute == 'lpa/replacement-attorney/edit' && $routeIndex == $idx) {
                continue;
            }

            if ($attorney instanceof Human) {
                $actorsList[] = $this->getActorDetails($attorney, 'replacement attorney');
            }
        }

        // on cp page, do not include np names for duplication check
        if ($matchedRoute != 'lpa/certificate-provider/add' && $matchedRoute != 'lpa/certificate-provider/edit') {
            foreach ($lpa->document->peopleToNotify as $idx => $notifiedPerson) {
                // when edit an np, do not include this np
                if ($matchedRoute == 'lpa/people-to-notify/edit' && $routeIndex == $idx) {
                    continue;
                }

                $actorsList[] = $this->getActorDetails($notifiedPerson, 'people to notify');
            }
        }

        return $actorsList;
    }

    /**
     * Simple function to format the actor details is a consistent manner
     *
     * @param AbstractData $actorData
     * @param $actorType
     * @return array
     */
    private function getActorDetails(AbstractData $actorData, $actorType)
    {
        $actorDetails = [];

        if (isset($actorData->name) && $actorData->name instanceof Name) {
            $actorDetails = [
                'firstname' => $actorData->name->first,
                'lastname'  => $actorData->name->last,
                'type'      => $actorType
            ];
        }

        return $actorDetails;
    }
}
