<?php

namespace App\Service;

use App\Entity\Item;

class ItemHandler
{

    public function __construct()
    {
    }

    public function changeStatus($item)
    {
        // Règle métier : Enchère dépubliée ↔ (sans offre) publiée (avec offre) → fermée

        if($item->getStatus() === Item::UNPUBLISHED) {
            return $item->setStatus(Item::PUBLISHED);
        } 
        
        if(count($item->getOffers())) {
            return $item->setStatus(Item::CLOSED);
        }

        return $item->setStatus(Item::UNPUBLISHED);
    }
}
