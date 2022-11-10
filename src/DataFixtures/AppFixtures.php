<?php

namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\User;
use App\Entity\Author;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture {
    private $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher) {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Création d'un user "normal"
        $user = new User();
        $user->setEmail("user@bookapi.com");
        $user->setRoles(["ROLE_USER"]);
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "password"));
        $manager->persist($user);
        // Création d'un user admin
        $userAdmin = new User();
        $userAdmin->setEmail("admin@bookapi.com");
        $userAdmin->setRoles(["ROLE_ADMIN"]);
        $userAdmin->setPassword($this->userPasswordHasher->hashPassword($userAdmin, "password")); //hash le mdp
        $manager->persist($userAdmin);

        $listAuthor =[];
            //Création de l'auteur
            for ($i = 0; $i < 10; $i++) {
                $author = new Author;
                $author->setFirstName('Prénom ' . $i);
                $author->setLastName('Nom ' . $i);
                $manager->persist($author);
                //On sauvegarde l'auteur créé dans un tableau
                $listAuthor[]=$author;
            }


            // Création d'une vingtaine de livres ayant pour titre
            for ($i = 0; $i < 20; $i++) {
                $livre = new Book;
                $livre->setTitle('Livre ' . $i);
                $livre->setCoverText('Quatrième de couverture numéro : ' . $i);


                $livre->setComment("Commentaire du bibliothécaire " . $i);
                $livre->setAuthor($listAuthor[array_rand($listAuthor)]);//Viens prendre un author aléatoirement
                $manager->persist($livre);
            }
        $manager->flush();
    }
}