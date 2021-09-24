<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class UserController extends AbstractController
{
    /**
     * @Route("/users", name="user_list")
     *
     * @IsGranted("ROLE_ADMIN", message="Tu dois te connecter en tant qu'administrateur pour conulter cette page.")
     */
    public function listAction(UserRepository $userRepository)
    {
        return $this->render(
            'user/list.html.twig',
            ['users' => $userRepository->findAll()]
        );
    }

    /**
     * @Route("/users/create", name="user_create")
     */
    public function createAction(Request $request, UserPasswordHasherInterface $hasher, EntityManagerInterface $em)
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $hasher->hashPassword($user, $request->request->get('user')['password']['first']);
            $user->setPassword($password);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', "L'utilisateur a bien été ajouté.");

            return $this->redirectToRoute('user_list');
        }

        return $this->render('user/create.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/users/{id}/edit", name="user_edit")
     *
     * @IsGranted("ROLE_USER", subject="user", message="Tu peux modifier que ton propre compte.")
     */
    public function editAction(User $user, Request $request, UserPasswordHasherInterface $hasher, EntityManagerInterface $em)
    {
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $hasher->hashPassword($user, $request->request->get('user')['password']['first']);
            $user->setPassword($password);

            $em->flush();

            $this->addFlash('success', "L'utilisateur a bien été modifié");

            return $this->redirectToRoute('user_list');
        }

        return $this->render('user/edit.html.twig', ['form' => $form->createView(), 'user' => $user]);
    }

    /**
     * @Route("/users/{id}/delete", name="user_delete")
     *
     * @IsGranted("ROLE_ADMIN", subject="user", message="Tu ne peux pas supprimer des utilisateurs.")
     */
    public function deleteUserAction(User $user, EntityManagerInterface $em)
    {
        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'L\'utilisateur a bien été supprimé.');

        return $this->redirectToRoute('user_list');
    }
}
