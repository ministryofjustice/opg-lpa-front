<?php

namespace Application\Model\Service\Signatures;

use Application\Model\Service\Date\DateService;
use Application\Model\Service\Date\IDateService;
use DateTime;
use InvalidArgumentException;
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
        $errors = [];

        $donor = $dates['sign-date-donor'];
        $certificateProvider = $dates['sign-date-certificate-provider'];

        // Donor must be first
        if ($donor > $certificateProvider) {
            $errors['sign-date-certificate-provider'][] = 'The donor must be the first person to sign the LPA';
        }

        $allTimestamps = [
            'sign-date-donor'                => $donor,
            'sign-date-certificate-provider' => $certificateProvider
        ];

        if (isset($dates['sign-date-donor-life-sustaining'])) {
            $donorLifeSustaining = $dates['sign-date-donor-life-sustaining'];
            $allTimestamps['sign-date-donor-life-sustaining'] = $donorLifeSustaining;

            if ($donor < $donorLifeSustaining) {
                $errors['sign-date-donor-life-sustaining'][] = 'The donor must sign Section 5 on the same day or before section 9';
            }
        }

        $minAttorneyDate = $dates['sign-date-attorneys'][0];
        $maxAttorneyDate = $dates['sign-date-attorneys'][0];
        for ($i = 0; $i < count($dates['sign-date-attorneys']); $i++) {
            $timestamp = $dates['sign-date-attorneys'][$i];
            $attorneyKey = 'sign-date-attorney-' . $i;
            $allTimestamps[$attorneyKey] = $timestamp;

            if ($timestamp < $minAttorneyDate) {
                $minAttorneyDate = $timestamp;
            }
            if ($timestamp > $maxAttorneyDate) {
                $maxAttorneyDate = $timestamp;
            }

            // Donor must be first
            if ($donor > $timestamp) {
                $errors[$attorneyKey][] = 'The donor must be the first person to sign the LPA';
            }
        }

        // CP must be next
        if ($certificateProvider > $minAttorneyDate) {
            $errors['sign-date-certificate-provider'][] = 'The Certificate Provider must sign the LPA before the attorneys';
        }

        if (isset($dates['sign-date-applicants']) && count($dates['sign-date-applicants']) > 0) {
            for ($i = 0; $i < count($dates['sign-date-applicants']); $i++) {
                $timestamp = $dates['sign-date-applicants'][$i];
                $applicantKey = 'sign-date-applicant-' . $i;
                $allTimestamps[$applicantKey] = $timestamp;

                // Donor must be first
                if ($donor > $timestamp) {
                    $errors[$applicantKey][] = 'The donor must be the first person to sign the LPA';
                }

                // Applicants must sign on or after last attorney
                if ($timestamp < $maxAttorneyDate) {
                    $errors[$applicantKey][] = 'The applicant must sign on the same day or after all Section 11\'s have been signed';
                }
            }
        }

        $dateService = $dateService ?: new DateService();
        $today = $dateService->getToday()->getTimestamp();
        foreach ($allTimestamps as $timestampKey => $timestamp) {
            if ($timestamp instanceof DateTime) {
                $timestamp = $timestamp->getTimestamp();
            }
            if ($timestamp > $today) {
                if ($timestampKey === 'sign-date-donor') {
                    $errors[$timestampKey][] = 'The donor\'s signature date cannot be in the future';
                } elseif ($timestampKey === 'sign-date-certificate-provider') {
                    $errors[$timestampKey][] = 'The certificate provider\'s signature date cannot be in the future';
                } elseif ($timestampKey === 'sign-date-donor-life-sustaining') {
                    $errors[$timestampKey][] = 'The donor\'s signature date cannot be in the future';
                } elseif (strpos($timestampKey, 'sign-date-attorney-') === 0) {
                    $errors[$timestampKey][] = 'The attorney\'s signature date cannot be in the future';
                } elseif (strpos($timestampKey, 'sign-date-applicant-') === 0) {
                    $errors[$timestampKey][] = 'The applicant\'s signature date cannot be in the future';
                } else {
                    throw new InvalidArgumentException("timestampKey {$timestampKey} was not recognised");
                }
            }
        }

        if (count($errors) > 0) {
            return $errors;
        }

        return true;
    }
}
