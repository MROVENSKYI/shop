<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\OrderProducts;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class OrderService
{
    private EntityManagerInterface $entityManager;

    private CartService $cartService;

    public function __construct(EntityManagerInterface $entityManager,CartService $cartService)
    {
        $this->entityManager = $entityManager;
        $this->cartService = $cartService;
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
        $cart = $this->cartService->getCurrentCart($user);

        if ($cart->getCartProducts()->isEmpty()) {
            throw new Exception('Your cart is empty.');
        }

        $order = $this->createOrder($cart);
        $this->cartService->clearCart($cart);

        return $order;
    }
}