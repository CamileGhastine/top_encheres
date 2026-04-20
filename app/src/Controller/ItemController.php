<?php

namespace App\Controller;

use App\Entity\Item;
use App\Form\OfferType;
use App\Repository\CategoryRepository;
use App\Repository\ItemRepository;
use App\Repository\OfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;

final class ItemController extends AbstractController
{
    public function __construct(private ItemRepository $itemRepository,)
    {
    }

    #[Route('/{id<[0-9]+>?null}', name: 'app_item_index')]
    public function index($id, CategoryRepository $categoryRepository): Response
    {
        $find = $this->isGranted('ROLE_ADMIN') ? 'findAllWithOffers' : 'findPublishedAndClosedItems';

        return $this->render('item/index.html.twig', [
            'items' => $this->itemRepository->$find((int)$id),
            'categories' => $categoryRepository->findAll()
        ]);
    }

    #[Route('/item/{id<[0-9]+>}', name: 'app_item_show')]
    public function show($id, OfferRepository $offerRepository, Request $request, EntityManagerInterface $em): Response
    {
        $item = $this->itemRepository->findWithOffers($id);

        $existingOffer = null;
        $user = $this->getUser();

        $form = $this->createForm(OfferType::class);
        $form->handleRequest($request);

        if ($user) {
            $existingOffer = $offerRepository->findOneBy([
                'user' => $user,
                'item' => $item,
            ]);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $offer = $form->getData();

            if ($existingOffer) {
                $this->addFlash('danger', 'Vous avez déjà placé une ecnhère pour cet objet.');

                return $this->redirectToRoute('app_item_show', [
                    'id' => $item->getId()
                ]);
            }

            if ($offer->getAmount() < $item->getStartingPrice()) {
                $this->addFlash('danger', 'L’enchère doit être supérieure ou égale à ' . $item->getStartingPrice() . ' €.');

                return $this->redirectToRoute('app_item_show', [
                    'id' => $item->getId()
                ]);
            }

            $offer->setUser($user)
                ->setItem($item)
            ;

            $em->persist($offer);
            $em->flush();

            $this->addFlash('success', 'Votre enchère a été prise en compte.');

            return $this->redirectToRoute('app_item_show', [
                'id' => $item->getId()
            ]);
        }

        return $this->render('item/show.html.twig', [
            'item' => $item,
            'form' => $form->createView(),
            'existingOffer' => $existingOffer
        ]);
    }

     #[Route('/item/changeStatus/{id<[0-9]+>}', name: 'app_item_changeStatus')]
    public function changeStatus(Item $item, EntityManagerInterface $em): Response
    {
        if($item->getStatus() === Item::CLOSED) {
            return $this->redirectToRoute('app_item_show', ['id' => $item->getId()]);
        }

        if($item->getStatus() === Item::UNPUBLISHED) {
            $item->setStatus(Item::PUBLISHED);
        } elseif($item->getStatus() === Item::PUBLISHED && count($item->getOffers())) {
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
        } else {
            $item->setStatus(Item::UNPUBLISHED);
        }

        $em->flush();

        return $this->redirectToRoute('app_item_show', ['id' => $item->getId()]);
    }
}
