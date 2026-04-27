<?php

namespace Service;

use App\Entity\Item;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class StripeHandler 
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }
    
    public function createSession(Item $item)
    {
        Stripe::setApiKey($_ENV['STRIPE_SECRET']);
        return Session::create([
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
            'success_url' => $this->urlGenerator->generate(
                'app_payment_success', 
                ['id' => $item->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
                ),
            'cancel_url' => $this->urlGenerator->generate(
                'app_payment_cancel', 
                ['id' => $item->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
                ),
        ]);
    }

}