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
use App\Models;

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
    case 'interfaceListeUtilisateur':
    case 'creerReservation':
    case 'formulaireModifier':
        $controller = new ReservationController($pdo);
        // Mapping manuel pour les méthodes nommées différemment de l'action
        $map = [
            'interfaceUtilisateur'      => 'afficherPageReservation',
            'interfaceListeUtilisateur' => 'afficherMesReservations',
            'creerReservation'          => 'ajouterUneReservation',
            'formulaireModifier'        => 'afficherFormulaireModification'
        ];
        $method = $map[$action] ?? $action;
        $action === 'formulaireModifier' ? $controller->$method((int)($_GET['id'] ?? 0)) : $controller->$method();
        break;

    case 'modifierReservation':
    case 'supprimerReservation':
        (new ReservationController($pdo))->traiterActionReservation();
        break;

    // --- API & Calendrier ---
    case 'obtenirIndisponibilites':
    case 'verifierDisponibilite':
    case 'getReservationsJson':
        (new ReservationController($pdo))->$action();
        break;

    // --- Administration (Regroupement) ---
    case 'interfaceAdministrateur':
    case 'interfaceLogAdministrateur':
    case 'interfaceListeAdministrateur':
    case 'interfaceSalleMaterielAdministrateur':
    case 'interfaceRolePortAdministrateur':
    case 'interfaceReservationAdministrateur':
    case 'interfaceListeReservationAdministrateur':
        $controller = new AdminController($pdo);
        $map = [
            'interfaceAdministrateur'                 => 'interfaceAdministrateur',
            'interfaceLogAdministrateur'              => 'afficherListeLogs',
            'interfaceSalleMaterielAdministrateur'    => 'afficherActionsSallesMateriel',
            'interfaceRolePortAdministrateur'         => 'interfaceRolePortAdministrateur',
            'interfaceReservationAdministrateur'      => 'afficherReservationAdministrateur',
            'interfaceListeReservationAdministrateur' => 'afficherListeReservationAdministrateur',
            
        ];
        $method = $map[$action];
        $controller->$method();
        break;

    // --- Actions nécessitant des paramètres (ID) ---
    case 'formulaireModificationAdmin':
        $controller = new AdminController($pdo);
        $id = (int)($_GET['id'] ?? 0);
        $controller->afficherFormulaireModificationAdmin($id);
        break;

    // --- Actions CRUD Administrateur (Administration) ---
    case 'creerUtilisateursAdmin': case 'modifierUtilisateursAdmin': case 'supprimerUtilisateursAdmin':
    case 'creerRoleAdmin': case 'modifierRolesAdmin': case 'supprimerRolesAdmin':
    case 'creerPortAdmin': case 'modifierPortAdmin': case 'supprimerPortAdmin':
    case 'creerSallesAdmin': case 'modifierSallesAdmin': case 'supprimerSallesAdmin':
    case 'creerMaterielAdmin': case 'modifierMaterielAdmin': case 'supprimerMaterielAdmin':
        $controller = new AdminController($pdo);
        $controller->$action();
        break;

    // --- Actions CRUD Réservations (Spécifique Admin) ---
    case 'creerReservationAdmin':
        $controller = new AdminController($pdo);
        $controller->creerReservationAdmin(); 
        break;
        
    case 'modifierReservationAdmin':
        $controller = new AdminController($pdo);
        $controller->modifierReservationAdmin();
        break;

    case 'supprimerReservationAdmin':
        $controller = new AdminController($pdo);
        $controller->supprimerReservationAdmin();
        break;

    default:
        header("Location: index.php?action=connexion");
        exit();
}