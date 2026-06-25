<?php

namespace App\Models;

use App\Core\Model;
use App\Models\{Role, Port, Utilisateur, Reservation, Salle, Materiel, ReservationLog};
use PDO;
use PDOException; // Importation nécessaire pour capturer les erreurs PDO
use Exception;

/**
 * Classe ReservationModel
 * Gère les interactions avec la base de données pour les réservations.
 */
class ReservationModel extends Model
{
    protected PDO $db;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    /**
     * Récupère toutes les dates réservées pour vérification de conflits.
     */
    public function getDatesReservees(): array
    {
        try {
            $requete = $this->db->query("SELECT date_debut_reservation, date_fin_reservation FROM reservation");
            return $requete->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Log de l'erreur pour le développeur
            error_log("Erreur lors de la récupération des dates réservées : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère une réservation unique par son ID.
     */
    public function findById(int $id): ?Reservation
    {
        try {
            $sql = "SELECT r.*, 
                DATE_FORMAT(r.date_debut_reservation, '%d/%m/%Y') AS date_debut_fr,
                DATE_FORMAT(r.date_fin_reservation, '%d/%m/%Y') AS date_fin_fr,
                s.Id_salle, s.nom_salle, 
                m.Id_materiel, m.type_materiel, m.numero_materiel,
                u.Id_utilisateur, u.nom_utilisateur, u.prenom_utilisateur,
                p.Id_port, p.identite_port 
                FROM reservation r
                INNER JOIN utilisateur u ON r.Id_utilisateur = u.Id_utilisateur
                INNER JOIN port p ON u.Id_port = p.Id_port
                LEFT JOIN reservation_salle rs ON r.Id_reservation = rs.Id_reservation 
                LEFT JOIN salle s ON rs.Id_salle = s.Id_salle 
                LEFT JOIN reservation_materiel rm ON r.Id_reservation = rm.Id_reservation 
                LEFT JOIN materiel m ON rm.Id_materiel = m.Id_materiel
                WHERE r.Id_reservation = :id";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) return null;

            $results = $this->hydratation($rows);
            return $results[0] ?? null;
            
        } catch (PDOException $e) {
            error_log("Erreur dans ReservationModel::findById : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Transforme les résultats bruts SQL en objets Reservation complets.
     */
    private function hydratation(array $rows): array
    {
        $reservations = [];
        if (empty($rows)) return [];

        foreach ($rows as $row) {
            $idRes = $row['Id_reservation'] ?? null;
            if (!$idRes) continue;

            if (!isset($reservations[$idRes])) {
                // Initialisation des objets (Role, Port, Utilisateur, etc.)
                $role = new Role();
                $role->setIdRol($row['Id_role'] ?? null);
                $role->setLib($row['libelle_role'] ?? null);

                $port = new Port();
                $port->setIdPort($row['Id_port'] ?? null);
                $port->setLibPort($row['identite_port'] ?? $row['id_port'] ?? null);

                $utilisateur = new Utilisateur();
                $utilisateur->setIdUtilisateur($row['Id_utilisateur'] ?? null);
                $utilisateur->setIdentifiant($row['identifiant_utilisateur'] ?? null);
                $utilisateur->setNomUtilisateur($row['nom_utilisateur'] ?? null);
                $utilisateur->setPrenomUtilisateur($row['prenom_utilisateur'] ?? null);
                $utilisateur->setEmailUtilisateur($row['e_mail_utilisateur'] ?? null);
                $utilisateur->setRole($role);
                $utilisateur->setPort($port);
                
                $reservation = new Reservation();
                $reservation->setIdRes($idRes);
                $reservation->setMotif($row['motif_reservation'] ?? null);
                $reservation->setDebut($row['date_debut_fr'] ?? null); 
                $reservation->setFin($row['date_fin_fr'] ?? null);
                $reservation->setCre($row['creneau_reservation'] ?? null);
                $reservation->setUtilisateur($utilisateur);

                $reservations[$idRes] = $reservation;
            }

            // Ajout des salles et matériels à la réservation
            if (!empty($row['Id_salle'])) {
                $sallesDejaAjoutees = array_map(fn($s) => $s->getIdSalle(), $reservations[$idRes]->getSalles());
                if (!in_array($row['Id_salle'], $sallesDejaAjoutees)) {
                    $salle = new Salle();
                    $salle->setIdSalle($row['Id_salle']);
                    $salle->setNomSalle($row['nom_salle']);
                    $reservations[$idRes]->addSalle($salle);
                }
            }

            if (!empty($row['Id_materiel'])) {
                $materielsDejaAjoutes = array_map(fn($m) => $m->getIdMateriel(), $reservations[$idRes]->getMateriels());
                if (!in_array($row['Id_materiel'], $materielsDejaAjoutes)) {
                    $materiel = new Materiel();
                    $materiel->setIdMateriel($row['Id_materiel']);
                    $materiel->setTypMat($row['type_materiel']?? 'Inconnu');
                    $materiel->setNumMat($row['numero_materiel'] ?? null);
                    $reservations[$idRes]->addMateriel($materiel);
                }
            }
        }
        return array_values($reservations);
    }

/**
     * Récupère la liste complète des réservations avec leurs relations (Utilisateurs, Salles, Matériel).
     * @return array Tableau d'objets Reservation hydratés.
     */
    public function voirListeReservations(): array
    {
        try {
            // 1. Définition de la requête SQL principale
            // On utilise des LEFT JOIN car une réservation peut potentiellement n'avoir 
            // ni salle ni matériel associé, et nous voulons quand même récupérer la réservation.
            $queryListe = "SELECT r.*, 
                DATE_FORMAT(r.date_debut_reservation, '%d/%m/%Y') AS date_debut_fr, 
                DATE_FORMAT(r.date_fin_reservation, '%d/%m/%Y') AS date_fin_fr, 
                u.Id_utilisateur, u.prenom_utilisateur, u.nom_utilisateur, u.Id_role, 
                ro.libelle_role,
                u.Id_port, p.identite_port, 
                s.Id_salle, s.nom_salle,
                m.Id_materiel, m.type_materiel, m.numero_materiel
                FROM reservation r 
                INNER JOIN utilisateur u ON r.Id_utilisateur = u.Id_utilisateur
                INNER JOIN role ro ON u.Id_role = ro.Id_role 
                INNER JOIN port p ON u.Id_port = p.Id_port 
                LEFT JOIN reservation_salle rs ON r.Id_reservation = rs.Id_reservation 
                LEFT JOIN salle s ON rs.Id_salle = s.Id_salle 
                LEFT JOIN reservation_materiel rm ON r.Id_reservation = rm.Id_reservation 
                LEFT JOIN materiel m ON rm.Id_materiel = m.Id_materiel 
                ORDER BY r.date_debut_reservation DESC"; // Affichage des plus récentes en premier
                
            // 2. Préparation et exécution
            $stmt = $this->db->prepare($queryListe);
            $stmt->execute();
            
            // 3. Transformation des données
            // On récupère les résultats sous forme de tableau associatif, 
            // puis on passe ce tableau à la méthode d'hydratation pour transformer 
            // les lignes SQL en objets PHP complets (Entités).
            return $this->hydratation($stmt->fetchAll(PDO::FETCH_ASSOC));
            
        } catch (PDOException $e) {
            // 4. Gestion d'erreur
            // On journalise l'erreur dans le log du serveur pour le débogage, 
            // et on retourne un tableau vide pour ne pas casser l'interface utilisateur.
            error_log("Erreur dans ReservationModel::voirListeReservations : " . $e->getMessage());
            return [];
        }
    }

/**
     * Récupère la liste des réservations spécifiques à un utilisateur donné.
     * @param int $id_utilisateur_id L'ID de l'utilisateur concerné.
     * @return array Tableau d'objets Reservation hydratés appartenant à l'utilisateur.
     */
    public function voirListeReservationsParId(int $id_utilisateur_id): array
    {
        try {
            // 1. Définition de la requête SQL filtrée
            // On récupère les mêmes informations que pour la liste globale, 
            // mais avec une clause WHERE pour restreindre les résultats à un seul utilisateur.
            $queryListePerso = "SELECT r.*, 
                DATE_FORMAT(r.date_debut_reservation, '%d/%m/%Y') AS date_debut_fr, 
                DATE_FORMAT(r.date_fin_reservation, '%d/%m/%Y') AS date_fin_fr, 
                u.Id_utilisateur, u.prenom_utilisateur, u.nom_utilisateur, u.Id_role,
                ro.libelle_role,
                u.Id_port, p.identite_port, 
                s.Id_salle, s.nom_salle,
                m.Id_materiel, m.type_materiel, m.numero_materiel
                FROM reservation r 
                INNER JOIN utilisateur u ON r.Id_utilisateur = u.Id_utilisateur
                INNER JOIN role ro ON u.Id_role = ro.Id_role 
                INNER JOIN port p ON u.Id_port = p.Id_port 
                LEFT JOIN reservation_salle rs ON r.Id_reservation = rs.Id_reservation 
                LEFT JOIN salle s ON rs.Id_salle = s.Id_salle 
                LEFT JOIN reservation_materiel rm ON r.Id_reservation = rm.Id_reservation 
                LEFT JOIN materiel m ON rm.Id_materiel = m.Id_materiel 
                WHERE r.Id_utilisateur = :id 
                ORDER BY r.date_debut_reservation DESC";
                
            // 2. Préparation et exécution sécurisée
            // L'utilisation du paramètre nommé (:id) prévient les injections SQL.
            $stmt = $this->db->prepare($queryListePerso);
            $stmt->execute([':id' => $id_utilisateur_id]);
            
            // 3. Hydratation
            // Transformation des lignes SQL en objets PHP via la méthode d'hydratation, 
            // permettant une manipulation cohérente dans le reste de l'application.
            return $this->hydratation($stmt->fetchAll(PDO::FETCH_ASSOC));
            
        } catch (PDOException $e) {
            // 4. Gestion des erreurs
            // Journalisation technique pour le suivi des anomalies sans exposer le détail SQL à l'utilisateur.
            error_log("Erreur dans ReservationModel::voirListeReservationsParId : " . $e->getMessage());
            return [];
        }
    }

/**
     * Récupère les plages de dates occupées pour une ressource spécifique (salle ou matériel).
     * Utilisé généralement par le frontend pour désactiver les dates déjà réservées dans un calendrier.
     * @param string $type Le type de ressource ('salle' ou 'materiel').
     * @param int $id L'identifiant de la ressource.
     * @return array Tableau de plages de dates sous la forme ['from' => 'YYYY-MM-DD', 'to' => 'YYYY-MM-DD'].
     */
    public function voirRessourcesReservees(string $type, int $id): array 
    {
        try {
            // 1. Détermination dynamique de la table et de la colonne
            // Cela permet de mutualiser le code pour les salles et le matériel.
            $table = ($type === 'salle') ? 'reservation_salle' : 'reservation_materiel';
            $col = ($type === 'salle') ? 'Id_salle' : 'Id_materiel';

            // 2. Construction de la requête SQL
            // On récupère uniquement les dates de début et de fin pour limiter la charge réseau.
            $sql = "SELECT r.date_debut_reservation, r.date_fin_reservation 
                    FROM reservation r
                    JOIN $table rel ON r.Id_reservation = rel.Id_reservation
                    WHERE rel.$col = :id";
                    
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            // 3. Transformation en format lisible par le client
            // Le résultat est mis en forme pour correspondre aux standards attendus 
            // par les bibliothèques JS de calendrier (ex: {from: ..., to: ...}).
            $plages = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $plages[] = [
                    'from' => $row['date_debut_reservation'], 
                    'to'   => $row['date_fin_reservation']
                ];
            }
            return $plages;
            
        } catch (PDOException $e) {
            // 4. Gestion des erreurs
            // On enregistre l'erreur technique et on retourne un tableau vide pour 
            // éviter de bloquer l'affichage du calendrier côté utilisateur.
            error_log("Erreur dans ReservationModel::voirRessourcesReservees : " . $e->getMessage());
            return [];
        }
    }

    /**
 * Méthode unique pour créer une réservation, gérer ses relations et journaliser l'action.
 * Utilise une transaction pour garantir l'intégrité des données.
 */
public function creerAvecRelationsEtLog(Reservation $reservation): bool 
{
    try {
        // Début de la transaction : si une opération échoue, on pourra annuler tout le bloc
        $this->db->beginTransaction();

        // 1. Insertion de la réservation
        $sql = "INSERT INTO reservation (motif_reservation, date_debut_reservation, date_fin_reservation, creneau_reservation, id_utilisateur) 
                VALUES (:motif, :debut, :fin, :creneau, :id_util)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':motif'   => $reservation->getMotif(),
            ':debut'   => $reservation->getDebut(),
            ':fin'     => $reservation->getFin(),
            ':creneau' => $reservation->getCre(),
            ':id_util' => $reservation->getUtilisateur()->getIdUtilisateur()
        ]);

        $idReservation = (int)$this->db->lastInsertId();

        // 2. Insertion des Salles (si présentes)
        if (!empty($reservation->getSalles())) {
            $stmtS = $this->db->prepare("INSERT INTO reservation_salle (Id_reservation, Id_salle) VALUES (?, ?)");
            foreach ($reservation->getSalles() as $salle) {
                $stmtS->execute([$idReservation, $salle->getIdSalle()]);
            }
        }

        // 3. Insertion du Matériel (si présent)
        if (!empty($reservation->getMateriels())) {
            $stmtM = $this->db->prepare("INSERT INTO reservation_materiel (Id_reservation, Id_materiel) VALUES (?, ?)");
            foreach ($reservation->getMateriels() as $materiel) {
                $stmtM->execute([$idReservation, $materiel->getIdMateriel()]);
            }
        }

        // 4. Préparation des données pour la journalisation
        $donneesEnregistrees = [
            'utilisateur_id' => $reservation->getUtilisateur()->getIdUtilisateur(),
            'reservation' => [
                'motif' => $reservation->getMotif(),
                'debut' => $reservation->getDebut(),
                'fin'   => $reservation->getFin(),
                'cre'   => $reservation->getCre()
            ],
            'salles' => array_map(fn($s) => [
                'id'  => $s->getIdSalle(), 
                'nom' => property_exists($s, 'nom_salle') ? $s->getNomSalle() : 'Non défini'
            ], $reservation->getSalles()),
            'materiels' => array_map(fn($m) => [
                'id'   => $m->getIdMateriel(), 
                'type' => method_exists($m, 'getTypMat') ? $m->getTypMat() : 'Non défini',
                'numero' => $m->getNumMat()
            ], $reservation->getMateriels())
        ];

        // 5. Appel du log (enregistrement de l'action en base)
        $user = $reservation->getUtilisateur();
        $role = ($user !== null) ? $user->getRole() : null;

        $this->logAction(
        'INSERT', 
        "Création réservation ID: $idReservation par Utilisateur ID: " . ($user ? $user->getIdUtilisateur() : 'Inconnu') . 
        " (Rôle: " . ($role ? $role->getLib() : 'Non défini') . ")", 
        (int)$idReservation, // Conversion explicite en int pour éviter l'erreur TypeError
        null,
        $donneesEnregistrees
    );

        // Validation de toutes les opérations
        $this->db->commit();
        return true;

    } catch (PDOException $e) {
        // En cas d'erreur spécifique à la base de données (ex: contrainte de clé étrangère, serveur indisponible)
        if ($this->db->inTransaction()) {
            $this->db->rollBack(); // Annule tout ce qui a été fait avant l'erreur
        }
        error_log("Erreur PDO lors de la création de la réservation : " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        // En cas d'erreur générale (logique métier, problèmes de typage, etc.)
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        error_log("Erreur générale lors de la création de la réservation : " . $e->getMessage());
        return false;
    }
}

    /**
 * Modifie une réservation existante, synchronise ses relations (Salles/Matériel)
 * et journalise les modifications.
 */
public function modifierAvecRelationsEtLog(Reservation $reservation): bool 
{

/*
    // --- CONTRÔLE D'ACCÈS ---

    // Récupération de l'ID depuis la session
    $idUserSession = $_SESSION['user_id'] ?? null;
    $roleUserSession = $_SESSION['user_role'] ?? null; // Exemple : 'ADMIN'

    // Vérification : L'utilisateur est-il Admin ou le propriétaire ?
    $estAdmin = ($roleUserSession === 'Administrateur');
    $estProprietaire = ($idUserSession === $reservation->getUtilisateur()->getIdUtilisateur());

    if (!$estAdmin && !$estProprietaire) {
        throw new Exception("Accès refusé : Vous n'êtes pas autorisé à modifier cette réservation.");
    }
        */

    try {

        // Début de la transaction pour garantir l'intégrité des données
        $this->db->beginTransaction();

        $idRes = $reservation->getIdRes();
        
        // 1. RÉCUPÉRATION DE L'ÉTAT INITIAL
        // On récupère les données avant modification pour pouvoir les comparer dans le log
        $oldReservation = $this->findById($idRes);
        if (!$oldReservation) {
            throw new Exception("La réservation avec l'ID $idRes est inexistante.");
        }

        $oldData = [
            'utilisateur_id' => $oldReservation->getUtilisateur()->getIdUtilisateur(),
            'reservation' => [
                'motif' => $oldReservation->getMotif(),
                'debut' => $oldReservation->getDebut(),
                'fin'   => $oldReservation->getFin(),
                'cre'   => $oldReservation->getCre()
            ],
            'salles' => array_map(fn($s) => [
                'id'  => $s->getIdSalle(), 
                'nom' => method_exists($s, 'getNomSalle') ? $s->getNomSalle() : 'Non défini'
            ], $oldReservation->getSalles()),
            'materiels' => array_map(fn($m) => [
                'id'     => $m->getIdMateriel(), 
                'type'   => method_exists($m, 'getTypMat') ? $m->getTypMat() : 'Non défini',
                'numero' => $m->getNumMat()
            ], $oldReservation->getMateriels())
        ];

        // 2. MISE À JOUR DE LA RÉSERVATION
        $sql = "UPDATE reservation SET motif_reservation = :motif, date_debut_reservation = :debut, 
                date_fin_reservation = :fin, creneau_reservation = :creneau WHERE Id_reservation = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id'      => $idRes, 
            ':motif'   => $reservation->getMotif(), 
            ':debut'   => $reservation->getDebut(),
            ':fin'     => $reservation->getFin(), 
            ':creneau' => $reservation->getCre()
        ]);

        // 3. SYNCHRONISATION DES RELATIONS (Suppression puis ré-insertion)
        // Salles
        $this->db->prepare("DELETE FROM reservation_salle WHERE Id_reservation = ?")->execute([$idRes]);
        $stmtS = $this->db->prepare("INSERT INTO reservation_salle (Id_reservation, Id_salle) VALUES (?, ?)");
        foreach ($reservation->getSalles() as $salle) {
            $stmtS->execute([$idRes, $salle->getIdSalle()]);
        }

        // Matériel
        $this->db->prepare("DELETE FROM reservation_materiel WHERE Id_reservation = ?")->execute([$idRes]);
        $stmtM = $this->db->prepare("INSERT INTO reservation_materiel (Id_reservation, Id_materiel) VALUES (?, ?)");
        foreach ($reservation->getMateriels() as $materiel) {
            $stmtM->execute([$idRes, $materiel->getIdMateriel()]);
        }

        // 4. Tableau des nouvelles données
        $newData = [
            'utilisateur_id' => $reservation->getUtilisateur()->getIdUtilisateur(),
            'reservation' => [
                'motif' => $reservation->getMotif(),
                'debut' => $reservation->getDebut(),
                'fin'   => $reservation->getFin(),
                'cre'   => $reservation->getCre()
            ],
            'salles' => array_map(fn($s) => [
                'id'  => $s->getIdSalle(), 
                'nom' => method_exists($s, 'getNomSalle') ? $s->getNomSalle() : 'Non défini'
            ], $reservation->getSalles()),
            'materiels' => array_map(fn($m) => [
                'id'     => $m->getIdMateriel(), 
                'type'   => method_exists($m, 'getTypMat') ? $m->getTypMat() : 'Non défini',
                'numero' => $m->getNumMat()
            ], $reservation->getMateriels())
        ];


        // 5. Appel du log (enregistrement de l'action en base)
        $user = $reservation->getUtilisateur();
        $role = ($user !== null) ? $user->getRole() : null;
        $idAuteur = $_SESSION['Id_utilisateur'] ?? 'Inconnu'; // Sécurisation de l'accès à la session

        // Utilisation des crochets pour $_POST
        if (isset($_POST['bouton_modification_admin'])) {
    
        $message = "Modification réservation ID: $idRes par Administrateur ID: $idAuteur. " . 
                    "Créateur initial: " . ($user ? $user->getIdUtilisateur() : 'Inconnu') . 
                    " (Rôle : " . ($role ? $role->getLib() : 'Non défini') . ")";

        $this->logAction('UPDATE', $message, (int)$idRes, $oldData, $newData);

    } else {
    
        $message = "Modification réservation ID: $idRes par Utilisateur ID: " . ($user ? $user->getIdUtilisateur() : 'Inconnu') . 
                " (Rôle: " . ($role ? $role->getLib() : 'Non défini') . ")";

        $this->logAction('UPDATE', $message, (int)$idRes, $oldData, $newData);
    }

        // Validation finale des changements
        $this->db->commit();
        return true;

    } catch (PDOException $e) {
        // Capture spécifique pour les erreurs SQL (clé étrangère, syntaxe, etc.)
        if ($this->db->inTransaction()) $this->db->rollBack();
        error_log("Erreur SQL lors de la modification (ID: {$reservation->getIdRes()}) : " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        // Capture les erreurs logiques (données introuvables, problèmes de méthodes)
        if ($this->db->inTransaction()) $this->db->rollBack();
        error_log("Erreur lors de la modification : " . $e->getMessage());
        return false;
    }
}

 /**
 * Annule une réservation (suppression) après avoir sauvegardé son état pour le log.
 * Utilise une transaction pour assurer la cohérence entre la suppression et le log.
 */
public function annulerAvecRelationsEtLog(int $idRes, Utilisateur $user): bool 
{
    try {
        // Début de la transaction
        $this->db->beginTransaction();
        
        // 1. RÉCUPÉRATION DE L'ÉTAT INITIAL
        // Nécessaire pour journaliser ce qui a été supprimé
        $oldReservation = $this->findById($idRes);
        if (!$oldReservation) {
            throw new Exception("La réservation ID $idRes est inexistante.");
        }

        // Construction du 'oldData' pour la traçabilité
        $oldData = [
            'utilisateur_id' => $oldReservation->getUtilisateur()->getIdUtilisateur(),
            'reservation' => [
                'motif' => $oldReservation->getMotif(),
                'debut' => $oldReservation->getDebut(),
                'fin'   => $oldReservation->getFin(),
                'cre'   => $oldReservation->getCre()
            ],
            'salles' => array_map(fn($s) => [
                'id'  => $s->getIdSalle(), 
                'nom' => method_exists($s, 'getNomSalle') ? $s->getNomSalle() : 'Non défini'
            ], $oldReservation->getSalles()),
            'materiels' => array_map(fn($m) => [
                'id'     => $m->getIdMateriel(), 
                'type'   => method_exists($m, 'getTypMat') ? $m->getTypMat() : 'Non défini',
                'numero' => $m->getNumMat()
            ], $oldReservation->getMateriels())
        ];

        
       // 2. PRÉPARATION DES DONNÉES POUR LE LOG (Avant suppression)
        $userCree = $oldReservation->getUtilisateur();
        $role = ($userCree !== null) ? $userCree->getRole() : null;
        $libelleRole = ($role !== null) ? $role->getLib() : 'Non défini';
        $idAuteur = $_SESSION['Id_utilisateur'] ?? 'Inconnu';
       
        // 3. EXÉCUTION DE LA SUPPRESSION
        // On vérifie les droits : l'admin peut tout supprimer, l'utilisateur seulement ses réservations
        $isAdmin = ($user->getRole() && $user->getRole()->getIdRol() === 1);
        $sql = $isAdmin 
            ? "DELETE FROM reservation WHERE Id_reservation = :id" 
            : "DELETE FROM reservation WHERE Id_reservation = :id AND Id_utilisateur = :userId";
        
        $params = $isAdmin ? [':id' => $idRes] : [':id' => $idRes, ':userId' => $user->getIdUtilisateur()];

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        // 3. VÉRIFICATION DE L'ACTION
        // Si aucune ligne n'est affectée, c'est que l'ID n'existe pas ou que l'utilisateur n'est pas le propriétaire
        if ($stmt->rowCount() === 0) {
            throw new Exception("Aucune réservation supprimée (ID inexistant ou accès refusé).");
        }

        // 4. JOURNALISATION (Suppression)
        // On enregistre l'action avant le commit final
        if (isset($_POST['bouton_suppression_admin'])) {
            $message = "Suppression de la réservation ID: $idRes par Administrateur ID: $idAuteur. " . 
                       "Créateur initial: " . ($userCree ? $userCree->getIdUtilisateur() : 'Inconnu') . 
                       " (Rôle : $libelleRole)";
        } else {
            $message = "Suppression de la réservation ID: $idRes par Utilisateur ID: " . ($userCree ? $userCree->getIdUtilisateur() : 'Inconnu') . 
                       " (Rôle : $libelleRole)";
        }

            $this->logAction('DELETE', $message, (int)$idRes, $oldData, null);

        // Validation des opérations
        $this->db->commit();
        return true;

    } catch (PDOException $e) {
        // En cas d'erreur SQL (ex: contrainte de clé étrangère bloquante)
        if ($this->db->inTransaction()) $this->db->rollBack();
        error_log("Erreur PDO lors de l'annulation de la réservation ID $idRes : " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        // En cas d'erreur de logique métier (réservation non trouvée, accès refusé)
        if ($this->db->inTransaction()) $this->db->rollBack();
        error_log("Erreur lors de l'annulation : " . $e->getMessage());
        return false;
    }
}
    
/**
 * Log une action avec des données optionnelles au format JSON.
 * @param string $action Le type d'action (INSERT, UPDATE, DELETE)
 * @param string $description Description textuelle de l'action
 * @param int $idRes L'ID de la réservation concernée
 * @param array|null $oldData État des données avant modification (optionnel)
 * @param array|null $newData État des données après modification (optionnel)
 */
private function logAction(string $action, string $description, int $idRes, ?array $oldData = null, ?array $newData = null): void 
{
    try {
        // Préparation de la requête d'insertion dans la table de log
        $stmt = $this->db->prepare("
            INSERT INTO reservation_log 
            (action_reservation_log, description_reservation_log, old_data_reservation_log, new_data_reservation_log, Id_reservation) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        // Exécution : conversion des tableaux PHP en chaînes JSON pour le stockage
        $stmt->execute([
            $action, 
            $description, 
            $oldData ? json_encode($oldData) : null, 
            $newData ? json_encode($newData) : null, 
            $idRes
        ]);

    } catch (PDOException $e) {
        // En cas d'échec de la journalisation, on log l'erreur serveur.
        // On ne fait pas de 'rollBack' ici car le log est souvent considéré comme 
        // une action "non-bloquante" pour la transaction principale.
        error_log("Erreur PDO lors de la journalisation (logAction) pour la réservation $idRes : " . $e->getMessage());
        
    } catch (Exception $e) {
        // Capture des erreurs de type JSON ou autres
        error_log("Erreur générale lors de la journalisation : " . $e->getMessage());
    }
}

/**
 * Vérifie si une ressource (salle ou matériel) est disponible sur une période donnée.
 * @param string $resourceType 'salle' ou 'materiel'
 * @param int $resourceId L'ID de la ressource
 * @param string $debut Format JJ/MM/AAAA
 * @param string $fin Format JJ/MM/AAAA
 * @param string $creneau Le créneau à vérifier
 * @return bool True si disponible, False si occupée
 * @throws Exception En cas de format de date invalide ou erreur SQL
 */
public function estDisponible(string $resourceType, int $resourceId, string $debut, string $fin, string $creneau): bool 
{
    try {
        // 1. Conversion des dates de JJ/MM/AAAA vers YYYY-MM-DD pour le SQL
        $dDeb = \DateTime::createFromFormat('d/m/Y', $debut);
        $dFin = \DateTime::createFromFormat('d/m/Y', $fin);

        if (!$dDeb || !$dFin) {
            throw new Exception("Format de date invalide. Attendu : JJ/MM/AAAA.");
        }

        $debutSQL = $dDeb->format('Y-m-d');
        $finSQL = $dFin->format('Y-m-d');

        // 2. Choix dynamique des tables de liaison (sécurisé car contrôlé par le code)
        $joinTable = ($resourceType === 'salle') ? 'reservation_salle' : 'reservation_materiel';
        $joinCol = ($resourceType === 'salle') ? 'Id_salle' : 'Id_materiel';

        // 3. Requête SQL de vérification de chevauchement
        // Logique : La ressource est occupée s'il existe une réservation qui :
        // - Commence avant la fin demandée
        // - Et finit après le début demandé
        // - Et correspond au même créneau (ou à une réservation "Journée complète")
        $sql = "SELECT COUNT(*) 
                FROM reservation r 
                INNER JOIN $joinTable jt ON r.Id_reservation = jt.Id_reservation 
                WHERE jt.$joinCol = :resourceId 
                AND (r.creneau_reservation = 'Journée complète' OR r.creneau_reservation = :creneau)
                AND r.date_debut_reservation < :fin 
                AND r.date_fin_reservation > :debut";

        $stmt = $this->db->prepare($sql);
        
        $stmt->execute([
            ':resourceId' => $resourceId,
            ':creneau'    => $creneau,
            ':debut'      => $debutSQL,
            ':fin'        => $finSQL
        ]);

        // 4. Si COUNT est 0, aucun conflit trouvé -> disponible
        return (int)$stmt->fetchColumn() === 0;

    } catch (PDOException $e) {
        // Log l'erreur technique pour le développeur
        error_log("Erreur PDO dans ReservationModel::estDisponible : " . $e->getMessage());
        // On retourne false par sécurité si une erreur SQL survient
        return false;
    } catch (Exception $e) {
        // Log l'erreur de logique (ex: format de date)
        error_log("Erreur dans ReservationModel::estDisponible : " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les dates occupées par les ressources choisies (salles et/ou matériels).
 * Inclut le nombre de salles et matériels par réservation pour permettre la coloration dynamique.
 * @param array $sallesIds Liste des IDs de salles sélectionnés
 * @param array $materielsIds Liste des IDs de matériels sélectionnés
 * @param int $ignoreId ID de la réservation en cours à exclure (pour la modification)
 * @return array Liste des réservations avec leurs détails
 */
public function voirDatesPourRessources(array $sallesIds, array $materielsIds, int $ignoreId = 0): array 
{
    // Si aucune ressource n'est sélectionnée, il n'y a pas de conflit possible
    if (empty($sallesIds) && empty($materielsIds)) {
        return [];
    }

    try {
        $conditions = [];
        
        // 1. Construction dynamique de la clause WHERE
        // Note : Les IDs étant castés en (int), le risque d'injection est nul même sans paramètres préparés.
        if (!empty($sallesIds)) {
            $sallesList = implode(',', array_map('intval', $sallesIds));
            $conditions[] = "rs.Id_salle IN ($sallesList)";
        }

        if (!empty($materielsIds)) {
            $matList = implode(',', array_map('intval', $materielsIds));
            $conditions[] = "rm.Id_materiel IN ($matList)";
        }

        // On utilise OR car une réservation doit être signalée si l'un de ses éléments est occupé
        $whereClause = implode(' OR ', $conditions);

        /** * 2. Requête SQL optimisée :
         * - On compte les ressources distinctes pour chaque réservation.
         * - Le GROUP BY est essentiel pour éviter les doublons lors des JOIN.
         */
        $sql = "SELECT r.date_debut_reservation, 
                       r.date_fin_reservation, 
                       r.creneau_reservation,
                       COUNT(DISTINCT rs.Id_salle) as nb_salles,
                       COUNT(DISTINCT rm.Id_materiel) as nb_materiels
                FROM reservation r
                LEFT JOIN reservation_salle rs ON r.Id_reservation = rs.Id_reservation
                LEFT JOIN reservation_materiel rm ON r.Id_reservation = rm.Id_reservation
                WHERE ($whereClause)
                AND r.Id_reservation != :ignoreId
                GROUP BY r.Id_reservation";

        // 3. Préparation et exécution
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':ignoreId', $ignoreId, PDO::PARAM_INT);
        $stmt->execute();
        
        // Retourne les données sous forme de tableau associatif
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        // En cas d'erreur de base de données, on log et on retourne un tableau vide
        error_log("Erreur PDO dans ReservationModel::voirDatesPourRessources : " . $e->getMessage());
        return [];
    } catch (Exception $e) {
        // Capture toute autre erreur imprévue
        error_log("Erreur générale dans ReservationModel::voirDatesPourRessources : " . $e->getMessage());
        return [];
    }
}

/**
 * Recherche des réservations dans un intervalle de dates spécifique.
 * Utilise des jointures pour récupérer les détails des salles et du matériel associés.
 * @param string $dateDebut Date au format YYYY-MM-DD
 * @param string $dateFin Date au format YYYY-MM-DD
 * @return array Liste des réservations trouvées, ou tableau vide en cas d'erreur/absence
 */
public function voirReservationsParDate(string $dateDebut, string $dateFin): array 
{
    try {
        // Définition de la requête SQL avec jointures LEFT pour inclure les réservations 
        // même si elles n'ont pas forcément de salle ou de matériel lié.
        $sql = "SELECT 
                    r.*, 
                    s.nom_salle, 
                    s.capacite_salle, 
                    m.type_materiel, 
                    m.numero_materiel
                FROM reservation r
                LEFT JOIN reservation_salle rs ON r.Id_reservation = rs.Id_reservation
                LEFT JOIN salle s ON rs.Id_salle = s.Id_salle
                LEFT JOIN reservation_materiel rm ON r.Id_reservation = rm.Id_reservation
                LEFT JOIN materiel m ON rm.Id_materiel = m.Id_materiel
                WHERE r.date_debut_reservation >= :debut 
                AND r.date_fin_reservation <= :fin
                ORDER BY r.date_debut_reservation ASC";
        
        $stmt = $this->db->prepare($sql);
        
        // Liaison sécurisée des paramètres pour éviter les injections SQL
        $stmt->bindValue(':debut', $dateDebut, PDO::PARAM_STR);
        $stmt->bindValue(':fin', $dateFin, PDO::PARAM_STR);
        
        $stmt->execute();
        
        // Retourne toutes les lignes sous forme de tableau associatif
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        // Capture spécifique des erreurs de base de données (ex: colonnes inexistantes, accès refusé)
        error_log("Erreur PDO dans ReservationModel::voirReservationsParDate : " . $e->getMessage());
        return [];
    } catch (Exception $e) {
        // Capture des erreurs générales (ex: problèmes de connexion ou paramètres)
        error_log("Erreur générale dans ReservationModel::voirReservationsParDate : " . $e->getMessage());
        return [];
    }
}

}

?>