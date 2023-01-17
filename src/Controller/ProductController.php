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

class ProductController extends AbstractController
{
    /**
     * Cette méthode nous permet de récupérer la liste de tous les produits,
     * Pagination 5 produits par pages, et mise en cache des éléments
     *
     * @param Request $request
     * @param ProductRepository $productRepository
     * @param SerializerInterface $serializer
     * @param TagAwareCacheInterface $cache
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
     * Cette méthode nous permet de récupérer le detail d'un produit via son id
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
     * Cette méthode nous permet de supprimer un produit par rapport à son id
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
     * Cette méthode nous permet de créer un nouveau produit
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
     * Cette méthode nous permet de modifier un produit via son id
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
