<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OrderController extends AbstractController
{
    #[Route('/cart', name: 'app_cart')]
    public function cart(OrderRepository $orderRepository, Security $security): Response
    {
        $user = $security->getUser();
        
        $order = $orderRepository->findPendingOrderByUser($user);

        $totalPrice = 0;

        foreach ($order->getOrderProducts() as $product) {
            $totalPrice = $totalPrice + ($product->getProductId()->getPrice() * $product->getQuantity());
        } 

        return $this->render('order/cart.html.twig', [
            'order' => $order,
            'total' => $totalPrice
        ]);
    }

    #[Route('/cart/{id}', name: 'app_cart_delete')]
    public function deleteCart (Order $order, EntityManagerInterface $em): Response
    {
        $em->remove($order);
        $em->flush();
        
        return $this->redirectToRoute('app_cart');
    }
}
