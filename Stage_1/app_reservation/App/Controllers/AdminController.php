<?php
namespace App\Controllers;

use PDO;
use App\Models\{ReservationLogModel, UtilisateurModel, SalleModel, MaterielModel};

/**
 * AdminController : Gère uniquement les tâches d'administration pure.
 */
class AdminController extends BaseController {
    
    private SalleModel $salleModel;
    private MaterielModel $materielModel;
    private ReservationLogModel $reservationLogModel;
    private UtilisateurModel $utilisateurModel;

    public function __construct(PDO $pdo) {
        parent::__construct($pdo); 
        $this->salleModel = new SalleModel($pdo);
        $this->materielModel = new MaterielModel($pdo);
        $this->reservationLogModel = new ReservationLogModel($pdo);
        $this->utilisateurModel    = new UtilisateurModel($pdo);
    }

    /**
     * Vérification de sécurité globale pour les méthodes admin.
     */
    private function ensureAdmin(): void {
        $this->checkAuth();
        if (!$this->isAdmin()) {
            header('Location: index.php?action=login');
            exit();
        }
    }

    public function afficherActionsUtilisateurs(): void {
        $this->ensureAdmin();
        $this->render('interfaceAdministrateur');
    }

    public function afficherActionsReservations(): void {
        $this->ensureAdmin();
        $this->render('interfaceListeAdministrateur'); 
    }

    /**
     * Affiche l'interface de gestion des salles et du matériel
     */
    public function afficherActionsSallesMateriel(): void {
        $this->ensureAdmin();

        $this->render('interfaceSallesMaterielAdministrateur', [
            'salles'   => $this->salleModel->voirListeSalles(),
            'materiel' => $this->materielModel->voirListeMateriel()
        ]);
    }

    /**
     * Traite la soumission du formulaire d'ajout de salle
     */
    public function creerSallesAdmin(): void {
        $this->ensureAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['nom_salle'])) {
            
            $salle = new \App\Models\Salle();
            $salle->setNomSalle($_POST['nom_salle']);
            $salle->setCapaciteSalle((int)$_POST['capacite_salle']);
            $salle->setLocalisationSalle($_POST['localisation_salle']);

            $this->salleModel->ajouterUneSalle($salle);
        }

        header('Location: index.php?action=interfaceSalleMaterielAdministrateur');
        exit();
    }

    public function creerMaterielAdmin(): void {
        $this->ensureAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['type_materiel'])) {
            
            $materiel = new \App\Models\Materiel();
            $materiel->setTypMat($_POST['type_materiel']);
            $materiel->setNumMat($_POST['numero_materiel']);
            // On vérifie si un fichier a bien été envoyé et s'il n'y a pas d'erreur
        if (isset($_FILES['photo_materiel']) && $_FILES['photo_materiel']['error'] === UPLOAD_ERR_OK) {
            $nomFichier = $_FILES['photo_materiel']['name'];
            // Ici, tu pourrais ajouter move_uploaded_file() pour déplacer le fichier
            $materiel->setPhoMat($nomFichier); 
        } else {
            $materiel->setPhoMat(null); // Ou une chaîne vide si aucune photo
        }

            $this->materielModel->ajouterUnMateriel($materiel);
        }

        header('Location: index.php?action=interfaceSalleMaterielAdministrateur');
        exit();
    }

    public function modifierSallesAdmin(): void {
        $this->ensureAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['Id_salle'])) {
        
            $salle = new \App\Models\Salle();
            $salle->setIdSalle((int)$_POST['Id_salle']); // Récupération correcte
            $salle->setNomSalle($_POST['nom_salle'] ?? '');
            $salle->setCapaciteSalle((int)($_POST['capacite_salle'] ?? 0));
            $salle->setLocalisationSalle($_POST['localisation_salle'] ?? '');

            // 2. Appel au modèle
            $this->salleModel->modifierUneSalle($salle);
        
        }

        header('Location: index.php?action=interfaceSalleMaterielAdministrateur');
        exit();
    }

    public function modifierMaterielAdmin(): void {
        $this->ensureAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['Id_materiel'])) {
        
            $materiel = new \App\Models\Materiel();
            $materiel->setIdMateriel((int)$_POST['Id_materiel']); // Récupération correcte
            $materiel->setTypMat($_POST['type_materiel'] ?? '');
            $materiel->setNumMat((int)($_POST['numero_materiel'] ?? 0));
            // On vérifie si un fichier a bien été envoyé et s'il n'y a pas d'erreur
        if (isset($_FILES['photo_materiel']) && $_FILES['photo_materiel']['error'] === UPLOAD_ERR_OK) {
            $nomFichier = $_FILES['photo_materiel']['name'];
            // Ici, tu pourrais ajouter move_uploaded_file() pour déplacer le fichier
            $materiel->setPhoMat($nomFichier); 
        } else {
            $materiel->setPhoMat(null); // Ou une chaîne vide si aucune photo
        }

            // 2. Appel au modèle
            $this->materielModel->modifierUnMateriel($materiel);
        
        }

        header('Location: index.php?action=interfaceSalleMaterielAdministrateur');
        exit();
    }

    public function supprimerSallesAdmin(): void {
        $this->ensureAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['Id_salle'])) {
            $id = (int)$_POST['Id_salle'];
            $this->salleModel->supprimerUneSalle($id);
        }

        header('Location: index.php?action=interfaceSalleMaterielAdministrateur');
        exit();
    }

    public function supprimerMaterielAdmin(): void {
        $this->ensureAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['Id_materiel'])) {
            $id = (int)$_POST['Id_materiel'];
            $this->materielModel->supprimerUnMateriel($id);
        }

        header('Location: index.php?action=interfaceSalleMaterielAdministrateur');
        exit();
    }

    public function afficherListeLogs(): void {
        $this->ensureAdmin();
        $logs = $this->reservationLogModel->getAllLogsWithUsers();
        $this->render('interfaceLogAdministrateur', ['logs' => $logs]);
    }
}