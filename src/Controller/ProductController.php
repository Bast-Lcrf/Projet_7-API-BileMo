<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use JMS\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;

/**
 * * @OA\Tag(name="Products")
 */
class ProductController extends AbstractController
{
    /**
     * Cette méthode nous permet de récupérer la liste de tous les produits,
     * Pagination 5 produits par pages (par défaut), et mise en cache des éléments (Clients et admin uniquement)
     * 
     * @OA\Get(
     *      description="This method allow us to get the list of BileMo's products (Only for Clients and Admin)"
     * )
     * @OA\Response(
     *      response=200,
     *      description="Return the list of products",
     *      @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref=@Model(type=product::class, groups={"getProducts"}))
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
     * @param ProductRepository $productRepository
     * @param SerializerInterface $serializer
     * @param TagAwareCacheInterface $cache
     * 
     * @return JsonResponse
     */
    #[Route('/api/products', name: 'product', methods: ['GET'])] 
    #[IsGranted('ROLE_CLIENT', message: 'Vous n\'avez pas les droits suffisants pour voir la liste des produits')]
    public function getAllProducts(
        Request $request,
        ProductRepository $productRepository,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cache
    ): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 5);
        $nbElements = count($productRepository->findAll());
        $totalPage = ceil($nbElements / $limit);

        if($page <= 0 || $page > $totalPage) {
            return new JsonResponse("Erreur 400, La page demandé n'existe pas, veuillez revoir les paramètres", Response::HTTP_BAD_REQUEST);
        }

        $idCache = "getAllProduct-" . $page . "-" . $limit;

        $jsonProductList = $cache->get($idCache, function (ItemInterface $item) use ($productRepository, $page, $limit, $serializer) {
            echo ("LES ÉLÉMENTS NE SONT PAS ENCORE EN CACHE ! \n");
            $context = SerializationContext::create()->setGroups('getProducts');
            $item->tag("productCache");
            $item->expiresAfter(60);
            $productList = $productRepository->findAllPaginated($page, $limit);
            return $serializer->serialize($productList, 'json', $context);
        });

        return new JsonResponse($jsonProductList, Response::HTTP_OK, [], true);
    }
   
    /**
     * Cette méthode nous permet de récupérer le detail d'un produit via son id (Clients et admin uniquement)
     *
     * @OA\Get(
     *      description="This method allow us to view a product's detail by its id (Only for clients and Admin)"
     * )
     * @OA\Response(
     *      response=200,
     *      description="Detail of BileMo's product",
     *      @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref=@Model(type=Product::class, groups={"getProducts"}))
     *      )
     * )
     * 
     * @param  Product $product
     * @param  SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/api/products/{id}', name: 'detailProduct', methods: ['GET'])]
    #[IsGranted('ROLE_CLIENT', message: 'Vous n\'avez pas les droits suffisants pour voir le détail du produit')]
    public function getDetailProduct(Product $product, SerializerInterface $serializer): JsonResponse
    {
        $context = SerializationContext::create()->setGroups('getProducts');
        $jsonProduct = $serializer->serialize($product, 'json', $context);
        return new JsonResponse($jsonProduct, Response::HTTP_OK, [], true);
    }

    
    /**
     * Cette méthode nous permet de supprimer un produit par rapport à son id (Admin uniquement)
     *
     * @OA\Delete(
     *      description="This method allow us to delete a product by its id (Only for Admin)"
     * )
     * @OA\Response(
     *      response="204",
     *      description="Product deleted",
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
     * 
     * @param  mixed $product
     * @param  mixed $em
     * @param  mixed $cache
     * @return JsonResponse
     */
    #[Route('/api/products/{id}', name: 'deleteProduct', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un produit')]
    public function deleteProduct(Product $product, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse
    {
        $cache->invalidateTags(['productCache']);
        $em->remove($product);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
    
    /**
     * Cette méthode nous permet de créer un nouveau produit (Admin uniquement)
     * 
     * @OA\Post(
     *      description="This method allow us to create a new product (Only for Admin)"
     * )
     * @OA\Response(
     *      response=201,
     *      description="New product created",
     *      @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref=@Model(type=product::class, groups={"getProducts"}))
     *      )
     * )
     * @OA\RequestBody(
     *      description="Json Payload",
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              type="object",
     *              @OA\Property(
     *                  property="name",
     *                  description="Nom du nouveau produit",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="brand",
     *                  description="Marque du nouveau produit",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="releaseDate",
     *                  description="Date de sortie du produit",
     *                  type="date",
     *                  example="2023-01-01T00:00:00+01:00"
     *              ),
     *              @OA\Property(
     *                  property="operatingSystem",
     *                  description="OS du produit",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="Price",
     *                  description="Prix du produit",
     *                  type="int"
     *              )
     *          )
     *      )
     * )
     * 
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $em
     * @param UrlGeneratorInterface $urlGenerator
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    #[Route('api/products', name: 'createProduct', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour ajouter un produit')]
    public function createProduct(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $product = $serializer->deserialize($request->getContent(), Product::class, 'json');

        // On vérifie les erreurs
        $error = $validator->validate($product);
        if($error->count() > 0) {
            return new JsonResponse($serializer->serialize($error, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($product);
        $em->flush();

        $context = SerializationContext::create()->setGroups('getProducts');
        $jsonProduct = $serializer->serialize($product, 'json', $context);

        $location = $urlGenerator->generate('detailProduct', ['id' => $product->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonProduct, Response::HTTP_CREATED, ["location" => $location], true);
    }
    
    /**
     * Cette méthode nous permet de modifier un produit via son id (Admin uniquement)
     * 
     * @OA\Put(
     *      description="This method allow us to update a product by its id (Only for Admin)"
     * ),
     * @OA\RequestBody(
     *      description="Json Payload",
     *      @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *              type="object",
     *              @OA\Property(
     *                  property="name",
     *                  description="Nom du produit",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="brand",
     *                  description="Marque du produit",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="releaseDate",
     *                  description="Date de sortie du produit",
     *                  type="date",
     *                  example="2023-01-01T00:00:00+01:00"
     *              ),
     *              @OA\Property(
     *                  property="operatingSystem",
     *                  description="OS du produit",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="Price",
     *                  description="Prix du produit",
     *                  type="integer"
     *              )
     *          )
     *      )
     * )
     * @OA\Response(
     *      response="204",
     *      description="Product updated",
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
     * @param Product $currentProduct
     * @param EntityManagerInterface $em
     * @param ValidatorInterface $validator
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('api/products/{id}', name: 'updateProduct', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier un produit')]
    public function updateProduct(
        Request $request,
        SerializerInterface $serializer,
        Product $currentProduct,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache
    ): JsonResponse
    {
        $newProduct = $serializer->deserialize($request->getContent(), Product::class, 'json');

        $currentProduct->setName($newProduct->getName());
        $currentProduct->setBrand($newProduct->getBrand());
        $currentProduct->setReleaseDate($newProduct->getReleaseDate());
        $currentProduct->setOperatingSystem($newProduct->getOperatingSystem());
        $currentProduct->setPrice($newProduct->getPrice());

        // On vérifie les erreurs
        $error = $validator->validate($currentProduct);
        if($error->count() > 0) {
            return new JsonResponse($serializer->serialize($error,'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($currentProduct);
        $em->flush();

        // On vide le cache
        $cache->invalidateTags(['productCache']);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
