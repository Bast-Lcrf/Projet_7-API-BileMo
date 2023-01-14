<?php

namespace App\Controller;

use App\Entity\Clients;
use App\Repository\ClientsRepository;
use JMS\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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

    /**
     * Cette méthode nous permet de supprimer un client via son id
     * 
     * @param  Clients $clients
     * @param  EntityManagerInterface $em
     * @param  TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/clients/{id}', name: 'deleteClient', methods: ['DELETE'])] 
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un produit')]
    public function deleteClient(Clients $clients, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse
    {
        $cache->invalidateTags(['clientsCache']);
        $em->remove($clients);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
    
}
