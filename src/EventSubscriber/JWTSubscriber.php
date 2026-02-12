<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JWTSubscriber implements EventSubscriberInterface
{
    public function onLexikJwtAuthenticationOnJwtCreated($event): void
    {
        /*
            dd(
                $event->getUser(), -- Permet de récupérer l'utilisateur de symfony
                $event->getData(), -- Les données qui vont être envoyés dans le payload
                $event->getHeader() -- Les en têtes
            );
        */
        $data = $event->getData();
        /**
         * @var User
         */
        $user = $event->getUser();
        // $data['exp'] = 162415455; -- Un teste pour expirer le token

        if(!$user instanceof User) {
            $event->setData($data);
        }

        $data['id'] = $user->getId();
        $data['nom'] = $user->getNom();
        if($user->getEntreprise()) {
            $data['entrepriseId'] = $user->getEntreprise()->getId();
        }

        $event->setData($data);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'lexik_jwt_authentication.on_jwt_created' => 'onLexikJwtAuthenticationOnJwtCreated',
        ];
    }
}
