<?php

namespace App\Controller;

use App\Entity\Contrat;
use App\Entity\Facturation;
use App\Repository\ContratRepository;
use App\Repository\FacturationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/facturation')]
class FacturationController extends AbstractController
{
    #[Route(name: 'api_facturation_index', methods: ["GET"])]
    public function getAll(FacturationRepository $contratRepository, SerializerInterface $serializer): JsonResponse
    {
        $facturationList = $contratRepository->findAll();

        $facturationJson = $serializer->serialize($facturationList, 'json', ['groups' => "facturation"]);

        return new JsonResponse($facturationJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(path: '/{id}', name: 'api_facturation_show', methods: ["GET"])]
    public function get(Facturation $facturation, SerializerInterface $serializer): JsonResponse
    {
        $facturationJson = $serializer->serialize($facturation, 'json', ['groups' => "facturation"]);

        return new JsonResponse($facturationJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(name: 'api_facturation_new', methods: ["POST"])]
    public function create(Request $request, ContratRepository $contratRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $request->toArray();
        $contrat = $contratRepository->find($data["contrat"]);
        $facturation = $serializer->deserialize($request->getContent(), Facturation::class, 'json', []);
        $facturation->setcontrat($contrat)
            ->setStatus("on")
        ;
        $entityManager->persist($facturation);
        $entityManager->flush();
        $contratJson = $serializer->serialize($facturation, 'json', ['groups' => "facturation"]);
        return new JsonResponse($contratJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(path: '/{id}', name: 'api_facturation_edit', methods: ["PATCH"])]
    public function update(Facturation $facturation, Request $request, UrlGeneratorInterface $urlGenerator, ContratRepository $contratRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $request->toArray();
        if (isset($data{"contrat"})) {
            $contrat = $contratRepository->find($data["contrat"]);
        }

        $updateFacturation = $serializer->deserialize(data: $request->getContent(), type: Facturation::class, format:"json", context: [AbstractNormalizer::OBJECT_TO_POPULATE => $facturation]);
        $updateFacturation->setcontrat($contrat ?? $updateFacturation->getcontrat())->setStatus("on");

        $entityManager->persist(object: $updateFacturation);
        $entityManager->flush();
        $location = $urlGenerator->generate("api_facturation_show", ['id' => $updateFacturation->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $facturationJson = $serializer->serialize(data: $updateFacturation, format: "json", context: ["groups" => "facturation"]);
        return new JsonResponse($facturationJson, JsonResponse::HTTP_NO_CONTENT, ["Location" => $location]);
    }

    #[Route(path: '/{id}', name: 'api_facturation_delete', methods: ["DELETE"])]
    public function delete(Facturation $facturation, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $request->toArray();

        if (isset($data{'force'}) && $data['force'] === true) {
            $entityManager->remove(object: $facturation);
            $entityManager->flush();
        }
        $facturation->setStatus("off");

        $entityManager->persist(object: $facturation);
        $entityManager->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, []);
    }
}
