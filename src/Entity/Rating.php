<?php

namespace App\Entity;

use App\Repository\RatingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RatingRepository::class)]
#[ORM\Table(name: 'valoraciones')]
#[ORM\UniqueConstraint(name: 'unique_recipe_ip', columns: ['receta_id', 'direccion_ip'])]
class Rating
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $puntuacion = null;

    #[ORM\Column(length: 45)]
    private ?string $direccionIp = null;

    #[ORM\ManyToOne(inversedBy: 'valoraciones')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Recipe $receta = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPuntuacion(): ?int
    {
        return $this->puntuacion;
    }

    public function setPuntuacion(int $puntuacion): static
    {
        if ($puntuacion < 0 || $puntuacion > 5) {
            throw new \InvalidArgumentException('Las valoraciones deben estar entre 0 y 5');
        }
        $this->puntuacion = $puntuacion;
        return $this;
    }

    public function getDireccionIp(): ?string
    {
        return $this->direccionIp;
    }

    public function setDireccionIp(string $direccionIp): static
    {
        $this->direccionIp = $direccionIp;
        return $this;
    }

    public function getReceta(): ?Recipe
    {
        return $this->receta;
    }

    public function setReceta(?Recipe $receta): static
    {
        $this->receta = $receta;
        return $this;
    }
}
