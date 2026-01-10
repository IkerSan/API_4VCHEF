<?php

namespace App\Entity;

use App\Repository\RecipeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecipeRepository::class)]
#[ORM\Table(name: 'recetas')]
class Recipe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titulo = null;

    #[ORM\Column]
    private ?int $comensales = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $fechaEliminacion = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?RecipeType $tipo = null;

    #[ORM\OneToMany(mappedBy: 'receta', targetEntity: Ingredient::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $ingredientes;

    #[ORM\OneToMany(mappedBy: 'receta', targetEntity: Step::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $pasos;

    #[ORM\OneToMany(mappedBy: 'receta', targetEntity: RecipeNutrient::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $nutrientes;

    #[ORM\OneToMany(mappedBy: 'receta', targetEntity: Rating::class, cascade: ['remove'])]
    private Collection $valoraciones;

    public function __construct()
    {
        $this->ingredientes = new ArrayCollection();
        $this->pasos = new ArrayCollection();
        $this->nutrientes = new ArrayCollection();
        $this->valoraciones = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitulo(): ?string
    {
        return $this->titulo;
    }

    public function setTitulo(string $titulo): static
    {
        $this->titulo = $titulo;
        return $this;
    }

    public function getComensales(): ?int
    {
        return $this->comensales;
    }

    public function setComensales(int $comensales): static
    {
        $this->comensales = $comensales;
        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): static
    {
        $this->descripcion = $descripcion;
        return $this;
    }

    public function getFechaEliminacion(): ?\DateTimeInterface
    {
        return $this->fechaEliminacion;
    }

    public function setFechaEliminacion(?\DateTimeInterface $fechaEliminacion): static
    {
        $this->fechaEliminacion = $fechaEliminacion;
        return $this;
    }

    public function getTipo(): ?RecipeType
    {
        return $this->tipo;
    }

    public function setTipo(?RecipeType $tipo): static
    {
        $this->tipo = $tipo;
        return $this;
    }

    /**
     * @return Collection<int, Ingredient>
     */
    public function getIngredientes(): Collection
    {
        return $this->ingredientes;
    }

    public function addIngrediente(Ingredient $ingrediente): static
    {
        if (!$this->ingredientes->contains($ingrediente)) {
            $this->ingredientes->add($ingrediente);
            $ingrediente->setReceta($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, Step>
     */
    public function getPasos(): Collection
    {
        return $this->pasos;
    }

    public function addPaso(Step $paso): static
    {
        if (!$this->pasos->contains($paso)) {
            $this->pasos->add($paso);
            $paso->setReceta($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, RecipeNutrient>
     */
    public function getNutrientes(): Collection
    {
        return $this->nutrientes;
    }

    public function addNutriente(RecipeNutrient $nutriente): static
    {
        if (!$this->nutrientes->contains($nutriente)) {
            $this->nutrientes->add($nutriente);
            $nutriente->setReceta($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, Rating>
     */
    /**
     * @return Collection<int, Rating>
     */
    public function getValoraciones(): Collection
    {
        return $this->valoraciones;
    }

    public function addValoracion(Rating $valoracion): static
    {
        if (!$this->valoraciones->contains($valoracion)) {
            $this->valoraciones->add($valoracion);
            $valoracion->setReceta($this);
        }
        return $this;
    }

    public function removeValoracion(Rating $valoracion): static
    {
        if ($this->valoraciones->removeElement($valoracion)) {
            // set the owning side to null (unless already changed)
            if ($valoracion->getReceta() === $this) {
                $valoracion->setReceta(null);
            }
        }
        return $this;
    }
}
