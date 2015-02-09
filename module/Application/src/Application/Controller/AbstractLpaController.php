<?php
namespace Application\Controller;

use RuntimeException;

use Zend\Mvc\MvcEvent;
use Application\Model\FormFlowChecker;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Zend\View\Model\ViewModel;

abstract class AbstractLpaController extends AbstractAuthenticatedController implements LpaAwareInterface
{
    /**
     * @var LPA The LPA currently referenced in to the URL
     */
    private $lpa;
    
    /**
     * @var Application\Model\Service\Lpa\Application
     */
    protected $lpaService;
    
    public function onDispatch(MvcEvent $e)
    {
        $this->lpaService = $this->getServiceLocator()->get('LpaApplicationService');
        
        # load content header in the layout if controller has a $contentHeader
        if(isset($this->contentHeader)) {
            $this->layout()->contentHeader = $this->contentHeader;
        }
        
        # inject lpa into layout.
        $this->layout()->lpa = $this->getLpa();
        
        # @todo: remove the lines below to the return $view line once form data can persist.
        $view = parent::onDispatch($e);
        if($view instanceof ViewModel) {
            $view->setVariable('lpa', $this->getLpa());
        }
        
        return $view;
        
        /**
         * check the requested route and redirect user to the correct one if the requested route is not available.
         */   
        $formFlowChecker = $this->getFlowChecker();
        $currentRoute = $e->getRouteMatch()->getMatchedRouteName();
        $personIndex = $e->getRouteMatch()->getParam('person_index');
        
        $calculatedRoute = $formFlowChecker->check($currentRoute, $personIndex);
        
        if($calculatedRoute && ($calculatedRoute != $currentRoute)) {
            return $this->redirect()->toRoute($calculatedRoute);
        }
        
        // inject lpa into view
        $view = parent::onDispatch($e);
        if($view instanceof ViewModel) {
            $view->setVariable('lpa', $this->getLpa());
        }
        
        return $view;
    }
    
    /**
     * Returns the LPA currently referenced in to the URL
     *
     * @return Lpa
     */
    public function getLpa ()
    {
        if( !( $this->lpa instanceof Lpa ) ){
            throw new RuntimeException('A LPA has not been set');
        }
        return $this->lpa;
    }
    
    /**
     * Sets the LPA currently referenced in to the URL
     *
     * @param Lpa $lpa
     */
    public function setLpa ( Lpa $lpa )
    {
        $this->lpa = $lpa;
    }
    
    public function getFlowChecker()
    {
        $formFlowChecker = new FormFlowChecker($this->getLpa());
        $formFlowChecker->setLpa($this->getLpa());
        
        return $formFlowChecker;
    }
}
