<?php
namespace App\Controllers;

use PDO;
use Exception; // C'est une classe native PHP, mais il est préférable de l'importer
use App\Models\{Reservation, ReservationModel, ReservationLog, ReservationLogModel, UtilisateurModel, RoleModel, PortModel, SalleModel, MaterielModel, Utilisateur, Role, Port, Salle, Materiel};

/**
 * AdminController : Gère les opérations d'administration.
 */
class AdminController extends BaseController {
    
    private ReservationModel $reservationModel;
    private SalleModel $salleModel;
    private MaterielModel $materielModel;
    private ReservationLogModel $reservationLogModel;
    private UtilisateurModel $utilisateurModel;
    private RoleModel $roleModel;
    private PortModel $portModel;

    public function __construct(PDO $pdo) {
        parent::__construct($pdo);
        $this->reservationModel = new ReservationModel($pdo); 
        $this->salleModel = new SalleModel($pdo);
        $this->materielModel = new MaterielModel($pdo);
        $this->reservationLogModel = new ReservationLogModel($pdo);
        $this->utilisateurModel = new UtilisateurModel($pdo);
        $this->roleModel = new RoleModel($pdo);
        $this->portModel = new PortModel($pdo);
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

    /**
     * Vérification stricte des droits administrateur.
     */
    private function ensureAdmin(): void {
        $this->checkAuth();
        if (!$this->isAdmin()) {
            header('Location: index.php?action=login');
            exit();
        }
    }

    /**
     * Affiche l'interface de gestion des utilisateurs.
     */
    public function interfaceAdministrateur(): void {
        $this->ensureAdmin();
        $data = [
            'utilisateurs' => $this->utilisateurModel->recupererUtilisateurs(),
            'roles'        => $this->roleModel->findAll(),
            'ports'        => $this->portModel->findAll(),
            'csrf_token'   => $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32))
        ];
        $this->render('interfaceAdministrateur', $data);
    }

    /**
     * Création d'un utilisateur par l'admin avec hachage du mot de passe.
     */
    public function creerUtilisateursAdmin(): void {
        $this->ensureAdmin();
        $this->verifierCsrf();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = new Utilisateur();
            $user->setIdentifiant($_POST['identifiant_utilisateur']);
            $user->setPrenomUtilisateur($_POST['prenom_utilisateur']);
            $user->setNomUtilisateur($_POST['nom_utilisateur']);
            $user->setEmailUtilisateur($_POST['e_mail_utilisateur']);
            
            // Hachage sécurisé avant enregistrement
            $user->setMdpUtilisateur(password_hash($_POST['mot_de_passe_utilisateur'], PASSWORD_DEFAULT));

            // Création du rôle
            $role = new Role();
            $role->setIdRol((int)$_POST['Id_role']);
            $user->setRole($role);

            // Création du port
            $port = new Port();
            $port->setIdPort((int)$_POST['Id_port']);
            $user->setPort($port);

            $this->utilisateurModel->ajouterUnUtilisateur($user);
        }
        header('Location: index.php?action=interfaceAdministrateur');
        exit;
    }

    /**
     * Modification d'un utilisateur (hachage conditionnel si mot de passe fourni).
     */
    public function modifierUtilisateursAdmin(): void {
        $this->ensureAdmin();
        $this->verifierCsrf();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Id_utilisateur'])) {
            $user = new Utilisateur();
            $user->setIdUtilisateur((int)$_POST['Id_utilisateur']);
            $user->setIdentifiant($_POST['identifiant_utilisateur']);
            $user->setPrenomUtilisateur($_POST['prenom_utilisateur']);
            $user->setNomUtilisateur($_POST['nom_utilisateur']);
            $user->setEmailUtilisateur($_POST['e_mail_utilisateur']);
            
            if (!empty($_POST['mot_de_passe_utilisateur'])) {
                $user->setMdpUtilisateur(password_hash($_POST['mot_de_passe_utilisateur'], PASSWORD_DEFAULT));
            }

            // Création du rôle
            $role = new Role();
            $role->setIdRol((int)$_POST['Id_role']);
            $user->setRole($role);

            // Création du port
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
    public function supprimerUtilisateursAdmin(): void {
        $this->ensureAdmin();
        $this->verifierCsrf();
        if (isset($_POST['Id_utilisateur'])) {
            $this->utilisateurModel->supprimerUnUtilisateur((int)$_POST['Id_utilisateur']);
        }
        header('Location: index.php?action=interfaceAdministrateur');
        exit;
    }

    // Affiche l'interface de gestion
    public function interfaceRolePortAdministrateur() {
        $data = [
        'roles' => $this->roleModel->findAll(),
        'ports' => $this->portModel->findAll()
    ];
    $this->render('interfaceRolePortAdministrateur', $data);
}

    // --- ACTIONS SUR LES RÔLES ---

    public function creerRoleAdmin(): void {
        $this->verifierCsrf();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['libelle_role'])) {
            $role = new Role();
            $role->setLib($_POST['libelle_role']);
            $this->roleModel->creerUnRole($role);
        }
        header('Location: index.php?action=interfaceRolePortAdministrateur');
        exit;
    }

    public function modifierRolesAdmin() {
        $this->verifierCsrf();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['Id_role'])) {
            $role = new Role();
            $role->setIdRol((int)$_POST['Id_role']);
            $role->setLib($_POST['libelle_role']);
            $this->roleModel->modifierUnRole($role);
        }
        header('Location: index.php?action=interfaceRolePortAdministrateur');
    }

    public function supprimerRolesAdmin() {
        $this->verifierCsrf();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['Id_role'])) {
            $role = new Role();
            $role->setIdRol((int)$_POST['Id_role']);
            $this->roleModel->supprimerUnRole($role);
        }
        header('Location: index.php?action=interfaceRolePortAdministrateur');
    }

    // --- ACTIONS SUR LES PORTS ---

    public function creerPortAdmin() {
        $this->verifierCsrf();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['identite_port'])) {
            $port = new Port();
            $port->setLibPort($_POST['identite_port']);
            $this->portModel->creerUnPort($port);
        }
        header('Location: index.php?action=interfaceRolePortAdministrateur');
    }

    public function modifierPortAdmin() {
        $this->verifierCsrf();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['Id_port'])) {
            $port = new Port();
            $port->setIdPort((int)$_POST['Id_port']);
            $port->setLibPort($_POST['identite_port']);
            $this->portModel->modifierUnPort($port);
        }
        header('Location: index.php?action=interfaceRolePortAdministrateur');
    }

    public function supprimerPortAdmin() {
        $this->verifierCsrf();
        
        // On vérifie que l'ID est bien présent et qu'il s'agit d'une requête POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['Id_port'])) {
            
            // On convertit directement l'ID en entier
            $id = (int)$_POST['Id_port'];
            
            // On passe l'entier au modèle, et non l'objet
            $this->portModel->supprimerUnPort($id);
        }
        
        header('Location: index.php?action=interfaceRolePortAdministrateur');
        exit(); // Toujours ajouter exit après un header location
    }

    /**
     * Affiche l'interface de gestion des salles et du matériel
     */
    public function afficherActionsSallesMateriel(): void {
        $this->ensureAdmin();
        $this->verifierCsrf();

        $this->render('interfaceSallesMaterielAdministrateur', [
            'salles'   => $this->salleModel->voirListeSalles(),
            'materiel' => $this->materielModel->voirListeMateriel(),
            'ports'    => $this->portModel->findAll(),
        ]);
    }

    /**
     * Traite la soumission du formulaire d'ajout de salle
     */
    public function creerSallesAdmin(): void {
        $this->ensureAdmin();
        $this->verifierCsrf();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['nom_salle'])) {
            $salle = new Salle();
            $salle->setNomSalle($_POST['nom_salle']);
            $salle->setCapaciteSalle((int)$_POST['capacite_salle']);
            $salle->setLocalisationSalle($_POST['localisation_salle']);

            $idPort = (int)$_POST['id_port'];
            $port = new Port();
            $port->setIdPort($idPort);
            $salle->setPort($port); // On injecte l'objet port

            // Utilisation du retour booléen pour feedback
            if ($this->salleModel->ajouterUneSalle($salle)) {
                $_SESSION['flash'] = "Salle créée avec succès.";
            }
        }
        header('Location: index.php?action=interfaceSalleMaterielAdministrateur');
        exit();
    }

    public function creerMaterielAdmin(): void {
        $this->ensureAdmin();
        $this->verifierCsrf();

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

            $idPort = (int)$_POST['id_port'];
            $port = new Port();
            $port->setIdPort($idPort);
            $materiel->setPort($port); // On injecte l'objet port

            if ($this->materielModel->ajouterUnMateriel($materiel)) {
                $_SESSION['flash'] = "Materiel créé avec succès.";
            }
        }

        header('Location: index.php?action=interfaceSalleMaterielAdministrateur');
        exit();
    }

    public function modifierSallesAdmin(): void {
        $this->ensureAdmin();
        $this->verifierCsrf();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['Id_salle'])) {
        
            $salle = new \App\Models\Salle();
            $salle->setIdSalle((int)$_POST['Id_salle']); // Récupération correcte
            $salle->setNomSalle($_POST['nom_salle'] ?? '');
            $salle->setCapaciteSalle((int)($_POST['capacite_salle'] ?? 0));
            $salle->setLocalisationSalle($_POST['localisation_salle'] ?? '');

            $port = new Port();
            $port->setIdPort((int)$_POST['id_port']);
            $salle->setPort($port);

            // 2. Appel au modèle
            $this->salleModel->modifierUneSalle($salle);
        
        }

        header('Location: index.php?action=interfaceSalleMaterielAdministrateur');
        exit();
    }

    public function modifierMaterielAdmin(): void {
        $this->ensureAdmin();
        $this->verifierCsrf();

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

            $port = new Port();
            $port->setIdPort((int)$_POST['id_port']);
            $materiel->setPort($port);

            // 2. Appel au modèle
            $this->materielModel->modifierUnMateriel($materiel);
        
        }

        header('Location: index.php?action=interfaceSalleMaterielAdministrateur');
        exit();
    }

    public function supprimerSallesAdmin(): void {
        $this->ensureAdmin();
        $this->verifierCsrf();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['Id_salle'])) {
            $id = (int)$_POST['Id_salle'];
            $this->salleModel->supprimerUneSalle($id);
        }

        header('Location: index.php?action=interfaceSalleMaterielAdministrateur');
        exit();
    }

    public function supprimerMaterielAdmin(): void {
        $this->ensureAdmin();
        $this->verifierCsrf();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['Id_materiel'])) {
            $id = (int)$_POST['Id_materiel'];
            $this->materielModel->supprimerUnMateriel($id);
        }

        header('Location: index.php?action=interfaceSalleMaterielAdministrateur');
        exit();
    }

/**
 * Affiche l'interface de réservation côté administrateur (Calendrier + Formulaire)
 */
public function afficherReservationAdministrateur(): void 
{
    $this->ensureAdmin();

    $data = [
        'salle_liste'    => $this->salleModel->voirListeSalles() ?? [],
        'materiel_liste' => $this->materielModel->voirListeMateriel() ?? [],
        'reservations'   => $this->reservationModel->voirListeReservations() ?? [],
        'csrf_token'     => $this->getCsrfToken(),
        'isAdminMode'    => true
    ];

    $this->render('interfaceReservationAdministrateur', $data); 
}

public function afficherListeReservationAdministrateur(): void 
{ 
    $this->ensureAdmin();
    
    // On garantit toujours un tableau, même si la requête échoue
    $reservations = $this->reservationModel->voirListeReservations() ?? []; 
    
    $data = [
        'reservations'   => $reservations, 
        'salle_liste'    => $this->salleModel->voirListeSalles() ?? [],
        'materiel_liste' => $this->materielModel->voirListeMateriel() ?? [],
        'csrf_token'     => $this->getCsrfToken()
    ];

    $this->render('interfaceListeReservationAdministrateur', $data); 
}

public function afficherFormulaireModificationAdmin(int $id): void {
    $reservation = $this->reservationModel->findById($id);
    // DEBUG : est-ce que les salles sont bien là ?
    // var_dump($reservation->getSalles()); die(); 
    
    $this->render('interfaceListeModificationAdministrateur', [
        'reservation' => $reservation,
        'salle_liste' => $this->salleModel->voirListeSalles(),
        'materiel_liste' => $this->materielModel->voirListeMateriel(),
        'idsSallesDejaReservees' => array_map(fn($s) => $s->getIdSalle(), $reservation->getSalles()),
        'idsMaterielsDejaReserves' => array_map(fn($m) => $m->getIdMateriel(), $reservation->getMateriels()),
        'csrf_token' => $_SESSION['csrf_token']
    ]);
}

    public function creerReservationAdmin(): void {
    $this->ensureAdmin();
    $this->verifierCsrf();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bouton_administrateur'])) {
        try {
            $reservation = new Reservation();
            
            $reservation->setMotif(trim($_POST["motif_reservation"]));
            $reservation->setDebut($this->formatDate($_POST["date_debut_reservation"]));
            $reservation->setFin($this->formatDate($_POST["date_fin_reservation"]));
            $reservation->setCre($_POST["creneau_reservation"]);

            $userId = $_SESSION['Id_utilisateur'];
            $utilisateur = $this->utilisateurModel->recupererUtilisateursParId($userId); 

            if (!$utilisateur) {
                throw new Exception("Utilisateur introuvable en base de données.");
            }
            
            // Maintenant l'utilisateur possède son rôle chargé via le modèle
            $reservation->setUtilisateur($utilisateur);

            $this->hydraterSallesEtMateriels($reservation, $_POST);

            if ($this->reservationModel->creerAvecRelationsEtLog($reservation)) {
                header("Location: index.php?action=interfaceListeReservationAdministrateur&succes=1");
            } else {
                throw new Exception("L'enregistrement a échoué.");
            }
            exit();
        } catch (Exception $e) {
            error_log("Erreur création Admin : " . $e->getMessage());
            $_SESSION['erreur'] = $e->getMessage();
            header("Location: index.php?action=interfaceReservationAdministrateur");
            exit();
        }
    }
    
    $this->afficherReservationAdministrateur();
}

    /**
 * Vérifie si l'utilisateur est admin ou propriétaire de la réservation
 */
public function modifierReservationAdmin(): void {
    $this->checkAuth();
    $this->verifierCsrf();

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $reservation = $id ? $this->reservationModel->findById($id) : null;

    // Utilisation de la méthode maintenant présente dans BaseController
    if ($reservation && $this->verifierAccesReservation($reservation)) {

        $reservation->setMotif(trim($_POST['motif_reservation'] ?? ''));
        $reservation->setDebut($this->formatDate($_POST['date_debut_reservation']));
        $reservation->setFin($this->formatDate($_POST['date_fin_reservation']));
        $reservation->setCre($_POST['creneau_reservation']);
        
        // --- SÉCURITÉ : Ré-hydratation du rôle ---
        $user = $reservation->getUtilisateur();
        if ($user && $user->getIdUtilisateur()) {
            $userComplet = $this->utilisateurModel->recupererUtilisateursParId($user->getIdUtilisateur());
            if ($userComplet) {
                $reservation->setUtilisateur($userComplet);
            }
        }

        $this->hydraterSallesEtMateriels($reservation, $_POST);
        
        // Tentative de modification
        if ($this->reservationModel->modifierAvecRelationsEtLog($reservation)) {
            $_SESSION['succes'] = "Réservation modifiée avec succès.";
        } else {
            $_SESSION['erreur'] = "Erreur lors de la mise à jour de la réservation.";
        }
    } else {
        // Retourne une erreur si l'accès est refusé
        $_SESSION['erreur'] = "Accès non autorisé ou réservation introuvable.";
    }

    header('Location: index.php?action=interfaceListeReservationAdministrateur');
    exit();
}

public function supprimerReservationAdmin(): void {
    $this->checkAuth();
    $this->verifierCsrf();

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) return;

    $user = new \App\Models\Utilisateur();
    $role = new \App\Models\Role();
    $role->setIdRol(1); 
    $user->setRole($role);
    $user->setIdUtilisateur($_SESSION['Id_utilisateur']);

    $this->reservationModel->annulerAvecRelationsEtLog($id, $user);

    header('Location: index.php?action=interfaceListeReservationAdministrateur');
    exit();
}

    public function afficherListeLogs(): void {
        $this->ensureAdmin();
        $this->verifierCsrf();

        $logs = $this->reservationLogModel->getAllLogsWithUsers();
        $this->render('interfaceLogAdministrateur', ['logs' => $logs]);
    }

}

?>