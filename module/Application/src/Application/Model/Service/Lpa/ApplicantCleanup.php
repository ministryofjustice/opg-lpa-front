<?php

namespace Application\Model\Service\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use Opg\Lpa\DataModel\Lpa\Lpa;

class ApplicantCleanup
{
    /**
     * Cleanup applicant data (whoIsRegistering) if invalid
     *
     * @param Lpa $lpa
     * @param $client
     */
    public function cleanUp(Lpa $lpa, $client)
    {
        $updatedApplicant = $this->getUpdatedApplicant($lpa);
        if ($updatedApplicant !== $lpa->document->whoIsRegistering) {
            $client->setWhoIsRegistering($lpa->id, $updatedApplicant);
        }
    }

    /**
     * @param Lpa $lpa
     * @return array|string
     */
    protected function getUpdatedApplicant(Lpa $lpa)
    {
        $updatedApplicant = $lpa->document->whoIsRegistering;

        //Applicant is only suspicious when it's an array as that means it's one or more of the primary attorneys
        if ($lpa->document !== null && is_array($lpa->document->whoIsRegistering)) {
            $primaryAttorneys = $lpa->document->primaryAttorneys;
            $primaryAttorneyDecisions = $lpa->document->primaryAttorneyDecisions;
            $whoIsRegistering = $lpa->document->whoIsRegistering;

            //If primary attorneys make decisions jointly, all must be applicants
            if ($primaryAttorneyDecisions->how == AbstractDecisions::LPA_DECISION_HOW_JOINTLY) {
                $updatedApplicant = [];
                foreach ($primaryAttorneys as $primaryAttorney) {
                    $updatedApplicant[] = $primaryAttorney->id;
                }
                return $updatedApplicant;
            }

            //Verify all applicant ids are valid
            $updatedApplicant = [];
            foreach ($whoIsRegistering as $id) {
                foreach ($primaryAttorneys as $primaryAttorney) {
                    if ($id == $primaryAttorney->id) {
                        $updatedApplicant[] = $primaryAttorney->id;
                        break;
                    }
                }
            }
        }

        return $updatedApplicant;
    }
}
