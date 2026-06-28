<?php
namespace App\Controllers;

use PDO;
use Exception;
use App\Models\{Reservation, Utilisateur, ReservationModel, UtilisateurModel, SalleModel, MaterielModel};

/**
 * ReservationController : Gère le cycle de vie des réservations.
 * Hérite de BaseController pour accéder aux outils de rendu et de sécurité.
 */
class ReservationController extends BaseController {

    private ReservationModel $reservationModel;
    private SalleModel $salleModel;
    private MaterielModel $materielModel;
    private UtilisateurModel $utilisateurModel;

    public function __construct(PDO $pdo) {
        parent::__construct($pdo); 
        $this->reservationModel = new ReservationModel($pdo);
        $this->salleModel       = new SalleModel($pdo);
        $this->materielModel    = new MaterielModel($pdo);
        $this->utilisateurModel = new UtilisateurModel($pdo);
    }

    /**
     * Helper : Convertit JJ/MM/AAAA vers AAAA-MM-JJ (format SQL).
     */
    private function formatDate(string $date): string {
        return (strpos($date, '/') !== false) 
            ? implode('-', array_reverse(explode('/', $date))) 
            : $date;
    }

    /**
     * Hydrate l'objet Réservation avec ses relations.
     */
    private function hydraterSallesEtMateriels(Reservation $reservation, array $postData): void {
        $reservation->setSalles([]);
        $reservation->setMateriels([]);

        foreach ($postData['salles'] ?? [] as $id) {
            if ($salle = $this->salleModel->findById((int)$id)) {
                $reservation->addSalle($salle);
            }
        }

        foreach ($postData['materiels'] ?? [] as $id) {
            if ($materiel = $this->materielModel->findById((int)$id)) {
                $reservation->addMateriel($materiel);
            }
        }
    }

    // --- Actions CRUD ---

/**
 * Gère le cycle de vie de la création d'une réservation.
 * Compatible avec les rôles Administrateur et Utilisateur.
 */
    public function ajouterUneReservation(): void {
        $this->checkAuth();
        $this->verifierCsrf();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bouton_utilisateur'])) {
            try {
                $reservation = new Reservation();
                $reservation->setMotif(trim($_POST["motif_reservation"]));
                $reservation->setDebut($this->formatDate($_POST["date_debut_reservation"]));
                $reservation->setFin($this->formatDate($_POST["date_fin_reservation"]));
                $reservation->setCre($_POST["creneau_reservation"]);
    
                // Utilisateur obligatoirement celui de la session
                $utilisateur = new Utilisateur(); 
                $utilisateur->setIdUtilisateur($_SESSION['Id_utilisateur']);
                $reservation->setUtilisateur($utilisateur);

                $this->hydraterSallesEtMateriels($reservation, $_POST);

                if ($this->reservationModel->creerAvecRelationsEtLog($reservation)) {
                    // Redirection directe vers la liste de l'utilisateur
                    header("Location: index.php?action=interfaceListeUtilisateur&succes=1");
                } else {
                    throw new Exception("L'enregistrement a échoué.");
                }
                exit();
            } catch (Exception $e) {
                error_log("Erreur création : " . $e->getMessage());
                $_SESSION['erreur'] = $e->getMessage();
            
                // Redirection vers le formulaire en cas d'erreur
                header("Location: index.php?action=interfaceUtilisateur");
                exit();
            }
        }
    
        // Accès direct au formulaire
        $this->afficherPageReservation();
}
    public function traiterActionReservation(): void {
    $this->checkAuth();
    $this->verifierCsrf();

    if (isset($_POST['action'], $_POST['id'])) {
        $id = (int)$_POST['id'];

        // On vérifie que la réservation appartient bien à l'utilisateur actuel
        $reservation = $this->reservationModel->findById($id);
        if ($reservation && $reservation->getUtilisateur()->getIdUtilisateur() == $_SESSION['Id_utilisateur']) {
            
            if ($_POST['action'] === 'supprimer') {
                $user = new Utilisateur();
                $user->setIdUtilisateur($_SESSION['Id_utilisateur']);
                $this->reservationModel->annulerAvecRelationsEtLog($id, $user);
                
            } elseif ($_POST['action'] === 'modifier') {
                $reservation->setMotif(trim($_POST['motif_reservation']));
                $reservation->setDebut($this->formatDate($_POST['date_debut_reservation']));
                $reservation->setFin($this->formatDate($_POST['date_fin_reservation']));
                $reservation->setCre($_POST['creneau_reservation']);
                
                $this->hydraterSallesEtMateriels($reservation, $_POST);
                
                $this->reservationModel->modifierAvecRelationsEtLog($reservation) 
                    ? $_SESSION['message'] = "Modifié avec succès." 
                    : $_SESSION['erreur'] = "Échec de la mise à jour.";
            }
        }
    }

    // Redirection unique vers la liste des réservations de l'utilisateur
    header('Location: index.php?action=interfaceListeUtilisateur');
    exit();
}
    // --- Affichages ---

    public function afficherFormulaireModification(int $id): void {
    $this->checkAuth();
    $reservation = $this->reservationModel->findById($id);
    
    if (!$reservation) throw new Exception("Réservation introuvable.");

    $utilisateur = $this->utilisateurModel->recupererUtilisateursParId($_SESSION['Id_utilisateur']);
    $idPort = $utilisateur->getPort()->getIdPort();
    
    $this->render('interfaceListeModification', [
        'reservation' => $reservation,
        'salle_liste' => $this->salleModel->voirSallesParPort($idPort),
        'materiel_liste' => $this->materielModel->voirMaterielParPort($idPort),
        'idsSallesDejaReservees' => array_map(fn($s) => $s->getIdSalle(), $reservation->getSalles()),
        'idsMaterielsDejaReserves' => array_map(fn($m) => $m->getIdMateriel(), $reservation->getMateriels()),
        'csrf_token' => $_SESSION['csrf_token']
    ]);
}

    public function afficherPageReservation(): void {
    $this->checkAuth();
    
    $utilisateur = $this->utilisateurModel->recupererUtilisateursParId($_SESSION['Id_utilisateur']);
    $idPort = $utilisateur->getPort()->getIdPort();
    
    $idPort = $utilisateur->getPort()->getIdPort();
    
    $this->render('interfaceUtilisateur', [
        'salle_liste'    => $this->salleModel->voirSallesParPort($idPort),
        'materiel_liste' => $this->materielModel->voirMaterielParPort($idPort),
        'csrf_token'     => $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32))
    ]);
}

    public function afficherMesReservations(): void {
        $this->checkAuth();
        $idUtilisateur = $_SESSION["Id_utilisateur"];
        $reservations = (isset($_POST['type_affichage']) && $_POST['type_affichage'] === 'global') 
            ? $this->reservationModel->voirListeReservations()
            : $this->reservationModel->voirListeReservationsParId($idUtilisateur);
            
        $this->render('interfaceListeUtilisateur', ['reservations' => $reservations]);
    }

    // --- API JSON ---

    public function obtenirIndisponibilites(): void {
        $salles = array_map('intval', $_GET['salles'] ?? []);
        $materiels = array_map('intval', $_GET['materiels'] ?? []);
        $idIgnore = (int)($_GET['ignoreId'] ?? 0);
        
        header('Content-Type: application/json');
        echo json_encode($this->reservationModel->voirDatesPourRessources($salles, $materiels, $idIgnore) ?: []);
        exit();
    }

    public function verifierDisponibilite(): void {
        $resourceType = in_array($_GET['type'] ?? '', ['salle', 'materiel']) ? $_GET['type'] : 'salle';
        $disponible = $this->reservationModel->estDisponible(
            $resourceType, 
            (int)($_GET['id'] ?? 0), 
            $_GET['debut'] ?? '', 
            $_GET['fin'] ?? '', 
            $_GET['creneau'] ?? ''
        );

        header('Content-Type: application/json');
        echo json_encode(['disponible' => (bool)$disponible]);
        exit();
    }

    public function getReservationsJson(): void {
        $d1 = $_GET['start'] ?? date('Y-m-d');
        $d2 = $_GET['end'] ?? date('Y-m-d', strtotime('+1 year'));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d1) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d2)) {
            header('Content-Type: application/json', true, 400);
            echo json_encode(['error' => 'Format date invalide']);
            exit();
        }

        header('Content-Type: application/json');
        echo json_encode($this->reservationModel->voirReservationsParDate($d1, $d2) ?: []);
        exit();
    }
}

/*public function obtenirIndisponibilites() {
    // Filtrage simple : on s'assure que ce sont des tableaux d'entiers
    $salles = array_map('intval', $_GET['salles'] ?? []);
    $materiels = array_map('intval', $_GET['materiels'] ?? []);
    
    try {
        $dates = $this->reservationModel->voirDatesPourRessources($salles, $materiels);
    } catch (Exception $e) {
        // Loggez l'erreur ici : error_log($e->getMessage());
        $dates = [];
    }

    header('Content-Type: application/json');
    echo json_encode($dates ?: []); // Retourne toujours un tableau
    exit();
} */