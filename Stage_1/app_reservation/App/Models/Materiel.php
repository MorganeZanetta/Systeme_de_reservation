<?php
namespace App\Models;

class Materiel {

    private ?int $Id_materiel = null;
    private string $type_materiel = '';
    private ?int $numero_materiel = null;
    private ?string $photo_materiel = null;
    private ?Port $port = null; 

    public function getIdMateriel(): ?int { 
        return $this->Id_materiel; 
    }

    public function setIdMateriel(?int $id): void { 
        $this->Id_materiel = $id; 
    }
    
    public function getTypMat(): string { 
        return $this->type_materiel; 
    }

    public function setTypMat(string $type): void { 
        $this->type_materiel = $type; 
    }
    
    public function getNumMat(): ?int { 
        return $this->numero_materiel; 
    }

    public function setNumMat(?int $numero): void { 
        $this->numero_materiel = $numero; 
    }

    public function getPhoMat(): ?string {
        return $this->photo_materiel;
    }

    public function setPhoMat(?string $photo): void {
        $this->photo_materiel = $photo;
    }
    
    public function getPort(): ?Port {
        return $this->port;
    }
    public function setPort(?Port $port): void {
        $this->port = $port;
    }

}


//----------------------------------------------------------------------------------------------
// 11. REQUETE ADMINISTRATEUR : Trie de la liste des réservations par Id_materiel
//----------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------
// 13. REQUETE ADMINISTRATEUR : Trie du matériel par Id_materiel
//----------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------
// 20. REQUETE ADMINISTRATEUR : Ajout du matériel
//----------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------
// 21. REQUETE ADMINISTRATEUR : Modification du matériel
//----------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------
// 22. REQUETE ADMINISTRATEUR : Suppression du matériel
//----------------------------------------------------------------------------------------------



?>