<?php
namespace Application\Model\Service\Lpa;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;

use Application\Model\Service\Mail\Message as MailMessage;
use Opg\Lpa\DataModel\Lpa\Lpa;

/**
 * A model service class for sending emails on LPA creation and completion.
 * 
 * Class Communication
 * @package Application\Model\Service\Lpa
 */
class Communication implements ServiceLocatorAwareInterface {

    use ServiceLocatorAwareTrait;

    //---
    
    public function sendRegistrationCompleteEmail( Lpa $lpa, $signinUrl)
    {
        
        return $this->sendEmail('email/lpa-registration.phtml', $lpa, $signinUrl, 'Lasting power of attorney for '.$lpa->document->donor->name.' is ready to register', 'opg-lpa-complete-registration');
    }
    
    private function sendEmail($emailTemplate, Lpa $lpa, $signinUrl, $subject, $category)
    {
    
        //-------------------------------
        // Send the email

        $message = new MailMessage();
        
        $config = $this->getServiceLocator()->get('config');
        $message->addFrom($config['email']['sender']['default']['address'], $config['email']['sender']['default']['name']);
        
        $userSession = $this->getServiceLocator()->get('UserDetailsSession');
        
        $message->addTo( $userSession->user->email->address );

        $message->setSubject( $subject );

        //---

        $message->addCategory('opg');
        $message->addCategory('opg-lpa');
        $message->addCategory($category);

        //---

        // Load the content from the view and merge in our variables...
        $viewModel = new \Zend\View\Model\ViewModel();
        $viewModel->setTemplate($emailTemplate)->setVariables(['lpa' => $lpa, 'signinUrl' => $signinUrl]);
        
        $content = $this->getServiceLocator()->get('ViewRenderer')->render( $viewModel );

        //---

        $html = new MimePart( $content );
        $html->type = "text/html";

        $body = new MimeMessage();
        $body->setParts([$html]);

        $message->setBody($body);

        //---

        try {

            $this->getServiceLocator()->get('MailTransport')->send($message);

        } catch ( \Exception $e ){
            
            $this->getServiceLocator()->get('Logger')->alert("Failed sending '".$subject."' email to ".$userSession->user->email->address." due to:\n".$e->getMessage());
            
            return "failed-sending-email";

        }

        return true;

    } // function

} // class
