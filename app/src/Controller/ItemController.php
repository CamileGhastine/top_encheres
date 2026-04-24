<?php

namespace App\Controller;

use App\Form\OfferType;
use App\Repository\CategoryRepository;
use App\Repository\ItemRepository;
use App\Service\EmailSender;
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
    public function show($id, Request $request, OfferHandler $offerHandler): Response
    {
        $user = $this->getUser();
        $item = $this->itemRepository->findWithOffers($id);

        $form = $this->createForm(OfferType::class);
        $form->handleRequest($request);

        $existingOffer = $user ? $offerHandler->offerExists($user, $item) : false;

        if ($form->isSubmitted() && $form->isValid()) {
            $offer = $form->getData();

            if (!$offerHandler->isValid($offer->getAmount(), $item, $user)) {
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
}
