<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use App\Repository\OrderRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class OrderController extends AbstractController
{
    // Affichage du panier utilisateur
    #[Route('/cart', name: 'app_cart')]
    #[IsGranted('ROLE_USER', message:'Vous devez être connectés pour accéder à cette page.')]
    public function cart(OrderRepository $orderRepository, Security $security, EntityManagerInterface $em): Response
    {
        // On récupère l'utilisateur connecté
        $user = $security->getUser();
        
        // On récupère sa commande en cours 
        $order = $orderRepository->findPendingOrderByUser($user);

        // S'il y a une commande en cours on en calcule le prix total et on le persiste en base de donnée
        if (isset($order)) {
            $totalPrice = 0;

            foreach ($order->getOrderProducts() as $product) {
                $totalPrice = $totalPrice + ($product->getProductId()->getPrice() * $product->getQuantity());
            }
        
            $order->setTotalPrice($totalPrice);
            $em->persist($order);
            $em->flush();
        }

        return $this->render('order/cart.html.twig', [
            'order' => $order,
        ]);
    }

    // Suppression du panier et donc de la commande en cours
    #[Route('/cart/delete/{id}', name: 'app_cart_delete')]
    #[IsGranted('ROLE_USER', message:'Vous devez être connectés pour accéder à cette page.')]
    public function deleteCart (Order $order, EntityManagerInterface $em): Response
    {
        $em->remove($order);
        $em->flush();
        
        return $this->redirectToRoute('app_cart');
    }

    // Validation de la commande
    #[Route('cart/validate/{id}', name: 'app_cart_validate')]
    #[IsGranted('ROLE_USER', message:'Vous devez être connectés pour accéder à cette page.')]
    public function validateOrder(Order $order, EntityManagerInterface $em): Response
    {
        // On change le statut de la commande et son DateTime pour avoir sa date de validation
        $order->setStatus(false);
        $order->setCreatedAt(new DateTimeImmutable("now"));

        $em->persist($order);
        $em->flush();

        $this->addFlash('success', 'La commande a été validée.');
        return $this->redirectToRoute('app_account');
    }

    // Affichage du compte utilisateur
    #[Route('/account', name: 'app_account')]
    #[IsGranted('ROLE_USER', message:'Vous devez être connectés pour accéder à cette page.')]
    public function account(OrderRepository $orderRepository, Security $security): Response
    {
        // On récupère l'utilisateur connecté et ses commandes validées
        $user = $security->getUser();
        $orders = $orderRepository->findCompletedOrdersByUser($user);

        return $this->render('order/account.html.twig', [
            'orders' => $orders,
        ]);
    }

    // Modification de l'accès API
    #[Route('/account/status/{id}', name: 'app_account_status')]
    #[IsGranted('ROLE_USER', message:'Vous devez être connectés pour accéder à cette page.')]
    public function apiStatus(User $user, EntityManagerInterface $em, TokenStorageInterface $tokenStorage): Response
    {
        //On récupère les rôles de l'utilisateur
        $roles = $user->getRoles();

        // On vérifie si le rôle d'accès est attribué, si c'est le cas on l'enlève, sinon on l'ajoute
        if (in_array('API_ACCESS', $roles)) {
            $roles = array_diff($roles, ['API_ACCESS']);
        } else {
            $roles[] = 'API_ACCESS';
        }
        $user->setRoles($roles);

        $em->persist($user);
        $em->flush();

        // On récupère le token afin d'éviter la déconnexion à la modification des rôles
        $token = $tokenStorage->getToken();
        if ($token) {
            $newToken = new UsernamePasswordToken($user, 'main', $user->getRoles());
            $tokenStorage->setToken($newToken);
        }

        return $this->redirectToRoute('app_account');
    }

    //Suppression du compte utilisateur
    #[Route('/account/delete/{id}', name: 'app_account_delete')]
    #[IsGranted('ROLE_USER', message:'Vous devez être connectés pour accéder à cette page.')]
    public function deleteAccount(User $user, EntityManagerInterface $em): Response
    {
        $em->remove($user);
        $em->flush();
        
        return $this->redirectToRoute('app_product');
    }
}
