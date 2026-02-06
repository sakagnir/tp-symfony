<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Category;
use App\Entity\Post;
use App\Entity\Comment;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $users = [];

        // Admin
        $admin = new User();
        $admin->setEmail('admin@example.com')
            ->setFirstName('Admin')
            ->setLastName('Super')
            ->setStatut('ACTIF')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'password'));
        $manager->persist($admin);
        $users[] = $admin;

        // 3 users
        for ($i = 1; $i <= 3; $i++) {
            $user = new User();
            $user->setEmail(sprintf('user%d@example.com', $i))
                ->setFirstName('User'.$i)
                ->setLastName('Demo')
                ->setStatut('DESACTIVE')
                ->setCreatedAt(new \DateTimeImmutable());
            $user->setPassword($this->hasher->hashPassword($user, 'password'));
            $manager->persist($user);
            $users[] = $user;
        }

        // 5 categories, 2 posts each (10 posts)
        $posts = [];
        $postCount = 0;
        for ($c = 1; $c <= 5; $c++) {
            $category = new Category();
            $category->setName('Category '.$c);
            $manager->persist($category);

            for ($p = 1; $p <= 2; $p++) {
                $postCount++;
                $post = new Post();
                $post->setTitle(sprintf('Post %d - %s', $postCount, $category->getName()));
                $post->setContent('Contenu de l\'article '.$postCount.'. Ceci est un texte de démonstration.');
                $post->setPublishedAt(new \DateTimeImmutable('-'.rand(0, 365).' days'));
                $post->setPicture('https://picsum.photos/seed/'.$postCount.'/800/400');
                // assign a random user (including admin)
                $post->setUser($users[array_rand($users)]);
                $post->setCategory($category);
                $manager->persist($post);
                $posts[] = $post;
            }
        }

        // 3 comments per post
        foreach ($posts as $idx => $post) {
            for ($k = 1; $k <= 3; $k++) {
                $comment = new Comment();
                $comment->setContent(sprintf('Commentaire %d pour %s', $k, $post->getTitle() ?? ''));
                $comment->setCreatedAt(new \DateTimeImmutable('-'.rand(0, 365).' days'));
                // random user for comment
                $comment->setUser($users[array_rand($users)]);
                $comment->setPost($post);
                $manager->persist($comment);
            }
        }

        $manager->flush();
    }
}
// <?php

// namespace App\DataFixtures;

// use App\Entity\Category;
// use App\Entity\Post;
// use Doctrine\Bundle\FixturesBundle\Fixture;
// use Doctrine\Persistence\ObjectManager;

// class AppFixtures extends Fixture
// {
//     public function load(ObjectManager $manager): void
//     {
//         // $product = new Product();
//         // $manager->persist($product);

//         for ($i = 1; $i < 10; $i++) {
//             $category = new Category();
//             $category->setName("category_$i");
//             $manager->persist($category);
//             for ($f = 0; $f < 2; $f++) {
//                 $post = new Post();
//                 $post->setTitle("post_$f");
//                 $post->setContent("Lorem ipsum dolor sit, amet consectetur adipisicing elit.
//                 Ex incidunt fuga ea placeat ipsum itaque.");
//                 $post->setCategory($category);
//                 $post->setCreatedAt(new \DateTimeImmutable("now"));
//                 $post->setUpdatedAt(new \DateTimeImmutable("now"));
//                 $manager->persist($post);
//             }
//         }

//         $manager->flush();
//     }
// }
