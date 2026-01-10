<?php

namespace App\Entity;

use App\Repository\IngredientRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IngredientRepository::class)]
#[ORM\Table(name: 'ingredientes')]
class Ingredient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    #[ORM\Column]
    private ?float $cantidad = null;

    #[ORM\Column(length: 50)]
    private ?string $unidad = null;

    #[ORM\ManyToOne(inversedBy: 'ingredientes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Recipe $receta = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;
        return $this;
    }

    public function getCantidad(): ?float
    {
        return $this->cantidad;
    }

    public function setCantidad(float $cantidad): static
    {
        $this->cantidad = $cantidad;
        return $this;
    }

    public function getUnidad(): ?string
    {
        return $this->unidad;
    }

    public function setUnidad(string $unidad): static
    {
        $this->unidad = $unidad;
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
