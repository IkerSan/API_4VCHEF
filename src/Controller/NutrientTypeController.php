<?php

namespace App\Controller;

use App\Repository\NutrientTypeRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/nutrient-types')]
class NutrientTypeController
{
    public function __construct(private NutrientTypeRepository $repository) {}

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        // Obtener todos los nutrientes y meterlos en un array
        $nutrientes = $this->repository->findAll();

        $datos = array_map(fn($nutriente) => [
            'id' => $nutriente->getId(),
            'nombre' => $nutriente->getNombre(),
            'unidad' => $nutriente->getUnidad(),
        ], $nutrientes);

        return new JsonResponse($datos, Response::HTTP_OK);
    }
}
