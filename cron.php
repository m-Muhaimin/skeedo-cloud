<?php

declare(strict_types=1);

use Cron\Domain\Events\CronEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

// Application start timestamp
define('APP_START', microtime(true));

/** @var ContainerInterface $container */
$container = include __DIR__ . '/bootstrap/app.php';

$dispatcher = $container->get(EventDispatcherInterface::class);
$dispatcher->dispatch(new CronEvent());

try {
    // Persist data
    /** @var EntityManagerInterface $em */
    $em = $container->get(EntityManagerInterface::class);
    $em->flush();
} catch (NotFoundExceptionInterface) {
    // Do nothing
}
