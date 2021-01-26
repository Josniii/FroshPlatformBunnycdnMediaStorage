<?php declare(strict_types=1);

namespace Frosh\BunnycdnMediaStorage\Listener;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ResponseListener
{
    /**
     * @var array
     */
    private $urls = [];

    public function __construct(
        string $FilesystemPublicUrl,
        string $FilesystemThemeUrl,
        string $FilesystemAssetsUrl
    )
    {
        $this->urls[] = $this->getHost($FilesystemPublicUrl);
        $this->urls[] = $this->getHost($FilesystemThemeUrl);
        $this->urls[] = $this->getHost($FilesystemAssetsUrl);

        $this->urls = array_unique($this->urls);
    }

    /**
     * @param ResponseEvent $event
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $response = $event->getResponse();

        if ($response instanceof BinaryFileResponse ||
            $response instanceof StreamedResponse) {
            return;
        }

        if (strpos($response->headers->get('Content-Type', ''), 'text/html') === false) {
            return;
        }

        $mainHost = $event->getRequest()->getHost();

        foreach ($this->urls as $url) {
            if ($url === $mainHost) {
                continue;
            }

            $response->headers->add(['Link' => '<//' . $url . '>; rel="dns-prefetch"']); //for Firefox
            $response->headers->add(['Link' => '<//' . $url . '>; rel="preconnect"']);
        }
    }

    private function getHost(string $url) {
        return parse_url($url, PHP_URL_HOST);
    }
}
