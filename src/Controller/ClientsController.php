<?php

namespace App\Controller;

use App\Entity\Clients;
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
       
    /**
     * Cette méthode nous permet de récupérer la liste des clients de BileMo
     * Pagination 5 par pages, et mise en cache des éléments
     *
     * @param Request $request
     * @param ClientsRepository $clientsRepository
     * @param SerializerInterface $serializer
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
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

    /**
     * Cette méthode nous permet de récupérer le detail d'un client via son id
     *
     * @param  mixed $clients
     * @param  mixed $serializer
     * @return JsonResponse
     */
    #[Route('/api/clients/{id}', name: 'detailClient', methods: ['GET'])]  
    public function getDetailClient(Clients $clients, SerializerInterface $serializer): JsonResponse
    {
        $context = SerializationContext::create()->setGroups('getClients');
        $jsonClient = $serializer->serialize($clients, 'json', $context);
        return new JsonResponse($jsonClient, Response::HTTP_OK, [], true);
    }
}
