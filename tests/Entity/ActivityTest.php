<?php
declare(strict_types=1);

namespace Tests\Basster\ElasticaLoggable\Entity;

use Basster\ElasticaLoggable\Entity\Activity;
use PHPUnit\Framework\TestCase;

class ActivityTest extends TestCase
{
    const ACTION = 'create';
    const USERNAME = ' super-mario';
    const OBJECT_CLASS = \stdClass::class;

    /** @var  Activity */
    private $activity;

    /**
     * @test
     */
    public function toArrayContainsAction(): void
    {
        self::assertSame(self::ACTION, $this->activity->toArray()['action']);
    }

    /**
     * @test
     */
    public function toArrayContainsLoggedAtTimestampInAtomFormat(): void
    {
        // 'Y-m-d\TH:i:sP'
        self::assertRegExp(
            '/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[-+]\d{2}:\d{2}/',
            $this->activity->toArray()['logged_at']
        );
    }

    /**
     * @test
     */
    public function toArrayHasEmptyDataArrayByDefault(): void
    {
        self::assertSame([], $this->activity->toArray()['data']);

    }

    /**
     * @test
     */
    public function toArrayContainsObjectClassName(): void
    {
        self::assertSame(self::OBJECT_CLASS, $this->activity->toArray()['object_class']);
    }

    protected function setUp(): void
    {
        $this->activity = new Activity(self::ACTION, self::USERNAME, self::OBJECT_CLASS);
    }
}
