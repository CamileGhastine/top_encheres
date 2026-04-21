<?php

namespace App\Service;

use App\Entity\Item;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('color', [$this, 'getColor']),
            new TwigFilter('button', [$this, 'getButton']),
        ];
    }

    public function getColor($status)
    {
        if ($status === Item::UNPUBLISHED) return 'warning';
        if ($status === Item::PUBLISHED) return 'success';
        if ($status === Item::CLOSED) return 'danger';
    }

    public function getButton($item)
    {
        if ($item->getStatus() === Item::UNPUBLISHED) 
            return [
            'color'=>'success',
            'value' => 'Publier'
            ];

        if ($item->getStatus() === Item::PUBLISHED && count($item->getOffers()))
            return [
            'color'=>'danger',
            'value' => 'Fermer l\'enchère'
            ];

        if ($item->getStatus() === Item::PUBLISHED && count($item->getOffers()) === 0)
            return [
            'color'=>'warning',
            'value' => 'Dépublier'
            ];


    }
}