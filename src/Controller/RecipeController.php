<?php

namespace App\Controller;

use App\Entity\Ingredient;
use App\Entity\Rating;
use App\Entity\Recipe;
use App\Entity\RecipeNutrient;
use App\Entity\Step;
use App\Repository\NutrientTypeRepository;
use App\Repository\RecipeRepository;
use App\Repository\RecipeTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/recipes')]
class RecipeController
{
    public function __construct(
        private RecipeRepository $recipeRepository,
        private RecipeTypeRepository $typeRepository,
        private NutrientTypeRepository $nutrientRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        // Filtrar por tipo si existe, sino obtener todas (excepto borradas)
        $tipoId = $request->query->get('type');
        $criterio = ['fechaEliminacion' => null];
        if ($tipoId) {
            $criterio['tipo'] = $tipoId;
        }

        $recetas = $this->recipeRepository->findBy($criterio);
        // Usar array_map para código más corto
        $datos = array_map(fn($r) => $this->serializarReceta($r), $recetas);

        return new JsonResponse($datos, Response::HTTP_OK);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $receta = $this->recipeRepository->find($id);

        if (!$receta || $receta->getFechaEliminacion()) {
            return new JsonResponse(['error' => 'Receta no encontrada'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->serializarReceta($receta, true), Response::HTTP_OK);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $datos = json_decode($request->getContent(), true);

        // Validaciones simples
        if (empty($datos['title']) || empty($datos['diners'])) {
            return new JsonResponse(['error' => 'Titulo y comensales son obligatorios'], Response::HTTP_BAD_REQUEST);
        }

        $tipo = $this->typeRepository->find($datos['type_id'] ?? 0);
        if (!$tipo) {
            return new JsonResponse(['error' => 'Tipo de receta invalido'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($datos['ingredients']) || !is_array($datos['ingredients'])) {
            return new JsonResponse(['error' => 'Se requiere al menos un ingrediente'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($datos['steps']) || !is_array($datos['steps'])) {
            return new JsonResponse(['error' => 'Se requiere al menos un paso'], Response::HTTP_BAD_REQUEST);
        }

        // Creación de la Entidad
        $receta = new Recipe();
        $receta->setTitulo($datos['title']);
        $receta->setComensales($datos['diners']);
        $receta->setDescripcion($datos['description'] ?? null);
        $receta->setTipo($tipo);

        // Añadir Ingredientes
        foreach ($datos['ingredients'] as $ingDato) {
            $ingrediente = new Ingredient();
            $ingrediente->setNombre($ingDato['name']);
            $ingrediente->setCantidad($ingDato['amount']);
            $ingrediente->setUnidad($ingDato['unit']);
            $receta->addIngrediente($ingrediente);
        }

        // Añadir Pasos
        foreach ($datos['steps'] as $pasoDato) {
            $paso = new Step();
            $paso->setOrden($pasoDato['order']);
            $paso->setDescripcion($pasoDato['description']);
            $receta->addPaso($paso);
        }

        // Añadir Nutrientes (validando que existen)
        if (!empty($datos['nutrients'])) {
            foreach ($datos['nutrients'] as $nutDato) {
                $tipoNutriente = $this->nutrientRepository->find($nutDato['nutrient_type_id']);
                if (!$tipoNutriente) {
                    return new JsonResponse(['error' => "ID tipo nutriente {$nutDato['nutrient_type_id']} no encontrado"], Response::HTTP_BAD_REQUEST);
                }
                $recetaNutriente = new RecipeNutrient();
                $recetaNutriente->setTipoNutriente($tipoNutriente);
                $recetaNutriente->setCantidad($nutDato['amount']);
                $receta->addNutriente($recetaNutriente);
            }
        }

        $this->entityManager->persist($receta);
        $this->entityManager->flush();

        return new JsonResponse(['id' => $receta->getId(), 'mensaje' => 'Receta creada'], Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $receta = $this->recipeRepository->find($id);

        if (!$receta || $receta->getFechaEliminacion()) {
            return new JsonResponse(['error' => 'Receta no encontrada'], Response::HTTP_NOT_FOUND);
        }

        // Borrar
        $receta->setFechaEliminacion(new \DateTime());
        $this->entityManager->flush();

        return new JsonResponse(['mensaje' => 'Receta eliminada'], Response::HTTP_OK);
    }

    #[Route('/{id}/rating', methods: ['POST'])]
    public function rate(int $id, Request $request): JsonResponse
    {
        $receta = $this->recipeRepository->find($id);
        if (!$receta || $receta->getFechaEliminacion()) {
            return new JsonResponse(['error' => 'Receta no encontrada'], Response::HTTP_NOT_FOUND);
        }

        $datos = json_decode($request->getContent(), true);
        $valor = $datos['value'] ?? -1;
        $ip = $request->getClientIp();

        // Validar rango y unicidad de IP
        if (!is_int($valor) || $valor < 0 || $valor > 5) {
            return new JsonResponse(['error' => 'Valoracion debe ser entero entre 0 y 5'], Response::HTTP_BAD_REQUEST);
        }

        foreach ($receta->getValoraciones() as $votoExistente) {
            if ($votoExistente->getDireccionIp() === $ip) {
                return new JsonResponse(['error' => 'Ya has valorado esta receta'], Response::HTTP_CONFLICT);
            }
        }

        $valoracion = new Rating();
        $valoracion->setPuntuacion($valor);
        $valoracion->setDireccionIp($ip);
        $receta->addValoracion($valoracion);

        $this->entityManager->persist($valoracion);
        $this->entityManager->flush();

        return new JsonResponse(['mensaje' => 'Valoracion añadida'], Response::HTTP_CREATED);
    }

    private function serializarReceta(Recipe $receta, bool $detalles = false): array
    {
        $datos = [
            'id' => $receta->getId(),
            'titulo' => $receta->getTitulo(),
            'comensales' => $receta->getComensales(),
            'descripcion' => $receta->getDescripcion(),
            'tipo' => [
                'id' => $receta->getTipo()->getId(),
                'nombre' => $receta->getTipo()->getNombre(),
            ],
            'valoracion_media' => $this->calcularMediaValoracion($receta),
        ];

        if ($detalles) {
            $datos['ingredientes'] = array_map(fn($ing) => [
                'nombre' => $ing->getNombre(),
                'cantidad' => $ing->getCantidad(),
                'unidad' => $ing->getUnidad(),
            ], $receta->getIngredientes()->toArray());

            $datos['pasos'] = array_map(fn($paso) => [
                'orden' => $paso->getOrden(),
                'descripcion' => $paso->getDescripcion(),
            ], $receta->getPasos()->toArray());

            $datos['nutrientes'] = array_map(fn($rn) => [
                'nombre' => $rn->getTipoNutriente()->getNombre(),
                'cantidad' => $rn->getCantidad(),
                'unidad' => $rn->getTipoNutriente()->getUnidad(),
            ], $receta->getNutrientes()->toArray());
        }

        return $datos;
    }

    private function calcularMediaValoracion(Recipe $receta): float
    {
        $valoraciones = $receta->getValoraciones();
        if ($valoraciones->isEmpty()) {
            return 0;
        }

        $suma = array_reduce($valoraciones->toArray(), fn($carry, $v) => $carry + $v->getPuntuacion(), 0);
        return round($suma / count($valoraciones), 1);
    }
}
