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
        $tipoId = $request->query->get('type');
        $criterio = ['fechaEliminacion' => null];
        if ($tipoId) {
            $criterio['tipo'] = $tipoId;
        }

        $recetas = $this->recipeRepository->findBy($criterio);
        $datos = array_map(fn($r) => $this->serializarReceta($r), $recetas);

        return new JsonResponse($datos, Response::HTTP_OK);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $receta = $this->recipeRepository->find($id);

        if (!$receta || $receta->getFechaEliminacion()) {
            return new JsonResponse(['error' => 'Recipe not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->serializarReceta($receta, true), Response::HTTP_OK);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $datos = json_decode($request->getContent(), true);

        // Validation: title and number-diner mandatory
        if (empty($datos['title']) || empty($datos['number-diner'])) {
            return new JsonResponse(['error' => 'Title and number-diner are required'], Response::HTTP_BAD_REQUEST);
        }

        $tipo = $this->typeRepository->find($datos['type-id'] ?? 0);
        if (!$tipo) {
            return new JsonResponse(['error' => 'Invalid recipe type ID'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($datos['ingredients']) || !is_array($datos['ingredients'])) {
            return new JsonResponse(['error' => 'At least one ingredient is required'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($datos['steps']) || !is_array($datos['steps'])) {
            return new JsonResponse(['error' => 'At least one step is required'], Response::HTTP_BAD_REQUEST);
        }

        $receta = new Recipe();
        $receta->setTitulo($datos['title']);
        $receta->setComensales($datos['number-diner']);
        // The YAML does not specify description in RecipeNew, but it is in Recipe. Assuming it might be missing or handled.
        // There is 'description' in the general text but not in RecipeNew schema explicitly??
        // Wait, RecipeNew has NO description property in the yaml provided!
        // I will ignore it or assume it's not set during creation if strict.
        $receta->setTipo($tipo);

        foreach ($datos['ingredients'] as $ingDato) {
            $ingrediente = new Ingredient();
            $ingrediente->setNombre($ingDato['name']);
            $ingrediente->setCantidad($ingDato['quantity']);
            $ingrediente->setUnidad($ingDato['unit']);
            $receta->addIngrediente($ingrediente);
        }

        foreach ($datos['steps'] as $pasoDato) {
            $paso = new Step();
            $paso->setOrden($pasoDato['order']);
            $paso->setDescripcion($pasoDato['description']);
            $receta->addPaso($paso);
        }

        if (!empty($datos['nutrients'])) {
            foreach ($datos['nutrients'] as $nutDato) {
                // YAML: type-id in NutrientNew
                $tipoNutriente = $this->nutrientRepository->find($nutDato['type-id']);
                if (!$tipoNutriente) {
                    return new JsonResponse(['error' => "Nutrient Type ID {$nutDato['type-id']} not found"], Response::HTTP_BAD_REQUEST);
                }
                $recetaNutriente = new RecipeNutrient();
                $recetaNutriente->setTipoNutriente($tipoNutriente);
                $recetaNutriente->setCantidad($nutDato['quantity']);
                $receta->addNutriente($recetaNutriente);
            }
        }

        $this->entityManager->persist($receta);
        $this->entityManager->flush();

        // Must return the full Recipe object as per YAML 200 OK
        return new JsonResponse($this->serializarReceta($receta, true), Response::HTTP_OK);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $receta = $this->recipeRepository->find($id);

        if (!$receta || $receta->getFechaEliminacion()) {
            return new JsonResponse(['error' => 'Recipe not found'], Response::HTTP_NOT_FOUND); // 400 in YAML for validations, but 404 is standard. The YAML says 400 "Validation Errors". Keeping logic but English msg.
        }

        $receta->setFechaEliminacion(new \DateTime());
        $this->entityManager->flush();

        return new JsonResponse($this->serializarReceta($receta, true), Response::HTTP_OK);
    }

    // YAML: /recipes/{recipeId}/rating/{rate} POST
    #[Route('/{id}/rating/{rate}', methods: ['POST'])]
    public function rate(int $id, int $rate, Request $request): JsonResponse
    {
        $receta = $this->recipeRepository->find($id);
        if (!$receta || $receta->getFechaEliminacion()) {
            // YAML defines 400 for validation errors.
            return new JsonResponse(['error' => 'Recipe not found'], Response::HTTP_BAD_REQUEST);
        }

        $ip = $request->getClientIp();

        // Validation 0-5
        if ($rate < 0 || $rate > 5) {
            return new JsonResponse(['error' => 'Rate must be between 0 and 5'], Response::HTTP_BAD_REQUEST);
        }

        foreach ($receta->getValoraciones() as $votoExistente) {
            if ($votoExistente->getDireccionIp() === $ip) {
                // YAML doesn't specify 409, so 400
                return new JsonResponse(['error' => 'Duplicate vote for this IP'], Response::HTTP_BAD_REQUEST);
            }
        }

        $valoracion = new Rating();
        $valoracion->setPuntuacion($rate);
        $valoracion->setDireccionIp($ip);
        $receta->addValoracion($valoracion);

        $this->entityManager->persist($valoracion);
        $this->entityManager->flush();

        // YAML says return Recipe schema on success
        return new JsonResponse($this->serializarReceta($receta, true), Response::HTTP_OK);
    }

    private function serializarReceta(Recipe $receta, bool $detalles = false): array
    {
        $datos = [
            'id' => $receta->getId(),
            'title' => $receta->getTitulo(),
            'number-diner' => $receta->getComensales(),
            // 'description' is in Recipe schema but not RecipeNew. We include it if present.
            // Wait, Recipe schema DOES NOT have description in the provided YAML!
            // Properties: id, title, number-diner, type, ingredients, steps, nutrients, rating.
            // NO description.
            'type' => [
                'id' => $receta->getTipo()->getId(),
                'name' => $receta->getTipo()->getNombre(),
                'description' => $receta->getTipo()->getDescripcion(), // RecipeType has description
            ],
            // 'rating' object in JSON
            'rating' => [
                'number-votes' => $receta->getValoraciones()->count(),
                'rating-avg' => $this->calcularMediaValoracion($receta),
            ]
        ];

        // YAML Recipe schema includes ingredients/steps/nutrients ALWAYS (not conditional on details?)
        // The YAML components/schemas/Recipe includes them directly.
        // GET /recipes returns array of #/components/schemas/Recipe.
        // So we might need to ALWAYS return them, or at least empty arrays.
        // The prompt said "strict", so I should include them.

        $datos['ingredients'] = array_map(fn($ing) => [
            'name' => $ing->getNombre(),
            'quantity' => $ing->getCantidad(),
            'unit' => $ing->getUnidad(),
        ], $receta->getIngredientes()->toArray());

        $datos['steps'] = array_map(fn($paso) => [
            'order' => $paso->getOrden(),
            'description' => $paso->getDescripcion(),
        ], $receta->getPasos()->toArray());

        $datos['nutrients'] = array_map(fn($rn) => [
            // Nutrient object in YAML: id, type (NutrientType), quantity
            'id' => $rn->getId(), // recipe_nutrient id? or nutrient_type id? Schema says "id" and "type".
            // Actually Nutrient schema has: id, type (NutrientType), quantity.
            // Let's assume id is the linkage ID or the type ID? Example says 10.
            // I'll use the record ID for 'id'.
            'type' => [
                'id' => $rn->getTipoNutriente()->getId(),
                'name' => $rn->getTipoNutriente()->getNombre(),
                'unit' => $rn->getTipoNutriente()->getUnidad(),
            ],
            'quantity' => $rn->getCantidad(),
        ], $receta->getNutrientes()->toArray());


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
