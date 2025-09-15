<?php

declare(strict_types=1);

namespace Presentation\Cookies;

use DateTime;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

class ReferralCookie extends Cookie
{
    private const NAME = 'ref';

    /**
     * @param string $value 
     * @return void 
     * @throws InvalidArgumentException 
     */
    public function __construct(string $value)
    {
        parent::__construct(
            self::NAME,
            $value,
            new DateTime('@' . (time() + 86400 * 7)),
            '/'
        );
    }

    /**
     * Create an instance of this object with the
     * cookies values from the request
     * 
     * @param ServerRequestInterface $req 
     * @return null|ReferralCookie
     */
    public static function createFromRequest(
        ServerRequestInterface $req
    ): ?ReferralCookie {
        $cookies = $req->getCookieParams();

        if (isset($cookies[self::NAME])) {
            return new ReferralCookie($cookies[self::NAME]);
        }

        return null;
    }
}
