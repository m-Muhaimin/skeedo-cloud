<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Easy\Http\ResponseEmitter\EmitterInterface;
use Laminas\Diactoros\ServerRequestFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Presentation\ServerRequestHandler;
use Psr\Container\NotFoundExceptionInterface;

// Application start timestamp
define('APP_START', microtime(true));

/** @var ContainerInterface $container */
$container = include __DIR__ . '/../bootstrap/app.php';

/** @var ServerRequestHandler $handler */
$handler = $container->get(ServerRequestHandler::class);

/**
 * A server request captured from global PHP variables
 * @var ServerRequestInterface $request
 */
$request = ServerRequestFactory::fromGlobals(cookies: $_COOKIE);

/** @var ResponseInterface $response */
$response = $handler->handle($request);

/** @var EmitterInterface $emitter */
$emitter = $container->get(EmitterInterface::class);

// Emit response
$emitter->emit($response);

// Persist data
/** @var EntityManagerInterface */
try {
    // Persist data
    /** @var EntityManagerInterface $em */
    $em = $container->get(EntityManagerInterface::class);
    $em->flush();
} catch (NotFoundExceptionInterface) {
    // Do nothing
}
