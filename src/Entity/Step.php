<?php

namespace App\Entity;

use App\Repository\StepRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StepRepository::class)]
#[ORM\Table(name: 'steps')]
class Step
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: '`order`')]
    private ?int $orden = null;

    #[ORM\Column(type: 'text')]
    private ?string $descripcion = null;

    #[ORM\ManyToOne(inversedBy: 'pasos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Recipe $receta = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrden(): ?int
    {
        return $this->orden;
    }

    public function setOrden(int $orden): static
    {
        $this->orden = $orden;
        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(string $descripcion): static
    {
        $this->descripcion = $descripcion;
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
