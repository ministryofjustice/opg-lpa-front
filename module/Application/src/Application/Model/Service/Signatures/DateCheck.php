<?php

namespace Application\Model\Service\Signatures;

use Application\Model\Service\Date\DateService;
use Application\Model\Service\Date\IDateService;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

class DateCheck implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * Check that the donor, certificate provider, and attorneys
     * signed the LPA in the correct order
     *
     * Expects and array [
     *  'donor' => date,
     *  'certificate-provider' => date,
     *    'attorneys' => [
     *      date,
     *      date, // 1 or more attorney dates
     *    ]
     *  ];
     *
     * @param   array $dates
     * @param IDateService $dateService
     * @return array|bool List of errors or true if no errors
     */
    public static function checkDates(array $dates, $dateService = null)
    {
        $donor = $dates['donor'];
        $certificateProvider = $dates['certificate-provider'];

        $allDates = [
            $donor,
            $certificateProvider
        ];

        if (isset($dates['donor-life-sustaining'])) {
            $donorLifeSustaining = $dates['donor-life-sustaining'];
            $allDates[] = $donorLifeSustaining;
        }

        $minAttorneyDate = $dates['attorneys'][0];
        $maxAttorneyDate = $dates['attorneys'][0];
        $allDates[] = $minAttorneyDate;
        for ($i = 1; $i < count($dates['attorneys']); $i++) {
            $timestamp = $dates['attorneys'][$i];
            $allDates[] = $timestamp;

            if ($timestamp < $minAttorneyDate) {
                $minAttorneyDate = $timestamp;
            }
            if ($timestamp > $maxAttorneyDate) {
                $maxAttorneyDate = $timestamp;
            }
        }

        $minApplicantDate = $maxAttorneyDate;
        if (isset($dates['applicants'])) {
            $minApplicantDate = $dates['applicants'][0];
            $allDates[] = $minApplicantDate;
            for ($i = 1; $i < count($dates['applicants']); $i++) {
                $timestamp = $dates['applicants'][$i];
                $allDates[] = $timestamp;

                if ($timestamp < $minApplicantDate) {
                    $minApplicantDate = $timestamp;
                }
            }
        }

        $dateService = $dateService ?: new DateService();
        $today = $dateService->getToday();
        foreach ($allDates as $date) {
            if ($date > $today) {
                return 'No sign date can be in the future.';
            }
        }

        if (isset($donorLifeSustaining) && $donor < $donorLifeSustaining) {
            return 'The donor must sign Section 5 on the same day or before section 9.';
        }

        // Donor must be first
        if ($donor > $certificateProvider || $donor > $minAttorneyDate) {
            return 'The donor must be the first person to sign the LPA.';
        }

        // CP must be next
        if ($certificateProvider > $minAttorneyDate) {
            return 'The Certificate Provider must sign the LPA before the attorneys.';
        }

        // Applicants must sign on or after last attorney
        if ($minApplicantDate < $maxAttorneyDate) {
            if (count($dates['applicants']) > 1) {
                return 'The applicants must sign on the same day or after all Section 11\'s have been signed.';
            }
            return 'The applicant must sign on the same day or after all Section 11\'s have been signed.';
        }

        return true;
    }
}
