<?php

namespace App\Controller;

use App\Repository\ClientsRepository;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ClientsController extends AbstractController
{
    #[Route('/api/clients', name: 'clients', methods: ['GET'])]
    public function getAllClients(
        Request $request,
        ClientsRepository $clientsRepository,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cache
    ): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 5);

        $idCache = "getAllClients-" . $page . "-" . $limit;

        $jsonClientsList = $cache->get($idCache, function (ItemInterface $item) use ($clientsRepository, $page, $limit, $serializer) {
            echo("L\'ÉLÉMENT N\'NEST PAS ENCORE EN CACHE ! \n");
            $context = SerializationContext::create()->setGroups('getClients');
            $item->tag("clientsCache");
            $clientsList = $clientsRepository->findAllPaginated($page, $limit);
            return $serializer->serialize($clientsList, 'json', $context);
        });

        return new JsonResponse($jsonClientsList, Response::HTTP_OK, [], true);
    }
}
