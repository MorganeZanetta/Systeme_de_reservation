<?php

namespace App\Models;

use App\Core\Model;
use App\Models\{Role, Port, Utilisateur, Reservation, Salle, Materiel, ReservationLog};
use PDO;
use PDOException;
use Exception;

//----------------------------------------------------------------------------------------------
// Classe ReservationModel : Gère la logique métier et l'accès aux données des réservations.
//----------------------------------------------------------------------------------------------

class ReservationModel extends Model {

    /**
     * Récupère toutes les dates réservées pour vérification de conflits.
     */
    public function getDatesReservees(): array {
        try {
            $requete = $this->db->query("SELECT date_debut_reservation, date_fin_reservation FROM reservation");
            return $requete->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des dates réservées : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère une réservation unique par son ID avec ses relations (JOINs).
     */
    public function findById(int $id): ?Reservation {
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
     * Méthode pivot : transforme le tableau associatif SQL en objets métier.
     * Gère la déduplication des réservations (à cause des lignes multiples dues aux JOIN).
     */
    private function hydratation(array $rows): array
    {
        $reservations = [];
        if (empty($rows)) return [];

        foreach ($rows as $row) {
            $idRes = $row['Id_reservation'] ?? null;
            if (!$idRes) continue;
            // Si la réservation n'existe pas encore dans notre tableau, on crée l'objet.
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

            // Ajout dynamique des ressources liées (Salles et Matériel).
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
     * Récupère la liste complète des réservations (Trié par date décroissante).
     */
    public function voirListeReservations(): array
    {
        try {
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
                ORDER BY r.date_debut_reservation DESC";
                
            $stmt = $this->db->prepare($queryListe);
            $stmt->execute();
            
            return $this->hydratation($stmt->fetchAll(PDO::FETCH_ASSOC));
            
        } catch (PDOException $e) {

            error_log("Erreur dans ReservationModel::voirListeReservations : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère la liste des réservations spécifiques à un utilisateur donné.
     */
    public function voirListeReservationsParId(int $id_utilisateur_id): array
    {
        try {

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
                

            $stmt = $this->db->prepare($queryListePerso);
            $stmt->execute([':id' => $id_utilisateur_id]);
            
            return $this->hydratation($stmt->fetchAll(PDO::FETCH_ASSOC));
            
        } catch (PDOException $e) {

            error_log("Erreur dans ReservationModel::voirListeReservationsParId : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les plages de dates occupées pour une ressource spécifique (salle ou matériel).
     * Utilisé généralement par le frontend pour désactiver les dates déjà réservées dans un calendrier.
     * @return array Tableau de plages de dates sous la forme ['from' => 'YYYY-MM-DD', 'to' => 'YYYY-MM-DD'].
     */
    public function voirRessourcesReservees(string $type, int $id): array 
    {
        try {

            $table = ($type === 'salle') ? 'reservation_salle' : 'reservation_materiel';
            $col = ($type === 'salle') ? 'Id_salle' : 'Id_materiel';

            $sql = "SELECT r.date_debut_reservation, r.date_fin_reservation 
                    FROM reservation r
                    JOIN $table rel ON r.Id_reservation = rel.Id_reservation
                    WHERE rel.$col = :id";
                    
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $plages = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $plages[] = [
                    'from' => $row['date_debut_reservation'], 
                    'to'   => $row['date_fin_reservation']
                ];
            }
            return $plages;
            
        } catch (PDOException $e) {

            error_log("Erreur dans ReservationModel::voirRessourcesReservees : " . $e->getMessage());
            return [];
        }
    }

    /**
    * Méthode de secours pour s'assurer que le rôle est bien chargé pour le log.
    */
    private function chargerRoleSiAbsent(Utilisateur $user): void {

        if ($user->getRole() !== null && !empty($user->getRole()->getLib())) {
            return;
        }

        $stmt = $this->db->prepare("SELECT u.Id_role, r.libelle_role 
                                    FROM utilisateur u 
                                    INNER JOIN role r ON u.Id_role = r.Id_role 
                                    WHERE u.Id_utilisateur = :id");
        $stmt->execute([':id' => $user->getIdUtilisateur()]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $role = new Role();
            $role->setIdRol((int)$data['Id_role']);
            $role->setLib($data['libelle_role']);
            $user->setRole($role);
        }
    }


        /**
        * Création d'une réservation, de ses liens et journalisation en transaction.
        */
        public function creerAvecRelationsEtLog(Reservation $reservation): bool {
            try {
                $this->db->beginTransaction();

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

            if (!empty($reservation->getSalles())) {
                $stmtS = $this->db->prepare("INSERT INTO reservation_salle (Id_reservation, Id_salle) VALUES (?, ?)");
                foreach ($reservation->getSalles() as $salle) {
                    $stmtS->execute([$idReservation, $salle->getIdSalle()]);
                }
            }

            if (!empty($reservation->getMateriels())) {
                $stmtM = $this->db->prepare("INSERT INTO reservation_materiel (Id_reservation, Id_materiel) VALUES (?, ?)");
                foreach ($reservation->getMateriels() as $materiel) {
                    $stmtM->execute([$idReservation, $materiel->getIdMateriel()]);
                }
            }

            $donneesEnregistrees = [
                'utilisateur_id' => $reservation->getUtilisateur()->getIdUtilisateur(),
                'reservation' => [
                    'motif' => $reservation->getMotif(),
                    'debut' => $reservation->getDebut(),
                    'fin'   => $reservation->getFin(),
                    'creneau'   => $reservation->getCre()
                ],
                'salles' => array_map(fn($s) => ['id' => $s->getIdSalle(), 'nom' => method_exists($s, 'getNomSalle') ? $s->getNomSalle() : 'Non défini'], $reservation->getSalles()),
                'materiel' => array_map(fn($m) => ['id' => $m->getIdMateriel(), 'type' => method_exists($m, 'getTypMat') ? $m->getTypMat() : 'Non défini', 'numero' => $m->getNumMat()], $reservation->getMateriels())
            ];

            $user = $reservation->getUtilisateur();
            if ($user) $this->chargerRoleSiAbsent($user);
            $role = ($user !== null) ? $user->getRole() : null;
            // Journalisation de l'action pour l'audit
            $this->logAction(
                'INSERT', 
                "Création réservation ID: $idReservation par Utilisateur ID: " . ($user ? $user->getIdUtilisateur() : 'Inconnu') . " (Rôle: " . ($role ? $role->getLib() : 'Non défini') . ")", 
                $idReservation, 
                null,
                $donneesEnregistrees
            );

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("Erreur lors de la création : " . $e->getMessage());
            return false;
        }
    }

        /**
        * Modifie une réservation existante : 
        * synchronise les tables de liaison (N-N) et journalise les changements.
        */
        public function modifierAvecRelationsEtLog(Reservation $reservation): bool {
            
            try {
        
        $this->db->beginTransaction();

        $idRes = $reservation->getIdRes();
        
        // Récupération de l'état initial.
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
            'materiel' => array_map(fn($m) => [
                'id'     => $m->getIdMateriel(), 
                'type'   => method_exists($m, 'getTypMat') ? $m->getTypMat() : 'Non défini',
                'numero' => $m->getNumMat()
            ], $oldReservation->getMateriels())
        ];

        // Mise à jour de la réservation.
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

        // Synchronisation des relations.
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

        // Préparation des données de LOG.
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
            'materiel' => array_map(fn($m) => [
                'id'     => $m->getIdMateriel(), 
                'type'   => method_exists($m, 'getTypMat') ? $m->getTypMat() : 'Non défini',
                'numero' => $m->getNumMat()
            ], $reservation->getMateriels())
        ];

        // Journalisation sécurisée du LOG.
        $user = $reservation->getUtilisateur();
        if ($user) $this->chargerRoleSiAbsent($user);
        
        $role = ($user !== null) ? $user->getRole() : null;
        $idAuteur = $_SESSION['Id_utilisateur'] ?? 'Inconnu';

        $message = (isset($_POST['bouton_modification_admin']) ? "Modification par Administrateur ID: $idAuteur. Créateur initial: " : "Modification par Utilisateur ID: ") . 
                   ($user ? $user->getIdUtilisateur() : 'Inconnu') . 
                   " (Rôle : " . ($role ? $role->getLib() : 'Non défini') . ") sur réservation ID: $idRes";

        $this->logAction('UPDATE', $message, (int)$idRes, $oldData, $newData);

        $this->db->commit();
        return true;

    } catch (Exception $e) {
        if ($this->db->inTransaction()) $this->db->rollBack();
        error_log("Erreur lors de la modification de la réservation : " . $e->getMessage());
        return false;
    }
}

        /**
        * Annule une réservation : suppression sécurisée avec traçabilité.
        */
        public function annulerAvecRelationsEtLog(int $idRes, Utilisateur $user): bool 
        {
            try {

                $this->db->beginTransaction();
                
                // Récupération de l'état initial.
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
                        'creneau'   => $oldReservation->getCre()
                    ],
                    'salles' => array_map(fn($s) => [
                        'id'  => $s->getIdSalle(), 
                        'nom' => method_exists($s, 'getNomSalle') ? $s->getNomSalle() : 'Non défini'
                    ], $oldReservation->getSalles()),
                    'materiel' => array_map(fn($m) => [
                        'id'     => $m->getIdMateriel(), 
                        'type'   => method_exists($m, 'getTypMat') ? $m->getTypMat() : 'Non défini',
                        'numero' => $m->getNumMat()
                    ], $oldReservation->getMateriels())
                ];

                
                // Préparation des donneés pour le LOG.
                $userCree = $oldReservation->getUtilisateur();
        
                // Appel de la méthode de secours pour garantir que le rôle est chargé
                if ($userCree) {
                    $this->chargerRoleSiAbsent($userCree);
                }
        
                $role = ($userCree !== null) ? $userCree->getRole() : null;
                $libelleRole = ($role !== null) ? $role->getLib() : 'Non défini';
                $idAuteur = $_SESSION['Id_utilisateur'] ?? 'Inconnu';
            
                // Exécution de la suppression.
                // On vérifie les droits : l'admin peut tout supprimer, l'utilisateur seulement ses réservations.
                $isAdmin = ($user->getRole() && $user->getRole()->getIdRol() === 1);
                $sql = $isAdmin 
                    ? "DELETE FROM reservation WHERE Id_reservation = :id" 
                    : "DELETE FROM reservation WHERE Id_reservation = :id AND Id_utilisateur = :userId";
                
                $params = $isAdmin ? [':id' => $idRes] : [':id' => $idRes, ':userId' => $user->getIdUtilisateur()];

                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);

                // Vérification de l'action.
                // Si aucune ligne n'est affectée, c'est que l'ID n'existe pas ou que l'utilisateur n'est pas le propriétaire.
                if ($stmt->rowCount() === 0) {
                    throw new Exception("Aucune réservation supprimée (ID inexistant ou accès refusé).");
                }

                // Journalisation sécurisée du LOG.
                if (isset($_POST['bouton_suppression_admin'])) {
                    $message = "Suppression de la réservation ID: $idRes par Administrateur ID: $idAuteur. " . 
                            "Créateur initial: " . ($userCree ? $userCree->getIdUtilisateur() : 'Inconnu') . 
                            " (Rôle : $libelleRole)";
                } else {
                    $message = "Suppression de la réservation ID: $idRes par Utilisateur ID: " . ($userCree ? $userCree->getIdUtilisateur() : 'Inconnu') . 
                            " (Rôle : $libelleRole)";
                }
                    // Log de l'annulation
                    $this->logAction('DELETE', $message, (int)$idRes, $oldData, null);

                $this->db->commit();
                return true;

            } catch (PDOException $e) {
  
                if ($this->db->inTransaction()) $this->db->rollBack();
                error_log("Erreur PDO lors de l'annulation de la réservation ID $idRes : " . $e->getMessage());
                return false;
            } catch (Exception $e) {

                if ($this->db->inTransaction()) $this->db->rollBack();
                error_log("Erreur lors de l'annulation : " . $e->getMessage());
                return false;
            }
        }
    
        /**
        * Journalisation de l'action : conversion des tableaux PHP en chaîne JSON.
        */
        private function logAction(string $action, string $description, int $idRes, ?array $oldData = null, ?array $newData = null): void {
        
            try {
        // Préparation de la requête d'insertion dans la table de log.
                $stmt = $this->db->prepare("
            INSERT INTO reservation_log 
            (action_reservation_log, description_reservation_log, old_data_reservation_log, new_data_reservation_log, Id_reservation) 
            VALUES (?, ?, ?, ?, ?)
        ");
            // json_encode transforme les tableaux en format stockable en base de données.
            // Exécution : conversion des tableaux PHP en chaînes JSON pour le stockage.
            $stmt->execute([
                $action, 
                $description, 
                $oldData ? json_encode($oldData) : null, 
                $newData ? json_encode($newData) : null, 
                $idRes
        ]);

        } catch (PDOException $e) {

            error_log("Erreur PDO lors de la journalisation (logAction) pour la réservation $idRes : " . $e->getMessage());
        
        } catch (Exception $e) {

            error_log("Erreur générale lors de la journalisation : " . $e->getMessage());
        }
    }

        /**
        * Vérifie si une ressource (salle ou matériel) est disponible sur une période donnée.
        * Utilise une requête de chevauchement pour détecter les conflits.
        */
        public function estDisponible(string $resourceType, int $resourceId, string $debut, string $fin, string $creneau): bool {
    
        try {
        // Conversion des dates de JJ/MM/AAAA vers YYYY-MM-DD pour le SQL.
        $dDeb = \DateTime::createFromFormat('d/m/Y', $debut);
        $dFin = \DateTime::createFromFormat('d/m/Y', $fin);

        if (!$dDeb || !$dFin) {
            throw new Exception("Format de date invalide. Attendu : JJ/MM/AAAA.");
        }

        $debutSQL = $dDeb->format('Y-m-d');
        $finSQL = $dFin->format('Y-m-d');

        // Choix dynamique des tables de liaison (sécurisé car contrôlé par le code).
        $joinTable = ($resourceType === 'salle') ? 'reservation_salle' : 'reservation_materiel';
        $joinCol = ($resourceType === 'salle') ? 'Id_salle' : 'Id_materiel';

        // Requête SQL de vérification de chevauchement.
        // Logique : La ressource est occupée s'il existe une réservation qui :
        // - Commence avant la fin demandée.
        // - Et finit après le début demandé.
        // - Et correspond au même créneau (ou à une réservation "Journée complète").
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

        // Si COUNT est 0, aucun conflit trouvé -> disponible.
        // Disponible si aucun enregistrement trouvé.
        return (int)$stmt->fetchColumn() === 0;

    } catch (PDOException $e) {

        error_log("Erreur PDO dans ReservationModel::estDisponible : " . $e->getMessage());
        // On retourne false par sécurité si une erreur SQL survient.
        return false;
    } catch (Exception $e) {
        // Log l'erreur de logique (ex: format de date).
        error_log("Erreur dans ReservationModel::estDisponible : " . $e->getMessage());
        return false;
    }
}

        /**
        * Récupère les dates occupées par les ressources choisies.
        * Utile pour le frontend (ex: calendrier qui affiche les dates grisées).
        */
        public function voirDatesPourRessources(array $sallesIds, array $materielsIds, int $ignoreId = 0): array {
        // Si aucune ressource n'est sélectionnée, il n'y a pas de conflit possible.
        if (empty($sallesIds) && empty($materielsIds)) {
        return [];
    }

        try {
            $conditions = [];
        // Injection sécurisée des IDs via cast en int.
        // Construction dynamique de la clause WHERE
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

        // Requête SQL optimisée :
        // On compte les ressources distinctes pour chaque réservation.
        // GROUP BY permet de regrouper les résultats par réservation.
        // tout en comptant le nombre de ressources associées à chaque créneau.
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

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':ignoreId', $ignoreId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {

        error_log("Erreur PDO dans ReservationModel::voirDatesPourRessources : " . $e->getMessage());
        return [];
    } catch (Exception $e) {

        error_log("Erreur générale dans ReservationModel::voirDatesPourRessources : " . $e->getMessage());
        return [];
    }
}

        /**
        * Recherche globale sur une période (utilisé pour les vues d'administration/planning).
        */
        public function voirReservationsParDate(string $dateDebut, string $dateFin): array {
        
            try {
            // Jointures multiples pour récupérer les détails des ressources (salles/matériels)
                $sql = "SELECT r.*, s.nom_salle, s.capacite_salle, m.type_materiel, m.numero_materiel
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

    } catch (PDOException $e) {

        error_log("Erreur PDO dans ReservationModel::voirReservationsParDate : " . $e->getMessage());
        return [];
    } catch (Exception $e) {

        error_log("Erreur générale dans ReservationModel::voirReservationsParDate : " . $e->getMessage());
        return [];
    }
}

}

?>