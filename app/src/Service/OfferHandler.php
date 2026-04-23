<?php

namespace App\Service;

use App\Entity\Item;
use App\Repository\OfferRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

class OfferHandler
{
    private $offerExists = false;
    private Session $session;

    public function __construct(
        private OfferRepository $offerRepository,
        private RequestStack $requestStack
        )
    {
        $this->session = $this->requestStack->getSession();
    }

    public function offerExists($user, $item)
    {
        if ($item->getStatus() === Item::UNPUBLISHED) return false;

        $this->offerExists = $this->offerRepository->findOneBy([
                'user' => $user,
                'item' => $item,
            ]);

        return $this->offerExists;
    }

    private function isAmountValid(float $amount, Item $item)
    {
        if ($amount < $item->getStartingPrice()) {
            $this->session->getFlashBag()->add('danger', 'L’enchère doit être supérieure ou égale à ' . $item->getStartingPrice() . ' €.');
        
            return false;
        }

        return true;
    }

    public function isValid(float $amount, Item $item, $user)
    {
        if ($this->offerExists) {
           $this->session->getFlashBag()->add('danger', 'Vosu avez déjà placé une enchère sur cet objet.');

            return false;
        }
        if(!$this->isAmountValid($amount, $item)) {
            return false;
        }

        return true;
    }

    public function closeBid(Item $item)
    {
        $finalPrice = 0;
        $winner = null;
        foreach ($item->getOffers() as $offer) {
            if($offer->getAmount() < $finalPrice) continue;
            $finalPrice = $offer->getAmount();
            $winner = $offer->getUser();
        }
        $item->setStatus(Item::CLOSED)
        ->setWinner($winner)
        ->setFinalPrice($finalPrice);
        
        return $item;
    }
}
