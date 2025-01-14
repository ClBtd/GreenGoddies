<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

// Listener visant à empêcher la connexion à l'API pour les utilisateurs ayant leur accès désactivé
class ApiAuthenticationListener implements EventSubscriberInterface
{

    // On récupère l'évèmenement d'une athentification réussie via JWT
    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess'
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        // On récupèrer l'utilisateur authentifié
        $user = $event->getUser();
        
        // On vérifie que son accès API est activé, sinon on renvoie une erreur et on empêche la génération du token
        if (!in_array('API_ACCESS', $user->getRoles(), true)) {
            $event->setData([
                'code' => 403,
                'message' => 'Accès API désactivé'
            ]);
            
            $event->stopPropagation();
            
            throw new AccessDeniedException('Accès API désactivé');
        }
    }
}