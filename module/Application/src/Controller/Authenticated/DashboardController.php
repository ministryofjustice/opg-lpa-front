<?php

namespace Application\Controller\Authenticated;

use Application\Controller\AbstractAuthenticatedController;
use Aws\Exception\AwsException;
use Aws\Lambda\LambdaClient;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class DashboardController extends AbstractAuthenticatedController
{
    public function indexAction()
    {
        $search = $this->params()->fromQuery('search', null);
        $page = $this->params()->fromRoute('page', 1);

        //  Set the items per page for front application lists
        $lpasPerPage = 50;

        //  Get the LPA list summary using a query if provided
        $lpasSummary = $this->getLpaApplicationService()->getLpaSummaries($search, $page, $lpasPerPage);
        $lpas = $lpasSummary['applications'];
        $lpasTotalCount = $lpasSummary['total'];

        //  If there are no LPAs and this is NOT a query, redirect them to create one...
        if (is_null($search) && count($lpas) == 0) {
            return $this->createAction();
        }

        //  Get the pagination control data for these results
        $pagesInRange = 5;
        $paginationControlData = $this->getPaginationControlData($page, $lpasPerPage, $lpasTotalCount, $pagesInRange);

        return new ViewModel([
            'lpas'                  => $lpas,
            'lpaTotalCount'         => $lpasTotalCount,
            'paginationControlData' => $paginationControlData,
            'freeText'              => $search,
            'isSearch'              => (is_string($search) && !empty($search)),
            'user'                  => [
                'lastLogin' => $this->getIdentity()->lastLogin(),
            ],
        ]);
    }

    /**
     * Get the pagination control data from the page settings provided
     *
     * @param $page
     * @param $lpasPerPage
     * @param $lpasTotalCount
     * @param $numberOfPagesInRange
     * @return array
     */
    private function getPaginationControlData($page, $lpasPerPage, $lpasTotalCount, $numberOfPagesInRange)
    {
        //  Determine the total number of pages
        $pageCount = ceil($lpasTotalCount / $lpasPerPage);

        //  If the requested page is higher than allowed then set it to the highest possible value
        if ($page > $pageCount) {
            $page = $pageCount;
        }

        //  Figure out which pages to provide specific links to - pages in range
        //  Start the pages in range array with the current page
        $pagesInRange = [$page];

        for ($i = 0; $i < ($numberOfPagesInRange - 1); $i++) {
            //  Get the current lowest and highest page numbers
            $lowestPage = min($pagesInRange);
            $highestPage = max($pagesInRange);

            //  If this is an even numbered iteration add a higher page number
            if ($i % 2 == 0) {
                //  Try to add a higher page number if possible
                //  If not possible then try to add a lower page number if possible
                if ($highestPage < $pageCount) {
                    $pagesInRange[] = ++$highestPage;
                } elseif ($lowestPage > 1) {
                    $pagesInRange[] = --$lowestPage;
                }
            } else {
                //  Try to add a lower page number if possible
                //  If not possible then try to add a higher page number if possible
                if ($lowestPage > 1) {
                    $pagesInRange[] = --$lowestPage;
                } elseif ($highestPage < $pageCount) {
                    $pagesInRange[] = ++$highestPage;
                }
            }
        }

        //  Sort the page numbers into order
        asort($pagesInRange);

        //  Figure out the first and last item number that are being displayed
        $firstItemNumber = (($page - 1) * $lpasPerPage) + 1;
        $lastItemNumber = min($page * $lpasPerPage, $lpasTotalCount);

        return [
            'page'            => $page,
            'pageCount'       => $pageCount,
            'pagesInRange'    => $pagesInRange,
            'firstItemNumber' => $firstItemNumber,
            'lastItemNumber'  => $lastItemNumber,
            'totalItemCount'  => $lpasTotalCount,
        ];
    }

    /**
     * Creates a new LPA
     *
     * If 'lpa-id' is set, use the passed ID to seed the new LPA.
     */
    public function createAction()
    {
        $seedId = $this->params()->fromRoute('lpa-id');

        //-------------------------------------
        // If we're seeding the new LPA...

        if ($seedId != null) {
            //-------------------------------------
            // Create a new LPA...

            $lpa = $this->getLpaApplicationService()->createApplication();

            if (!$lpa instanceof Lpa) {
                $this->flashMessenger()->addErrorMessage('Error creating a new LPA. Please try again.');

                return $this->redirect()->toRoute('user/dashboard');
            }

            $result = $this->getLpaApplicationService()->setSeed($lpa, (int) $seedId);

            $this->resetSessionCloneData($seedId);

            if ($result !== true) {
                $this->flashMessenger()->addWarningMessage('LPA created but could not set seed');
            }

            // Redirect them to the first page...
            return $this->redirect()->toRoute('lpa/form-type', [ 'lpa-id'=>$lpa->id ]);
        }

        //---

        // Redirect them to the first page, no LPA created
        return $this->redirect()->toRoute('lpa-type-no-id');
    }

    public function deleteLpaAction()
    {
        $page = $this->params()->fromQuery('page');

        $lpaId = $this->getEvent()->getRouteMatch()->getParam('lpa-id');

        if ($this->getLpaApplicationService()->deleteApplication($lpaId) !== true) {
            throw new \RuntimeException('API client failed to delete LPA for id: '.$lpaId);
        }

        $target = 'user/dashboard';
        $params = [];

        if (is_numeric($page)) {
            $target .= '/pagination';

            $params = [
                'page' => $page,
            ];
        }

        return $this->redirect()->toRoute($target, $params);
    }

    public function confirmDeleteLpaAction()
    {
        $page = $this->params()->fromQuery('page');

        $lpaId = $this->getEvent()->getRouteMatch()->getParam('lpa-id');

        $lpa = $this->getLpaApplicationService()->getApplication($lpaId);

        $viewModel = new ViewModel([
            'lpa'  => $lpa,
            'page' => $page,
        ]);

        $viewModel->setTemplate('application/authenticated/dashboard/confirm-delete.twig');

        if ($this->getRequest()->isXmlHttpRequest()) {
            $viewModel->setTerminal(true);
            $viewModel->isPopup = true;
        }

        return $viewModel;
    }

    //---

    /**
     * Displayed when the Terms of use have changed since the user last logged in.
     */
    public function termsAction()
    {
        return new ViewModel();
    }

    public function lpaStatusAction()
    {
        $response =  new Response();

        $lpaId = $this->getEvent()->getRouteMatch()->getParam('lpa-id');

        // Needs 11 digits so if 10 zero pad on the left
        $internalLpaId = "A" . (count($lpaId) < 11 ? str_pad($lpaId, 11, '0', 'STR_PAD_LEFT') : $lpaId);
        
        $client = new LambdaClient([
            'region' => 'eu-west-1',
            'version' => 'latest',
            'credentials' => [
                'key'    => getenv('HACK_AWS_ACCESS_KEY_ID'),
                'secret' => getenv('HACK_AWS_SECRET_ACCESS_KEY'),

            ],
        ]);

        try {
            $result = $client->invoke([
                'FunctionName' => 'get_function',
                'Payload' => json_encode([
                    "toolId" => $internalLpaId
                ]),
            ]);

            $payload = json_decode((string)$result->get('Payload'));

            if ($payload->code == 200) {
                $response->setStatusCode(200);
                $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');
                // TODO - Get actual status from aws payload
                $response->setContent(\GuzzleHttp\json_encode(['status' => 'Pending', 'found' => true]));
            } elseif ($payload->code == 404) {
                $response->setStatusCode(200);
                $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');
                $response->setContent(\GuzzleHttp\json_encode(['status' => 'Unknown', 'found' => false]));
            } else {
                // TODO - Redo log and handle error
                error_log('Did not understand response');

                $response->setStatusCode(200);
                $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');
                $response->setContent(\GuzzleHttp\json_encode(['status' => 'Unknown', 'found' => false]));
            }
        } catch (AwsException $e) {
            // output error message if fails
            error_log($e->getMessage());

            $response->setStatusCode(500);
            $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');
            $response->setContent(\GuzzleHttp\json_encode(['status' => 'Pending', 'found' => false]));
        }

        return $response;
    }

    //------------------------------------------------------------------

    /**
     * This is overridden to prevent people being (accidently?) directed to this controller post-auth.
     *
     * @return bool|\Zend\Http\Response
     */
    protected function checkAuthenticated($allowRedirect = true)
    {
        return parent::checkAuthenticated(false);
    }
}
