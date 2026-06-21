<?php

namespace App\Models;

use App\Core\Model;
use App\Models\{Role, Port, Utilisateur, Reservation, Salle, Materiel, ReservationLog};
use PDO;
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
        $requete = $this->db->query("SELECT date_debut_reservation, date_fin_reservation FROM reservation");
        return $requete->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère une réservation unique par son ID avec ses dépendances (Salles/Matériel).
     */
public function findById(int $id): ?Reservation
{
    $sql = "SELECT r.*, 
        DATE_FORMAT(r.date_debut_reservation, '%d/%m/%Y') AS date_debut_fr,
        DATE_FORMAT(r.date_fin_reservation, '%d/%m/%Y') AS date_fin_fr,
        s.Id_salle, s.nom_salle, 
        m.Id_materiel, m.type_materiel, m.numero_materiel, -- Ajout de numero_materiel ici
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

    public function voirListeReservations(): array
    {
        $queryListe = "SELECT r.*, 
            DATE_FORMAT(r.date_debut_reservation, '%d/%m/%Y') AS date_debut_fr, 
            DATE_FORMAT(r.date_fin_reservation, '%d/%m/%Y') AS date_fin_fr, 
            u.Id_utilisateur, u.prenom_utilisateur, u.nom_utilisateur, u.Id_role, 
            u.Id_port, p.identite_port, 
            s.Id_salle, s.nom_salle,
            m.Id_materiel, m.type_materiel, m.numero_materiel
            FROM reservation r 
            INNER JOIN utilisateur u ON r.Id_utilisateur = u.Id_utilisateur 
            INNER JOIN port p ON u.Id_port = p.Id_port 
            LEFT JOIN reservation_salle rs ON r.Id_reservation = rs.Id_reservation 
            LEFT JOIN salle s ON rs.Id_salle = s.Id_salle 
            LEFT JOIN reservation_materiel rm ON r.Id_reservation = rm.Id_reservation 
            LEFT JOIN materiel m ON rm.Id_materiel = m.Id_materiel 
            ORDER BY r.date_debut_reservation DESC";
            
        $stmt = $this->db->prepare($queryListe);
        $stmt->execute();
        return $this->hydratation($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function voirListeReservationsParId(int $id_utilisateur_id): array
    {
        $queryListePerso = "SELECT r.*, 
            DATE_FORMAT(r.date_debut_reservation, '%d/%m/%Y') AS date_debut_fr, 
            DATE_FORMAT(r.date_fin_reservation, '%d/%m/%Y') AS date_fin_fr, 
            u.Id_utilisateur, u.prenom_utilisateur, u.nom_utilisateur, u.Id_role, 
            u.Id_port, p.identite_port, 
            s.Id_salle, s.nom_salle,
            m.Id_materiel, m.type_materiel, m.numero_materiel
            FROM reservation r 
            INNER JOIN utilisateur u ON r.Id_utilisateur = u.Id_utilisateur 
            INNER JOIN port p ON u.Id_port = p.Id_port 
            LEFT JOIN reservation_salle rs ON r.Id_reservation = rs.Id_reservation 
            LEFT JOIN salle s ON rs.Id_salle = s.Id_salle 
            LEFT JOIN reservation_materiel rm ON r.Id_reservation = rm.Id_reservation 
            LEFT JOIN materiel m ON rm.Id_materiel = m.Id_materiel 
            WHERE r.Id_utilisateur = :id 
            ORDER BY r.date_debut_reservation DESC";
            
        $stmt = $this->db->prepare($queryListePerso);
        $stmt->execute([':id' => $id_utilisateur_id]);
        return $this->hydratation($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

public function voirRessourcesReservees(string $type, int $id): array {
    $table = ($type === 'salle') ? 'reservation_salle' : 'reservation_materiel';
    $col = ($type === 'salle') ? 'Id_salle' : 'Id_materiel';

    // On récupère les plages de dates pour la ressource demandée
    $sql = "SELECT r.date_debut_reservation, r.date_fin_reservation 
            FROM reservation r
            JOIN $table rel ON r.Id_reservation = rel.Id_reservation
            WHERE rel.$col = :id";
            
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':id' => $id]);
    
    // Flatpickr accepte un format spécifique pour désactiver des plages : {from: 'YYYY-MM-DD', to: 'YYYY-MM-DD'}
    $plages = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $plages[] = ['from' => $row['date_debut_reservation'], 'to' => $row['date_fin_reservation']];
    }
    return $plages;
}

    /**
     * Méthode unique pour créer une réservation, gérer ses relations et journaliser l'action.
     */
public function creerAvecRelationsEtLog(Reservation $reservation): bool {
    
try {

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

        // 2. Insertion des Salles
        if (!empty($reservation->getSalles())) {
            $stmtS = $this->db->prepare("INSERT INTO reservation_salle (Id_reservation, Id_salle) VALUES (?, ?)");
            foreach ($reservation->getSalles() as $salle) {
                $stmtS->execute([$idReservation, $salle->getIdSalle()]);
            }
        }

        // 3. Insertion du Matériel
        if (!empty($reservation->getMateriels())) {
            $stmtM = $this->db->prepare("INSERT INTO reservation_materiel (Id_reservation, Id_materiel) VALUES (?, ?)");
            foreach ($reservation->getMateriels() as $materiel) {
                $stmtM->execute([$idReservation, $materiel->getIdMateriel()]);
            }
        }

        // 4. Préparation sécurisée des données pour le log
        // Note : On utilise '??' (null coalescing) pour éviter l'erreur d'initialisation
        $donneesEnregistrees = [
            'utilisateur_id' => $reservation->getUtilisateur()->getIdUtilisateur(), // Ajout de l'ID utilisateur
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

        // 5. Appel du log (3ème paramètre : ID, 4ème : null, 5ème : données)
$this->logAction(
    'INSERT', 
    "Création réservation ID: $idReservation par Utilisateur ID: " . $reservation->getUtilisateur()->getIdUtilisateur(), 
    $idReservation, 
    null,
    $donneesEnregistrees
);
        $this->db->commit();
        return true;
    } catch (Exception $e) {
        if ($this->db->inTransaction()) $this->db->rollBack();
        error_log("Erreur lors de la réservation : " . $e->getMessage());
        return false;
    }
}

public function modifierAvecRelationsEtLog(Reservation $reservation): bool {
    try {
        $this->db->beginTransaction();

        $idRes = $reservation->getIdRes();
        
        // 1. RÉCUPÉRATION DE L'ÉTAT INITIAL
        $oldReservation = $this->findById($idRes);
        if (!$oldReservation) throw new Exception("La réservation est inexistante.");

        // Construction du 'oldData' avec votre structure détaillée
        $oldData = [
             'utilisateur_id' => $reservation->getUtilisateur()->getIdUtilisateur(), // Ajout de l'ID utilisateur
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
        $this->db->prepare($sql)->execute([
            ':id'      => $idRes, 
            ':motif'   => $reservation->getMotif(), 
            ':debut'   => $reservation->getDebut(),
            ':fin'     => $reservation->getFin(), 
            ':creneau' => $reservation->getCre()
        ]);

        // 3. SYNCHRONISATION DES RELATIONS
        $this->db->prepare("DELETE FROM reservation_salle WHERE Id_reservation = ?")->execute([$idRes]);
        $stmtS = $this->db->prepare("INSERT INTO reservation_salle (Id_reservation, Id_salle) VALUES (?, ?)");
        foreach ($reservation->getSalles() as $salle) {
            $stmtS->execute([$idRes, $salle->getIdSalle()]);
        }

        $this->db->prepare("DELETE FROM reservation_materiel WHERE Id_reservation = ?")->execute([$idRes]);
        $stmtM = $this->db->prepare("INSERT INTO reservation_materiel (Id_reservation, Id_materiel) VALUES (?, ?)");
        foreach ($reservation->getMateriels() as $materiel) {
            $stmtM->execute([$idRes, $materiel->getIdMateriel()]);
        }

        // 4. CONSTRUCTION DU 'newData' (structure identique)
        $newData = [
             'utilisateur_id' => $reservation->getUtilisateur()->getIdUtilisateur(), // Ajout de l'ID utilisateur
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

// 5. JOURNALISATION (Correction ici)
        $idUtilisateur = $reservation->getUtilisateur()->getIdUtilisateur();
        $idReservation = (int)$this->db->lastInsertId();
        
// 5. JOURNALISATION (Correction)
$this->logAction(
    'UPDATE', 
    "Modification réservation ID: $idRes par Utilisateur ID: " . $reservation->getUtilisateur()->getIdUtilisateur(),
    $idRes, 
    $oldData, // 4ème paramètre : le tableau des anciennes données
    $newData  // 5ème paramètre : le tableau des nouvelles données
);

        $this->db->commit();
        return true;

    } catch (Exception $e) {
        if ($this->db->inTransaction()) $this->db->rollBack();
        error_log("Erreur lors de la modification : " . $e->getMessage());
        return false;
    }
}

    public function annulerAvecRelationsEtLog(int $id, Utilisateur $user): bool {
 
        try {

        $this->db->beginTransaction();
        
        // 1. RÉCUPÉRATION DE L'ÉTAT INITIAL
        $oldReservation = $this->findById($id);
        if (!$oldReservation) throw new Exception("La réservation est inexistante.");

        // Construction du 'oldData' avec votre structure détaillée
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

// 2. PRÉPARATION ET EXÉCUTION
        $isAdmin = ($user->getRole() && $user->getRole()->getIdRol() === 1);
        $sql = $isAdmin 
            ? "DELETE FROM reservation WHERE Id_reservation = :id" 
            : "DELETE FROM reservation WHERE Id_reservation = :id AND Id_utilisateur = :userId";
        
        $params = $isAdmin ? [':id' => $id] : [':id' => $id, ':userId' => $user->getIdUtilisateur()];

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        // 3. VÉRIFICATION
        if ($stmt->rowCount() === 0) {
            throw new Exception("Aucune réservation supprimée (ID inexistant ou accès refusé).");
        }

  // 4. JOURNALISATION
    $idUtilisateur = $user->getIdUtilisateur();

            $this->logAction(
            'DELETE', 
            "Suppression de la réservation ID: $id par Utilisateur ID: $idUtilisateur", 
            $id, 
            $oldData, // 4ème paramètre : le tableau des données de la réservation supprimée
            null      // 5ème paramètre : null (car aucune "nouvelle donnée" n'existe après une suppression)
);

            $this->db->commit();
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
/**
 * Log une action avec des données optionnelles au format JSON
 */
private function logAction(string $action, string $description, int $idRes, ?array $oldData = null, ?array $newData = null): void {
    $stmt = $this->db->prepare("
        INSERT INTO reservation_log 
        (action_reservation_log, description_reservation_log, old_data_reservation_log, new_data_reservation_log, Id_reservation) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    // On convertit les tableaux PHP en format JSON, ou on met NULL si aucune donnée
    $stmt->execute([
        $action, 
        $description, 
        $oldData ? json_encode($oldData) : null, 
        $newData ? json_encode($newData) : null, 
        $idRes
    ]);
}

/**
 * Recherche des réservations dans un intervalle de dates
 * Utilisation de requêtes préparées pour la sécurité
 */

/**
 * Vérifie si une ressource (salle ou matériel) est disponible.
 * @param string $resourceType 'salle' ou 'materiel'
 * @param int $resourceId L'ID de la ressource
 * @param string $debut Format JJ/MM/AAAA
 * @param string $fin Format JJ/MM/AAAA
 * @param string $creneau Le créneau à vérifier
 * @return bool True si disponible, False si occupée
 */
public function estDisponible(string $resourceType, int $resourceId, string $debut, string $fin, string $creneau): bool {
    
    // 1. Conversion des dates de JJ/MM/AAAA vers YYYY-MM-DD pour le SQL
    $dDeb = \DateTime::createFromFormat('d/m/Y', $debut);
    $dFin = \DateTime::createFromFormat('d/m/Y', $fin);

    if (!$dDeb || !$dFin) {
        throw new Exception("Format de date invalide. Utilisez JJ/MM/AAAA.");
    }

    $debutSQL = $dDeb->format('Y-m-d');
    $finSQL = $dFin->format('Y-m-d');

    // 2. Choix dynamique des tables de liaison
    $joinTable = ($resourceType === 'salle') ? 'reservation_salle' : 'reservation_materiel';
    $joinCol = ($resourceType === 'salle') ? 'Id_salle' : 'Id_materiel';

    // 3. Requête SQL de vérification de chevauchement
    $sql = "SELECT COUNT(*) 
            FROM reservation r 
            INNER JOIN $joinTable jt ON r.Id_reservation = jt.Id_reservation 
            WHERE jt.$joinCol = :resourceId 
            AND (r.creneau_reservation = 'Journée complète' OR r.creneau_reservation = :creneau)
            AND r.date_debut_reservation < :fin 
            AND r.date_fin_reservation > :debut";

    $stmt = $this->db->prepare($sql);
    
    $stmt->execute([
        'resourceId' => $resourceId,
        'creneau'    => $creneau,
        'debut'      => $debutSQL,
        'fin'        => $finSQL
    ]);

    // 4. Si COUNT est égal à 0, la ressource est disponible
    return $stmt->fetchColumn() == 0;
}

// --- Partie Interface : Récupère les dates occupées par les ressources choisies ---
/**
 * Récupère les dates occupées par les ressources choisies (salles et/ou matériels)
 * Inclut le nombre de salles et matériels par réservation pour permettre la coloration dynamique.
 * @param array $sallesIds Liste des IDs de salles sélectionnés
 * @param array $materielsIds Liste des IDs de matériels sélectionnés
 * @param int $ignoreId ID de la réservation en cours à exclure
 * @return array Liste des réservations avec leurs détails de ressources
 */
public function voirDatesPourRessources(array $sallesIds, array $materielsIds, int $ignoreId = 0): array {
    // Si aucune ressource n'est sélectionnée, on retourne un tableau vide
    if (empty($sallesIds) && empty($materielsIds)) return [];

    $conditions = [];
    
    // 1. Construction dynamique de la clause WHERE
    if (!empty($sallesIds)) {
        $sallesList = implode(',', array_map('intval', $sallesIds));
        $conditions[] = "rs.Id_salle IN ($sallesList)";
    }

    if (!empty($materielsIds)) {
        $matList = implode(',', array_map('intval', $materielsIds));
        $conditions[] = "rm.Id_materiel IN ($matList)";
    }

    // On utilise OR car une réservation peut être bloquée si la salle OU le matériel est pris
    $whereClause = implode(' OR ', $conditions);

    /** * 2. Requête SQL optimisée :
     * - On utilise COUNT(DISTINCT ...) pour savoir combien de salles/matériels sont dans la résa.
     * - GROUP BY r.Id_reservation est nécessaire pour combiner les compteurs avec les infos de la résa.
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
    
    // Retourne un tableau associatif directement exploitable par json_encode()
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Recherche des réservations dans un intervalle de dates avec jointures
 */
public function voirReservationsParDate(string $dateDebut, string $dateFin): array {
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
    
    $stmt->bindValue(':debut', $dateDebut, PDO::PARAM_STR);
    $stmt->bindValue(':fin', $dateFin, PDO::PARAM_STR);
    
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

}

?>