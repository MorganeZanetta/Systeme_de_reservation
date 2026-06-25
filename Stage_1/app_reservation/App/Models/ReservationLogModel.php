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
}

?>