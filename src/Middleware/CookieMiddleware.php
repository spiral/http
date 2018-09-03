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
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spiral\Core\ScopeInterface;
use Spiral\Encrypter\EncrypterInterface;
use Spiral\Encrypter\Exceptions\DecryptException;
use Spiral\Http\Configs\HttpConfig;
use Spiral\Http\Cookies\Cookie;
use Spiral\Http\Cookies\Queue;

/**
 * Middleware used to encrypt and decrypt cookies. Creates container scope for a cookie bucket.
 *
 * Attention, EncrypterInterface is requested from container on demand.
 */
class CookieMiddleware implements MiddlewareInterface
{
    /** @var HttpConfig */
    private $config = null;

    /** @var ScopeInterface */
    private $scope = null;

    /** @var EncrypterInterface */
    private $encrypter = null;

    /**
     * @param HttpConfig         $config
     * @param ScopeInterface     $scope
     * @param EncrypterInterface $encrypter
     */
    public function __construct(HttpConfig $config, ScopeInterface $scope, EncrypterInterface $encrypter)
    {
        $this->config = $config;
        $this->scope = $scope;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        //Aggregates all user cookies
        $queue = new Queue(
            $this->config->cookieDomain($request->getUri()),
            $request->getUri()->getScheme() == "https"
        );

        $response = $this->scope->runScope([], function () use ($request, $handler, $queue) {
            return $handler->handle(
                $this->unpackCookies($request)->withAttribute(Queue::ATTRIBUTE, $queue)
            );
        });

        return $this->packCookies($response, $queue);
    }

    /**
     * Unpack incoming cookies and decrypt their content.
     *
     * @param Request $request
     * @return Request
     */
    protected function unpackCookies(Request $request): Request
    {
        $cookies = $request->getCookieParams();

        foreach ($cookies as $name => $cookie) {
            if (!$this->isProtected($name)) {
                //Nothing to protect
                continue;
            }

            $cookies[$name] = $this->decodeCookie($cookie);
        }

        return $request->withCookieParams($cookies);
    }

    /**
     * Check if cookie has to be protected.
     *
     * @param string $cookie
     *
     * @return bool
     */
    protected function isProtected(string $cookie): bool
    {
        if (in_array($cookie, $this->config->cookiesExcluded())) {
            //Excluded
            return false;
        }

        return $this->config->cookieProtection() != HttpConfig::COOKIE_UNPROTECTED;
    }

    /**
     * @param string|array $cookie
     *
     * @return array|mixed|null
     */
    private function decodeCookie($cookie)
    {
        if ($this->config->cookieProtection() == HttpConfig::COOKIE_ENCRYPT) {
            try {
                if (is_array($cookie)) {
                    return array_map([$this, 'decodeCookie'], $cookie);
                }

                return $this->encrypter->decrypt($cookie);
            } catch (DecryptException $exception) {
                return null;
            }
        }

        //HMAC
        $hmac = substr($cookie, -1 * HttpConfig::MAC_LENGTH);
        $value = substr($cookie, 0, strlen($cookie) - strlen($hmac));

        if ($this->hmacSign($value) != $hmac) {
            return null;
        }

        return $value;
    }

    /**
     * Sign string.
     *
     * @param string|null $value
     *
     * @return string
     */
    private function hmacSign($value): string
    {
        return hash_hmac(HttpConfig::HMAC_ALGORITHM, $value, $this->encrypter->getKey());
    }

    /**
     * Pack outcoming cookies with encrypted value.
     *
     * @param Response $response
     * @param Queue    $queue
     *
     * @return Response
     *
     * @throws \Spiral\Encrypter\Exceptions\EncryptException
     */
    protected function packCookies(Response $response, Queue $queue): Response
    {
        if (empty($queue->getScheduled())) {
            return $response;
        }

        $cookies = $response->getHeader('Set-Cookie');

        foreach ($queue->getScheduled() as $cookie) {
            if (!$this->isProtected($cookie->getName()) || empty($cookie->getValue())) {
                $cookies[] = $cookie->createHeader();
                continue;
            }

            $cookies[] = $this->encodeCookie($cookie)->createHeader();
        }

        return $response->withHeader('Set-Cookie', $cookies);
    }

    /**
     * @param Cookie $cookie
     *
     * @return Cookie
     */
    private function encodeCookie(Cookie $cookie): Cookie
    {
        if ($this->config->cookieProtection() == HttpConfig::COOKIE_ENCRYPT) {
            return $cookie->withValue(
                $this->encrypter->encrypt($cookie->getValue())
            );
        }

        //VALUE.HMAC
        return $cookie->withValue($cookie->getValue() . $this->hmacSign($cookie->getValue()));
    }
}