<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Entity\UserRole;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
        private Security $security,
        private EntrepriseRepository $entrepriseRepository
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var User $data */

        /**
         * @var User
         */
        $currentUser = $this->security->getUser();
        $entrepriseId = $currentUser->getEntrepriseid();
        $entreprise = $this->entrepriseRepository->find($entrepriseId);

        if($operation instanceof Post) {
        }

        if($operation instanceof Patch) {
        }

        $data->setEntreprise($entreprise); // On lui affecte l'entreprise de l'utilisateur connecté 

        if(!empty($data->getPlainPassword())) {
            $data->setPassword(
                $this->hasher->hashPassword(
                    $data,
                    $data->getPlainPassword()
                )
            );
            $data->setPlainPassword(null); // Permet d'éviter de laisser des données sensibles comme le mot de passe en clair en mémoire
        }

        if($data->getId()) {
            $existingRoles = $this->em->getRepository(UserRole::class)->findBy([
                'usere' => $data
            ]);
            foreach($existingRoles as $existing) { // On supprime les anciens 'UserRole' de l'utilisateur ou avoir le 'orphanRemoval: true' et 'cascade: ['persist', 'remove']' sur le 'OneToMany'
                $this->em->remove($existing);
            }
        }

        foreach($data->getUserRoles() as $userRole) {
            if (!$userRole->getRole()) {
                continue; /*
                    - Pour éviter un rôle 'null' ou '{}' et on peut 'throw' une exception 'BadRequestHttpException'
                */
            } 

            $userRole->setUsere($data);
            $userRole->setIdentreprise($entreprise->getId());
            $this->em->persist($userRole);

            # Si un rôle accès a tous on met 'User Admin'
            if($userRole->getRole()->getTyperole() === 'ROLE_ADMIN') {
                $data->setRoles(['ROLE_ADMIN']);
            } else {
                $data->setRoles(['ROLE_USER']);
            }
        }

        /*
        Très important avec API Platform

En PATCH :

$data = entité déjà existante

$context['previous_data'] = ancienne version

Si tu modifies l’email :

Doctrine compare les changements et fait un UPDATE.

Si l’email existe ailleurs → boom.


Ou encore plus simple :

👉 Supprime complètement persist()
👉 Laisse PersistProcessor faire le travail

Car API Platform gère déjà ça ici :

ApiPlatform\Doctrine\Common\State\PersistProcessor


            if(!$context['previous_data']) { - Si l'entité n'existe pas en d'abord
                $this->em->persist($data);
            }
            // $this->em->persist($data);
        */

        return $this->processor->process($data, $operation, $uriVariables, $context); /*
            - Pas de '->flush()' vu qu'on a le 'process'
        */
    }
}
