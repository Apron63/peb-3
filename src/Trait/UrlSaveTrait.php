<?php

declare (strict_types=1);

namespace App\Trait;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

trait UrlSaveTrait
{
    private ?string $returnUrl;

    public function saveUrl(Request $request): void
    {
        $session = $request->getSession();
        $this->returnUrl = $session->get('returnUrl');

        $session->set('returnUrl', $request->headers->get('referer'));
    }

    public function getRedirectUrl(Request $request, string $defaultRoute): RedirectResponse
    {
        $session = $request->getSession();
        $this->returnUrl = $session->get('returnUrl');

        if (null !== $this->returnUrl) {
            $session->set('returnUrl', null);
            return $this->redirect($this->returnUrl);
        }

        return $this->redirectToRoute($defaultRoute);
    }
}
