<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Http\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spiral\Http\Configs\HttpConfig;
use Spiral\Http\Cookies\Cookie;

/**
 * Provides generic CSRF protection using cookie as token storage. Set "csrfToken" attribute to
 * request.
 *
 * Do not use middleware without CookieManager at top!
 *
 * @see https://www.owasp.org/index.php/Cross-Site_Request_Forgery_(CSRF)_Prevention_Cheat_Sheet#Double_Submit_Cookie
 */
class CsrfMiddleware implements MiddlewareInterface
{
    const ATTRIBUTE = 'csrfToken';

    /** @var HttpConfig */
    protected $config = null;

    /**
     * @param HttpConfig $config
     */
    public function __construct(HttpConfig $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if (isset($request->getCookieParams()[$this->config->csrfCookie()])) {
            $token = $request->getCookieParams()[$this->config->csrfCookie()];
        } else {
            //Making new token
            $token = $this->random($this->config->csrfLength());

            //Token cookie!
            $cookie = $this->tokenCookie($request->getUri(), $token);
        }

        //CSRF issues must be handled by Firewall middleware
        $response = $handler->handle($request->withAttribute(static::ATTRIBUTE, $token));

        if (!empty($cookie)) {
            return $response->withAddedHeader('Set-Cookie', (string)$cookie);
        }

        return $response;
    }

    /**
     * Create a random string with desired length.
     *
     * @param int $length String length. 32 symbols by default.
     * @return string
     */
    private function random(int $length = 32): string
    {
        try {
            if (empty($string = random_bytes($length))) {
                throw new \RuntimeException("Unable to generate random string");
            }
        } catch (\Throwable $e) {
            throw new \RuntimeException("Unable to generate random string", $e->getCode(), $e);
        }

        return substr(base64_encode($string), 0, $length);
    }

    /**
     * Generate CSRF cookie.
     *
     * @param UriInterface $uri Incoming uri.
     * @param string       $token
     *
     * @return Cookie
     */
    protected function tokenCookie(UriInterface $uri, string $token): Cookie
    {
        return Cookie::create(
            $this->config->csrfCookie(),
            $token,
            $this->config->csrfLifetime(),
            $this->config->basePath(),
            $this->config->cookieDomain($uri),
            $this->config->csrfSecure(),
            true
        );
    }
}