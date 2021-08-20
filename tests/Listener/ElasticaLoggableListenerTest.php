<?php
declare(strict_types=1);

namespace Tests\Basster\ElasticaLoggable\Listener;

use Basster\ElasticaLoggable\Listener\ElasticaLoggableListener;
use Elastica\Index;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @covers \Basster\ElasticaLoggable\Listener\ElasticaLoggableListener
 */
class ElasticaLoggableListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function subscribesPostFlushEvent(): void
    {
        $listener = new ElasticaLoggableListener($this->prophesize(Index::class)->reveal());
        self::assertContains('postFlush', $listener->getSubscribedEvents());
    }
}
