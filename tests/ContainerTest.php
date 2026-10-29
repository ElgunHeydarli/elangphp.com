<?php
declare(strict_types=1);
namespace Tests;
use Elang\Container\{Container, ContainerException, NotFoundException};
interface Clock {}
final class SystemClock implements Clock {}
final class ClockConsumer { public function __construct(public Clock $clock) {} }
final class CircularA { public function __construct(public CircularB $b) {} }
final class CircularB { public function __construct(public CircularA $a) {} }
final class OptionalDependency { public function __construct(public ?Clock $clock = null) {} }
final class ContainerTest extends TestCase
{
    public function testAutowiringAndInterfaceBindings(): void
    {
        $c = new Container(); $c->bind(Clock::class, SystemClock::class);
        self::assertInstanceOf(SystemClock::class, $c->get(ClockConsumer::class)->clock);
        self::assertNotSame($c->get(ClockConsumer::class), $c->get(ClockConsumer::class));
    }
    public function testSingletonsAndRebinding(): void
    {
        $c = new Container(); $c->singleton(Clock::class, SystemClock::class);
        self::assertSame($c->get(Clock::class), $c->get(Clock::class));
        $c->bind(Clock::class, SystemClock::class);
        self::assertNotSame($c->get(Clock::class), $c->get(Clock::class));
    }
    public function testMissingServiceIsPsrNotFound(): void { $this->expectException(NotFoundException::class); (new Container())->get('missing'); }
    public function testCircularDependencyHasUsefulError(): void { $this->expectException(ContainerException::class); $this->expectExceptionMessage('Circular dependency'); (new Container())->get(CircularA::class); }
    public function testOptionalDependencyDefaultWorks(): void { self::assertNull((new Container())->get(OptionalDependency::class)->clock); }
    public function testNamedParametersAndInjection(): void
    {
        $c = new Container(); $c->instance(Clock::class, new SystemClock());
        self::assertSame(42, $c->invoke(fn (Clock $clock, int $id) => $id, ['id' => '42']));
    }
    public function testInstancesCanContainNull(): void { $c = new Container(); $c->instance('nullable', null); self::assertTrue($c->has('nullable')); self::assertNull($c->get('nullable')); }
}
