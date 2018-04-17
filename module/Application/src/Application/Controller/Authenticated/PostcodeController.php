<?php

namespace Application\Controller\Authenticated;

use Application\Controller\AbstractAuthenticatedController;
use Application\Model\Service\AddressLookup\PostcodeInfo;
use Zend\View\Model\JsonModel;

class PostcodeController extends AbstractAuthenticatedController
{
    /**
     * @var PostcodeInfo
     */
    private $addressLookup;

    /**
     * Flag to indicate if complete user details are required when accessing this controller
     *
     * @var bool
     */
    protected $requireCompleteUserDetails = false;

    public function indexAction()
    {
        $postcode = $this->params()->fromQuery('postcode');

        if (empty($postcode)) {
            return $this->notFoundAction();
        }

        $addresses = $this->addressLookup->lookupPostcode($postcode);

        return new JsonModel([
            'isPostcodeValid' => true,
            'success'         => (count($addresses) > 0),
            'addresses'       => $addresses,
        ]);
    }

    public function setAddressLookup(PostcodeInfo $addressLookup)
    {
        $this->addressLookup = $addressLookup;
    }
}
