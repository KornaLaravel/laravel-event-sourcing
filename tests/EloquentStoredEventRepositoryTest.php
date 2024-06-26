<?php

namespace Spatie\EventSourcing\Tests;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertSame;

use Spatie\EventSourcing\StoredEvents\Repositories\EloquentStoredEventRepository;
use Spatie\EventSourcing\Tests\TestClasses\AggregateRoots\AccountAggregateRoot;
use Spatie\EventSourcing\Tests\TestClasses\AggregateRoots\StorableEvents\MoneyAdded;
use Spatie\EventSourcing\Tests\TestClasses\Events\EventWithCustomSerializer;

it('can get the latest version id for a given aggregate uuid', function () {
    $eloquentStoredEventRepository = new EloquentStoredEventRepository();

    assertEquals(0, $eloquentStoredEventRepository->getLatestAggregateVersion('uuid-non-existing'));

    $aggregateRoot = AccountAggregateRoot::retrieve('uuid-1');
    assertEquals(0, $eloquentStoredEventRepository->getLatestAggregateVersion('uuid-1'));

    $aggregateRoot->addMoney(100)->persist();
    assertEquals(1, $eloquentStoredEventRepository->getLatestAggregateVersion('uuid-1'));

    $aggregateRoot->addMoney(100)->persist();
    assertEquals(2, $eloquentStoredEventRepository->getLatestAggregateVersion('uuid-1'));

    $anotherAggregateRoot = AccountAggregateRoot::retrieve('uuid-2');
    $anotherAggregateRoot->addMoney(100)->persist();
    assertEquals(1, $eloquentStoredEventRepository->getLatestAggregateVersion('uuid-2'));
    assertEquals(2, $eloquentStoredEventRepository->getLatestAggregateVersion('uuid-1'));
});

it('sets the original event on persist', function () {
    $eloquentStoredEventRepository = app(EloquentStoredEventRepository::class);

    $originalEvent = new MoneyAdded(100);
    $storedEvent = $eloquentStoredEventRepository->persist($originalEvent, 'uuid-1', 1);

    assertSame($originalEvent, $storedEvent->event);
});

it('uses the custom serializer if one is set', function () {
    $eloquentStoredEventRepository = app(EloquentStoredEventRepository::class);

    $originalEvent = new EventWithCustomSerializer('default message');
    $storedEvent = $eloquentStoredEventRepository->persist($originalEvent, 'uuid-1', 1);

    $eventFromDatabase = $eloquentStoredEventRepository->find($storedEvent->id)->event;
    assertSame('message set by custom serializer', $eventFromDatabase->message);
});
