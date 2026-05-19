<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Post;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class PostFixtures implements FixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $categories = $manager->getRepository(Category::class)->findAll();

        if (count($categories) === 0) {
            throw new \RuntimeException('Отсутствуют категории');
        }

        for ($i = 1; $i <= 10000; $i++) {
            $post = new Post();
            $post
                ->setTitle($faker->name)
                ->setDescription($faker->name)
                ->setContent($faker->text(1500))
                ->setCreatedAt($faker->dateTimeBetween('-2 years', 'now'))
                ->setViews($faker->numberBetween(2, 100))
                ->setImage($faker->imageUrl(640, 480, 'cats', true, 'Faker'))
            ;

            $categoriesCount = $faker->numberBetween(1, min(4, count($categories)));
            $randCategories = $faker->randomElements($categories, $categoriesCount);

            foreach ($randCategories as $cat) {
                $post->addCategory($cat);
            }

            $manager->persist($post);

            if ($i % 100 === 0) {
                $manager->flush();
                $manager->clear();
                $categories = $manager->getRepository(Category::class)->findAll();
            }
        }

        $manager->flush();
    }
}
