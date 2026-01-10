<?php

namespace App\Controller;

use App\Repository\RecipeTypeRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/recipe-types')]
class RecipeTypeController
{
    public function __construct(private RecipeTypeRepository $repository) {}

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        // Obtener todos los tipos y meterlos en un array
        $tipos = $this->repository->findAll();

        $datos = array_map(fn($tipo) => [
            'id' => $tipo->getId(),
            'nombre' => $tipo->getNombre(),
            'descripcion' => $tipo->getDescripcion(),
        ], $tipos);

        return new JsonResponse($datos, Response::HTTP_OK);
    }
}
