<?php

namespace Application\Controller;

use Application\Model\FormFlowChecker;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\Http\RouteMatch;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use RuntimeException;

abstract class AbstractLpaController extends AbstractAuthenticatedController
{
    /**
     * @var LPA The LPA currently referenced in to the URL
     */
    private $lpa;

    /**
     * @var \Application\Model\FormFlowChecker
     */
    private $flowChecker;

    public function onDispatch(MvcEvent $e)
    {
        // Check we have a user set, thus ensuring an authenticated user
        if (($authenticated = $this->checkAuthenticated()) !== true) {
            return $authenticated;
        }

        //  Try to get the lpa for this controller - if we can't find one then redirect to the user dashboard
        $lpa = null;

        try {
            $lpa = $this->getLpa();
        } catch (RuntimeException $rte) {
            //  There was a problem retrieving the LPA so redirect to the user dashboard
            return $this->redirect()->toRoute('user/dashboard');
        }

        # inject lpa into layout.
        $this->layout()->lpa = $lpa;

        /**
         * check the requested route and redirect user to the correct one if the requested route is not available.
         */
        $currentRoute = $e->getRouteMatch()->getMatchedRouteName();

        // get extra input query param from the request url.
        if ($currentRoute == 'lpa/download') {
            $param = $e->getRouteMatch()->getParam('pdf-type');
        } else {
            $param = $e->getRouteMatch()->getParam('idx');
        }

        // call flow checker to get the nearest accessible route.
        $calculatedRoute = $this->getFlowChecker()->getNearestAccessibleRoute($currentRoute, $param);

        // if false, do not run action method.
        if ($calculatedRoute === false) {
            return $this->response;
        }

        // redirect to the calculated route if it is not equal to the current route
        if ($calculatedRoute != $currentRoute) {
            return $this->redirect()->toRoute($calculatedRoute, ['lpa-id' => $lpa->id]);
        }

        // inject lpa into view
        $view = parent::onDispatch($e);

        if (($view instanceof ViewModel) && !($view instanceof JsonModel)) {
            $view->setVariable('lpa', $lpa);
        }

        return $view;
    }

    /**
     * Return an appropriate view model to move to the next route from the current route
     *
     * @return ViewModel|\Zend\Http\Response
     */
    protected function moveToNextRoute()
    {
        if ($this->isPopup()) {
            return new JsonModel(['success' => true]);
        }

        //  Check that the route match is the correct type
        $routeMatch = $this->getEvent()->getRouteMatch();

        if (!$routeMatch instanceof RouteMatch) {
            throw new \RuntimeException('RouteMatch must be an instance of Zend\Mvc\Router\Http\RouteMatch when using the moveToNextRoute function');
        }

        //  Get the current route and the LPA ID to move to the next route
        return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($routeMatch->getMatchedRouteName()), [
            'lpa-id' => $this->getLpa()->id
        ]);
    }

    /**
     * removes replacement attorney decisions that no longer apply to this LPA.
     *
     */
    protected function cleanUpReplacementAttorneyDecisions(){
        $lpa = $this->getServiceLocator()->get('LpaApplicationService')->getApplication((int) $this->getLpa()->id);
        $RACleanupService = $this->getServiceLocator()->get('ReplacementAttorneyCleanup');
        $RACleanupService->cleanUp($lpa, $this->getLpaApplicationService());
    }

    /**
     * removes replacement attorney decisions that no longer apply to this LPA.
     *
     */
    protected function cleanUpApplicant()
    {
        $lpa = $this->getServiceLocator()->get('LpaApplicationService')->getApplication((int) $this->getLpa()->id);
        $applicantCleanupService = $this->getServiceLocator()->get('ApplicantCleanup');
        $applicantCleanupService->cleanUp($lpa, $this->getLpaApplicationService());
    }

    /**
     * Return a flag indicating if this is a request from a popup (XmlHttpRequest)
     *
     * @return bool
     */
    protected function isPopup()
    {
        return $this->getRequest()->isXmlHttpRequest();
    }

    /**
     * Returns the LPA currently referenced in to the URL
     *
     * @return Lpa
     */
    public function getLpa()
    {
        if (!( $this->lpa instanceof Lpa )) {
            throw new RuntimeException('A LPA has not been set');
        }

        return $this->lpa;
    }

    /**
     * Sets the LPA currently referenced in to the URL
     *
     * @param Lpa $lpa
     */
    public function setLpa(Lpa $lpa)
    {
        $this->lpa = $lpa;
    }

    /**
     * @return \Application\Model\FormFlowChecker
     */
    public function getFlowChecker()
    {
        if ($this->flowChecker == null) {
            $formFlowChecker = new FormFlowChecker($this->getLpa());
            $this->flowChecker = $formFlowChecker;
        }

        return $this->flowChecker;
    }

    /**
     * Convert model/seed data for populating into form
     *
     * @param array $modelData - eg. [name=>[title=>'Mr', first=>'John', last=>'Smith']]
     * @return array - eg [name-title=>'Mr', name-first=>'John', name-last=>'Smith']
     */
    protected function flattenData($modelData)
    {
        $formData = [];

        foreach ($modelData as $l1 => $l2) {
            if (is_array($l2)) {
                foreach ($l2 as $name => $l3) {
                    if ($l1=='dob') {
                        $dob = new \DateTime($l3);
                        $formData['dob-date'] = [
                                'day'   => $dob->format('d'),
                                'month' => $dob->format('m'),
                                'year'  => $dob->format('Y'),
                        ];
                    } else {
                        $formData[$l1.'-'.$name] = $l3;
                    }
                }
            } else {
                $formData[$l1] = $l2;
            }
        }

        return $formData;
    }
}
