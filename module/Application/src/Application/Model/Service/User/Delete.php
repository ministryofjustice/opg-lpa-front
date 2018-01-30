<?php
namespace Application\Model\Service\User;

use Opg\Lpa\Logger\LoggerTrait;

class Delete
{
    use LoggerTrait;

    //---

    /**
     * Deletes a user. i.e. all their LPAs, and their
     *
     * @return bool whether delete was successful.
     */
    public function delete(){

        $this->getLogger()->info(
            'Deleting user and all their LPAs', 
            $this->getServiceLocator()->get('AuthenticationService')->getIdentity()->toArray()
        );
        
        return $this->getServiceLocator()->get('ApiClient')->deleteUserAndAllTheirLpas();

    } // function

} // class
