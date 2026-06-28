<?php
namespace App\Models;

use App\Core\Model; 
use PDO; 
use PDOException;
use Exception;

class ReservationLogModel extends Model {
    protected PDO $db;

    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }    

    /**
     * Récupère les logs pour une réservation spécifique
     * @return array
     * @throws Exception
     */
    public function getLogsByReservation(int $idReservation): array {
        try {
            $sql = "SELECT * FROM reservation_log 
                    WHERE Id_reservation = :id 
                    ORDER BY timestamp_reservation_log DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $idReservation]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur dans ReservationLogModel::getLogsByReservation : " . $e->getMessage());
            throw new Exception("Impossible de récupérer l'historique de cette réservation.");
        }
    }

    /**
     * Récupère l'historique complet des logs avec les informations utilisateurs associées
     * @return ReservationLog[]
     * @throws Exception
     */
    public function getAllLogsWithUsers(): array {
        try {
            $sql = "SELECT rl.*, r.Id_utilisateur
                    FROM reservation_log rl
                    LEFT JOIN reservation r ON rl.Id_reservation = r.Id_reservation
                    LEFT JOIN utilisateur u ON r.Id_utilisateur = u.Id_utilisateur
                    ORDER BY rl.timestamp_reservation_log DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $logs = [];
            foreach ($rows as $row) {
                $log = new \App\Models\ReservationLog();
                
                // Hydratation de l'objet avec vérification des clés
                $log->setIdReservationLog((int)($row['Id_reservation_log'] ?? 0));
                $log->setIdUtilisateur(isset($row['Id_utilisateur']) ? (int)$row['Id_utilisateur'] : null);
                $log->setActionReservationLog($row['action_reservation_log'] ?? '');
                $log->setDescriptionReservationLog($row['description_reservation_log'] ?? '');
                $log->setOldDataReservationLog($row['old_data_reservation_log'] ?? null);
                $log->setNewDataReservationLog($row['new_data_reservation_log'] ?? null);
                $log->setTimestampReservationLog($row['timestamp_reservation_log'] ?? '');
                
                $logs[] = $log;
            }
            
            return $logs;

        } catch (PDOException $e) {
            error_log("Erreur dans ReservationLogModel::getAllLogsWithUsers : " . $e->getMessage());
            throw new Exception("Impossible de charger la liste complète des logs.");
        }
    }

    /**
    * Recherche les logs selon des critères filtrés et des colonnes JSON ciblées.
    * @param array $criteres Liste des filtres (id_utilisateur, salle, materiel)
    * @param array $options  Options d'interface (colonnes JSON à fouiller, recherche rapide)
    * @return ReservationLog[]
    * @throws Exception
    */
    public function chercherParCriteres(array $criteres, array $options) {
        try {
            // Initialisation de la requête de base
            $sql = "SELECT * FROM reservation_log WHERE 1=1";
            $params = [];

            // 1. Détermination des colonnes JSON ciblées (côte à côte)
            // Par défaut, si aucune case n'est cochée, on fouille partout.
            $cibles = [];
            if (!empty($options['recherche_new_data'])) $cibles[] = 'new_data_reservation_log';
            if (!empty($options['recherche_old_data'])) $cibles[] = 'old_data_reservation_log';
        
            if (empty($cibles)) {
            $cibles = ['new_data_reservation_log', 'old_data_reservation_log'];
            }

            // 2. Filtre sur l'utilisateur (colonne JSON racine)
            if (!empty($criteres['id_utilisateur'])) {
                $sql .= " AND new_data_reservation_log->>'$.utilisateur_id' = :id_utilisateur";
                $params[':id_utilisateur'] = (int)$criteres['id_utilisateur'];
            }

            // 3. Filtres sur Salles et Matériel
        $conditions = [];
        $i = 0;

        foreach ($cibles as $col) {
        $i++;
        $sub = [];

            if (!empty($criteres['salle'])) {
                $paramName = ":salle_" . $i;
                // On extrait tous les IDs des objets contenus dans le tableau "salles"
                // Le chemin '$.salles[*].id' pointe directement sur la liste des IDs
                $sub[] = "JSON_CONTAINS(JSON_EXTRACT($col, '$.salles[*].id'), CAST($paramName AS JSON))";
                $params[$paramName] = (int)$criteres['salle'];
        }

            if (!empty($criteres['materiel'])) {
                $paramName = ":materiel_" . $i;
                $sub[] = "JSON_CONTAINS(JSON_EXTRACT($col, '$.materiels[*].id'), CAST($paramName AS JSON))";
                $params[$paramName] = (int)$criteres['materiel'];
        }
            
            if (!empty($sub)) {
                $conditions[] = "(" . implode(" OR ", $sub) . ")";
        }

    }
        
            // Ajout des conditions au SQL principal
            if (!empty($conditions)) {
                $sql .= " AND (" . implode(" OR ", $conditions) . ")";
        }

        // 5. NOUVEAU : Filtre sur les dates (extraction depuis le JSON 'reservation' -> 'debut'/'fin')
        if (!empty($criteres['date_debut'])) {
            $sql .= " AND new_data_reservation_log->>'$.reservation.debut' >= :date_debut";
            $params[':date_debut'] = $criteres['date_debut'];
        }
        
        if (!empty($criteres['date_fin'])) {
            $sql .= " AND new_data_reservation_log->>'$.reservation.fin' <= :date_fin";
            $params[':date_fin'] = $criteres['date_fin'];
        }

        // 5. Exécution et hydratation automatique via votre classe
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        // PDO crée directement des objets de type ReservationLog
        return $stmt->fetchAll(PDO::FETCH_CLASS, '\App\Models\ReservationLog');

    } catch (PDOException $e) {
        // Log l'erreur réelle pour le débogage
        error_log("Erreur SQL dans ReservationLogModel : " . $e->getMessage());
        throw new Exception("Impossible de charger les logs : " . $e->getMessage());
        }
    }
}

?>