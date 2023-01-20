<?php

namespace App\Controller;

use App\Entity\Clients;
use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
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
    #[Route('/api/allUsers', name: 'allUsers', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez pas les droits suffisant pour lire l'ensemble des utilisateurs")]
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
            echo("LES ÉLÉMENTS NE SONT PAS ENCORE EN CACHE ! \n");
            $context = SerializationContext::create()->setGroups('getAllUsers');
            $item->tag("usersCache");
            $item->expiresAfter(5);
            $usersList = $usersRepository->findAllPaginated($page, $limit);
            return $serializer->serialize($usersList, 'json', $context);
        });

        return new JsonResponse($jsonUsersList, Response::HTTP_OK, [], true);
    }

    /**
     * Cette méthode nous permet de récupérer les informations des utilisateurs liés au client,
     * via la connection JWT du client,
     * Pagination 5 par pages et mise en cache des éléments
     * 
     * @param Request $request
     * @param UsersRepository $usersRepository
     * @param SerializerInterface $serializer
     * @param TagAwareCacheInterface $cache
     *
     * @return JsonResponse
     */
    #[Route('/api/users', name: 'users', methods: ['GET'])]
    #[IsGranted('ROLE_CLIENT', message: "Vous n'avez pas les droits suffisant pour lire les données des utilisateurs")] 
    public function getAllUsersLinkedWithClient(
        Request $request,
        UsersRepository $usersRepository,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cache
    ): JsonResponse
    {
        $clients = $this->getUser();
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 5);

        $idCache = "getAllUsers-" . $page . "-" . $limit;

        $jsonUsersList = $cache->get($idCache, function (ItemInterface $item) use ($usersRepository, $page, $limit, $serializer, $clients) {
            echo("LES ÉLÉMENTS NE SONT PAS ENCORE EN CACHE ! \n");
            $context = SerializationContext::create()->setGroups('getAllUsers');
            $item->tag("usersCache");
            $item->expiresAfter(5);
            $usersList = $usersRepository->findAllPaginatedwithClient($page, $limit, $clients);
            return $serializer->serialize($usersList, 'json', $context);
        });

        return new JsonResponse($jsonUsersList, Response::HTTP_OK, [], true);
    }

    /**
     * Cette méthode nous permet de récupérer les informations d'un utilisateur relié au client, sinon une erreur 401 lui est retourné
     * Via la connexion JWT du client
     * 
     * @param Users $users
     * @param SerializerInterface $serializer
     * @param UsersRepository $usersRepository
     *
     * @return JsonResponse
     */
    #[Route('/api/users/{id}', name: 'detailUser', methods: ['GET'])]
    #[IsGranted('ROLE_CLIENT', message: "Vous n'avez pas les droits suffisant pour lire les données de l'utilisateur")]  
    public function getDetailUser(
        Users $users,
        SerializerInterface $serializer,
        UsersRepository $usersRepository
    ): JsonResponse
    {
        // On récupère le client connecté
        $client = $this->getUser();

        // On va chercher les informations de l'utilisateur relié au client
        $user = $usersRepository->findUserWithClient($users ,$client);

        // On vérifie les erreurs
        if($user == null) {
            return new JsonResponse("Erreur 401, Vous n'êtes pas autorisé à récupérer ces informations", Response::HTTP_UNAUTHORIZED);
        }
        
        $context = SerializationContext::create()->setGroups('getAllUsers');
        $jsonUsers = $serializer->serialize($user, 'json', $context);
        
        return new JsonResponse($jsonUsers, Response::HTTP_OK, [], true);
    }

    
    /**
     * Cette méthode permet à un client de créer un nouvel utilisateur qui lui sera lié,
     * Via la connexion JWT du client
     * 
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $em
     * @param UrlGeneratorInterface $urlGenerator
     * @param ValidatorInterface $validator
     * @param UserPasswordHasherInterface $userHash
     *
     * @return JsonResponse
     */
    #[Route('api/users', name: 'createUser', methods: ['POST'])]
    #[IsGranted('ROLE_CLIENT', message: 'Vous n\'avez pas les droits suffisants pour ajouter un nouvel utilisateur')] 
    public function createUser(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $userHash
    ): JsonResponse
    {
        $user = $serializer->deserialize($request->getContent(), Users::class, 'json');

        // On vérifie les erreurs
        $error = $validator->validate($user);
        if($error->count() > 0) {
            return new JsonResponse($serializer->serialize($error, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        // On hash le mot de passe renseigné
        $pass = $userHash->hashPassword($user, $user->getPassword());
        $user->setPassword($pass);

        // On ajoute le rôle
        $user->setRoles(['ROLE_USER']);

        // On ajoute la date de création du compte
        $user->setCreatedAt(new \DateTimeImmutable('Europe/Paris'));

        // On relie l'utilisateur à son client
        $client = $this->getUser();
        $user->setClient($client);

        $em->persist($user);
        $em->flush();

        $context = SerializationContext::create()->setGroups('getAllUsers');
        $jsonUser = $serializer->serialize($user, 'json', $context);

        $location = $urlGenerator->generate('detailUser', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ["location" => $location], true);
    }

    /**
     * Cette méthode permet à un client de supprimer l'un de ses utilisateurs, sinon une erreur 401 lui est retourné
     * Via la connexion JWT du client
     *
     * @param  Users $users
     * @param  EntityManagerInterface $em
     * @param  TagAwareCacheInterface $cache
     * 
     * @return JsonResponse
     */
    #[Route('/api/users/{id}', name: 'deleteUser', methods: ['DELETE'])]
    #[IsGranted('ROLE_CLIENT', message: 'Vous n\'avez pas les droits suffisants pour supprimer un utilisateur')] 
    public function deleteUser(Users $users, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse
    {
        // On récupère le client connecté
        $client = $this->getUser();

        // On vérifie si l'utilisateur supprimer correspond à son client
        if($client == $users->getClient()) {
            $cache->invalidateTags(['usersCache']);
            $em->remove($users);
            $em->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        } else {
            return new JsonResponse("Erreur 401, Vous n'êtes pas autorisé à supprimer les données d'un autre client", Response::HTTP_UNAUTHORIZED);
        }
    }
}
