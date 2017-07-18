<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Application\Model\Service\Mail\Message as MessageService;
use Zend\Mime\Message;
use Zend\Mime\Part;
use Exception;

class SendgridController extends AbstractBaseController
{
    /**
     * No reply email address to use
     *
     * @var string
     */
    private $blackHoleAddress = 'blackhole@lastingpowerofattorney.service.gov.uk';

    /**
     * List of email addresses that will not get this "Unmonitored Mailbox" email sent to them under any circumstances
     *
     * @var array
     */
    private $blacklistEmailAddresses = [
        'david.hyams@tinyonline.co.uk',
    ];

    public function bounceAction()
    {
        $fromAddress = $this->request->getPost('from');
        $originalToAddress = $this->request->getPost('to');

        //  If there is no from email address, or the user has responded to the blackhole email address then do nothing
        if (!is_string($fromAddress) || !is_string($originalToAddress) || strpos(strtolower($originalToAddress), $this->blackHoleAddress) !== false) {
            $this->log()->err('Sender or recipient missing, or email sent to ' . $this->blackHoleAddress . ' - the message message will not be sent to SendGrid', [
                'from-address' => $fromAddress,
                'to-address'   => $originalToAddress,
            ]);

            return $this->getResponse();
        }

        //  Check that the to email address is not on the list of blacklist email addresses
        if (in_array($fromAddress, $this->blacklistEmailAddresses)) {
            $this->log()->err('To email address is blacklisted - the unmonitored email will not be sent to this user', [
                'from-address' => $fromAddress,
                'to-address'   => $originalToAddress,
            ]);

            return $this->getResponse();
        }

        $token = $this->params()->fromRoute('token');

        $config = $this->getServiceLocator()->get('config');
        $emailConfig = $config['email'];

        if (!$token || $token !== $emailConfig['sendgrid']['webhook']['token']) {
            $this->log()->err('Missing or invalid bounce token used', [
                'from-address' => $fromAddress,
                'to-address'   => $originalToAddress,
                'token'        => $token,
            ]);

            $response = $this->getResponse();
            $response->setStatusCode(403);
            $response->setContent('Invalid Token');

            return $response;
        }

        //  Log the attempt to compose the email
        $messageService = new MessageService();
        $messageService->addFrom($this->blackHoleAddress, $emailConfig['sender']['default']['name']);
        $messageService->addCategory('opg');
        $messageService->addCategory('opg-lpa');
        $messageService->addCategory('opg-lpa-autoresponse');

        if (preg_match('/\<(.*)\>$/', $fromAddress, $matches)) {
            $fromAddress = $matches[1];
        }

        $messageService->addTo($fromAddress);

        //  Set the subject in the message
        $content = $this->getServiceLocator()
                        ->get('TwigEmailRenderer')
                        ->loadTemplate('bounce.twig')
                        ->render([]);

        $subject = 'This mailbox is not monitored';

        if (preg_match('/<!-- SUBJECT: (.*?) -->/m', $content, $matches) === 1) {
            $subject = $matches[1];
        }

        $messageService->setSubject($subject);

        //  Set the content in a mime message
        $mimeMessage = new Message();
        $html = new Part($content);
        $html->type = "text/html";
        $mimeMessage->setParts([$html]);

        $messageService->setBody($mimeMessage);

        try {
            $this->getServiceLocator()
                 ->get('MailTransport')
                 ->send($messageService);

            echo 'Email sent';
        } catch (Exception $e) {
            $this->log()->alert("Failed sending '" . $subject . "' email to " . $fromAddress . " due to:\n" . $e->getMessage());

            return "failed-sending-email";
        }

        $response = $this->getResponse();
        $response->setStatusCode(200);

        return $response;
    }
}
