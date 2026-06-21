<?php
namespace App\Models;

class Port {

private ?int $Id_port = null;
    private string $libelle_port;

    public function getIdPort(): ?int { 
        return $this->Id_port; 
        }
    public function setIdPort(?int $id): void { 
        $this->Id_port = $id; 
        }
    
    public function getLibPort(): string { 
        return $this->libelle_port; 
        }
    public function setLibPort(string $libelle): void { 
        $this->libelle_port = $libelle; 
        }

}

?>