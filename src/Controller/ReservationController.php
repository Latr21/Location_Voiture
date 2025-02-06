<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reservation')]
class ReservationController extends AbstractController
{
    #[Route('/', name: 'app_reservation_index', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository): Response
    {
        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservationRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Associer le client connecté à la réservation
            $user = $this->getUser();
            if (!$user) {
                // Gérer le cas où l'utilisateur n'est pas connecté
                $this->addFlash('error', 'Vous devez être connecté pour effectuer une réservation.');
                return $this->redirectToRoute('app_login');
            }
            $reservation->setClient($user);

            // Calculer le prix total en fonction de la durée de la réservation et du prix journalier du véhicule
            $vehicule = $reservation->getVehicule();
            $dateDebut = $reservation->getDateDebut();
            $dateFin = $reservation->getDateFin();

            if ($dateDebut >= $dateFin) {
                $this->addFlash('error', 'La date de fin doit être postérieure à la date de début.');
                return $this->render('reservation/new.html.twig', [
                    'reservation' => $reservation,
                    'form' => $form->createView(),
                ]);
            }

            $diff = $dateFin->diff($dateDebut)->days + 1; // Inclure le jour de début
            $prixTotal = $diff * $vehicule->getPrixJournalier();
            $reservation->setPrixTotal($prixTotal);

            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Réservation effectuée avec succès.');

            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_show', methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Recalculer le prix total en cas de modification des dates ou du véhicule
            $vehicule = $reservation->getVehicule();
            $dateDebut = $reservation->getDateDebut();
            $dateFin = $reservation->getDateFin();

            if ($dateDebut >= $dateFin) {
                $this->addFlash('error', 'La date de fin doit être postérieure à la date de début.');
                return $this->render('reservation/edit.html.twig', [
                    'reservation' => $reservation,
                    'form' => $form->createView(),
                ]);
            }

            $diff = $dateFin->diff($dateDebut)->days + 1; // Inclure le jour de début
            $prixTotal = $diff * $vehicule->getPrixJournalier();
            $reservation->setPrixTotal($prixTotal);

            $entityManager->flush();

            $this->addFlash('success', 'Réservation mise à jour avec succès.');

            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Réservation supprimée avec succès.');
        }

        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }
}