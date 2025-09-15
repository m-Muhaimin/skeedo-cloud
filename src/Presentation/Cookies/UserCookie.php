<?php

declare(strict_types=1);

namespace Presentation\Cookies;

use Application;
use DateTime;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

class UserCookie extends Cookie
{
    private const NAME = 'user';

    /**
     * @param string $value 
     * @return void 
     * @throws InvalidArgumentException 
     */
    public function __construct(string $value)
    {
        $secure = Application::make('option.site.is_secure', false);
        $domain = env('COOKIE_DOMAIN', '');

        parent::__construct(
            self::NAME,
            $value,
            new DateTime('@' . (time() + 86400 * 30)),
            '/',
            $domain,
            secure: (bool) $secure
        );
    }

    /**
     * Create an instance of this object with the
     * cookies values from the request
     * 
     * @param ServerRequestInterface $req 
     * @return null|UserCookie 
     */
    public static function createFromRequest(
        ServerRequestInterface $req
    ): ?UserCookie {
        $cookies = $req->getCookieParams();

        if (isset($cookies[self::NAME])) {
            return new UserCookie($cookies[self::NAME]);
        }

        return null;
    }
}
