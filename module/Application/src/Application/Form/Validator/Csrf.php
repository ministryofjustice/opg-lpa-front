<?php
namespace Application\Form\Validator;

use RuntimeException;

use Zend\Math\Rand;
use Zend\Session\Container as SessionContainer;
use Zend\Validator\Csrf as ZFCsrfValidator;

/**
 * A simplified replacement of Zend's Csrf Validator.
 *
 * This implementation is based on the idea that we have just a single secret token stored in the session
 * which does not change whilst the session is active.
 *
 * This means that session writes are not needed after the initial token is generated.
 *
 * This is to help mitigate the false positive Csrf validation errors we were getting,
 * which is caused by slow writes of the session data.
 *
 * Class Csrf
 * @package Application\Form\Validator
 */
class Csrf extends ZFCsrfValidator {

    /**
     * Error messages
     * @var array
     */
    protected $messageTemplates = array(
        self::NOT_SAME => "Oops! Something went wrong with the information you entered. Please try again.",
    );


    /**
     * Does the provided token match the one generated?
     *
     * @param  string $value
     * @param  mixed $context
     * @return bool
     */
    public function isValid($value, $context = null){

        if( $value !== $this->getHash( true ) ){

            $this->error(self::NOT_SAME);
            return false;

        }

        return true;

    }


    /**
     * Generate CSRF token
     *
     * The hash is made up of:
     *  - The form's name
     *  - The CSRF token from the session.
     *  - The validator's salt.
     *
     * @return void
     */
    protected function generateHash()
    {

        $salt = $this->getSalt();

        if( $salt == null || empty($salt) ){
            throw new RuntimeException('CSRF salt cannot be null or empty');
        }

        //---

        $session = new SessionContainer('CsrfValidator');

        if( !isset($session->token) ){
            $session->token = hash( 'sha512', Rand::getBytes(128, true) );
        }

        //---

        $this->hash =  hash( 'sha512', $this->getName() . $session->token . $salt );

    }

} // class
