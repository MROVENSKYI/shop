<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartProducts;
use App\Entity\Order;
use App\Entity\OrderProducts;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class ShopService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getCartProduct(int $cartId, int $productId): CartProducts
    {
        return $this->entityManager->getRepository(CartProducts::class)->findOneBy([
            'cart' => $cartId,
            'product' => $productId,
        ]);
    }

    public function getCurrentCart(User $user): Cart
    {
        $cart = $this->entityManager->getRepository(Cart::class)->findOneBy([
            'user' => $user,
        ]);

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

    public function removeFromCart(Cart $cart, CartProducts $cartProduct): void
    {
        $cart->removeCartProduct($cartProduct);
        $cart->setSum($cart->getSum() - ($cartProduct->getPrice() * $cartProduct->getQuantity()));

        $this->entityManager->remove($cartProduct);
        $this->entityManager->flush();
    }

    public function updateQuantity(Cart $cart, CartProducts $cartProduct, int $newQuantity): void
    {
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

    public function createOrder(Cart $cart): Order
    {
        $order = new Order();
        $order->setUser($cart->getUser());

        foreach ($cart->getCartProducts() as $cartProduct) {
            $orderProduct = new OrderProducts();
            $orderProduct->setProduct($cartProduct->getProduct());
            $orderProduct->setQuantity($cartProduct->getQuantity());
            $orderProduct->setPrice($cartProduct->getPrice());

            $order->addOrderProduct($orderProduct);

            $this->entityManager->persist($orderProduct);
        }

        $order->setSum($cart->getSum());

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }

    /**
     * @throws Exception
     */
    public function checkoutOrder(User $user): Order
    {
        $cart = $this->getCurrentCart($user);

        if ($cart->getCartProducts()->isEmpty()) {
            throw new Exception('Your cart is empty.');
        }

        $order = $this->createOrder($cart);
        $this->clearCart($cart);

        return $order;
    }
}