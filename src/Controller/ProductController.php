<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Entity\Product;
use App\Entity\User;
use App\Form\OrderProductType;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    #[Route('/', name: 'app_product')]
    public function index(ProductRepository $repository): Response
    {
        $products = $repository->findAll();

        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/{id}', name: 'app_product_detail', requirements: ['id' => '\d+'])]
    public function product_detail(Product $product, Request $request, EntityManagerInterface $em, Security $security, OrderRepository $order_repository): Response
    {
        $user = $security->getUser();

        if ($user)
        {            
            if ($order_repository->findPendingOrderByUser($security->getUser())) {
                $pendingOrder = $order_repository->findPendingOrderByUser($security->getUser());

                $pendingOrderProducts = $pendingOrder->getOrderProducts();

                foreach ($pendingOrderProducts as $pendingOrderProduct) {
                    if ($pendingOrderProduct->getProductId() === $product) {
                        $orderProduct = $pendingOrderProduct;
                    }                
                }

                if (!isset($orderProduct)) {
                    $orderProduct = new OrderProduct;
                    $orderProduct->setProductId($product);
                    $orderProduct->setPrice($product->getPrice());
                    $orderProduct->setOrderId($pendingOrder);
                }
            }

            else {
                $orderProduct = new OrderProduct;
                $orderProduct->setProductId($product);
                $orderProduct->setPrice($product->getPrice());
            }

            $form = $this->createForm(OrderProductType::class, $orderProduct);
            $form->handleRequest($request);
        
            if ($form->isSubmitted() && $form->isValid()) {            

                if (!$orderProduct->getOrderId()) {
                    $order = new Order;
                    $order->setCreatedAt(new DateTimeImmutable("now"));
                    $order->setStatus(true);
                    $order->setUserId($user);
                    $em->persist($order);
                    $em->flush();
                    $orderProduct->setOrderId($order);
                }

                $em->persist($orderProduct);
                $em->flush();
                return $this->redirectToRoute('app_cart');;
            }

            else {
                return $this->render('product/detail.html.twig', [
                'product' => $product,
                'form' => $form,
                'orderProduct' => $orderProduct
                ]);
            }
        }

        return $this->render('product/detail.html.twig', [
            'product' => $product,
        ]);
    }


}
