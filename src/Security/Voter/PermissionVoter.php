<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Repository\PermissionRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class PermissionVoter extends Voter
{
    public function __construct(
        private PermissionRepository $permissionRepository
    )
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, ['VOIR', 'CREER', 'MODIFIER', 'SUPPRIMER', 'IMPRIMER', 'IMPORTER', 'EXPORTER']); # On ne s'occupe que des actions définies dans notre système
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null
    ): bool
    {
        /**
         * @var User
         */
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        /*
            - On détermine l'entité concernée
            - Le sujet peut être
                - Un objet entité '$typepiece'
                - Un string 'Typepiece' sensible à la casse
                - Un tableau d'objets
        */
        if (is_object($subject)) {
            $entityName = (new \ReflectionClass($subject))->getShortName();
        } elseif (is_string($subject)) {
            $entityName = $subject;
        } elseif (is_array($subject) && !empty($subject) && is_object($subject[0])) {
            $entityName = (new \ReflectionClass($subject[0]))->getShortName();
        } else {
            return false;
        }

        /* -- Si on veut optimisé les performances sinon si un utilisateur a 5 rôles il fera 5 requêtes
            $roles = array_map(
                fn($u) => $u->getRole(),
                $user->getUserRoles()->toArray()
            );
            return $this->permissionRepository->hasPermission(
                $roles,
                $entityName,
                $attribute,
                $user->getEntreprise()->getId()
            );
        */

        // -- On vérifie des permissions à travers les rôles utilisateur
        foreach($user->getUserRoles() as $userRole) {
            $role = $userRole->getRole();
            $permission = $this->permissionRepository->findOneBy([
                'role' => $role,
                'entity' => $entityName,
                'action' => strtoupper($attribute),
                'identreprise' => $user->getEntreprise()->getId() // Sinon si 2 entreprises ont le même nom de rôle on aura une collision
            ]);
            if ($permission) {
                return true;
            }
        }

        return false;
    }

}
