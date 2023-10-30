<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartProducts;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class CartService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getCartProduct(int $cartId, int $productId): CartProducts
    {
        return $this->entityManager->getRepository(CartProducts::class)->getCartProduct($cartId, $productId);
    }

    public function getCurrentCart(User $user): Cart
    {
        $cart = $this->entityManager->getRepository(Cart::class)->getCurrentCart($user);
        if ($cart === null) {
            $cart = $this->createCart($user);
        }
        return $cart;
    }

    public function createCart(User $user): Cart
    {
        $cart = new Cart();
        $cart->setUser($user);
        $cart->setSum(0);

        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        return $cart;
    }

    public function addToCart(Cart $cart, Product $product, int $quantity = 1): void
    {
        $cartProduct = new CartProducts();
        $cartProduct->setProduct($product);
        $cartProduct->setQuantity($quantity);
        $cartProduct->setPrice($product->getPrice());

        $cart->addCartProduct($cartProduct);

        $total = $this->calculateTotal($cart);
        $cart->setSum($total);
        $this->entityManager->persist($cart);
        $this->entityManager->flush();
    }

    public function removeFromCart(CartProducts $cartProduct): void
    {
        $cart= $cartProduct->getCart();
        $cart->removeCartProduct($cartProduct);
        $cart->setSum($cart->getSum() - ($cartProduct->getPrice() * $cartProduct->getQuantity()));

        $this->entityManager->remove($cartProduct);
        $this->entityManager->flush();
    }

    public function updateQuantity(CartProducts $cartProduct, int $newQuantity): void
    {
        $cart= $cartProduct->getCart();
        $cart->setSum($cart->getSum() - ($cartProduct->getPrice() * $cartProduct->getQuantity()));
        $cartProduct->setQuantity($newQuantity);
        $cart->setSum($cart->getSum() + ($cartProduct->getPrice() * $newQuantity));

        $this->entityManager->persist($cartProduct);
        $this->entityManager->flush();
    }

    public function calculateTotal(Cart $cart): float
    {
        $total = 0;
        foreach ($cart->getCartProducts() as $cartProduct) {
            $total += ($cartProduct->getPrice() * $cartProduct->getQuantity());
        }

        return $total;
    }

    public function clearCart(Cart $cart): void
    {
        foreach ($cart->getCartProducts() as $cartProduct) {
            $this->entityManager->remove($cartProduct);
        }

        $cart->setSum(0);
        $cart->clearCartProducts();

        $this->entityManager->flush();
    }
}