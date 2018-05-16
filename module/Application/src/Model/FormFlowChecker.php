<?php

namespace Application\Model;

use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use RuntimeException;

class FormFlowChecker
{
    private $accessibleFunctionMap = [
        'lpa'                                           => 'isLpaAccessible',
        'lpa/form-type'                                 => 'isFormTypeAccessible',
        'lpa-type-no-id'                                => 'isFormTypeAccessible',
        'lpa/donor'                                     => 'isDonorAccessible',
        'lpa/donor/add'                                 => 'isDonorAddAccessible',
        'lpa/donor/edit'                                => 'isDonorEditAccessible',
        'lpa/when-lpa-starts'                           => 'isWhenLpaStartsAccessible',
        'lpa/life-sustaining'                           => 'isLifeSustainingAccessible',
        'lpa/primary-attorney'                          => 'isAttorneyAccessible',
        'lpa/primary-attorney/add'                      => 'isAttorneyAddAccessible',
        'lpa/primary-attorney/edit'                     => 'isAttorneyEditAccessible',
        'lpa/primary-attorney/confirm-delete'           => 'isAttorneyDeleteAccessible',
        'lpa/primary-attorney/delete'                   => 'isAttorneyDeleteAccessible',
        'lpa/primary-attorney/add-trust'                => 'isAttorneyAddTrustAccessible',
        'lpa/how-primary-attorneys-make-decision'       => 'isHowPrimaryAttorneysMakeDecisionAccessible',
        'lpa/replacement-attorney'                      => 'isReplacementAttorneyAccessible',
        'lpa/replacement-attorney/add'                  => 'isReplacementAttorneyAddAccessible',
        'lpa/replacement-attorney/edit'                 => 'isReplacementAttorneyEditAccessible',
        'lpa/replacement-attorney/confirm-delete'       => 'isReplacementAttorneyDeleteAccessible',
        'lpa/replacement-attorney/delete'               => 'isReplacementAttorneyDeleteAccessible',
        'lpa/replacement-attorney/add-trust'            => 'isReplacementAttorneyAddTrustAccessible',
        'lpa/when-replacement-attorney-step-in'         => 'isWhenReplacementAttorneyStepInAccessible',
        'lpa/how-replacement-attorneys-make-decision'   => 'isHowReplacementAttorneysMakeDecisionAccessible',
        'lpa/certificate-provider'                      => 'isCertificateProviderAccessible',
        'lpa/certificate-provider/add'                  => 'isCertificateProviderAddAccessible',
        'lpa/certificate-provider/edit'                 => 'isCertificateProviderEditAccessible',
        'lpa/certificate-provider/confirm-delete'       => 'isCertificateProviderDeleteAccessible',
        'lpa/certificate-provider/delete'               => 'isCertificateProviderDeleteAccessible',
        'lpa/people-to-notify'                          => 'isPeopleToNotifyAccessible',
        'lpa/people-to-notify/add'                      => 'isPeopleToNotifyAddAccessible',
        'lpa/people-to-notify/edit'                     => 'isPeopleToNotifyEditAccessible',
        'lpa/people-to-notify/confirm-delete'           => 'isPeopleToNotifyDeleteAccessible',
        'lpa/people-to-notify/delete'                   => 'isPeopleToNotifyDeleteAccessible',
        'lpa/instructions'                              => 'isInstructionsAccessible',
        'lpa/download'                                  => 'isDownloadAccessible',
        'lpa/download/draft'                            => 'isDownloadAccessible',
        'lpa/download/file'                             => 'isDownloadAccessible',
        'lpa/applicant'                                 => 'isApplicantAccessible',
        'lpa/correspondent'                             => 'isCorrespondentAccessible',
        'lpa/correspondent/edit'                        => 'isCorrespondentEditAccessible',
        'lpa/who-are-you'                               => 'isWhoAreYouAccessible',
        'lpa/repeat-application'                        => 'isRepeatApplicationAccessible',
        'lpa/fee-reduction'                             => 'isFeeReductionAccessible',
        'lpa/checkout'                                  => 'isPaymentAccessible',
        'lpa/checkout/cheque'                           => 'isPaymentAccessible',
        'lpa/checkout/pay'                              => 'isPaymentAccessible',
        'lpa/checkout/pay/response'                     => 'isPaymentAccessible',
        'lpa/checkout/confirm'                          => 'isPaymentAccessible',
        'lpa/complete'                                  => 'isCompleteAccessible',
        'lpa/more-info-required'                        => 'isMoreInfoRequiredAccessible',
        'lpa/date-check'                                => 'isApplicantAccessible',
        'lpa/date-check/complete'                       => 'isCompleteAccessible',
        'lpa/date-check/valid'                          => 'isApplicantAccessible',
        'lpa/summary'                                   => 'isInstructionsAccessible',
        'lpa/view-docs'                                 => 'isViewDocsAccessible',
        'lpa/reuse-details'                             => 'isReuseDetailsAccessible',
    ];

    private $nextRouteMap = [
        'lpa-type-no-id'                                => 'lpa/donor',
        'lpa/form-type'                                 => 'lpa/donor',
        'lpa/donor'                                     => [
            'lpa/when-lpa-starts',
            'lpa/life-sustaining'
        ],
        'lpa/donor/add'                                 => 'lpa/donor',
        'lpa/donor/edit'                                => 'lpa/donor',
        'lpa/when-lpa-starts'                           => 'lpa/primary-attorney',
        'lpa/life-sustaining'                           => 'lpa/primary-attorney',
        'lpa/primary-attorney'                          => [
            'lpa/how-primary-attorneys-make-decision',
            'lpa/replacement-attorney'
        ],
        'lpa/primary-attorney/add'                      => 'lpa/primary-attorney',
        'lpa/primary-attorney/edit'                     => 'lpa/primary-attorney',
        'lpa/primary-attorney/delete'                   => 'lpa/primary-attorney',
        'lpa/primary-attorney/add-trust'                => 'lpa/primary-attorney',
        'lpa/how-primary-attorneys-make-decision'       => 'lpa/replacement-attorney',
        'lpa/replacement-attorney'                      => [
            'lpa/when-replacement-attorney-step-in',
            'lpa/how-replacement-attorneys-make-decision',
            'lpa/certificate-provider'
        ],
        'lpa/replacement-attorney/add'                  => 'lpa/replacement-attorney',
        'lpa/replacement-attorney/edit'                 => 'lpa/replacement-attorney',
        'lpa/replacement-attorney/delete'               => 'lpa/replacement-attorney',
        'lpa/replacement-attorney/add-trust'            => 'lpa/replacement-attorney',
        'lpa/when-replacement-attorney-step-in'         => [
            'lpa/how-replacement-attorneys-make-decision',
            'lpa/certificate-provider'
        ],
        'lpa/how-replacement-attorneys-make-decision'   => 'lpa/certificate-provider',
        'lpa/certificate-provider'                      => 'lpa/people-to-notify',
        'lpa/certificate-provider/add'                  => 'lpa/certificate-provider',
        'lpa/certificate-provider/edit'                 => 'lpa/certificate-provider',
        'lpa/certificate-provider/delete'               => 'lpa/certificate-provider',
        'lpa/people-to-notify'                          => 'lpa/instructions',
        'lpa/people-to-notify/add'                      => 'lpa/people-to-notify',
        'lpa/people-to-notify/edit'                     => 'lpa/people-to-notify',
        'lpa/people-to-notify/delete'                   => 'lpa/people-to-notify',
        'lpa/instructions'                              => 'lpa/applicant',
        'lpa/applicant'                                 => 'lpa/correspondent',
        'lpa/correspondent'                             => 'lpa/who-are-you',
        'lpa/correspondent/edit'                        => 'lpa/correspondent',
        'lpa/who-are-you'                               => 'lpa/repeat-application',
        'lpa/repeat-application'                        => 'lpa/fee-reduction',
        'lpa/fee-reduction'                             => 'lpa/checkout',
        'lpa/payment'                                   => 'lpa/payment/summary',
        'lpa/payment/summary'                           => 'lpa/complete',
        'lpa/checkout/cheque'                           => 'lpa/complete',
        'lpa/checkout/confirm'                          => 'lpa/complete',
        'lpa/checkout/pay/response'                     => 'lpa/complete',
    ];

    private $returnFunctionMap = [
        'lpa/form-type'                                 => 'returnToFormType',
        'lpa/donor'                                     => 'returnToDonor',
        'lpa/when-lpa-starts'                           => 'returnToWhenLpaStarts',
        'lpa/life-sustaining'                           => 'returnToLifeSustaining',
        'lpa/primary-attorney'                          => 'returnToPrimaryAttorney',
        'lpa/how-primary-attorneys-make-decision'       => 'returnToHowPrimaryAttorneysMakeDecision',
        'lpa/replacement-attorney'                      => 'returnToReplacementAttorney',
        'lpa/when-replacement-attorney-step-in'         => 'returnToWhenReplacementAttorneyStepIn',
        'lpa/how-replacement-attorneys-make-decision'   => 'returnToHowReplacementAttorneysMakeDecision',
        'lpa/certificate-provider'                      => 'returnToCertificateProvider',
        'lpa/people-to-notify'                          => 'returnToPeopleToNotify',
        'lpa/instructions'                              => 'returnToInstructions',
        'lpa/applicant'                                 => 'returnToApplicant',
        'lpa/correspondent'                             => 'returnToCorrespondent',
        'lpa/who-are-you'                               => 'returnToWhoAreYou',
        'lpa/repeat-application'                        => 'returnToRepeatApplication',
        'lpa/fee-reduction'                             => 'returnToFeeReduction',
        'lpa/checkout'                                  => 'returnToCheckout',
        'lpa/view-docs'                                 => 'returnToViewDocs',
    ];

    /**
     * Route for the final check screen
     *
     * @var string
     */
    private $finalCheckRoute = 'lpa/checkout';

    /**
     * String key to show that a route is not applicable for the LPA concerned
     *
     * @var string
     */
    private $routeNotApplicableKey = 'NA';

    /**
     * @var Lpa
     */
    private $lpa;

    /**
     * FormFlowChecker constructor.
     * @param Lpa|null $lpa
     */
    public function __construct(Lpa $lpa = null)
    {
        $this->lpa = $lpa;
    }

    /**
     * Given a specific route name return either that route name (if it is accessible)
     * or the latter most route possible for the LPA concerned
     *
     * @param string $currentRouteName - a route name
     * @param mixed $param - person-idx or pdf-type
     * @throws RuntimeException
     * @return string - a route name
     */
    public function getNearestAccessibleRoute($currentRouteName, $param = null)
    {
        //  If there is no LPA then just return to the dashboard
        if (!$this->lpa instanceof Lpa) {
            return 'user/dashboard';
        }

        // check if route exists in the mapping table.
        if (!array_key_exists($currentRouteName, $this->accessibleFunctionMap)) {
            throw new RuntimeException('Check() received an undefined route: '. $currentRouteName);
        }

        // Once an LPA has been locked, only allow the following pages.
        if (!empty($this->lpa)
            && $this->lpa->isLocked() === true
            && $currentRouteName != 'lpa/complete'
            && strpos($currentRouteName, 'lpa/date-check') === false
            && $currentRouteName != 'lpa/download'
            && $currentRouteName != 'lpa/download/file') {
            return 'lpa/view-docs';
        }

        $checkFunction = $this->accessibleFunctionMap[$currentRouteName];

        $checkValue = call_user_func([$this, $checkFunction], $param);

        if ($checkValue === true) {
            return $currentRouteName;
        } else {
            if (array_key_exists($checkValue, $this->accessibleFunctionMap)) {
                return $this->getNearestAccessibleRoute($checkValue);
            } else {
                return $checkValue;
            }
        }
    }

    /**
     * Given a route name (and an option parameter) return the next route in the LPA flow
     * If there is more than one route possible then determine which should be used for the LPA concerned
     *
     * @param $currentRoute
     * @param $personIdex
     * @return string
     */
    public function nextRoute($currentRoute, $personIdex = null)
    {
        //  If the final check route is accessible then that is automatically the next route
        if ($this->finalCheckAccessible()) {
            return $this->finalCheckRoute;
        }

        $nextRoute = $currentRoute;

        if (array_key_exists($currentRoute, $this->nextRouteMap)) {
            $nextRouteConfig = $this->nextRouteMap[$currentRoute];

            if (is_string($nextRouteConfig)) {
                $nextRoute = $nextRouteConfig;
            } elseif (is_array($nextRouteConfig)) {
                foreach ($nextRouteConfig as $nextRouteOption) {
                    if ($this->getNearestAccessibleRoute($nextRouteOption, $personIdex) == $nextRouteOption) {
                        $nextRoute = $nextRouteOption;
                        break;
                    }
                }
            }
        }

        return $nextRoute;
    }

    /**
     * Step forwards through the LPA flow until we find a route that is not
     * accessible (based on the associated LPA) then return the last working route
     *
     * @return string
     */
    public function backToForm()
    {
        $lastValidRoute = '';

        foreach ($this->returnFunctionMap as $route => $function) {
            $canAccess = call_user_func([$this, $function]);

            if ($canAccess === true) {
                $lastValidRoute = $route;
            } elseif ($canAccess === false) {
                break;
            }
        }

        return $lastValidRoute;
    }

    /**
     * Centralised function to indicate if the final check page (checkout) should be available for the current LPA
     *
     * @return bool
     */
    public function finalCheckAccessible()
    {
        if ($this->lpa instanceof Lpa) {
            return ($this->finalCheckRoute == $this->backToForm());
        }

        return false;
    }

    public function getRouteOptions(/** @noinspection PhpUnusedParameterInspection */$route)
    {
        return [];
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isLpaAccessible()
    {
        if ($this->lpa->hasDocument()) {
            return true;
        }

        return 'user/dashboard';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isFormTypeAccessible()
    {
        if ($this->lpa->hasDocument()) {
            return true;
        }

        return 'user/dashboard';
    }

    private function isDonorAccessible()
    {
        if ($this->lpa->hasType()) {
            return true;
        }

        return 'lpa/form-type';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isDonorAddAccessible()
    {
        return $this->isDonorAccessible();
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isDonorEditAccessible()
    {
        if ($this->lpa->hasDonor()) {
            return true;
        }

        return 'lpa/donor';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isLifeSustainingAccessible()
    {
        if ($this->lpa->hasDonor() && $this->lpa->getDocument()->getType() == Document::LPA_TYPE_HW) {
            return true;
        }

        return 'lpa/donor';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isWhenLpaStartsAccessible()
    {
        if ($this->lpa->hasDonor() && $this->lpa->getDocument()->getType() == Document::LPA_TYPE_PF) {
            return true;
        }

        return 'lpa/donor';
    }

    private function isAttorneyAccessible()
    {
        if ($this->lpa->hasType()) {
            if ($this->lpa->getDocument()->getType() == Document::LPA_TYPE_PF) {
                if ($this->lpa->hasWhenLpaStarts()) {
                    return true;
                }

                return 'lpa/when-lpa-starts';
            } else {
                if ($this->lpa->hasLifeSustaining()) {
                    return true;
                }

                return 'lpa/life-sustaining';
            }
        }

        return 'lpa/form-type';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isAttorneyAddAccessible()
    {
        if ($this->isAttorneyAccessible() === true) {
            return true;
        }

        return 'lpa/primary-attorney';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isAttorneyEditAccessible($idx)
    {
        if ($this->isAttorneyAccessible() === true && $this->lpa->hasPrimaryAttorney($idx)) {
            return true;
        }

        return 'lpa/primary-attorney';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isAttorneyDeleteAccessible($idx)
    {
        if ($this->isAttorneyAccessible() === true && $this->lpa->hasPrimaryAttorney($idx)) {
            return true;
        }

        return 'lpa/primary-attorney';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isAttorneyAddTrustAccessible()
    {
        if ($this->isAttorneyAccessible() === true && !$this->lpa->hasTrustCorporation('primary')) {
            return true;
        }

        return 'lpa/primary-attorney';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isHowPrimaryAttorneysMakeDecisionAccessible()
    {
        if ($this->lpa->hasMultiplePrimaryAttorneys()) {
            return true;
        }

        return 'lpa/primary-attorney';
    }

    private function isReplacementAttorneyAccessible()
    {
        if ($this->lpa->hasMultiplePrimaryAttorneys()) {
            if ($this->lpa->isHowPrimaryAttorneysMakeDecisionHasValue()) {
                return true;
            }

            return 'lpa/how-primary-attorneys-make-decision';
        } elseif ($this->lpa->hasPrimaryAttorney()) {
            return true;
        }

        return 'lpa/primary-attorney';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isReplacementAttorneyAddAccessible()
    {
        if ($this->isReplacementAttorneyAccessible() === true) {
            return true;
        }

        return 'lpa/replacement-attorney';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isReplacementAttorneyEditAccessible($idx)
    {
        if ($this->isReplacementAttorneyAccessible() === true && $this->lpa->hasReplacementAttorney($idx)) {
            return true;
        }

        return 'lpa/replacement-attorney';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isReplacementAttorneyDeleteAccessible($idx)
    {
        if ($this->isReplacementAttorneyAccessible() === true && $this->lpa->hasReplacementAttorney($idx)) {
            return true;
        }

        return 'lpa/replacement-attorney';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isReplacementAttorneyAddTrustAccessible()
    {
        if ($this->isReplacementAttorneyAccessible() === true && !$this->lpa->hasTrustCorporation('replacement')) {
            return true;
        }

        return 'lpa/replacement-attorney';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isWhenReplacementAttorneyStepInAccessible()
    {
        if ($this->lpa->hasReplacementAttorney() && $this->lpa->hasMultiplePrimaryAttorneys()
            && $this->lpa->isHowPrimaryAttorneysMakeDecisionJointlyAndSeverally()) {
            return true;
        }

        return 'lpa/replacement-attorney';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isHowReplacementAttorneysMakeDecisionAccessible()
    {
        if ($this->lpa->hasMultipleReplacementAttorneys()) {
            if (count($this->lpa->getDocument()->getPrimaryAttorneys()) == 1
                || $this->lpa->isWhenReplacementAttorneyStepInWhenLastPrimaryUnableAct()
                || $this->lpa->isHowPrimaryAttorneysMakeDecisionJointly()) {
                return true;
            } else {
                if ($this->lpa->hasMultiplePrimaryAttorneys()
                    && $this->lpa->isHowPrimaryAttorneysMakeDecisionJointlyAndSeverally()) {
                    return 'lpa/when-replacement-attorney-step-in';
                }
            }
        }

        return 'lpa/replacement-attorney';
    }

    private function isCertificateProviderAccessible()
    {
        if ($this->lpa->hasPrimaryAttorney() && (
           ($this->lpaHasSinglePrimaryAttorney() && $this->lpaHasNoReplacementAttorney()
               && $this->metadataIsPresent(Lpa::REPLACEMENT_ATTORNEYS_CONFIRMED))
           || ($this->lpaHasSinglePrimaryAttorney() && $this->lpaHasSingleReplacementAttorney())
           || ($this->lpaHasSinglePrimaryAttorney() && $this->lpa->hasMultipleReplacementAttorneys()
               && $this->lpa->isHowReplacementAttorneysMakeDecisionHasValue())
           || ($this->lpa->hasMultiplePrimaryAttorneys() && $this->lpa->isHowPrimaryAttorneysMakeDecisionHasValue()
               && $this->lpaHasNoReplacementAttorney()
               && $this->metadataIsPresent(Lpa::REPLACEMENT_ATTORNEYS_CONFIRMED))
           || ($this->lpa->hasMultiplePrimaryAttorneys() && $this->lpa->isHowPrimaryAttorneysMakeDecisionDepends()
               && $this->lpa->hasMultipleReplacementAttorneys())
           || ($this->lpa->hasMultiplePrimaryAttorneys() && ($this->lpa->isHowPrimaryAttorneysMakeDecisionJointly()
                   || $this->lpa->isHowPrimaryAttorneysMakeDecisionDepends())
               && $this->lpaHasSingleReplacementAttorney())
           || ($this->lpa->hasMultiplePrimaryAttorneys() && $this->lpa->isHowPrimaryAttorneysMakeDecisionJointly()
               && $this->lpa->hasMultipleReplacementAttorneys()
               && $this->lpa->isHowReplacementAttorneysMakeDecisionHasValue())
           || ($this->lpa->hasMultiplePrimaryAttorneys()
               && $this->lpa->isHowPrimaryAttorneysMakeDecisionJointlyAndSeverally()
               && $this->lpaHasSingleReplacementAttorney() && $this->lpa->isWhenReplacementAttorneyStepInHasValue())
           || ($this->lpa->hasMultiplePrimaryAttorneys()
               && $this->lpa->isHowPrimaryAttorneysMakeDecisionJointlyAndSeverally()
               && $this->lpa->hasMultipleReplacementAttorneys() && $this->lpa->isWhenReplacementAttorneyStepInHasValue()
               && ($this->lpa->getDocument()->getReplacementAttorneyDecisions()->getWhen()
                   != ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST))
           || ($this->lpa->hasMultiplePrimaryAttorneys()
               && $this->lpa->isHowPrimaryAttorneysMakeDecisionJointlyAndSeverally()
               && $this->lpa->hasMultipleReplacementAttorneys()
               && $this->lpa->isWhenReplacementAttorneyStepInWhenLastPrimaryUnableAct()
               && $this->lpa->isHowReplacementAttorneysMakeDecisionHasValue())
        )) {
            return true;
        } else {
            if ($this->lpa->hasMultipleReplacementAttorneys() && (
                count($this->lpa->getDocument()->getPrimaryAttorneys()) == 1
                || ($this->lpa->hasMultiplePrimaryAttorneys() && $this->lpa->isHowPrimaryAttorneysMakeDecisionJointly())
                || ($this->lpa->hasMultiplePrimaryAttorneys()
                    && $this->lpa->isHowPrimaryAttorneysMakeDecisionJointlyAndSeverally()
                    && $this->lpa->isWhenReplacementAttorneyStepInWhenLastPrimaryUnableAct())
            ) && (
                !$this->lpa->isHowReplacementAttorneysMakeDecisionHasValue()
                || $this->lpa->getDocument()->getReplacementAttorneyDecisions()->getHow() == null)
            ) {
                return 'lpa/how-replacement-attorneys-make-decision';
            } elseif ($this->lpa->hasReplacementAttorney() && $this->lpa->hasMultiplePrimaryAttorneys()
                && $this->lpa->isHowPrimaryAttorneysMakeDecisionJointlyAndSeverally()) {
                return 'lpa/when-replacement-attorney-step-in';
            }

            return 'lpa/replacement-attorney';
        }
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isCertificateProviderAddAccessible()
    {
        if ($this->isCertificateProviderAccessible() === true) {
            return true;
        }

        return 'lpa/certificate-provider';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isCertificateProviderEditAccessible()
    {
        if ($this->isCertificateProviderAccessible() === true && $this->lpa->hasCertificateProvider()) {
            return true;
        }

        return 'lpa/certificate-provider';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isCertificateProviderDeleteAccessible()
    {
        if ($this->isCertificateProviderAccessible() === true && $this->lpa->hasCertificateProvider()) {
            return true;
        }

        return 'lpa/certificate-provider';
    }

    private function isPeopleToNotifyAccessible()
    {
        if ($this->isCertificateProviderAccessible() === true && ($this->lpa->hasCertificateProviderSkipped()
                || $this->lpa->hasCertificateProvider())) {
            return true;
        }

        return 'lpa/certificate-provider';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isPeopleToNotifyAddAccessible()
    {
        if ($this->isPeopleToNotifyAccessible() === true) {
            return true;
        }

        return 'lpa/people-to-notify';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isPeopleToNotifyEditAccessible($idx)
    {
        if ($this->isPeopleToNotifyAccessible() === true && $this->lpa->hasPeopleToNotify($idx)) {
            return true;
        }

        return 'lpa/people-to-notify';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isPeopleToNotifyDeleteAccessible($idx)
    {
        if ($this->isPeopleToNotifyAccessible() === true && $this->lpa->hasPeopleToNotify($idx)) {
            return true;
        }

        return 'lpa/people-to-notify';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isInstructionsAccessible()
    {
        if ($this->isPeopleToNotifyAccessible() === true && $this->peopleToNotifyHasBeenConfirmed()) {
            return true;
        }

        return 'lpa/people-to-notify';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isDownloadAccessible(/** @noinspection PhpUnusedParameterInspection */$pdfType)
    {
        return true;
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isApplicantAccessible()
    {
        if ($this->lpa->hasInstructionOrPreference()) {
            return true;
        }

        return 'lpa/instructions';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isCorrespondentAccessible()
    {
        if ($this->lpa->hasApplicant()) {
            return true;
        }

        return 'lpa/applicant';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isCorrespondentEditAccessible()
    {
        if ($this->lpa->hasApplicant()) {
            return true;
        }

        return 'lpa/applicant';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isWhoAreYouAccessible()
    {
        if ($this->lpa->hasCorrespondent()) {
            return true;
        }

        return 'lpa/correspondent';
    }

    private function isRepeatApplicationAccessible()
    {
        if ($this->isWhoAreYouAnsweredAfterSpecifyingCorrespondent()) {
            return true;
        }

        return 'lpa/who-are-you';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isFeeReductionAccessible()
    {
        if ($this->isRepeatApplicationAccessible() === true
            && $this->metadataIsPresent(Lpa::REPEAT_APPLICATION_CONFIRMED)) {
            return true;
        }

        return 'lpa/repeat-application';
    }

    private function isPaymentAccessible()
    {
        if ($this->lpa->getPayment() instanceof Payment) {
            return true;
        }

        return 'lpa/fee-reduction';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isOnlinePaymentSuccessAccessible()
    {
        if ($this->isPaymentAccessible() === true && $this->lpa->getPayment()->getMethod() == 'card') {
            return true;
        }

        return 'lpa/checkout';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isOnlinePaymentFailureAccessible()
    {
        if ($this->isPaymentAccessible() === true && $this->lpa->getPayment()->getMethod() == 'card') {
            return true;
        }

        return 'lpa/checkout';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isOnlinePaymentCancelAccessible()
    {
        if ($this->isPaymentAccessible() === true && $this->lpa->getPayment()->getMethod() == 'card') {
            return true;
        }

        return 'lpa/checkout';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isCompleteAccessible()
    {
        if ($this->lpa->isPaymentResolved()) {
            return true;
        }

        return 'lpa/checkout';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isMoreInfoRequiredAccessible()
    {
        return true;
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isViewDocsAccessible()
    {
        if ($this->lpa->isPaymentResolved() && $this->lpa->getCompletedAt() !== null) {
            return true;
        }

        return 'lpa/checkout';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function isReuseDetailsAccessible()
    {
        return true;
    }

    private function peopleToNotifyHasBeenConfirmed()
    {
        return ($this->lpa->hasCertificateProvider() && count($this->lpa->getDocument()->getPeopleToNotify()) > 0
            || $this->metadataIsPresent(Lpa::PEOPLE_TO_NOTIFY_CONFIRMED));
    }

    protected function replacementAttorneyHasBeenConfirmed()
    {
        return ($this->lpa->hasPrimaryAttorney() && count($this->lpa->getDocument()->getReplacementAttorneys()) > 0
            || $this->metadataIsPresent(Lpa::REPLACEMENT_ATTORNEYS_CONFIRMED));
    }

    protected function lpaHasSingleReplacementAttorney()
    {
        return ($this->lpa->hasReplacementAttorney()
            && count($this->lpa->getDocument()->getReplacementAttorneys()) == 1);
    }

    protected function lpaHasNoReplacementAttorney()
    {
        return (count($this->lpa->getDocument()->getReplacementAttorneys()) == 0);
    }

    protected function lpaHasSinglePrimaryAttorney()
    {
        return ($this->lpa->hasPrimaryAttorney() && count($this->lpa->getDocument()->getPrimaryAttorneys()) == 1);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function returnToFormType()
    {
        return true;
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function returnToDonor()
    {
        return $this->lpa->hasDonor();
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function returnToLifeSustaining()
    {
        if ($this->lpa->getDocument()->getType() != Document::LPA_TYPE_HW) {
            return $this->routeNotApplicableKey;
        }

        return $this->lpa->hasLifeSustaining();
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function returnToWhenLpaStarts()
    {
        if ($this->lpa->getDocument()->getType() != Document::LPA_TYPE_PF) {
            return $this->routeNotApplicableKey;
        }

        return $this->lpa->hasWhenLpaStarts();
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function returnToPrimaryAttorney()
    {
        return $this->lpa->hasPrimaryAttorney();
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function returnToHowPrimaryAttorneysMakeDecision()
    {
        //  Only required if there are multiple primary attorneys
        if (!$this->lpa->hasMultiplePrimaryAttorneys()) {
            return "NA";
        }

        return $this->lpa->isHowPrimaryAttorneysMakeDecisionHasValue();
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function returnToReplacementAttorney()
    {
        return $this->replacementAttorneyHasBeenConfirmed();
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function returnToWhenReplacementAttorneyStepIn()
    {
        if (!$this->lpa->hasReplacementAttorney() || !$this->lpa->hasMultiplePrimaryAttorneys()
            || !$this->lpa->isHowPrimaryAttorneysMakeDecisionJointlyAndSeverally()) {
            return "NA";
        }

        return $this->lpa->isWhenReplacementAttorneyStepInHasValue();
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function returnToHowReplacementAttorneysMakeDecision()
    {
        if (!$this->lpa->hasMultipleReplacementAttorneys()) {
            return "NA";
        }

        if (count($this->lpa->getDocument()->getPrimaryAttorneys()) != 1
            && !$this->lpa->isWhenReplacementAttorneyStepInWhenLastPrimaryUnableAct()
            && !$this->lpa->isHowPrimaryAttorneysMakeDecisionJointly()) {
            return "NA";
        }

        return $this->lpa->isHowReplacementAttorneysMakeDecisionHasValue();
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function returnToCertificateProvider()
    {
        return ($this->lpa->hasCertificateProviderSkipped() || $this->lpa->hasCertificateProvider());
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function returnToPeopleToNotify()
    {
        return $this->peopleToNotifyHasBeenConfirmed();
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function returnToInstructions()
    {
        return (!is_null($this->lpa->getDocument()->getInstruction())
            || !is_null($this->lpa->getDocument()->getPreference()));
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function returnToApplicant()
    {
        return $this->lpa->hasApplicant();
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function returnToCorrespondent()
    {
        return $this->lpa->hasCorrespondent();
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function returnToWhoAreYou()
    {
        return $this->isWhoAreYouAnsweredAfterSpecifyingCorrespondent();
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function returnToRepeatApplication()
    {
        return $this->metadataIsPresent(Lpa::REPEAT_APPLICATION_CONFIRMED);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function returnToFeeReduction()
    {
        return $this->lpa->getPayment() instanceof Payment;
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function returnToCheckout()
    {
        return $this->lpa->getPayment() instanceof Payment &&
            ($this->lpa->isEligibleForFeeReduction() || $this->lpa->getPayment()->getAmount() > 0);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function returnToViewDocs()
    {
        return $this->lpa->isPaymentResolved() && $this->lpa->getCompletedAt() !== null;
    }

    private function metadataIsPresent($metadataKey)
    {
        return array_key_exists($metadataKey, $this->lpa->getMetadata());
    }

    /**
     * @return bool
     */
    private function isWhoAreYouAnsweredAfterSpecifyingCorrespondent(): bool
    {
        return ($this->lpa->hasCorrespondent() && $this->lpa->isWhoAreYouAnswered() == true);
    }
}
