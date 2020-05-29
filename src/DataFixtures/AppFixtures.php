<?php

namespace App\DataFixtures;

use App\Entity\Admin;
use App\Entity\Banned;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $admin = new User();
        $banned = new Banned();
        $banned->setNbBannis(0);
        $admin
            ->setUsername("admin")
            ->setEmail("admin@gmail.com")
            ->setEmailCanonical("admin@gmail.com")
            ->setEnabled(1)
            ->setCouleurFond('#4ecdc4')
            ->setCouleurMenu('#7bdff2')
            ->setPassword("admin")
            ->setRoles((array)"ROLE_SUPER_ADMIN")
            ->setNom("Nom-Admin")
            ->setPrenom("Prenom-Admin")
            ->setMdp("admin")
            ->setEstActive(1)
            ->setEstBanni(0)
            ->setMailTokenVerification("0")
            ->setMail('admin@gmail.com')
            ->setPseudo("admin");

        $manager->persist($banned);
        $manager->persist($admin);
        $manager->flush();
    }
}
