<?php

namespace App\Controller;

use App\Repository\VehiculeRepository;
use App\Repository\CommentaireRepository;
use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        VehiculeRepository $vehiculeRepository,
        CommentaireRepository $commentaireRepository,
        ReservationRepository $reservationRepository
    ): Response {
        $vehicules = $vehiculeRepository->findAll();

        // Tableau pour stocker les informations des véhicules
        $vehiculeInfos = [];

        foreach ($vehicules as $vehicule) {
            // Calcul de la note moyenne
            $commentaires = $commentaireRepository->findBy(['vehicule' => $vehicule]);
            $totalNotes = 0;
            $nombreNotes = count($commentaires);

            foreach ($commentaires as $commentaire) {
                $note = $commentaire->getNote();
                // S'assurer que la note est numérique avant de l'ajouter
                if (is_numeric($note)) {
                    $totalNotes += (float) $note;
                }
            }

            $noteMoyenne = ($nombreNotes > 0) ? round($totalNotes / $nombreNotes, 1) : "Pas encore noté";

            // Récupérer le dernier utilisateur ayant réservé ce véhicule
            $reservations = $reservationRepository->findBy(['vehicule' => $vehicule], ['dateDebut' => 'DESC'], 1);
            $dernierClient = (count($reservations) > 0) ? $reservations[0]->getClient()->getEmail() : "Aucune réservation";

            $vehiculeInfos[$vehicule->getId()] = [
                'vehicule' => $vehicule,
                'noteMoyenne' => $noteMoyenne,
                'dernierClient' => $dernierClient,
            ];
        }

        return $this->render('home/index.html.twig', [
            'vehiculeInfos' => $vehiculeInfos,
        ]);
    }
}