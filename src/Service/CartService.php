<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\User;
use App\Entity\Order;
use App\Entity\OrderProducts;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class CartService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getOrderProduct(int $orderId, int $productId): OrderProducts
    {
        return $this->entityManager->getRepository(OrderProducts::class)->findOneBy([
            'order' => $orderId,
            'product' => $productId,
        ]);
    }

    public function getCurrentCart(User $user): Order
    {
        $cart = $this->entityManager->getRepository(Order::class)->findOneBy([
            'user' => $user,
        ]);

        if ($cart === null) {
            $cart = $this->createCart($user);
        }

        return $cart;
    }

    public function createCart(User $user): Order
    {
        $cart = new Order();
        $cart->setUser($user);
        $cart->setSum(0);

        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        return $cart;
    }

    public function addToCart(Order $cart, Product $product, int $quantity = 1): void
    {
        $orderProduct = new OrderProducts();
        $orderProduct->setProduct($product);
        $orderProduct->setOrder($cart);
        $orderProduct->setQuantity($quantity);
        $orderProduct->setPrice($product->getPrice());

        $cart->addOrderProduct($orderProduct);

        $total = $this->calculateTotal($cart);
        $cart->setSum($total);
        $this->entityManager->persist($cart);
        $this->entityManager->flush();
    }

    public function removeFromCart(Order $cart, OrderProducts $orderProduct): void
    {
        $cart->removeOrderProduct($orderProduct);
        $cart->setSum($cart->getSum() - ($orderProduct->getPrice() * $orderProduct->getQuantity()));

        $this->entityManager->remove($orderProduct);
        $this->entityManager->flush();
    }

    public function updateQuantity(Order $cart, OrderProducts $orderProduct, int $newQuantity): void
    {
        $cart->setSum($cart->getSum() - ($orderProduct->getPrice() * $orderProduct->getQuantity()));
        $orderProduct->setQuantity($newQuantity);
        $cart->setSum($cart->getSum() + ($orderProduct->getPrice() * $newQuantity));

        $this->entityManager->persist($orderProduct);
        $this->entityManager->flush();
    }

    public function calculateTotal(Order $cart): float
    {
        $total = 0;
        foreach ($cart->getOrderProducts() as $orderProduct) {
            $total += ($orderProduct->getPrice() * $orderProduct->getQuantity());
        }

        return $total;
    }

    public function clearCart(Order $cart): void
    {
        foreach ($cart->getOrderProducts() as $orderProduct) {
            $this->entityManager->remove($orderProduct);
        }

        $cart->setSum(0);
        $cart->clearOrderProducts();

        $this->entityManager->flush();
    }
    public function createOrder(Order $cart): Order
    {

        $order = new Order();
        $order->setUser($cart->getUser());
        foreach ($cart->getOrderProducts() as $orderProduct) {
            $order->addOrderProduct($orderProduct);
            $orderProduct->setOrder($order);
        }

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }
    /**
     * @throws Exception
     */
    public function checkoutOrder($user): Order
    {
        $cart = $this->getCurrentCart($user);

        if ($cart->getOrderProducts()->isEmpty()) {
            throw new Exception('Your cart is empty.');
        }

        $order = $this->createOrder($cart);
        $this->clearCart($cart);

        return $order;
    }
}
