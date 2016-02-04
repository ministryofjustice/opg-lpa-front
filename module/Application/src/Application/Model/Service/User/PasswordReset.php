<?php
namespace Application\Model\Service\User;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;

use Application\Model\Service\Mail\Message as MailMessage;

class PasswordReset implements ServiceLocatorAwareInterface {

    use ServiceLocatorAwareTrait;

    //---

    public function requestPasswordResetEmail( $email, callable $fpRouteCallback, callable $activateRouteCallback ){

        $this->getServiceLocator()->get('Logger')->info(
            'User requested password reset email'
        );
        
        $client = $this->getServiceLocator()->get('ApiClient');

        $resetToken = $client->requestPasswordReset( strtolower( $email ) );

        // A successful response is a string...
        if( !is_string($resetToken) ){

            // Error...
            $body = $client->getLastContent();

            if( isset($body['activation_token']) ){

                // If they have not yet activated their account, we re-send them the activation link.
                $this->sendActivateEmail( $email, $activateRouteCallback( $body['activation_token'] ) );
                
                return 'account-not-activated';

            }elseif( isset($body['status']) && $body['status'] == 404 ){

                return "user-not-found";

            }elseif( isset($body['reason']) ){

                return trim( $body['reason'] );

            }

            return "unknown-error";

        } // if

        //-------------------------------
        // Send the email

        $this->sendResetEmail( $email, $fpRouteCallback( $resetToken ) );

        $this->getServiceLocator()->get('Logger')->info('Password reset email sent to ' . $email);
        
        return true;

    } // function


    /**
     * Check if a given reset token is currently valid.
     *
     * @param $restToken
     * @return bool
     */
    public function isResetTokenValid( $restToken ){

        // If we can exchange it for a auth token, then it's valid.
        return is_string( $this->getAuthTokenFromRestToken( $restToken ) );

    } // function



    public function setNewPassword( $restToken, $password ){

        $this->getServiceLocator()->get('Logger')->info(
            'Setting new password following password reset'
        );
        
        $client = $this->getServiceLocator()->get('ApiClient');

        $result = $client->updateAuthPasswordWithToken( $restToken, $password );
        
        if ($result !== true) {

            // Error...
            $body = $client->getLastContent();

            if( isset($body['detail']) ){
                return $body['detail'];
            }

            return "unknown-error";

        }

        //---

        return true;

    } // function

    //----------------------------------------------------

    /**
     * Exchange the reset token for an auth token.
     *
     * @param $restToken string The reset token.
     * @return bool|string Returns false on an error or the auth token on success.
     */
    private function getAuthTokenFromRestToken( $restToken ){

        return $this->getServiceLocator()->get('ApiClient')->requestPasswordResetAuthToken( $restToken );

    }  // function

    //----------------------------------------------------
    // Send Emails

    private function sendResetEmail( $email, $callbackUrl ){

        $this->getServiceLocator()->get('Logger')->info(
            'Sending password reset email'
        );
        
        $message = new MailMessage();

        $config = $this->getServiceLocator()->get('config');
        $message->addFrom($config['email']['sender']['default']['address'], $config['email']['sender']['default']['name']);

        $message->addTo( $email );

        $message->setSubject( 'Password reset request' );

        //---

        $message->addCategory('opg');
        $message->addCategory('opg-lpa');
        $message->addCategory('opg-lpa-passwordreset');
        $message->addCategory('opg-lpa-passwordreset-normal');

        //---

        $content = $this->getServiceLocator()->get('TwigEmailRenderer')->loadTemplate('password-reset.twig')->render([
            'callback' => $callbackUrl,
        ]);

        //---

        $html = new MimePart( $content );
        $html->type = "text/html";

        $body = new MimeMessage();
        $body->setParts([$html]);

        $message->setBody($body);

        //--------------------

        try {

            $this->getServiceLocator()->get('MailTransport')->send($message);

        } catch ( \Exception $e ){

            return "failed-sending-email";

        }

        return true;

    } // function

    private function sendActivateEmail( $email, $callbackUrl ){

        $this->getServiceLocator()->get('Logger')->info(
            'Sending account activation email'
        );
        
        $message = new MailMessage();

        $config = $this->getServiceLocator()->get('config');
        $message->addFrom($config['email']['sender']['default']['address'], $config['email']['sender']['default']['name']);

        $message->addTo( $email );

        $message->setSubject( 'Password reset request' );

        //---

        $message->addCategory('opg');
        $message->addCategory('opg-lpa');
        $message->addCategory('opg-lpa-passwordreset');
        $message->addCategory('opg-lpa-passwordreset-activate');

        //---

        $content = $this->getServiceLocator()->get('TwigEmailRenderer')->loadTemplate('password-reset-not-active.twig')->render([
            'callback' => $callbackUrl,
        ]);
        
        //---

        $html = new MimePart( $content );
        $html->type = "text/html";

        $body = new MimeMessage();
        $body->setParts([$html]);

        $message->setBody($body);

        //--------------------

        try {

            $this->getServiceLocator()->get('MailTransport')->send($message);

        } catch ( \Exception $e ){

            return "failed-sending-email";

        }

        return true;

    } // function

} // class