<?php
namespace App\Controllers;

use PDO;
use Exception;
use App\Models\{Reservation, Utilisateur, ReservationModel, UtilisateurModel, SalleModel, MaterielModel};

//----------------------------------------------------------------------------------------------
// ReservationController : Gère le cycle de vie complet des réservations (CRUD).
// Hérite de BaseController pour bénéficier des outils de sécurité (Auth, CSRF) et de rendu.
//----------------------------------------------------------------------------------------------

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
     * Helper : Convertit les dates du format français (JJ/MM/AAAA) vers SQL (AAAA-MM-JJ).
     */
    private function formatDate(string $date): string {
        return (strpos($date, '/') !== false) 
            ? implode('-', array_reverse(explode('/', $date))) 
            : $date;
    }

    /**
     * Hydrate l'objet Réservation en associant les salles et matériels sélectionnés.
     */
    private function hydraterSallesEtMateriels(Reservation $reservation, array $postData): void {
        $reservation->setSalles([]);
        $reservation->setMateriels([]);
        // Association des salles via le modèle
        foreach ($postData['salles'] ?? [] as $id) {
            if ($salle = $this->salleModel->findById((int)$id)) {
                $reservation->addSalle($salle);
            }
        }
        // Association des matériels via le modèle
        foreach ($postData['materiels'] ?? [] as $id) {
            if ($materiel = $this->materielModel->findById((int)$id)) {
                $reservation->addMateriel($materiel);
            }
        }
    }

    // --- Actions CRUD ---

    /**
     * Gère la création d'une nouvelle réservation après soumission du formulaire.
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
    
                // L'utilisateur est identifié via la session (sécurisé)
                $utilisateur = new Utilisateur(); 
                $utilisateur->setIdUtilisateur($_SESSION['Id_utilisateur']);
                $reservation->setUtilisateur($utilisateur);

                $this->hydraterSallesEtMateriels($reservation, $_POST);
                // Persistance en base et journalisation
                if ($this->reservationModel->creerAvecRelationsEtLog($reservation)) {
                    header("Location: index.php?action=interfaceListeUtilisateur&succes=1");
                } else {
                    throw new Exception("L'enregistrement a échoué.");
                }
                exit();
            } catch (Exception $e) {
                error_log("Erreur création : " . $e->getMessage());
                $_SESSION['erreur'] = $e->getMessage(); // Message stocké pour affichage ultérieur
                header("Location: index.php?action=interfaceUtilisateur");
                exit();
            }
        }
    
        $this->afficherPageReservation();
}

    /**
     * Traite les modifications ou suppressions demandées par l'utilisateur.
     */
    public function traiterActionReservation(): void {
    $this->checkAuth();
    $this->verifierCsrf();

    if (isset($_POST['action'], $_POST['id'])) {
        $id = (int)$_POST['id'];

        // Sécurité : Vérifie que la réservation appartient bien à l'utilisateur de la session
        $reservation = $this->reservationModel->findById($id);
        if ($reservation && $reservation->getUtilisateur()->getIdUtilisateur() == $_SESSION['Id_utilisateur']) {
            
            if ($_POST['action'] === 'supprimer') {
                $user = new Utilisateur();
                $user->setIdUtilisateur($_SESSION['Id_utilisateur']);
                $this->reservationModel->annulerAvecRelationsEtLog($id, $user);
                
            } elseif ($_POST['action'] === 'modifier') {
                // Mise à jour des données de l'objet
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
    // --- Affichages et API ---

    public function afficherFormulaireModification(int $id): void {
    $this->checkAuth();
    $reservation = $this->reservationModel->findById($id);
    
    if (!$reservation) throw new Exception("Réservation introuvable.");
    // Récupère les ressources disponibles liées au port de l'utilisateur
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

    /**
     * API : Récupère les plages d'indisponibilité pour un ensemble de ressources.
     * Utile pour griser les dates non disponibles dans un calendrier (ex: Datepicker).
     */
    public function obtenirIndisponibilites(): void {
        // Nettoyage : conversion forcée des paramètres d'URL en entiers
        $salles = array_map('intval', $_GET['salles'] ?? []);
        $materiels = array_map('intval', $_GET['materiels'] ?? []);
        $idIgnore = (int)($_GET['ignoreId'] ?? 0); // Permet d'exclure la résa en cours lors d'une modif
        // Définition du type de contenu attendu par le client
        header('Content-Type: application/json');
        // Appel au modèle et encodage JSON. Le "?: []" garantit un tableau vide si aucune donnée trouvée
        echo json_encode($this->reservationModel->voirDatesPourRessources($salles, $materiels, $idIgnore) ?: []);
        exit(); // Arrêt immédiat pour éviter l'affichage de code HTML parasite
    }

    /**
     * API : Vérifie si une ressource précise est disponible sur un créneau donné.
     * Utilisée pour valider le formulaire avant ou après la soumission.
     */
    public function verifierDisponibilite(): void {
        // Validation : on limite strictement le type de ressource autorisé
        $resourceType = in_array($_GET['type'] ?? '', ['salle', 'materiel']) ? $_GET['type'] : 'salle';

        // Vérification de la disponibilité via la logique métier du modèle
        $disponible = $this->reservationModel->estDisponible(
            $resourceType, 
            (int)($_GET['id'] ?? 0), 
            $_GET['debut'] ?? '', 
            $_GET['fin'] ?? '', 
            $_GET['creneau'] ?? ''
        );

        header('Content-Type: application/json');
        // Retourne un objet simple { "disponible": true/false }
        echo json_encode(['disponible' => (bool)$disponible]);
        exit();
    }

    /**
     * API JSON pour les outils de calendrier/disponibilité (ex: FullCalendar).
     */
    public function getReservationsJson(): void {
        $d1 = $_GET['start'] ?? date('Y-m-d');
        $d2 = $_GET['end'] ?? date('Y-m-d', strtotime('+1 year'));
        // Validation stricte du format de date avant requêtage
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