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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Clients")
 */
class ClientsController extends AbstractController
{     
    /**
     * Cette méthode nous permet de récupérer la liste des clients de BileMo. (Admin uniquement)
     * Pagination par défaut: 5 par pages, et mise en cache des éléments.
     * 
     * @OA\Get(
     *      description="This method allow us to get the list of BileMo's clients (Only for Admin)"
     * )
     * @OA\Response(
     *      response=200,
     *      description="Return the list of BileMo's clients",
     *      @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref=@Model(type=Clients::class, groups={"getClients"}))
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
     *
     * @param Request $request
     * @param ClientsRepository $clientsRepository
     * @param SerializerInterface $serializer
     * @param TagAwareCacheInterface $cache
     * 
     * @return JsonResponse
     */
    #[Route('/api/clients', name: 'clients', methods: ['GET'])] 
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour voir la liste des clients de BileMo')]
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
            $item->expiresAfter(60);
            $clientsList = $clientsRepository->findAllPaginated($page, $limit);
            return $serializer->serialize($clientsList, 'json', $context);
        });

        return new JsonResponse($jsonClientsList, Response::HTTP_OK, [], true);
    }

    /**
     * Cette méthode nous permet de récupérer le detail d'un client via son id (Admin uniquement)
     *
     *  @OA\Get(
     *      description="This method allow us to view a client's detail (Only for Admin)"
     * )
     * @OA\Response(
     *      response=200,
     *      description="Detail of BileMo's client",
     *      @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref=@Model(type=Clients::class, groups={"getClients"}))
     *      )
     * )
     *
     * @param  Clients $clients
     * @param  SerializerInterface $serializer
     * 
     * @return JsonResponse
     */
    #[Route('/api/clients/{id}', name: 'detailClient', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour voir le détail d\'un client de BileMo')]
    public function getDetailClient(Clients $clients, SerializerInterface $serializer): JsonResponse
    {
        $context = SerializationContext::create()->setGroups('getClients');
        $jsonClient = $serializer->serialize($clients, 'json', $context);
        return new JsonResponse($jsonClient, Response::HTTP_OK, [], true);
    }

    /**
     * Cette méthode nous permet de supprimer un client via son id (Admin uniquement)
     * 
     * @OA\Delete(
     *      description="This method allow us to delete a client (Only for Admin)"
     * )
     * @OA\Response(
     *      response="204",
     *      description="Client deleted",
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
     * @param  Clients $clients
     * @param  EntityManagerInterface $em
     * @param  TagAwareCacheInterface $cache
     * 
     * @return JsonResponse
     */
    #[Route('/api/clients/{id}', name: 'deleteClient', methods: ['DELETE'])] 
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un client')]
    public function deleteClient(Clients $clients, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse
    {
        $cache->invalidateTags(['clientsCache']);
        $em->remove($clients);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Cette méthode nous permet de créer un nouveau client (Admin uniquement)
     * 
     * @OA\Post(
     *      description="This method allow us to create a new client (Only for Admin)"
     * ),
     * @OA\RequestBody(
     *      description="Json Payload",
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              type="object",
     *              @OA\Property(
     *                  property="email",
     *                  description="Email de l'entreprise cliente de BileMo (unique)",
     *                  type="string",
     *                  format="email"
     *              ),
     *              @OA\Property(
     *                  property="password",
     *                  description="Mot de passe de l'entreprise cliente de BileMo",
     *                  type="string",
     *                  format="password"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  description="Nom de l'entreprise cliente de BileMo",
     *                  type="string"
     *              )
     *          )
     *      )
     * )
     * @OA\Response(
     *      response="201",
     *      description="New client created",
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
     * @param UserPasswordHasherInterface $clientHash
     * 
     * @return JsonResponse
     */
    #[Route('/api/clients', name: 'createClient', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour ajouter un nouveau client')] 
    public function createClient(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $clientHash
    ): JsonResponse
    {
        $client = $serializer->deserialize($request->getContent(), Clients::class, 'json');

        // On vérifie les erreurs
        $error = $validator->validate($client);
        if($error->count() > 0) {
            return new JsonResponse($serializer->serialize($error, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        // On hash le mot de passe renseigné
        $pass = $clientHash->hashPassword($client, $client->getPassword());
        $client->setPassword($pass);

        // on ajoute le role
        $client->setRoles(['ROLE_CLIENT']);

        // On ajoute la date de création du compte
        $client->setCreatedAt(new \DateTimeImmutable('Europe/Paris'));

        $em->persist($client);
        $em->flush();

        $context = SerializationContext::create()->setGroups('getClients');
        $jsonClient = $serializer->serialize($client, 'json', $context);

        $location = $urlGenerator->generate('detailClient', ['id' => $client->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonClient, Response::HTTP_CREATED, ["location" => $location], true);
    }
 
    /**
     * Cette méthode nous permet de mettre à jour l'email et le nom du client (Admin uniquement)
     * (son role, son mdp et la date de création du compte reste inchangé)
     * 
     * @OA\Put(
     *      description="This method allow us to update a client's name and email (Only for Admin)"
     * ),
     * @OA\RequestBody(
     *      description="Json Payload",
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              type="object",
     *              @OA\Property(
     *                  property="email",
     *                  description="Email de l'entreprise cliente de BileMo (unique)",
     *                  type="string",
     *                  format="email"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  description="Nom de l'entreprise cliente de BileMo",
     *                  type="string"
     *              )
     *          )
     *      )
     * ),
     * @OA\Response(
     *      response="204",
     *      description="Client updated",
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
     * @param Clients $currentClient
     * @param EntityManagerInterface $em
     * @param ValidatorInterface $validator
     * @param TagAwareCacheInterface $cache
     *
     * @return JsonResponse
     */
    #[Route('/api/clients/{id}', name: 'updateClient', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour mettre à jour un client')]
    public function updateClient(
        Request $request,
        SerializerInterface $serializer,
        Clients $currentClient,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache
    ): JsonResponse
    {
        $updateClient = $serializer->deserialize($request->getContent(), Clients::class, 'json');

        // On vérifie les erreurs
        $error = $validator->validate($updateClient);
        if($error->count() > 0) {
            return new JsonResponse($serializer->serialize($error, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        // On update les informations
        $currentClient->setEmail($updateClient->getEmail());
        $currentClient->setName($updateClient->getName());

        $em->persist($currentClient);
        $em->flush();

        // On vide la cache
        $cache->invalidateTags(['clientsCache']);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
    
}
