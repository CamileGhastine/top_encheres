<?php

namespace App\Controller;

use App\Entity\Item;
use App\Form\OfferType;
use App\Repository\CategoryRepository;
use App\Repository\ItemRepository;
use App\Repository\OfferRepository;
use App\Service\ItemHandler;
use App\Service\OfferHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ItemController extends AbstractController
{
    public function __construct(
        private ItemRepository $itemRepository,
        private EntityManagerInterface $em,
        private OfferHandler $offerHandler
        )
    {
    }

    #[Route('/{id<[0-9]+>?}', name: 'app_item_index')]
    public function index($id, CategoryRepository $categoryRepository): Response
    {
        $items = $this->isGranted('ROLE_ADMIN') 
        ? $this->itemRepository->findAllWithOffers((int)$id) 
        : $this->itemRepository->findPublishedAndClosedItemsWithOffers((int)$id);

        return $this->render('item/index.html.twig', [
            'items' => $items,
            'categories' => $categoryRepository->findAll()
        ]);
    }

    #[Route('/item/{id<[0-9]+>}', name: 'app_item_show')]
    public function show($id, OfferRepository $offerRepository, Request $request): Response
    {
        $user = $this->getUser();
        $item = $this->itemRepository->findWithOffers($id);

        $form = $this->createForm(OfferType::class);
        $form->handleRequest($request);

        $existingOffer = $user ? $this->offerHandler->offerExists($user, $item) : false;

        if ($form->isSubmitted() && $form->isValid()) {
            $offer = $form->getData();

            if (!$this->offerHandler->isValid($request, $offer->getAmount(), $item, $user)) {
                return $this->redirectToRoute('app_item_show', [
                    'id' => $item->getId()
                ]);
            }           

            $offer->setUser($user)
                ->setItem($item);

            $this->em->persist($offer);
            $this->em->flush();

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
    public function changeStatus(Item $item, ItemHandler $itemHandler): Response
    {
        
        // Si le status est déjà closed, on redirige direct.
        if($item->getStatus() === Item::CLOSED) {
            return $this->redirectToRoute('app_item_show', ['id' => $item->getId()]);
        }

        // changer le status
        $item = $itemHandler->changeStatus($item);

        // Si le status passe en close trouver le winner et el finalPrice

        if($item->getStatus() === Item::CLOSED) {
            $item = $this->offerHandler->closeBid($item);
        }

        $this->em->flush();

        return $this->redirectToRoute('app_item_show', ['id' => $item->getId()]);
    }
}
