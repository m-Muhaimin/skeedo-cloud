<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Tools;

use Ai\Domain\ValueObjects\Model;
use Ai\Infrastructure\Services\CostCalculator;
use Easy\Container\Attributes\Inject;
use Override;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

class GoogleSearch implements ToolInterface
{
    public const LOOKUP_KEY = 'google_search';
    private string $baseUrl = 'https://google.serper.dev/';

    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private CostCalculator $calc,

        #[Inject('option.serper.api_key')]
        private ?string $apiKey = null,

        #[Inject('option.features.tools.google_search.is_enabled')]
        private ?bool $isEnabled = null,
    ) {}

    #[Override]
    public function isEnabled(): bool
    {
        return (bool) $this->apiKey && $this->isEnabled;
    }

    #[Override]
    public function getDescription(): string
    {
        return 'Fetches Google search results for a specified query. The query must be in the same language as the user\'s prompt. The `hl` parameter is the language code according to the user prompt. The `gl` parameter should be determined according to the language code (hl parameter) The tool returns the search results as a JSON string. It should be utilized when a user requests current or up-to-date information, or information not present in the AI model\'s knowledge base. Regardless of the language of the search results, the user\'s prompt must be answered in the original language.';
    }

    #[Override]
    public function getDefinitions(): array
    {
        return [
            "type" => "object",
            "properties" => [
                "query" => [
                    "type" => "string",
                    "description" => "Search query. Always use the same language with conversation."
                ],
                'hl' => [
                    'type' => 'string',
                    'description' => 'Language code. It should be in same language with conversation.',
                    "enum" => [
                        "af",
                        "ak",
                        "sq",
                        "am",
                        "ar",
                        "hy",
                        "az",
                        "eu",
                        "be",
                        "bem",
                        "bn",
                        "bh",
                        "xx-borg",
                        "bs",
                        "br",
                        "bg",
                        "km",
                        "ca",
                        "chr",
                        "ny",
                        "zh-cn",
                        "zh-tw",
                        "co",
                        "hr",
                        "cs",
                        "da",
                        "nl",
                        "xx-elmer",
                        "en",
                        "eo",
                        "et",
                        "ee",
                        "fo",
                        "tl",
                        "fi",
                        "fr",
                        "fy",
                        "gaa",
                        "gl",
                        "ka",
                        "de",
                        "el",
                        "gn",
                        "gu",
                        "xx-hacker",
                        "ht",
                        "ha",
                        "haw",
                        "iw",
                        "hi",
                        "hu",
                        "is",
                        "ig",
                        "id",
                        "ia",
                        "ga",
                        "it",
                        "ja",
                        "jw",
                        "kn",
                        "kk",
                        "rw",
                        "rn",
                        "xx-klingon",
                        "kg",
                        "ko",
                        "kri",
                        "ku",
                        "ckb",
                        "ky",
                        "lo",
                        "la",
                        "lv",
                        "ln",
                        "lt",
                        "loz",
                        "lg",
                        "ach",
                        "mk",
                        "mg",
                        "ms",
                        "ml",
                        "mt",
                        "mi",
                        "mr",
                        "mfe",
                        "mo",
                        "mn",
                        "sr-ME",
                        "ne",
                        "pcm",
                        "nso",
                        "no",
                        "nn",
                        "oc",
                        "or",
                        "om",
                        "ps",
                        "fa",
                        "xx-pirate",
                        "pl",
                        "pt-br",
                        "pt-pt",
                        "pa",
                        "qu",
                        "ro",
                        "rm",
                        "nyn",
                        "ru",
                        "gd",
                        "sr",
                        "sh",
                        "st",
                        "tn",
                        "crs",
                        "sn",
                        "sd",
                        "si",
                        "sk",
                        "sl",
                        "so",
                        "es",
                        "es-419",
                        "su",
                        "sw",
                        "sv",
                        "tg",
                        "ta",
                        "tt",
                        "te",
                        "th",
                        "ti",
                        "to",
                        "lua",
                        "tum",
                        "tr",
                        "tk",
                        "tw",
                        "ug",
                        "uk",
                        "ur",
                        "uz",
                        "vi",
                        "cy",
                        "wo",
                        "xh",
                        "yi",
                        "yo",
                        "zu"
                    ],
                ],
                'gl' => [
                    'type' => 'string',
                    'description' => 'Country code. Should be determined according to the language code (hl parameter).',
                    "enum" => [
                        'af',
                        'al',
                        'dz',
                        'as',
                        'ad',
                        'ao',
                        'ai',
                        'aq',
                        'ag',
                        'ar',
                        'am',
                        'aw',
                        'au',
                        'at',
                        'az',
                        'bs',
                        'bh',
                        'bd',
                        'bb',
                        'by',
                        'be',
                        'bz',
                        'bj',
                        'bm',
                        'bt',
                        'bo',
                        'ba',
                        'bw',
                        'bv',
                        'br',
                        'io',
                        'bn',
                        'bg',
                        'bf',
                        'bi',
                        'cv',
                        'kh',
                        'cm',
                        'ca',
                        'ky',
                        'cf',
                        'td',
                        'cl',
                        'cn',
                        'cx',
                        'cc',
                        'co',
                        'km',
                        'cd',
                        'cg',
                        'ck',
                        'cr',
                        'ci',
                        'hr',
                        'cu',
                        'cw',
                        'cy',
                        'cz',
                        'dk',
                        'dj',
                        'dm',
                        'do',
                        'ec',
                        'eg',
                        'sv',
                        'gq',
                        'er',
                        'ee',
                        'sz',
                        'et',
                        'fk',
                        'fo',
                        'fj',
                        'fi',
                        'fr',
                        'gf',
                        'pf',
                        'tf',
                        'ga',
                        'gm',
                        'ge',
                        'de',
                        'gh',
                        'gi',
                        'gr',
                        'gl',
                        'gd',
                        'gp',
                        'gu',
                        'gt',
                        'gg',
                        'gn',
                        'gw',
                        'gy',
                        'ht',
                        'hm',
                        'va',
                        'hn',
                        'hk',
                        'hu',
                        'is',
                        'in',
                        'id',
                        'ir',
                        'iq',
                        'ie',
                        'im',
                        'il',
                        'it',
                        'jm',
                        'jp',
                        'je',
                        'jo',
                        'kz',
                        'ke',
                        'ki',
                        'kp',
                        'kr',
                        'kw',
                        'kg',
                        'la',
                        'lv',
                        'lb',
                        'ls',
                        'lr',
                        'ly',
                        'li',
                        'lt',
                        'lu',
                        'mo',
                        'mg',
                        'mw',
                        'my',
                        'mv',
                        'ml',
                        'mt',
                        'mh',
                        'mq',
                        'mr',
                        'mu',
                        'yt',
                        'mx',
                        'fm',
                        'md',
                        'mc',
                        'mn',
                        'me',
                        'ms',
                        'ma',
                        'mz',
                        'mm',
                        'na',
                        'nr',
                        'np',
                        'nl',
                        'nc',
                        'nz',
                        'ni',
                        'ne',
                        'ng',
                        'nu',
                        'nf',
                        'mp',
                        'no',
                        'om',
                        'pk',
                        'pw',
                        'ps',
                        'pa',
                        'pg',
                        'py',
                        'pe',
                        'ph',
                        'pn',
                        'pl',
                        'pt',
                        'pr',
                        'qa',
                        'mk',
                        'ro',
                        'ru',
                        'rw',
                        're',
                        'bl',
                        'sh',
                        'kn',
                        'lc',
                        'mf',
                        'pm',
                        'vc',
                        'ws',
                        'sm',
                        'st',
                        'sa',
                        'sn',
                        'rs',
                        'sc',
                        'sl',
                        'sg',
                        'sx',
                        'sk',
                        'si',
                        'sb',
                        'so',
                        'za',
                        'gs',
                        'ss',
                        'es',
                        'lk',
                        'sd',
                        'sr',
                        'sj',
                        'se',
                        'ch',
                        'sy',
                        'tw',
                        'tj',
                        'tz',
                        'th',
                        'tl',
                        'tg',
                        'tk',
                        'to',
                        'tt',
                        'tn',
                        'tr',
                        'tm',
                        'tc',
                        'tv',
                        'ug',
                        'ua',
                        'ae',
                        'gb',
                        'um',
                        'us',
                        'uy',
                        'uz',
                        'vu',
                        've',
                        'vn',
                        'vg',
                        'vi',
                        'wf',
                        'eh',
                        'ye',
                        'zm',
                        'zw',
                    ]
                ],
            ],
            "required" => ["query", "hl", "gl"]
        ];
    }

    #[Override]
    public function call(
        UserEntity $user,
        WorkspaceEntity $workspace,
        array $params = [],
        array $files = [],
        array $knowledgeBase = [],
    ): CallResponse {
        $resp = $this->sendRequest(
            'POST',
            '/search',
            params: [
                'q' => $params['query'] ?? '',
                'hl' => $params['hl'] ?? 'en',
                'gl' => $params['gl'] ?? 'us',
            ]
        );

        if ($resp->getStatusCode() !== 200) {
            throw new CallException('Failed to search: ' . $resp->getBody()->getContents());
        }

        $cost = $this->calc->calculate(1, new Model('serper'));

        return new CallResponse(
            $resp->getBody()->getContents(),
            $cost
        );
    }

    private function sendRequest(
        string $method,
        string $path,
        array|string $body = [],
        array $params = [],
        array $headers = []
    ): ResponseInterface {
        $baseUrl = $this->baseUrl;

        $req = $this->requestFactory
            ->createRequest($method, $baseUrl . trim($path, "/"))
            ->withHeader('X-Api-Key', $this->apiKey)
            ->withHeader('Content-Type', 'application/json');

        if ($body) {
            $req = $req
                ->withBody($this->streamFactory->createStream(
                    is_array($body) ? json_encode($body) : $body
                ));
        }

        if ($params) {
            $req = $req->withUri(
                $req->getUri()->withQuery(http_build_query($params))
            );
        }

        if ($headers) {
            foreach ($headers as $key => $value) {
                $req = $req->withHeader($key, $value);
            }
        }

        return $this->client->sendRequest($req);
    }
}
