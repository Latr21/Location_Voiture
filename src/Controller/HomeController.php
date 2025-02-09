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
            // Récupérer tous les commentaires du véhicule
            $commentaires = $commentaireRepository->findBy(['vehicule' => $vehicule]);
            $totalNotes = 0;
            $nombreNotes = count($commentaires);

            foreach ($commentaires as $commentaire) {
                $note = $commentaire->getNote();
                if (is_numeric($note)) {
                    $totalNotes += (float) $note;
                }
            }

            // Calculer la note moyenne
            $noteMoyenne = ($nombreNotes > 0) ? round($totalNotes / $nombreNotes, 1) : "Pas encore noté";

            // Récupérer le dernier utilisateur ayant réservé ce véhicule
            $reservations = $reservationRepository->findBy(['vehicule' => $vehicule], ['dateDebut' => 'DESC'], 1);
            $dernierClient = "Aucune réservation";

            if (count($reservations) > 0 && $reservations[0]->getClient()) {
                $dernierClient = $reservations[0]->getClient()->getEmail();
            }

            $vehiculeInfos[$vehicule->getId()] = [
                'vehicule' => $vehicule,
                'noteMoyenne' => $noteMoyenne,
                'dernierClient' => $dernierClient,
            ];
        }

        return $this->render('home/index.html.twig', [
            'vehiculeInfos' => $vehiculeInfos,
            'vehicules' => $vehicules, // Pour éviter l'erreur Twig "Variable 'vehicules' does not exist."
        ]);
    }
}