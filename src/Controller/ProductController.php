<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    #[Route('/', name: 'app_shop')]
    public function shop(ProductRepository $productRepository): Response
    {
        return $this->render('product/shopList.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    #[Route('/product/{id}', name: 'product.detail')]
    public function shopItem(?Product $product): Response
    {
        if ($product === null) {
            throw $this->createNotFoundException('Product not found');
        }

        return $this->render('product/shopItem.html.twig', [
            'product' => $product,
        ]);
    }
}
