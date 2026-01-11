<?php

namespace App\Controller;

use App\Repository\NutrientTypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/nutrient-types')]
class NutrientTypeController extends AbstractController
{
    public function __construct(private NutrientTypeRepository $repository) {}

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        // Obtener todos los nutrientes y meterlos en un array
        $nutrientes = $this->repository->findAll();

        $datos = array_map(fn($nutriente) => [
            'id' => $nutriente->getId(),
            'name' => $nutriente->getNombre(),
            'unit' => $nutriente->getUnidad(),
        ], $nutrientes);

        return new JsonResponse($datos, Response::HTTP_OK);
    }
}
