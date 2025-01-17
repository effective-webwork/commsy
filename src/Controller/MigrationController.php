<?php

namespace App\Controller;

use App\Entity\Account;
use App\Form\Model\NewPassword;
use App\Form\Type\PasswordMigrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class MigrationController extends AbstractController
{
    /**
     * @Route("/migration/password")
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse|Response
     */
    public function password(
        Request $request,
        UserPasswordEncoderInterface $passwordEncoder,
        EntityManagerInterface $entityManager
    ) {
        $newPasswordData = new NewPassword();
        $form = $this->createForm(PasswordMigrationType::class, $newPasswordData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Account $user */
            $user = $this->getUser();
            $user->setPasswordMd5(null);
            $user->setPassword($passwordEncoder->encodePassword($user, $newPasswordData->getPassword()));

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_logout');
        }

        return $this->render('migration/password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
