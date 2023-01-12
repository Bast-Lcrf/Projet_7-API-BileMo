<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Product;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
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
