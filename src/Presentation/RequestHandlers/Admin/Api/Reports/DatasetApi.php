<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Reports;

use DateTime;
use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Exception;
use Presentation\Resources\Admin\Api\WorkspaceResource;
use Presentation\Resources\CountryResource;
use Presentation\Response\JsonResponse;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Stat\Application\Commands\GetDatasetCommand;
use Stat\Domain\ValueObjects\DatasetCategory;
use Traversable;
use Workspace\Application\Commands\ReadWorkspaceCommand;

#[Route(path: '/dataset/[workspace-usage|usage|signup|subscription|order|country:type]', method: RequestMethod::GET)]
class DatasetApi extends ReportsApi implements RequestHandlerInterface
{
    private const CACHE_VERSION = 1;
    private const CACHE_TTL = 300; // 5 minutes

    public function __construct(
        private Dispatcher $dispatcher,
        private CacheItemPoolInterface $cache,

        #[Inject('config.enable_caching')]
        private bool $enableCaching = false,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $type = $request->getAttribute('type');
        $params = $request->getQueryParams();

        // Generate cache key based on request parameters
        $cacheKey = $this->generateCacheKey($type, $params);

        if ($this->enableCaching) {
            $item = $this->cache->getItem($cacheKey);
            if ($item->isHit()) {
                return new JsonResponse($item->get());
            }
        }

        $category = DatasetCategory::DATE;

        if ($type == 'country') {
            $type = 'signup';
            $category = DatasetCategory::COUNTRY;
        } else if ($type == 'workspace-usage') {
            $type = 'usage';
            $category = DatasetCategory::WORKSPACE_USAGE;
        }

        $endDate = new DateTime();
        $startDate = new DateTime('-30 days');

        if (isset($params['start'])) {
            try {
                $startDate = new DateTime($params['start']);
            } catch (Exception $e) {
                // Invalid start date format, keep the default
            }
        }

        if (isset($params['end'])) {
            try {
                $endDate = new DateTime($params['end']);
            } catch (Exception $e) {
                // Invalid end date format, keep the default
            }
        }

        $startDate->setTime(0, 0, 0); // Set start date to the beginning of the day
        $endDate->setTime(0, 0, 0); // Set end date to the beginning of the day

        // Ensure start date is not after end date
        if ($startDate > $endDate) {
            $temp = $startDate;
            $startDate = $endDate;
            $endDate = $temp;
        }

        $cmd = new GetDatasetCommand($type);
        $cmd->startDate = $startDate;
        $cmd->endDate = $endDate;
        $cmd->category = $category;

        if (isset($params['wsid'])) {
            $cmd->setWorkspace($params['wsid']);
        }

        /** @var Traversable<array{category:string,value:int}> */
        $dataset = $this->dispatcher->dispatch($cmd);

        $data = $this->processDataset($dataset, $request->getAttribute('type'));

        // Cache the result
        if ($this->enableCaching) {
            $item->set($data);
            $item->expiresAfter(self::CACHE_TTL);
            $this->cache->save($item);
        }

        return new JsonResponse($data);
    }

    /**
     * Generate a cache key based on request parameters
     */
    private function generateCacheKey(string $type, array $params): string
    {
        $key = sprintf('dataset.%s.v%d', $type, self::CACHE_VERSION);

        // Add query parameters to the cache key
        if (!empty($params)) {
            ksort($params); // Sort by key to ensure consistent cache keys
            $key .= '.' . md5(http_build_query($params));
        }

        return $key;
    }

    /**
     * Process the dataset based on the request type
     */
    private function processDataset(Traversable $dataset, string $requestType): array
    {
        if ($requestType == 'country') {
            $data = [];
            foreach ($dataset as $stat) {
                $data[] = [
                    'category' => new CountryResource($stat['category']),
                    'value' => $stat['value'],
                ];
            }
            return $data;
        }

        if ($requestType == 'workspace-usage') {
            $data = [];
            foreach ($dataset as $stat) {
                try {
                    $cmd = new ReadWorkspaceCommand($stat['category']);
                    $workspace = $this->dispatcher->dispatch($cmd);

                    $data[] = [
                        'category' => json_decode(json_encode(new WorkspaceResource($workspace, ['owner']))),
                        'value' => $stat['value'],
                    ];
                } catch (\Throwable $th) {
                    // Skip this stat if it fails to fetch the workspace
                }
            }
            return $data;
        }

        return iterator_to_array($dataset);
    }
}
