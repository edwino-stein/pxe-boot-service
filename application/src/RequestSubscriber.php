<?php

namespace App;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;

class RequestSubscriber implements EventSubscriberInterface
{
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        $headers = $request->headers;
        if(!$headers->has('x-forwarded-prefix')) return;

        $requestUri = $request->getRequestUri();
        $prefix = $headers->get('x-forwarded-prefix');

        $this->router->getContext()->setBaseUrl(
            substr($prefix, 0, strlen($prefix) - strlen($requestUri))
        );
    }

    public static function getSubscribedEvents()
    {
        return [ 'kernel.request' => 'onKernelRequest'];
    }
}
