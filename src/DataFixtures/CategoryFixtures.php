<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class CategoryFixtures implements FixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        for ($i = 0; $i < 20; $i++) {
            $category = new Category();
            $category->setName($faker->sentence(rand(1, 3)));
            $category->setDescription($faker->text(rand(300, 400)));

            $manager->persist($category);
        }

        $manager->flush();
    }
}
