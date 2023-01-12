<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ProductController extends AbstractController
{
    /**
     * Cette méthode nous permet de récupérer la liste de tous les produits
     *
     * @return void
     */
    #[Route('/api/products', name: 'product', methods: ['GET'])] 
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
            echo ("L\'ÉLÉMENT N\'EST PAS ENCORE EN CACHE ! \n");
            $context = SerializationContext::create()->setGroups('getProducts');
            $item->tag("productCache");
            $productList = $productRepository->findAllPaginated($page, $limit);
            return $serializer->serialize($productList, 'json', $context);
        });

        return new JsonResponse($jsonProductList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/products/{id}', name: 'detailProduct', methods: ['GET'])] 
    public function getDetailProduct(Product $product, SerializerInterface $serializer): JsonResponse
    {
        $context = SerializationContext::create()->setGroups('getProducts');
        $jsonProduct = $serializer->serialize($product, 'json', $context);
        return new JsonResponse($jsonProduct, Response::HTTP_OK, [], true);
    }
    
}
