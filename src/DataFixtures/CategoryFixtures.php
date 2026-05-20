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
            $category->setName($faker->name);
            $category->setDescription($faker->name);

            $manager->persist($category);
        }

        $manager->flush();
    }
}
