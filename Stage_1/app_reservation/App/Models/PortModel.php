<?php
namespace App\Models;

use PDO;

class PortModel {
    private PDO $db;

    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    /**
     * Retourne tous les ports sous forme d'objets Port
     * @return Port[]
     */
    public function findAll(): array {
        $stmt = $this->db->query("SELECT * FROM port");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $ports = [];

        foreach ($data as $row) {
            $port = new Port();
            $port->setIdPort($row['Id_port']);
            $port->setLibPort($row['identite_port']);
            $ports[] = $port;
        }
        return $ports;
    }
}