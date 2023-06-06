<?php

namespace App\Controller;

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
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;

/**
 * * @OA\Tag(name="Users")
 */
class UsersController extends AbstractController
{    
    /**
     * Cette méthode nous permet de récupérer la liste de tous les utilisateurs,
     * Pagination 5 par pages (par defaut) et mise en cache des éléments (Admin uniquement)
     * 
     * @OA\Get(
     *      description="This method allow us to get the list of BileMo's client's users (Only for Admin)"
     * )
     * @OA\Response(
     *      response=200,
     *      description="List of BileMo's client's users",
     *      @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref=@Model(type=Users::class, groups={"getUsers"}))
     *      )
     * )
     * @OA\Parameter(
     *      name="page",
     *      in="query",
     *      description="La page que l'on veut récupérer",
     *      @OA\Schema(type="in")
     * )
     * @OA\Parameter(
     *      name="limit",
     *      in="query",
     *      description="Le nombre d'éléments que l'on veut récupérer",
     *      @OA\Schema(type="in")
     * )
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
            $item->expiresAfter(60);
            $usersList = $usersRepository->findAllPaginated($page, $limit);
            return $serializer->serialize($usersList, 'json', $context);
        });

        return new JsonResponse($jsonUsersList, Response::HTTP_OK, [], true);
    }

    /**
     * Cette méthode permet à nos clients de récupérer les informations de leurs utilisateurs liés avec leurs id,
     * via la connection JWT du client,
     * Pagination 5 par pages et mise en cache des éléments (Clients uniquement)
     * 
     * @OA\Get(
     *      description="This method allows our clients to get detail of their own users linked on their id (Only for Clients)"
     * )
     * @OA\Response(
     *      response=200,
     *      description="List of client's users",
     *      @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref=@Model(type=Users::class, groups={"getUsers"}))
     *      )
     * )
     * @OA\Parameter(
     *      name="page",
     *      in="query",
     *      description="La page que l'on veut récupérer / Which pages",
     *      @OA\Schema(type="in")
     * )
     * @OA\Parameter(
     *      name="limit",
     *      in="query",
     *      description="Le nombre d'éléments que l'on veut récupérer / How many elements",
     *      @OA\Schema(type="in")
     * )
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
        
        $idCache = "getUsers-" . $page . "-" . $limit;

        $jsonUsersList = $cache->get($idCache, function (ItemInterface $item) use ($usersRepository, $page, $limit, $serializer, $clients) {
            echo("LES ÉLÉMENTS NE SONT PAS ENCORE EN CACHE ! \n");
            $context = SerializationContext::create()->setGroups('getUsers');
            $item->tag("usersCache");
            $item->expiresAfter(60);
            $usersList = $usersRepository->findAllPaginatedwithClient($page, $limit, $clients);
            if(empty($usersList)) {
                return new JsonResponse('Erreur 204, Aucun utilisateur n\'est relier a votre compte client !', Response::HTTP_NO_CONTENT);
            }
            return $serializer->serialize($usersList, 'json', $context);
        });

        // if(empty($jsonUsersList)) {
        //     return new JsonResponse('Erreur', Response::HTTP_NO_CONTENT);
        // }

        return new JsonResponse($jsonUsersList, Response::HTTP_OK, [], true);
    }

    /**
     * Cette méthode permet à nos clients d'obtenir le détail de leurs utilisateurs, sinon une erreur 401 lui est retourné
     * Via la connexion JWT du client (Clients uniquement)
     * 
     *  @OA\Get(
     *      description="This method allow our clients to get detail of their users (Only for Clients)"
     * )
     * @OA\Response(
     *      response=200,
     *      description="Detail of client's users",
     *      @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref=@Model(type=Users::class, groups={"getUsers"}))
     *      )
     * )
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
        
        $context = SerializationContext::create()->setGroups('getUsers');
        $jsonUsers = $serializer->serialize($user, 'json', $context);
        
        return new JsonResponse($jsonUsers, Response::HTTP_OK, [], true);
    }

    
    /**
     * Cette méthode permet à un client de créer un nouvel utilisateur qui lui sera lié,
     * Via la connexion JWT du client (Clients uniquement)
     * 
     * @OA\Post(
     *      description="This method allows our clients to create users who are linked to them. (Only for Clients)"
     * ),
     * @OA\RequestBody(
     *      description="Json Payload",
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              type="object",
     *              @OA\Property(
     *                  property="email",
     *                  description="Email de l'utilisateur (unique)",
     *                  type="string",
     *                  format="email"
     *              ),
     *              @OA\Property(
     *                  property="password",
     *                  description="Mot de passe de l'utilisateur",
     *                  type="string",
     *                  format="password"
     *              ),
     *              @OA\Property(
     *                  property="lastName",
     *                  description="Nom de l'utilisateur",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="firstName",
     *                  description="Prenom de l'utilisateur",
     *                  type="string"
     *              )
     *          )
     *      )
     * )
     * @OA\Response(
     *      response="201",
     *      description="New user created",
     *      content={
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="status",
     *                      type="boolean",
     *                      description="POST API Response"
     *                  )
     *              )
     *          )
     *      }
     * )
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
     * Cette méthode permet à nos clients de supprimer leurs utilisateurs, sinon une erreur 401 lui est retourné
     * Via la connexion JWT du client
     * 
     * @OA\Delete(
     *      description="This method allow our clients to delete their users (Only for Clients)"
     * )
     * @OA\Response(
     *      response="204",
     *      description="User deleted",
     *      content={
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="status",
     *                      type="boolean",
     *                      description="DELETE API Response"
     *                  )
     *              )
     *          )
     *      }
     * )
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
   
    /**
     * Cette méthode permet à un client de mettre à jour les informations de ses utilisateurs,
     * sinon une erreur 401 lui est retourné.
     * Connexion via JWT token du client
     * 
     * @OA\Put(
     *      description="This method allow our clients to update their users (Only for Clients)"
     * ),
     * @OA\RequestBody(
     *      description="Json Payload",
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              type="object",
     *              @OA\Property(
     *                  property="email",
     *                  description="Email de l'utilisateur (unique)",
     *                  type="string",
     *                  format="email"
     *              ),
     *              @OA\Property(
     *                  property="lastName",
     *                  description="Nom de l'utilisateur",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="firstName",
     *                  description="Prenom de l'utilisateur",
     *                  type="string"
     *              )
     *          )
     *      )
     * ),
     * @OA\Response(
     *      response="204",
     *      description="User updated",
     *      content={
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="status",
     *                      type="boolean",
     *                      description="PUT API Response"
     *                  )
     *              )
     *          )
     *      }
     * )
     * 
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param Users $users
     * @param EntityManagerInterface $em
     * @param ValidatorInterface $validator
     * @param TagAwareCacheInterface $cache
     *
     * @return JsonResponse
     */
    #[Route('/api/users/{id}', name: 'updateUser', methods: ['PUT'])]
    #[IsGranted('ROLE_CLIENT', message: 'Vous n\'avez pas les droits suffisants pour mettre à jour un utilisateur')] 
    public function updateUser(
        Request $request,
        SerializerInterface $serializer,
        Users $users,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache
    ): JsonResponse
    {
        // On récupère le client connecté
        $client = $this->getUser();

        if($client == $users->getClient()) {
            $updateUser = $serializer->deserialize($request->getContent(), Users::class, 'json');

            // On vérifie les erreurs
            $error = $validator->validate($updateUser);
            if($error->count() > 0) {
                return new JsonResponse($serializer->serialize($error, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }

            // On update les informations
            $users->setEmail($updateUser->getEmail());
            $users->setLastName($updateUser->getLastname());
            $users->setFirstName($updateUser->getFirstName());

            $em->persist($users);
            $em->flush();

            // On vide le cache
            $cache->invalidateTags(['usersCache']);

            return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
        } else {
            return new JsonResponse("Erreur 401, Vous n'êtes pas autorisé à modifier les données d'un autre client", Response::HTTP_UNAUTHORIZED);
        }
    }
}
