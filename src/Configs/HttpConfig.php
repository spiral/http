<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Http\Configs;

use Psr\Http\Message\UriInterface;
use Spiral\Core\InjectableConfig;

class HttpConfig extends InjectableConfig
{
    const CONFIG = 'http';

    /**
     * Cookie protection methods.
     */
    const COOKIE_UNPROTECTED = 0;
    const COOKIE_ENCRYPT     = 1;
    const COOKIE_HMAC        = 2;

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
        'basePath'    => '/',
        'cookies'     => [
            'domain'   => '.%s',
            'method'   => self::COOKIE_ENCRYPT,
            'excluded' => []
        ],
        'headers'     => [],
        'middlewares' => []
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
     * Initial middlewares set.
     *
     * @return array
     */
    public function baseMiddlewares(): array
    {
        return $this->config['middlewares'];
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

        $pattern = $this->config['cookies']['domain'];
        if (filter_var($host, FILTER_VALIDATE_IP)) {
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
    public function cookieProtection(): int
    {
        return $this->config['cookies']['method'];
    }
}