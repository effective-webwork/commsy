<?php

namespace App\Controller;

use App\Entity\Portal;
use App\Entity\RoomPrivat;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{

    /**
     * @Route("/test")
     * @Template(template="portal/login.html.twig")
     */
    public function test() {
        return [];
    }
    /**
     * @Route("/login/{context}", name="app_login")
     * @param AuthenticationUtils $authenticationUtils
     * @param EntityManagerInterface $entityManager
     * @param string $context
     * @return Response
     */
    public function login(
        AuthenticationUtils $authenticationUtils,
        EntityManagerInterface $entityManager,
        string $context = 'server'
    ): Response {
        // Redirect if the user is already logged in
        $currentUser = $this->getUser();
        if ($currentUser) {
            $privateRoom = $entityManager->getRepository(RoomPrivat::class)
                ->findByContextIdAndUsername($context, $currentUser->getUsername());

            // If unable to find a private room this is very likely a root login (which does not own a private room)
            // so we will redirect to the list of all portals
            if (!$privateRoom) {
                return $this->redirectToRoute('app_server_show');
            }

            return $this->redirectToRoute('app_dashboard_overview', [
                'roomId' => $privateRoom->getItemId(),
            ]);
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($context !== 'server') {
            $portal = $entityManager->getRepository(Portal::class)->find($context);
            if (!$portal) {
                throw $this->createNotFoundException('Portal not found');
            }
        } else {
            $lastUsername = 'root';
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'context' => $context
        ]);
    }

    /**
     * @Route("/logout", name="app_logout", methods={"GET"})
     * @throws Exception
     */
    public function logout()
    {
        // controller can be blank: it will never be executed!
        throw new Exception('Don\'t forget to activate logout in security.yaml');
    }
}
