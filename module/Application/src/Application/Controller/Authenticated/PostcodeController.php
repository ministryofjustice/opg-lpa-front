<?php
namespace Application\Controller\Authenticated;

use Zend\View\Model\JsonModel;
use Application\Controller\AbstractAuthenticatedController;

class PostcodeController extends AbstractAuthenticatedController {

    /**
     * Allow access to this controller before About You details are set.
     *
     * @var bool
     */
    protected $excludeFromAboutYouCheck = true;

    public function indexAction(){

        $usingMojDsdPostcodeService = $this->cache()->getItem('use-new-postcode-lookup-method') == 1;
        
        $postcode = $this->params()->fromQuery('postcode');
        
        return $this->postcodeLookup($usingMojDsdPostcodeService, $postcode);
        
    } // function
    
    private function postcodeLookup($usingMojDsdPostcodeService, $postcode) {
        
        if ($usingMojDsdPostcodeService) {
            $service = $this->getServiceLocator()->get('AddressLookupMoj');
        } else {
            $service = $this->getServiceLocator()->get('AddressLookupPostcodeAnywhere');
        }
        
        //-----------------------
        // Postcode lookup
    
        if( isset($postcode) ){
            $result = $service->lookupPostcode( $postcode );
            
            if ($usingMojDsdPostcodeService && count($result) == 0) {
                
                $this->log()->info(
                    'Postcode not found in DSD lookup service. Trying PostcodeAnywhere.',
                    ['postcode' => $postcode]
                );
                
                // Drop through to use PostcodeAnywhere if no result from DSD service
                // In addition to providing an a ray of hope that the address may still
                // be found, it also makes sure that the return structure is always the 
                // same when an address is not found for the a given postcode
                $service = $this->getServiceLocator()->get('AddressLookupPostcodeAnywhere');
                $result = $service->lookupPostcode( $postcode );
                $usingMojDsdPostcodeService = false;
            }
            
            // Map the result to match the format from v1.
            $v1Format = array_map( function($addr) use ($usingMojDsdPostcodeService) {
    
                if ($usingMojDsdPostcodeService) {
                    
                    $details = [
                        'id' => $addr['Id'],
                        'description' => $addr['Summary'],
                        'line1' => $addr['Detail']['line1'],
                        'line2' => $addr['Detail']['line2'],
                        'line3' => $addr['Detail']['line3'],
                        'postcode' => $addr['Detail']['postcode'],
                    ];
                    
                    $this->log()->info(
                        'Postcode found in DSD postcode lookup service.',
                        $details
                    );
                    
                    return $details;
                } else {
                    $details = [
                        'id' => $addr['Id'],
                        'description' => $addr['StreetAddress'].' '.$addr['Place'],
                    ];
                    
                    $this->log()->info(
                        'Postcode found in PostcodeAnywhere.',
                        $details
                    );
                    
                    return $details;
                }
            }, $result );
    
                return new JsonModel( [
                    'isPostcodeValid'=>true,
                    'success'=> ( count($v1Format) > 0 ),
                    'addresses' => $v1Format,
                    'postcodeService' => $usingMojDsdPostcodeService ? 'mojDs' : 'postcodeAnywhere',
                ]);
    
        }
        //-----------------------
        // Address lookup
    
        $addressId = $this->params()->fromQuery('addressid');
    
        if( isset($addressId) ){
    
            $result = $service->lookupAddress( $addressId );
    
            return new JsonModel( $result );
    
        }
    
        //---
    
        // else not found.
        return $this->notFoundAction();
    
    }
    

} // class
