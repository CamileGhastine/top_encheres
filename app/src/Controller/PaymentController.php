<?php

namespace App\Controller;

use App\Entity\Item;
use App\Service\EmailSender;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PaymentController extends AbstractController
{
    public function __construct(private EmailSender $emailSender)
    {
    }

    #[Route('/payment/{id}', name: 'app_payment')]
    public function pay(?Item $item, UriSigner $uriSigner, Request $request, UrlGeneratorInterface $urlGenerator): Response
    {      
        // Vérifier le Token_csrf
        if(!$uriSigner->check($request->getUri())) {
            $this->addFlash('danger', 'Token invalide.');

            return $this->redirectToRoute('app_item_index');
        }
        
        // Vérifier si le user est connecté
        if (!$this->getUser()) {
            $this->addFlash('danger', 'Merci de vous connecter et de recliquer sur le lien de payement reçu par mail.');

            return $this->redirectToRoute('app_login');
        }
        
        // Vérifier si c'est bien lui qui a gagné l'enchère
        if (!$item || $item->getWinner()->getId() !== $this->getUser()->getId()) {
            $this->addFlash('danger', 'Payement invalide.');

            return $this->redirectToRoute('app_item_index');            
        }
        
        // Paiement Stripe
        Stripe::setApiKey($_ENV['STRIPE_SECRET']);
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $item->getTitle(),
                    ],
                    'unit_amount' => $item->getFinalPrice() * 100, // en centimes
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $urlGenerator->generate(
                'app_payment_success', 
                ['id' => $item->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
                ),
            'cancel_url' => $urlGenerator->generate(
                'app_payment_cancel', 
                ['id' => $item->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
                ),
        ]);

        // Redirection vers le formulaire de paiement géré par Stripe
        return $this->redirect($session->url);    
    }


    #[Route('/payment/success/{id}', name: 'app_payment_success')]
    public function success(Item $item, EmailSender $emailSender): Response
    {
        $this->addFlash('success', 'Votre payement a été réalisé avec succès.');
        $emailSender->sendPaymentSuccess($item);

        return $this->redirectToRoute('app_item_index');
    }

    #[Route('/payment/cancel/{id}', name: 'app_payment_cancel')]
    public function cancel(Item $item): Response
    {
        $this->addFlash('danger', 'Votre payement a échoue. Consulter vos mails et rééssayer');

        return $this->redirectToRoute('app_item_index', ['id' => $item->getId()]);
    }
}

