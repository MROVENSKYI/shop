<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $products = [
            ['name' => 'Kettle', 'price' => 500],
            ['name' => 'Refrigerator', 'price' => 20000],
            ['name' => 'TV', 'price' => 12000],
        ];

        foreach ($products as $p) {
            $product = new Product();
            $product
                ->setName($p['name'])
                ->setPrice($p['price']);

            $manager->persist($product);
        }

        $manager->flush();
    }
}
