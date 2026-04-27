<?php

namespace App\Controller;

use App\Entity\Image;
use App\Entity\Item;
use App\Form\ItemType;
use App\Service\EmailSender;
use App\Service\ItemHandler;
use App\Service\OfferHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class AdminController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[Route('/admin/delete/{id<[0-9]+>}', name: 'app_admin_delete')]
    public function delete(Item $item, Request $request): Response
    {
        // Récupère le token de la Request
        $submittedToken = $request->getPayload()->get('token');

        // On vérifie si le token est valide.
        if (!$this->isCsrfTokenValid($_ENV['CSRF_SECRET'] . $item->getId(), $submittedToken)) {
            $this->addFlash('danger', 'Token invalide');

            return $this->redirectToRoute('app_item_show', ['id' => $item->getId()]);
        }

        $this->em->remove($item);
        $this->em->flush();
        $this->addFlash('success', "L'objet a été supprimé avec succès.");

        return $this->redirectToRoute('app_item_index');
    }

    #[Route('/admin/save/{id<[0-9]+>?}', name: 'app_admin_save')]
    public function save(?Item $item, Request $request, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ItemType::class, $item);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $item = $form->getData();

            $imageFile = $form->get('image')->getData();

            // Il faudrait placer cette aprtie dans un service pour le sortir du controleur
            if($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);

                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                
                $newFilename = 'img_' .uniqid() . '-' .$safeFilename . '.' . $imageFile->guessExtension();

                // Move the file to the directory where brochures are stored
                $imageFile->move('images', $newFilename);

                $image = new Image;
                $image->setPath('images/' . $newFilename)
                ->setAlt($originalFilename)
                ->setItem($item)
                ;
                $this->em->persist($image);

                $item->addImage($image);
            }
            
            $this->em->persist($item);
            $this->em->flush();

            $this->addFlash('success', "L'objet a été ajouté avec succès.");

            return $this->redirectToRoute('app_item_index');
        }
        
        return $this->render('admin/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/admin/changeStatus/{id<[0-9]+>}', name: 'app_admin_changeStatus')]
    public function changeStatus(Item $item, ItemHandler $itemHandler, OfferHandler $offerHandler, EmailSender $emailSender): Response
    {
        // Si le status est déjà closed, on redirige direct.
        if($item->getStatus() === Item::CLOSED) {
            return $this->redirectToRoute('app_item_show', ['id' => $item->getId()]);
        }

        // changer le status
        $item = $itemHandler->changeStatus($item);

        // Si le status passe en close trouver le winner et el finalPrice
        if($item->getStatus() === Item::CLOSED) {
            $item = $offerHandler->closeBid($item);
            $emailSender->sendPaymentLink($item);
        }

        $this->em->flush();

        return $this->redirectToRoute('app_item_show', ['id' => $item->getId()]);
    }
}
