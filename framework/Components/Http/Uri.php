<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 * @copyright ©2009-2015
 */
namespace Spiral\Components\Http;

use Psr\Http\Message\UriInterface;

/**
 * Value object representing a URI. A lot of solutions inspired by https://github.com/phly/http
 * which is PHP5.5 only. Spiral might migrate to https://github.com/zendframework/zend-diactoros
 * once PHP5.4 is completely deprecated.
 *
 * This interface is meant to represent URIs according to RFC 3986 and to
 * provide methods for most common operations. Additional functionality for
 * working with URIs can be provided on top of the interface or externally.
 * Its primary use is for HTTP requests, but may also be used in other
 * contexts.
 *
 * Instances of this interface are considered immutable; all methods that
 * might change state MUST be implemented such that they retain the internal
 * state of the current instance and return an instance that contains the
 * changed state.
 *
 * Typically the Host header will be also be present in the request message.
 * For server-side requests, the scheme will typically be discoverable in the
 * server parameters.
 *
 * @link http://tools.ietf.org/html/rfc3986 (the URI specification)
 */
class Uri implements UriInterface
{
    /**
     * The scheme of the URI.
     *
     * @var string
     */
    private $scheme = '';

    /**
     * Authority portion of the URI, in "[user-info@]host[:port]" format.
     *
     * @var string
     */
    private $userInfo = '';

    /**
     * Host segment of the URI.
     *
     * @var string
     */
    private $host = '';

    /**
     * Host segment of the URI.
     *
     * @var int
     */
    private $port = 80;

    /**
     * The path segment of the URI.
     *
     * @var string
     */
    private $path = '';

    /**
     * The URI query string.
     *
     * @var string
     */
    private $query = '';

    /**
     * The URI fragment.
     *
     * @var string
     */
    private $fragment = '';

    /**
     * Set of supported Uri schemes and their ports.
     *
     * @invisible
     * @var array
     */
    private $supportedSchemes = array(
        'http'  => 80,
        'https' => 443
    );

    /**
     * Used to speedup multiple __toString conversions.
     *
     * @var string
     */
    private $uriCache = '';

    /**
     * Create new Uri instance based on provided Uri string. All Uri object properties declared as
     * private to respect immutability or Uri.
     *
     * @param string $uri
     */
    public function __construct($uri = '')
    {
        if (!empty($uri))
        {
            $this->parseUri($uri);
        }
    }

    /**
     * Retrieve the scheme component of the URI.
     *
     * If no scheme is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.1.
     *
     * The trailing ":" character is not part of the scheme and MUST NOT be
     * added.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     * @return string The URI scheme.
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Retrieve the authority component of the URI.
     *
     * If no authority information is present, this method MUST return an empty
     * string.
     *
     * The authority syntax of the URI is:
     *
     * <pre>
     * [user-info@]host[:port]
     * </pre>
     *
     * If the port component is not set or is the standard port for the current
     * scheme, it SHOULD NOT be included.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     * @return string The URI authority, in "[user-info@]host[:port]" format.
     */
    public function getAuthority()
    {
        if (empty($this->host))
        {
            return '';
        }

        $result = ($this->userInfo ? $this->userInfo . '@' : '') . $this->host;
        if (!empty($this->port) && !$this->isStandardPort())
        {
            $result .= ':' . $this->port;
        }

        return $result;
    }

    /**
     * Retrieve the user information component of the URI.
     *
     * If no user information is present, this method MUST return an empty
     * string.
     *
     * If a user is present in the URI, this will return that value;
     * additionally, if the password is also present, it will be appended to the
     * user value, with a colon (":") separating the values.
     *
     * The trailing "@" character is not part of the user information and MUST
     * NOT be added.
     *
     * @return string The URI user information, in "username[:password]" format.
     */
    public function getUserInfo()
    {
        return $this->userInfo;
    }

    /**
     * Retrieve the host component of the URI.
     *
     * If no host is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.2.2.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     * @return string The URI host.
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Retrieve the port component of the URI.
     *
     * If a port is present, and it is non-standard for the current scheme,
     * this method MUST return it as an integer. If the port is the standard port
     * used with the current scheme, this method SHOULD return null.
     *
     * If no port is present, and no scheme is present, this method MUST return
     * a null value.
     *
     * If no port is present, but a scheme is present, this method MAY return
     * the standard port for that scheme, but SHOULD return null.
     *
     * @return null|int The URI port.
     */
    public function getPort()
    {
        if (empty($this->port))
        {
            return null;
        }

        return !$this->isStandardPort() ? $this->port : null;
    }

    /**
     * Check if current port default for current scheme.
     *
     * @return bool
     */
    private function isStandardPort()
    {
        if (empty($this->scheme))
        {
            return true;
        }

        if (!isset($this->supportedSchemes[$this->scheme]))
        {
            return false;
        }

        return $this->supportedSchemes[$this->scheme] == $this->port;
    }

    /**
     * Retrieve the path component of the URI.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * Normally, the empty path "" and absolute path "/" are considered equal as
     * defined in RFC 7230 Section 2.7.3. But this method MUST NOT automatically
     * do this normalization because in contexts with a trimmed base path, e.g.
     * the front controller, this difference becomes significant. It's the task
     * of the user to handle both "" and "/".
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.3.
     *
     * As an example, if the value should include a slash ("/") not intended as
     * delimiter between path segments, that value MUST be passed in encoded
     * form (e.g., "%2F") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     * @return string The URI path.
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Retrieve the query string of the URI.
     *
     * If no query string is present, this method MUST return an empty string.
     *
     * The leading "?" character is not part of the query and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.4.
     *
     * As an example, if a value in a key/value pair of the query string should
     * include an ampersand ("&") not intended as a delimiter between values,
     * that value MUST be passed in encoded form (e.g., "%26") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.4
     * @return string The URI query string.
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Retrieve the fragment component of the URI.
     *
     * If no fragment is present, this method MUST return an empty string.
     *
     * The leading "#" character is not part of the fragment and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.5.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     * @return string The URI fragment.
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * Return an instance with the specified scheme.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified scheme.
     *
     * Implementations MUST support the schemes "http" and "https" case
     * insensitively, and MAY accommodate other schemes if required.
     *
     * An empty scheme is equivalent to removing the scheme.
     *
     * @param string $scheme The scheme to use with the new instance.
     * @return self A new instance with the specified scheme.
     * @throws \InvalidArgumentException for invalid or unsupported schemes.
     */
    public function withScheme($scheme)
    {
        $scheme = $this->normalizeScheme($scheme);
        if (!empty($scheme) && !isset($this->supportedSchemes[$scheme]))
        {
            throw new \InvalidArgumentException(
                'Invalid scheme value, only "http" and "https" allowed.'
            );
        }

        $uri = clone $this;
        $uri->scheme = $scheme;

        return $uri;
    }

    /**
     * Return an instance with the specified user information.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     *
     * Password is optional, but the user information MUST include the
     * user; an empty string for the user is equivalent to removing user
     * information.
     *
     * @param string      $user     The user name to use for authority.
     * @param null|string $password The password associated with $user.
     * @return self A new instance with the specified user information.
     */
    public function withUserInfo($user, $password = null)
    {
        $uri = clone $this;
        $uri->userInfo = $user . ($password ? ':' . $password : '');

        return $uri;
    }

    /**
     * Return an instance with the specified host.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified host.
     *
     * An empty host value is equivalent to removing the host.
     *
     * @param string $host The hostname to use with the new instance.
     * @return self A new instance with the specified host.
     * @throws \InvalidArgumentException for invalid hostnames.
     */
    public function withHost($host)
    {
        $uri = clone $this;
        $uri->host = $host;

        return $uri;
    }

    /**
     * Return an instance with the specified port.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified port.
     *
     * Implementations MUST raise an exception for ports outside the
     * established TCP and UDP port ranges.
     *
     * A null value provided for the port is equivalent to removing the port
     * information.
     *
     * @param null|int $port The port to use with the new instance; a null value
     *                       removes the port information.
     * @return self A new instance with the specified port.
     * @throws \InvalidArgumentException for invalid ports.
     */
    public function withPort($port)
    {
        if (!empty($port))
        {
            $port = (int)$port;
            if ($port < 1 || $port > 65535)
            {
                throw new \InvalidArgumentException(
                    'Invalid port value, use only TCP and UDP range.'
                );
            }
        }

        $uri = clone $this;
        $uri->port = (int)$port;

        return $uri;
    }

    /**
     * Return an instance with the specified path.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified path.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * If the path is intended to be domain-relative rather than path relative then
     * it must begin with a slash ("/"). Paths not starting with a slash ("/")
     * are assumed to be relative to some base path known to the application or
     * consumer.
     *
     * Users can provide both encoded and decoded path characters.
     * Implementations ensure the correct encoding as outlined in getPath().
     *
     * @param string $path The path to use with the new instance.
     * @return self A new instance with the specified path.
     * @throws \InvalidArgumentException for invalid paths.
     */
    public function withPath($path)
    {
        if (strpos($path, '?') !== false || strpos($path, '#') !== false)
        {
            throw new \InvalidArgumentException(
                'Invalid path value, path must not include URI query of URI fragment.'
            );
        }

        $uri = clone $this;
        $uri->path = $this->normalizePath($path);

        return $uri;
    }

    /**
     * Return an instance with the specified query string.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified query string.
     *
     * Users can provide both encoded and decoded query characters.
     * Implementations ensure the correct encoding as outlined in getQuery().
     *
     * An empty query string value is equivalent to removing the query string.
     *
     * @param string $query The query string to use with the new instance.
     * @return self A new instance with the specified query string.
     * @throws \InvalidArgumentException for invalid query strings.
     */
    public function withQuery($query)
    {
        if (strpos($query, '#') !== false)
        {
            throw new \InvalidArgumentException(
                'Invalid query value, query must not URI fragment.'
            );
        }

        $uri = clone $this;
        $uri->query = $this->normalizeQuery($query);

        return $uri;
    }

    /**
     * Return an instance with the specified URI fragment.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified URI fragment.
     *
     * Users can provide both encoded and decoded fragment characters.
     * Implementations ensure the correct encoding as outlined in getFragment().
     *
     * An empty fragment value is equivalent to removing the fragment.
     *
     * @param string $fragment The fragment to use with the new instance.
     * @return self A new instance with the specified fragment.
     */
    public function withFragment($fragment)
    {
        $uri = clone $this;
        $uri->fragment = $this->normalizeFragment($fragment);

        return $uri;
    }

    /**
     * Return the string representation as a URI reference.
     *
     * Depending on which components of the URI are present, the resulting
     * string is either a full URI or relative reference according to RFC 3986,
     * Section 4.1. The method concatenates the various components of the URI,
     * using the appropriate delimiters:
     *
     * - If a scheme is present, it MUST be suffixed by ":".
     * - If an authority is present, it MUST be prefixed by "//".
     * - The path can be concatenated without delimiters. But there are two
     *   cases where the path has to be adjusted to make the URI reference
     *   valid as PHP does not allow to throw an exception in __toString():
     *     - If the path is rootless and an authority is present, the path MUST
     *       be prefixed by "/".
     *     - If the path is starting with more than one "/" and no authority is
     *       present, the starting slashes MUST be reduced to one.
     * - If a query is present, it MUST be prefixed by "?".
     * - If a fragment is present, it MUST be prefixed by "#".
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.1
     * @return string
     */
    public function __toString()
    {
        if (!empty($this->uriCache))
        {
            return $this->uriCache;
        }

        $this->uriCache = '';
        if (!empty($this->scheme))
        {
            $this->uriCache .= $this->scheme . '://';
        }

        $this->uriCache .= $this->getAuthority();

        if (!empty($this->path))
        {
            $this->uriCache .= '/';
        }
        else
        {
            $path = $this->path;
            if ($path[0] !== '/')
            {
                $path = '/' . $path;
            }

            $this->uriCache .= $path;
        }

        if (!empty($this->query))
        {
            $this->uriCache .= '?' . $this->query;
        }

        if (!empty($this->fragment))
        {
            $this->uriCache .= '#' . $this->fragment;
        }

        return $this->uriCache;
    }

    /**
     * Cleaning uri cache on cloning.
     */
    public function __clone()
    {
        $this->uriCache = '';
    }

    /**
     * Normalize path.
     *
     * @param string $path
     * @return string
     */
    private function normalizePath($path)
    {
        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~:@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/',
            array($this, 'encodeCharacter'),
            $path
        );
    }

    /**
     * Normalize scheme value to remove trailing ://.
     *
     * @param string $scheme
     * @return string
     */
    private function normalizeScheme($scheme)
    {
        $scheme = rtrim(strtolower($scheme), '/:');

        return !empty($scheme) ? $scheme : '';
    }

    /**
     * Normalize query string.
     *
     * @param string $query
     * @return mixed
     */
    private function normalizeQuery($query)
    {
        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/',
            array($this, 'encodeCharacter'),
            $query
        );
    }

    /**
     * Perform query normalization.
     *
     * @param string $fragment
     * @return string
     */
    private function normalizeFragment($fragment)
    {
        //Both have identical normalization
        return $this->normalizeQuery($fragment);
    }

    /**
     * Regexp callback to encode invalid character.
     *
     * @param array $matches
     * @return string
     */
    private function encodeCharacter(array $matches)
    {
        return rawurlencode($matches[0]);
    }

    /**
     * Parse income uri and populate instance values.
     *
     * @param string $uri
     * @throws \InvalidArgumentException
     */
    private function parseUri($uri)
    {
        $components = parse_url($uri);

        if (empty($components))
        {
            throw new \InvalidArgumentException("Unable to parse URI.");
        }

        $this->scheme = isset($components['scheme']) ? $components['scheme'] : '';
        $this->host = isset($components['host']) ? $components['host'] : '';
        $this->port = isset($components['port']) ? $components['port'] : null;

        $this->path = isset($components['path'])
            ? $this->normalizePath($components['path'])
            : '';

        $this->query = isset($components['query'])
            ? $this->normalizeQuery($components['query'])
            : '';

        $this->fragment = isset($components['fragment'])
            ? $this->normalizeFragment($components['fragment'])
            : '';

        if (isset($components['pass']))
        {
            $this->userInfo = $components['user'] . ':' . $components['pass'];
        }
        elseif (isset($components['user']))
        {
            $this->userInfo = $components['user'];
        }
    }

    /**
     * Cast Uri object properties based on values provided in server array ($_SERVER).
     *
     * @param array $server
     * @return self
     */
    public static function castUri(array $server)
    {
        $uri = new static;

        $uri->scheme = 'http';
        if (isset($server['HTTPS']) && $server['HTTPS'] == 'on')
        {
            $uri->scheme = 'https';
        }
        elseif (isset($server['HTTP_X_FORWARDED_PROTO']) && $server['HTTP_X_FORWARDED_PROTO'] == 'https')
        {
            $uri->scheme = 'https';
        }
        elseif (isset($server['HTTP_X_FORWARDED_SSL']) && $server['HTTP_X_FORWARDED_SSL'] == 'on')
        {
            $uri->scheme = 'https';
        }

        if (isset($server['SERVER_PORT']))
        {
            $uri->port = (int)$server['SERVER_PORT'];
        }

        if (isset($server['HTTP_HOST']))
        {
            $uri->host = $server['HTTP_HOST'];
            if ($delimiter = strpos($server['HTTP_HOST'], ':'))
            {
                $uri->port = (int)substr($uri->host, $delimiter + 1);
                $uri->host = substr($uri->host, 0, $delimiter);
            }
        }
        elseif (isset($server['HTTP_NAME']))
        {
            $uri->host = $server['HTTP_NAME'];
        }

        if (isset($server['UNENCODED_URL']))
        {
            $uri->path = $server['UNENCODED_URL'];
        }
        elseif (isset($server['REQUEST_URI']))
        {
            $uri->path = $server['REQUEST_URI'];
        }
        elseif (isset($server['HTTP_X_REWRITE_URL']))
        {
            $uri->path = $server['HTTP_X_REWRITE_URL'];
        }
        elseif (isset($server['HTTP_X_ORIGINAL_URL']))
        {
            $uri->path = $server['HTTP_X_ORIGINAL_URL'];
        }

        if (($query = strpos($uri->path, '?')) !== false)
        {
            $uri->path = substr($uri->path, 0, $query);
        }

        $uri->path = $uri->path ?: '/';
        if (isset($server['QUERY_STRING']))
        {
            $uri->query = ltrim($server['QUERY_STRING'], '?');
        }

        return $uri;
    }
}