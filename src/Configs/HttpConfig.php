<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Http\Configs;

use Psr\Http\Message\UriInterface;
use Spiral\Core\Container\Autowire;
use Spiral\Core\InjectableConfig;
use Spiral\Http\Middleware\CookieMiddleware;
use Spiral\Http\Middleware\CsrfMiddleware;

class HttpConfig extends InjectableConfig
{
    const CONFIG = 'http';

    /**
     * Cookie protection methods.
     */
    const COOKIE_UNPROTECTED = 0;
    const COOKIE_ENCRYPT = 1;
    const COOKIE_HMAC = 2;

    /**
     * Algorithm used to sign cookies.
     */
    const HMAC_ALGORITHM = 'sha256';

    /**
     * Generated MAC length, has to be stripped from cookie.
     */
    const MAC_LENGTH = 64;

    /**
     * @var array
     */
    protected $config = [
        'basePath'   => '/',
        'headers'    => [
            'Content-Type' => 'text/html; charset=UTF-8'
        ],
        'middleware' => [CookieMiddleware::class, CsrfMiddleware::class],
        'cookies'    => [
            'domain'   => '.%s',
            'method'   => self::COOKIE_ENCRYPT,
            'excluded' => ['PHPSESSID', 'csrf-token']
        ],
        'csrf'       => [
            'cookie'   => 'csrf-token',
            'length'   => 16,
            'lifetime' => 86400
        ]
    ];

    /**
     * @return string
     */
    public function basePath(): string
    {
        return $this->config['basePath'];
    }

    /**
     * Initial set of headers.
     *
     * @return array
     */
    public function baseHeaders(): array
    {
        return $this->config['headers'];
    }

    /**
     * Initial middleware set.
     *
     * @return array|Autowire[]
     */
    public function baseMiddleware(): array
    {
        return $this->config['middleware'] ?? $this->config['middlewares'];
    }

    /**
     * Return domain associated with the cookie.
     *
     * @param UriInterface $uri
     *
     * @return string|null
     */
    public function cookieDomain(UriInterface $uri): ?string
    {
        $host = $uri->getHost();
        if (empty($host)) {
            return null;
        }

        $pattern = $this->config['cookies']['domain'];
        if (filter_var($host, FILTER_VALIDATE_IP) || $host == 'localhost') {
            //We can't use sub-domains when website required by IP
            $pattern = ltrim($pattern, '.');
        }

        if (!empty($port = $uri->getPort())) {
            $host = $host . ':' . $port;
        }

        if (strpos($pattern, '%s') === false) {
            //Forced domain
            return $pattern;
        }

        return sprintf($pattern, $host);
    }

    /**
     * Cookie protection method.
     *
     * @return int
     */
    public function cookieProtectionMethod(): int
    {
        return $this->config['cookies']['method'];
    }

    /**
     * Cookies excluded from protection.
     *
     * @return array
     */
    public function cookiesExcluded(): array
    {
        return $this->config['cookies']['excluded'];
    }

    /**
     * @return string
     */
    public function csrfCookie(): string
    {
        return $this->config['csrf']['cookie'];
    }

    /**
     * @return int
     */
    public function csrfLength(): int
    {
        return $this->config['csrf']['length'];
    }

    /**
     * @return int|null
     */
    public function csrfLifetime(): ?int
    {
        return $this->config['csrf']['lifetime'] ?? null;
    }

    /**
     * @return bool
     */
    public function csrfSecure(): bool
    {
        return !empty($this->config['csrf']['secure']);
    }
}