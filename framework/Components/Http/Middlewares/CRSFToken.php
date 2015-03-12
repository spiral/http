<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 * @copyright ©2009-2015
 */
namespace Spiral\Components\Http\Middlewares;

use Spiral\Components\Http\Message\Stream;
use Spiral\Components\Http\MiddlewareInterface;
use Spiral\Components\Http\Request;
use Spiral\Components\Http\Response;
use Spiral\Core\Component;

class CRSFToken implements MiddlewareInterface
{
    /**
     * Token have to check in cookies and queries.
     */
    const TOKEN_NAME = 'crsf-token';

    const CSRF_HEADER = 'X-CSRF-Token';

    static protected $token = '';

    /**
     * Handle request generate response. Middleware used to alter incoming Request and/or Response
     * generated by inner pipeline layers.
     *
     * @param Request     $request Server request instance.
     * @param \Closure    $next    Next middleware/target.
     * @param object|null $context Pipeline context, can be HttpDispatcher, Route or module.
     * @return Response
     */
    public function __invoke(Request $request, \Closure $next = null, $context = null)
    {
        //if (!self::$token)
        {
            self::$token = base64_encode(openssl_random_pseudo_bytes(32));
        }

        if (in_array($request->getMethod(), array('GET', 'HEAD', 'OPTIONS')))
        {
        }

        /**
         * @var Response $response
         */

        return $next();
    }

    public static function getToken()
    {
        return self::$token;
    }
}