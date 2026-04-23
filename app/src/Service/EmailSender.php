<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailSender
{
    public function __construct(
        private MailerInterface $mailer, 
        private UrlGeneratorInterface $urlGenerator,
        private UriSigner $uriSigner
        ){
    }

    public function sendPaymentLink($item, $subject = null)
    {
        $from = $_ENV['EMAIL_CONTACT'];
        $to = $item->getWinner()->getEmail();
        $subject = $subject ?: 'Enchère remportée : ' . $item->getTitle();

        $price = $item->getFinalPrice();
        $url = $_ENV['APP_URL'] . $this->urlGenerator->generate('app_payment', ['id' => $item->getId()]);
        $urlWithToken = $this->uriSigner->sign($url);        
        $text = "Vous avez remporté l'enchère.\nMontant à payer : {$price} €\nLien : {$urlWithToken}";
        $html = '<p>Vous avez remportez l\'enchère. Payer ' . $price . '€</p> <a href="' . $urlWithToken . '">Payer</a>';


        $email = (new Email())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->text($text)           
            ->html($html);

        $this->mailer->send($email);
    }

    public function sendPaymentSuccess($item)
    {
        $from = $_ENV['EMAIL_CONTACT'];
        $to = $item->getWinner()->getEmail();
        $subject = 'Payement réalisé : ' . $item->getTitle();
        $text = "Votre payement a été effectué avec succès.";
        $html = '<p>Votre payement a été effectué avec succès</a>';

        $email = (new Email())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->text($text)           
            ->html($html);

        $this->mailer->send($email);
    }

    public function sendPaymentCancel($item)
    {
        $this->sendPaymentLink($item, 'Payez à nouveau, payement échoué : ' . $item->getTitle());
    }
}
