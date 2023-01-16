<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Clients;
use App\Entity\Product;
use App\Entity\Users;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // Fixtures liées au clients
        $listClients = [];
        for ($i = 0; $i  < 5; $i++) { 
            $clients = new Clients();
            $clients->setEmail($faker->email());
            $clients->setRoles(['ROLE_CLIENT']);
            $clients->setPassword($this->passwordHasher->hashPassword($clients, "password"));
            $clients->setName($faker->name());
            $clients->setCreatedAt(new \DateTimeImmutable('Europe/Paris'));
            $manager->persist($clients);
            // On sauvegarde le client créer dans un tableau
            $listClients[] = $clients;
        }

        // Fixtures liées au users
        for ($i = 0; $i < 20; $i++) { 
            $user = new Users();
            $user->setEmail($faker->email());
            $user->setLastName($faker->lastname());
            $user->setFirstName($faker->firstname());
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $user->setCreatedAt(new \DateTimeImmutable('Europe/Paris'));
            $user->setClient($listClients[array_rand($listClients)]);
            $manager->persist($user);
        }

        // Fixtures liées au produits
        $date = new \DateTime('Europe/Paris');
        $marques = ["Apple", "Samsung", "Huawei", "LG"];
        $os = ['IOS', 'Android'];
        $price = [500, 1000, 1500];

        for ($i = 0; $i < 20; $i++) { 
            $product = new Product();
            $product->setName('mobile nº: ' . $i);
            $product->setBrand($marques[array_rand($marques)]);
            $product->setReleaseDate($date);
            $product->setOperatingSystem($os[array_rand($os)]);
            $product->setPrice($price[array_rand($price)]);
            $manager->persist($product);
        }

        $manager->flush();
    }
}
