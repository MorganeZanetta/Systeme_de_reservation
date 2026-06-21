<?php
namespace App\Models;

use PDO;

class RoleModel {
    private PDO $db;

    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    /**
     * Retourne tous les rôles sous forme d'objets Role
     * @return Role[]
     */
    public function findAll(): array {
        $stmt = $this->db->query("SELECT * FROM role");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $roles = [];

        foreach ($data as $row) {
            $role = new Role();
            $role->setIdRol($row['Id_role']);
            $role->setLib($row['libelle_role']);
            $roles[] = $role;
        }
        return $roles;
    }
}