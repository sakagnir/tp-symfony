<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Security\Roles;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($this->getUser()) {
            return $this->redirectToRoute('app_post_index');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/register', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $error = null;

        if ($request->isMethod('POST')) {
            $firstName = $request->get('firstName');
            $lastName = $request->get('lastName');
            $email = $request->get('email');

            $password = $request->get('password');
            $confirmPassword = $request->get('confirm_password');

            if ($password !== null && $password !== $confirmPassword) {
                $error = 'Les mots de passe ne correspondent pas.';
            } else {
                $existingUser = $entityManager
                    ->getRepository(User::class)
                    ->findOneBy(['email' => $request->get('email')]);

                if ($existingUser) {
                    $error = 'Un compte avec cet email exite déjà.';
                } else {
                    $user = new User();
                    $user->setEmail($email);
                    $user->setFirstName($firstName);
                    $user->setLastName($lastName);
                    $user->setCreatedAt(new \DateTimeImmutable('now'));

                    $hashedPassword = $passwordHasher->hashPassword($user, $password);
                    $user->setPassword($hashedPassword);

                    $entityManager->persist($user);
                    $entityManager->flush();

                    return $this->redirectToRoute('app_login');
                }
            }
        }

        return $this->render('security/register.html.twig', [
            'error' => $error,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/admin', name: 'app_admin_index')]
    public function adminIndex(EntityManagerInterface $entityManager): Response
    {
        return $this->render('security/admin/index.html.twig', [
            'posts' => $entityManager->getRepository(Post::class)->findAll(),
            'comments' => $entityManager->getRepository(Comment::class)->findAll(),
            'categories' => $entityManager->getRepository(Category::class)->findAll(),
            'users' => $entityManager->getRepository(User::class)->findAll(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/admin/users', name: 'app_user_index')]
    public function allUsers(EntityManagerInterface $entityManager)
    {
        $users = $entityManager->getRepository(User::class)->findAll();
        return $this->render('security/admin/users.html.twig', [
            'users'=> $users,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route(path: '/admin/users/{id}', name: 'app_user_toggle')]
    public function userToggle(EntityManagerInterface $entityManager, int $id)
    {
        $user = $entityManager->getRepository(User::class)->findOneBy(['id' => $id]);
        if($user->getStatut() == 'ACTIF'){
            $user->setStatut('DESACTIVE');
        } else {
            $user->setStatut('ACTIF');
        }
        $entityManager->persist($user);
        $entityManager->flush();
        return $this->redirectToRoute('app_user_index');
    }


    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
