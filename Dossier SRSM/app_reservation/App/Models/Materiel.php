<?php
namespace App\Models;

//----------------------------------------------------------------------------------------------
// Classe Materiel : Représente une ressource matérielle.
// Elle sert de structure pour manipuler les objets matériels extraits de la BDD.
//----------------------------------------------------------------------------------------------

class Materiel {
    // Le typage des propriétés pour garantir que chaque donnée stockée correspond au type attendu.
    // Propriétés privées pour garantir l'encapsulation (accès uniquement via méthodes).
    private ?int $Id_materiel = null; // Identifiant unique : nullable car l'objet n'a pas d'ID avant d'être persisté en base de données.
    private string $type_materiel = ''; // Type du matériel : chaîne de caractères obligatoire, initialisée à vide par défaut.
    private ?int $numero_materiel = null; // Numéro d'inventaire : nullable car un matériel peut ne pas encore avoir de numéro attribué.
    private ?string $photo_materiel = null; // Chemin vers la photo : nullable car le matériel peut ne pas avoir d'image associée.

    private ?Port $port = null; // Relation d'objet : contient une instance de la classe Port, ou null si aucun port n'est associé.

    // Accesseurs (Getters) et Mutateurs (Setters).
    // Permettent d'encapsuler les propriétés pour un contrôle total sur l'accès et la modification.

    // Récupère l'identifiant unique du matériel.
    public function getIdMateriel(): ?int { 
        return $this->Id_materiel; 
    }
    // Définit l'identifiant du matériel.
    public function setIdMateriel(?int $id): void { 
        $this->Id_materiel = $id; 
    }
    // Récupère le type du matériel.
    public function getTypMat(): string { 
        return $this->type_materiel; 
    }
    // Définit le type du matériel.
    public function setTypMat(string $type): void { 
        $this->type_materiel = $type; 
    }
    // Récupère le numéro de série ou d'inventaire du matériel.
    public function getNumMat(): ?int { 
        return $this->numero_materiel; 
    }
    // Définit le numéro de série ou d'inventaire du matériel.
    public function setNumMat(?int $numero): void { 
        $this->numero_materiel = $numero; 
    }
    // Récupère le chemin ou le nom du fichier image associé au matériel.
    public function getPhoMat(): ?string {
        return $this->photo_materiel;
    }
    // Définit le chemin ou le nom du fichier image associé au matériel.
    public function setPhoMat(?string $photo): void {
        $this->photo_materiel = $photo;
    }
    // Getter/Setter pour la relation avec la classe Port (gestion de l'association objet).
    public function getPort(): ?Port {
        return $this->port;
    }
    public function setPort(?Port $port): void {
        $this->port = $port;
    }

}

?>