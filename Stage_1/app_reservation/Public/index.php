<?php
/**
 * Routeur principal de l'application
 */

// 1. Démarrage de session (Maintenant géré par les contrôleurs via BaseController, 
// mais on le laisse ici par précaution si nécessaire au démarrage)
if (session_status() === PHP_SESSION_NONE) session_start();

// 2. Autoloader
require_once __DIR__ . '/../vendor/autoload.php'; 

use App\Core\Database;
use App\Controllers\{AuthController, ReservationController, AdminController};

// 3. Initialisation DB
$pdo = Database::getConnection();

// 4. Récupération de l'action
$action = $_GET['action'] ?? 'connexion';

// 5. Routeur : Instanciation à la demande pour optimiser les performances
switch ($action) {
    
    // --- Authentification ---
    case 'connexion':
    case 'deconnexion':
        $controller = new AuthController($pdo);
        $action === 'connexion' ? $controller->connexion() : $controller->deconnexion();
        break;

    // --- Gestion des Réservations (Utilisateur) ---
    case 'interfaceUtilisateur':
        $controller = new ReservationController($pdo);
        $controller->afficherPageReservation();
        break;

    case 'interfaceListeUtilisateur':
        $controller = new ReservationController($pdo);
        $controller->afficherMesReservations();
        break;

    case 'creerReservation':
        $controller = new ReservationController($pdo);
        $controller->ajouterUneReservation();
        break;

    case 'modifierReservation':
    case 'supprimerReservation':
        $controller = new ReservationController($pdo);
        $controller->traiterActionReservation();
        break;

    case 'formulaireModifier':
        $controller = new ReservationController($pdo);
        $controller->afficherFormulaireModification((int)($_GET['id'] ?? 0));
        break;

    // --- API & Calendrier ---
    case 'obtenirIndisponibilites':
    case 'verifierDisponibilite':
    case 'getReservationsJson':
        $controller = new ReservationController($pdo);
        $controller->$action(); // Appel dynamique de la méthode
        break;

    // --- Administration ---
    case 'interfaceAdministrateur':
    case 'interfaceSalleMaterielAdministrateur':
    case 'interfaceLogAdministrateur':
    $controller = new AdminController($pdo);
    $map = [
        'interfaceAdministrateur' => 'afficherActionsUtilisateurs',
        'interfaceSalleMaterielAdministrateur' => 'afficherActionsSallesMateriel',
        'interfaceLogAdministrateur' => 'afficherListeLogs'
    ];
    $method = $map[$action];
    $controller->$method();
    break;

    // On gère la liste séparément pour utiliser le ReservationController
    case 'interfaceListeAdministrateur':
    $controller = new ReservationController($pdo);
    $controller->afficherListeAdministrateur();
    break;

    case 'creerReservationAdmin': // Nouvelle action spécifique
    $controller = new ReservationController($pdo);
    $controller->ajouterUneReservation();
    break;

    case 'modifierReservationAdmin':
    case 'supprimerReservationAdmin':
        $controller = new ReservationController($pdo);
        $controller->traiterActionReservation();
        break;

    case 'creerSallesAdmin':
        $controller = new AdminController($pdo);
        $controller->creerSallesAdmin(); // Appel de la méthode définie dans AdminController
        break;

    case 'voirSallesAdmin':
        $controller = new AdminController($pdo);
        $controller->afficherActionsSallesMateriel();
        break;

    case 'modifierSallesAdmin':
        $controller = new AdminController($pdo);
        $controller->modifierSallesAdmin();
        break;

    case 'supprimerSallesAdmin':
        $controller = new AdminController($pdo);
        $controller->supprimerSallesAdmin();
        break;

    case 'creerMaterielAdmin':
        $controller = new AdminController($pdo);
        $controller->creerMaterielAdmin();
        break;

    case 'voirMaterielAdmin':
        $controller = new AdminController($pdo);
        $controller->afficherActionsSallesMateriel();
        break;

    case 'modifierMaterielAdmin':
        $controller = new AdminController($pdo);
        $controller->modifierMaterielAdmin();
        break;

    case 'supprimerMaterielAdmin':
        $controller = new AdminController($pdo);
        $controller->supprimerMaterielAdmin();
        break;

    default:
        header("Location: index.php?action=connexion");
        exit();
}