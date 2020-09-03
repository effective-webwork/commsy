<?php

namespace App\Controller;

use App\Services\LegacyEnvironment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LogoutController extends AbstractController
{
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @Route("/room/{roomId}/logout")
     * @param Request $request
     * @param LegacyEnvironment $environment
     * @return RedirectResponse
     */
    public function logoutAction(
        Request $request,
        LegacyEnvironment $environment
    ) {
        $legacyEnvironment = $environment->getEnvironment();

        $session = $legacyEnvironment->getSessionItem();
        $sessionId = $session->getSessionID();
        $cookie = $session->getValue('cookie');

        // restore root session
        if ($session->issetValue('root_session_id')) {
            $rootSessionId = $session->getValue('root_session_id');
        }

        $sessionManager = $legacyEnvironment->getSessionManager();
        $sessionManager->delete($sessionId, true);

        $session->reset();

        $portal = $legacyEnvironment->getCurrentPortalItem();

        $url = $this->urlGenerator->generate('app_helper_portalenter', [
            'context' => $portal->getItemId(),
        ]);

        // restore root session
        if (isset($rootSessionId)) {
            $session = $sessionManager->get($rootSessionId);
            $session->setValue('cookie',2);
            $legacyEnvironment->setSessionItem($session);
            if ($cookie != 1) {
                $url .= '&SID='.$rootSessionId;
            }
        }

        return $this->redirect($url);
    }
}
