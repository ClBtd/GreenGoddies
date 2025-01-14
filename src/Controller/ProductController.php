<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Entity\Product;
use App\Form\OrderProductType;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ProductController extends AbstractController
{
    // Affichage de tous les produits pour la page d'accueil
    #[Route('/', name: 'app_product')]
    public function index(ProductRepository $repository): Response
    {
        $products = $repository->findAll();

        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }

    // Affichage de la page de détail d'un produit
    #[Route('/{id}', name: 'app_product_detail', requirements: ['id' => '\d+'])]
    public function product_detail(Product $product, Request $request, EntityManagerInterface $em, Security $security, OrderRepository $order_repository): Response
    {
        // On vérifie si un utilisateur est connecté
        $user = $security->getUser();

        // Si c'est le cas, on vérifie s'il a une commande en cours, et si le produit s'y trouve 
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

                // Si le produit n'est pas présent dans la commande on crée un nouvel OrderProduct
                if (!isset($orderProduct)) {
                    $orderProduct = new OrderProduct;
                    $orderProduct->setProductId($product);
                    $orderProduct->setOrderId($pendingOrder);
                }
            }

            // Si aucune commande n'est en cours on crée un nouvel OrderProduct
            else {
                $orderProduct = new OrderProduct;
                $orderProduct->setProductId($product);
            }

            // On envoie le formulaire pour le OrderProduct
            $form = $this->createForm(OrderProductType::class, $orderProduct);
            $form->handleRequest($request);
        
            // On vérifie la validité du formulaire
            if ($form->isSubmitted() && $form->isValid()) {            

                // Si aucune commande n'est en cours on en crée une
                if (!$orderProduct->getOrderId()) {
                    $order = new Order;
                    $order->setCreatedAt(new DateTimeImmutable("now"));
                    $order->setStatus(true);
                    $order->setUserId($user);
                    $em->persist($order);
                    $em->flush();
                    $orderProduct->setOrderId($order);
                }

                // On persiste le OrderProduct en base de donnée et on redirige l'utilisateur vers le panier
                $em->persist($orderProduct);
                $em->flush();
                return $this->redirectToRoute('app_cart');;
            }

            // Si aucun formulaire n'est envoyé on affiche la page avec la gestion du panier
            else {
                return $this->render('product/detail.html.twig', [
                'product' => $product,
                'form' => $form,
                'orderProduct' => $orderProduct
                ]);
            }
        }

        // Si aucun utilisateur n'est connecté, on affiche la page sans la gestion du panier
        return $this->render('product/detail.html.twig', [
            'product' => $product,
        ]);
    }

    // Renvoie la liste des produits et leur détail dans l'API
    #[Route('/api/products', name: 'app_api_products', methods: ['GET'])]
    public function api(ProductRepository $repository, SerializerInterface $serializer): JsonResponse
    {
        // On récupère tous les produits
        $products = $repository->findAll();

        // On sérialise les données
        $jsonProducts = $serializer->serialize($products, 'json', ['groups' => 'product:read']);

        // On retourne la réponse en JSON
        return new JsonResponse($jsonProducts, Response::HTTP_OK, [], true);
    }
}
