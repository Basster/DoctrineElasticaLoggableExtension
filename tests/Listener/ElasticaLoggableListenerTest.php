<?php
declare(strict_types=1);

namespace Tests\Basster\ElasticaLoggable\Listener;

use Basster\ElasticaLoggable\Listener\ElasticaLoggableListener;
use Elastica\Type;
use PHPUnit\Framework\TestCase;

class ElasticaLoggableListenerTest extends TestCase
{
    /**
     * @test
     */
    public function subscribesPostFlushEvent(): void
    {
        $listener = new ElasticaLoggableListener($this->prophesize(Type::class)->reveal());
        self::assertContains('postFlush', $listener->getSubscribedEvents());
    }
}
