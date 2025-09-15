<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Workspaces;

use DateTime;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Exception;
use Presentation\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Stat\Application\Commands\GetDatasetCommand;
use Stat\Domain\ValueObjects\DatasetCategory;
use Traversable;

#[Route(path: '/[uuid:wid]/datasets/usage', method: RequestMethod::GET)]
class DatasetApi extends WorkspaceApi implements RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $type = $request->getAttribute('type');
        $category = DatasetCategory::DATE;

        $endDate = new DateTime();
        $startDate = new DateTime('-30 days');

        $params = $request->getQueryParams();

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

        $cmd = new GetDatasetCommand('usage');
        $cmd->startDate = $startDate;
        $cmd->endDate = $endDate;
        $cmd->category = $category;
        $cmd->setWorkspace($request->getAttribute('wid'));

        /** @var Traversable<array{category:string,value:int}> */
        $dataset = $this->dispatcher->dispatch($cmd);
        $data = iterator_to_array($dataset);

        return new JsonResponse($data);
    }
}
