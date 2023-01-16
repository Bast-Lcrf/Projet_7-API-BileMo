<?php

namespace App\Controller;

use App\Repository\UsersRepository;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class UsersController extends AbstractController
{    
    /**
     * Cette méthode nous permet de récupérer la liste de tous les utilisateurs
     *
     * @param Request $request
     * @param UsersRepository $usersRepository
     * @param SerializerInterface $serializer
     * @param TagAwareCacheInterface $cache
     * 
     * @return JsonResponse
     */
    #[Route('/api/users', name: 'users')]
    #[IsGranted('ROLE_ADMIN', message: "Vous n\'avez pas les droits suffisant pour lire l'ensemble des utilisateurs")]
    public function getAllUsers(
        Request $request,
        UsersRepository $usersRepository,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cache
    ): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 5);

        $idCache = "getAllUsers-" . $page . "-" . $limit;

        $jsonUsersList = $cache->get($idCache, function (ItemInterface $item) use ($usersRepository, $page, $limit, $serializer) {
            echo("L\'ÉLÉMENT N\'EST PAS ENCORE EN CACHE ! \n");
            $context = SerializationContext::create()->setGroups('getAllUsers');
            $item->tag("usersCache");
            $usersList = $usersRepository->findAllPaginated($page, $limit);
            return $serializer->serialize($usersList, 'json', $context);
        });

        return new JsonResponse($jsonUsersList, Response::HTTP_OK, [], true);
    }
}
