<?php
namespace App\Controllers;

use PDO;
// Importation des modèles et entités nécessaires
use App\Models\{ReservationLogModel, UtilisateurModel, RoleModel, PortModel, SalleModel, MaterielModel, Utilisateur, Role, Port};

/**
 * AdminController : Gère toutes les opérations d'administration.
 * Hérite de BaseController pour bénéficier des méthodes utilitaires (render, auth, etc.)
 */
class AdminController extends BaseController {
    
    // Propriétés pour stocker les instances de modèles
    private SalleModel $salleModel;
    private MaterielModel $materielModel;
    private ReservationLogModel $reservationLogModel;
    private UtilisateurModel $utilisateurModel;
    private RoleModel $roleModel;
    private PortModel $portModel;

    public function __construct(PDO $pdo) {
        parent::__construct($pdo); 
        // Initialisation des modèles une seule fois pour tout le contrôleur
        $this->salleModel = new SalleModel($pdo);
        $this->materielModel = new MaterielModel($pdo);
        $this->reservationLogModel = new ReservationLogModel($pdo);
        $this->utilisateurModel = new UtilisateurModel($pdo);
        $this->roleModel = new RoleModel($pdo);
        $this->portModel = new PortModel($pdo);
    }

    /**
     * Vérification de sécurité : s'assure que l'utilisateur est bien administrateur.
     */
    private function ensureAdmin(): void {
        $this->checkAuth();
        if (!$this->isAdmin()) {
            header('Location: index.php?action=login');
            exit();
        }
    }

    /**
     * Affiche l'interface principale de gestion des utilisateurs
     */
    public function interfaceAdministrateur() {
        $this->ensureAdmin();
        
        // Préparation des données pour la vue
        $data = [
            'utilisateurs' => $this->utilisateurModel->RecupererUtilisateurs(),
            'roles'        => $this->roleModel->findAll(),
            'ports'        => $this->portModel->findAll(),
            'csrf_token'   => $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32))
        ];

        $this->render('interfaceAdministrateur', $data);
    }

    /**
     * Traite la création d'un utilisateur
     */
    public function creerUtilisateursAdmin() {
        $this->ensureAdmin();
        $this->verifierCsrf();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = new Utilisateur();
            $user->setIdentifiant($_POST['identifiant_utilisateur']);
            $user->setPrenomUtilisateur($_POST['prenom_utilisateur']);
            $user->setNomUtilisateur($_POST['nom_utilisateur']);
            $user->setEmailUtilisateur($_POST['e_mail_utilisateur']);
            $user->setMdpUtilisateur($_POST['mot_de_passe_utilisateur']);
            
            // Hachage du mot de passe à la création
            $hash = password_hash($_POST['mot_de_passe_utilisateur'], PASSWORD_DEFAULT);
            $user->setMdpUtilisateur($hash);

            // Hydratation des relations
            $role = new Role();
            $role->setIdRol((int)$_POST['Id_role']);
            $user->setRole($role);

            $port = new Port();
            $port->setIdPort((int)$_POST['Id_port']);
            $user->setPort($port);

            $this->utilisateurModel->ajouterUnUtilisateur($user);
        }
        header('Location: index.php?action=interfaceAdministrateur');
        exit;
    }

    /**
     * Traite la modification d'un utilisateur existant
     */
    public function modifierUtilisateursAdmin() {
        $this->ensureAdmin();
        $this->verifierCsrf();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Id_utilisateur'])) {
            $user = new Utilisateur();
            $user->setIdUtilisateur((int)$_POST['Id_utilisateur']);
            $user->setIdentifiant($_POST['identifiant_utilisateur']);
            $user->setPrenomUtilisateur($_POST['prenom_utilisateur']);
            $user->setNomUtilisateur($_POST['nom_utilisateur']);
            $user->setEmailUtilisateur($_POST['e_mail_utilisateur']);
            
            // Hachage optionnel : seulement si un nouveau mot de passe est saisi
            if (!empty($_POST['mot_de_passe_utilisateur'])) {
                $hash = password_hash($_POST['mot_de_passe_utilisateur'], PASSWORD_DEFAULT);
                $user->setMdpUtilisateur($hash);
            }

            $role = new Role();
            $role->setIdRol((int)$_POST['Id_role']);
            $user->setRole($role);

            $port = new Port();
            $port->setIdPort((int)$_POST['Id_port']);
            $user->setPort($port);

            $this->utilisateurModel->modifierUnUtilisateur($user);
        }
        header('Location: index.php?action=interfaceAdministrateur');
        exit;
    }

    /**
     * Traite la suppression d'un utilisateur
     */
    public function supprimerUtilisateursAdmin() {
        $this->ensureAdmin();
        $this->verifierCsrf();
        
        if (isset($_POST['Id_utilisateur'])) {
            $this->utilisateurModel->supprimerUnUtilisateur((int)$_POST['Id_utilisateur']);
        }
        header('Location: index.php?action=interfaceAdministrateur');
        exit;
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

    /**
     * Gère la sécurité des formulaires (Anti-CSRF)
     */
    private function verifierCsrf() {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            die("Erreur de sécurité : Jeton CSRF invalide.");
        }
    }
}