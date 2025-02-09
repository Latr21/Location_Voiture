<?php

namespace App\Controller;

use App\Entity\Vehicule;
use App\Entity\Commentaire;
use App\Entity\Reservation;
use App\Form\VehiculeType;
use App\Form\CommentaireType;
use App\Form\ReservationType;
use App\Repository\VehiculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/vehicule')]
final class VehiculeController extends AbstractController
{
    #[Route(name: 'app_vehicule_index', methods: ['GET'])]
    public function index(VehiculeRepository $vehiculeRepository): Response
    {
        return $this->render('vehicule/index.html.twig', [
            'vehicules' => $vehiculeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_vehicule_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $vehicule = new Vehicule();
        $form = $this->createForm(VehiculeType::class, $vehicule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $vehicule->setImageFile($imageFile);
            }

            $entityManager->persist($vehicule);
            $entityManager->flush();

            $this->addFlash('success', 'Véhicule ajouté avec succès.');

            return $this->redirectToRoute('app_vehicule_index');
        }

        return $this->render('vehicule/new.html.twig', [
            'vehicule' => $vehicule,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_vehicule_show', methods: ['GET', 'POST'])]
    public function show(Vehicule $vehicule, Request $request, EntityManagerInterface $entityManager): Response
    {
        $commentaires = $vehicule->getCommentaires();

        // Formulaire d'ajout de commentaire
        $commentaire = new Commentaire();
        $commentaireForm = $this->createForm(CommentaireType::class, $commentaire);
        $commentaireForm->handleRequest($request);

        if ($commentaireForm->isSubmitted() && $commentaireForm->isValid()) {
            $commentaire->setVehicule($vehicule);
            $commentaire->setClient($this->getUser());

            $entityManager->persist($commentaire);
            $entityManager->flush();

            $this->addFlash('success', 'Votre avis a été ajouté avec succès.');

            return $this->redirectToRoute('app_vehicule_show', ['id' => $vehicule->getId()]);
        }

        // Formulaire de réservation
        $reservation = new Reservation();
        $reservationForm = $this->createForm(ReservationType::class, $reservation);
        $reservationForm->handleRequest($request);

        if ($reservationForm->isSubmitted() && $reservationForm->isValid()) {
            if (!$this->getUser()) {
                $this->addFlash('error', 'Vous devez être connecté pour réserver un véhicule.');
                return $this->redirectToRoute('app_login');
            }

            if (!$vehicule->isDisponible()) {
                $this->addFlash('error', 'Ce véhicule n\'est pas disponible.');
                return $this->redirectToRoute('app_vehicule_show', ['id' => $vehicule->getId()]);
            }

            $reservation->setVehicule($vehicule);
            $reservation->setClient($this->getUser());

            $diff = $reservation->getDateDebut()->diff($reservation->getDateFin());
            $nombreJours = $diff->days;
            $prixTotal = $nombreJours * $vehicule->getPrixjournalier();
            $reservation->setPrixTotal($prixTotal);

            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Réservation enregistrée avec succès.');

            return $this->redirectToRoute('app_vehicule_show', ['id' => $vehicule->getId()]);
        }

        return $this->render('vehicule/show.html.twig', [
            'vehicule' => $vehicule,
            'commentaires' => $commentaires,
            'commentaire_form' => $commentaireForm->createView(),
            'reservation_form' => $reservationForm->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_vehicule_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Vehicule $vehicule, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(VehiculeType::class, $vehicule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $vehicule->setImageFile($imageFile);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Véhicule modifié avec succès.');

            return $this->redirectToRoute('app_vehicule_index');
        }

        return $this->render('vehicule/edit.html.twig', [
            'vehicule' => $vehicule,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_vehicule_delete', methods: ['POST'])]
    public function delete(Request $request, Vehicule $vehicule, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $vehicule->getId(), $request->request->get('_token'))) {
            if (!$vehicule->getReservations()->isEmpty()) {
                $this->addFlash('error', 'Impossible de supprimer ce véhicule car il a des réservations en cours.');
                return $this->redirectToRoute('app_vehicule_index');
            }
    
            $entityManager->remove($vehicule);
            $entityManager->flush();
    
            $this->addFlash('success', 'Véhicule supprimé avec succès.');
    
            // ✅ Redirection correcte vers la liste des véhicules
            return $this->redirectToRoute('app_vehicule_index', [], Response::HTTP_SEE_OTHER);
        }
    
        // ✅ En cas d'erreur, redirection vers la liste des véhicules aussi
        return $this->redirectToRoute('app_vehicule_index', [], Response::HTTP_SEE_OTHER);
    }
}