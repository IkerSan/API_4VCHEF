<?php

namespace App\Entity;

use App\Repository\RecipeNutrientRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecipeNutrientRepository::class)]
#[ORM\Table(name: 'recipe_nutrients')]
class RecipeNutrient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'nutrientes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Recipe $receta = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?NutrientType $tipoNutriente = null;

    #[ORM\Column]
    private ?float $cantidad = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTipoNutriente(): ?NutrientType
    {
        return $this->tipoNutriente;
    }

    public function setTipoNutriente(?NutrientType $tipoNutriente): static
    {
        $this->tipoNutriente = $tipoNutriente;
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
}
