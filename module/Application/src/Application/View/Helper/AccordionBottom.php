<?php
namespace Application\View\Helper;

use Application\Model\FormFlowChecker;
use Opg\Lpa\DataModel\Lpa\Lpa;

class AccordionBottom extends AbstractAccordion
{
    /**
     * @param Lpa $lpa
     * @return array|null
     */
    public function __invoke (Lpa $lpa = null)
    {
        if($lpa === null) {
            return null;
        }

        $this->lpa = $lpa;
        
        $routeName = $this->getRouteName();
        $barConfig = $this->getBarConfig($routeName);
        
        // empty if the route does not have accordions.
        if($barConfig == null) {
            return null;
        }
        
        $flowChecker = new FormFlowChecker($lpa);
        $seq = 0;
        $barList = [];
        
        $skip = true;
        foreach($barConfig as $barRouteName => $barDataFuncName) {
            
            // we only care about accessible pages
            if($barRouteName == $flowChecker->getNearestAccessibleRoute($barRouteName)) {
                
                // skip bar items until the one for current page.
                if($barRouteName == $routeName) {
                    $seq++;
                    $skip = false;
                    continue;
                }
                
                if($skip) {
                    $seq++;
                    continue;
                }
                
                // generate bar items starting from the next page.
                if(method_exists($this, $barDataFuncName)) {
                    if($this->$barDataFuncName() === null) break;
                    
                    $seq++;
                    $barList[$seq-1] = [
                            'name'      => $this->getViewScriptName($barDataFuncName),
                            'routeName' => $barRouteName,
                            'lpaId'     => $lpa->id,
                            'params'    => [
                                    'idx'   => $seq,
                                    'values'=> $this->$barDataFuncName()]
                    ];
                }
            }
        }
        
        return $barList;
    }
}
