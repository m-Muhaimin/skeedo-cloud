<?php

declare(strict_types=1);

namespace Presentation\Cookies;

use DateTime;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

class PreviewCookie extends Cookie
{
    public const NAME = 'preview';

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
            new DateTime('@' . (time() + 15 * 60)),
            '/'
        );
    }

    /**
     * Create an instance of this object with the
     * cookies values from the request
     * 
     * @param ServerRequestInterface $req 
     * @return null|PreviewCookie
     */
    public static function createFromRequest(
        ServerRequestInterface $req
    ): ?PreviewCookie {
        $cookies = $req->getCookieParams();

        if (isset($cookies[self::NAME])) {
            return new PreviewCookie($cookies[self::NAME]);
        }

        return null;
    }
}
