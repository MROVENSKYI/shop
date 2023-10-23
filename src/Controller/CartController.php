<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Service\CartService;
use Exception;
use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class CartController extends AbstractController
{
    private CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    #[Route('/shop/cart/checkout', name: 'cart_checkout')]
    public function checkout(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedException('You must be logged in to checkout.');
        }

        if (!$user instanceof User) {
            throw new LogicException('Expected user to be an instance of App\Entity\User.');
        }

        try {
            $this->cartService->checkoutOrder($user);
        } catch (Exception) {
            $this->addFlash('error', 'Your cart is empty.');
            return $this->redirectToRoute('app_cart');
        }

        $this->addFlash('success', 'Your order has been placed successfully.');
        return $this->redirectToRoute('user_orders');
    }

    #[Route('/shop/cart', name: 'app_cart')]
    public function shopCart(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user === null) {
            throw new AccessDeniedException('You must be logged in to view your cart.');
        }

        $cart = $this->cartService->getCurrentCart($user);
        $cartItems = $cart->getOrderProducts();

        return $this->render('cart/shopCart.html.twig', [
            'cart' => $cart,
            'cartItems' => $cartItems,
        ]);
    }

    #[Route('/shop/cart/add/{id}', name: 'cart_add')]
    public function addToCart(Request $request,Product $product): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user === null) {
            throw new AccessDeniedException('You must be logged in to add products to your cart.');
        }

        $quantity = $request->request->get('quantity', 1);

        $cart = $this->cartService->getCurrentCart($user);
        $this->cartService->addToCart($cart, $product, $quantity);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/shop/cart/remove/{orderId}/{productId}', name: 'cart_remove')]
    public function removeFromCart(int $orderId, int $productId): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user === null) {
            throw new AccessDeniedException('You must be logged in to remove products from your cart.');
        }

        $cart = $this->cartService->getCurrentCart($user);
        $orderProduct = $this->cartService->getOrderProduct($orderId, $productId);

        $this->cartService->removeFromCart($cart, $orderProduct);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/shop/cart/update/{orderId}/{productId}/{quantity}', name: 'cart_update')]
    public function updateQuantity(int $orderId, int $productId, int $quantity): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user === null) {
            throw new AccessDeniedException('You must be logged in to update products in your cart.');
        }

        $cart = $this->cartService->getCurrentCart($user);
        $orderProduct = $this->cartService->getOrderProduct($orderId, $productId);

        $this->cartService->updateQuantity($cart, $orderProduct, $quantity);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/shop/cart/clear', name: 'cart_clear')]
    public function clearCart(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user === null) {
            throw new AccessDeniedException('You must be logged in to clear your cart.');
        }

        $cart = $this->cartService->getCurrentCart($user);

        $this->cartService->clearCart($cart);

        return $this->redirectToRoute('app_cart');
    }
}
