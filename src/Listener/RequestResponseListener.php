<?php

namespace App\Listener;

use Exception;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouterInterface;

class RequestResponseListener
{
    private array $compressionRoutesToExclude = [
        '_wdt'
    ];

    private array $compressionClassesToExclude = [
        StreamedResponse::class,
        BinaryFileResponse::class
    ];

    public function __construct(private readonly RouterInterface $router) {
    }


    public function onKernelRequest(RequestEvent $event): void {
        $request = $event->getRequest();
        $this->trimRequest($request);
    }

    private function trimRequest(Request $request): void {
        if (!$request->attributes->has('ignoreTrimming') && !$request->request->has('ignoreTrimming') && !$request->query->has('ignoreTrimming')) {
            foreach ([$request->request, $request->query] as $item) {
                foreach ($item->all() as $field => $fieldValue) {
                    if (is_string($fieldValue) && trim($fieldValue) !== $fieldValue) {
                        $item->set($field, trim($fieldValue));
                    }
                }
            }
        }
    }

    /**
     * @param ResponseEvent $event
     * @throws Exception
     */
    public function onKernelResponse(ResponseEvent $event): void {
        $this->xhrRedirectResponse($event);
        $allowCompression = $this->compressionAllowed($event);
        if ($allowCompression) {
            $this->compressResponse($event);
        }
    }

    private function xhrRedirectResponse(ResponseEvent $event): void {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if ($request->getUser() === null && $response->getStatusCode() === Response::HTTP_UNAUTHORIZED) {
            $loginRoute = $this->router->generate('login');
            if ($request->isXmlHttpRequest()) {
                $response = new JsonResponse(
                    [
                        'redirect' => $loginRoute,
                        'alerts' => [['type' => 'error', 'text' => 'You are no longer authenticated, please log in again, redirecting...']]
                    ],
                    Response::HTTP_UNAUTHORIZED);
            } else {
                $response = new RedirectResponse($loginRoute);
            }
            $event->setResponse($response);
            return;
        }

        if (!$request->isXmlHttpRequest()) {
            return;
        }

        if ($response->isRedirection() || $response->getStatusCode() === Response::HTTP_MISDIRECTED_REQUEST) {
            $location = null;
            try {
                $content = json_decode($response->getContent(), true);
                if (isset($content['redirect']) && !empty($content['redirect'])) {
                    $location = $content['redirect'];
                }
//                if (isset($content['alerts']) && !empty($content['alerts'])) {
//                    foreach ($content['alerts'] as $alert) {
//                        $this->getJsonResponseData()->addAlert($alert['type'], $alert['text']);
//                    }
//                }
            } catch (Exception $exception) {

            }
            $location = $response->headers->get('location', $location);

            if (str_contains($location, '/login')) {
                $response = new JsonResponse(
                    [
                        'redirect' => $location,
                        'alerts' => [['type' => 'error', 'text' => 'You are no longer authenticated, please log in again, redirecting...']]
                    ],
                    Response::HTTP_UNAUTHORIZED);
                $event->setResponse($response);
            } elseif (trim($location, '/') !== trim($request->getUri(), '/')) {
                $response = new JsonResponse(
                    [
                        'redirect' => $location,
                        'alerts' => [['type' => 'warning', 'text' => 'Redirecting...']]
                    ],
                    Response::HTTP_MISDIRECTED_REQUEST);
                $event->setResponse($response);
            }
        }
    }

    private function compressionAllowed(ResponseEvent $event): bool {
        if ($event->getRequestType() !== HttpKernelInterface::MAIN_REQUEST) {
            return false;
        }
        $request = $event->getRequest();
        if (!$request->isXmlHttpRequest()) {
            return false;
        }
        $fos = $request->attributes->get('_fos_rest_zone', false);
        $controller = $request->attributes->get('_controller');
        $route = $request->attributes->get('_route');
        if ($fos || in_array($route, $this->compressionRoutesToExclude, true) || str_contains('website', strtolower($controller))) {
            return false;
        }
        $response = $event->getResponse();
        if ($response->isRedirection()) {
            return false;
        }
        if ($response->getStatusCode() === Response::HTTP_MISDIRECTED_REQUEST) {
            return false;
        }
        return true;
    }

    /**
     * @param ResponseEvent $event
     * @throws Exception
     */
    private function compressResponse(ResponseEvent $event): void {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $encodings = $request->getEncodings();
        $content = $response->getContent();
        if (
            !empty($content) &&
            function_exists('gzencode') &&
            in_array('gzip', $encodings, true) &&
            !in_array(get_class($response), $this->compressionClassesToExclude, true)
        ) {
            $content = gzencode($content);
            $response->setContent($content);
            $response->headers->set('Content-encoding', 'gzip');
        } elseif (
            !empty($content) &&
            function_exists('gzdeflate') &&
            in_array('deflate', $encodings, true) &&
            !in_array(get_class($response), $this->compressionClassesToExclude, true)
        ) {
            $content = gzdeflate($content);
            $response->setContent($content);
            $response->headers->set('Content-encoding', 'deflate');
        }
    }
}
