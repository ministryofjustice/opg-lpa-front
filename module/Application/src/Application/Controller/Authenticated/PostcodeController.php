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

        $service = $this->getServiceLocator()->get('AddressLookup');

        //-----------------------
        // Postcode lookup

        $postcode = $this->params()->fromQuery('postcode');

        if( isset($postcode) ){

            $result = $service->lookupPostcode( $postcode );

            // Map the result to match the format from v1.
            $v1Format = array_map( function($addr){
                return [
                    'id' => $addr['Id'],
                    'description' => $addr['StreetAddress'].' '.$addr['Place'],
                ];
            }, $result );

            return new JsonModel( [ 'isPostcodeValid'=>true, 'success'=> ( count($v1Format) > 0 ), 'addresses' => $v1Format ] );

        } // if

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

    } // function

} // class
