<?php

beforeEach(function () {
    $this->f3->CONTAINER = \F3\Service::instance();
});

test('Container initialized', function () {
    expect($this->f3->CONTAINER)->toBeInstanceOf(\F3\Service::class);
});

test('Service injected', function () {
    $bar = $this->f3->CONTAINER->get(BarService::class);
    expect($bar)->toBeInstanceOf(BarService::class);
});

test('Service not cached', function () {
    $bar = $this->f3->CONTAINER->get(BarService::class);
    $bar2 = $this->f3->CONTAINER->get(BarService::class);
    expect($bar)->not()->toBe($bar2);

    $bar3 = $this->f3->CONTAINER->make(BarService::class);
    expect($bar)->not()->toBe($bar3, 'New service created');
});

test('Singleton Service cached', function () {
    $this->f3->CONTAINER->singleton(BarService2::class);

    $obj1 = $this->f3->CONTAINER->get(BarService2::class);
    $obj2 = $this->f3->CONTAINER->get(BarService2::class);
    expect($obj1)->toBe($obj2);

    $bar3 = $this->f3->CONTAINER->make(BarService2::class);
    expect($obj1)->not()->toBe($bar3, 'New singleton service created');
});

test('Making with new custom args', function () {
    /** @var BazService $baz */
    $baz = $this->f3->CONTAINER->make(BazService::class);
    /** @var BazService $baz2 */
    $baz2 = $this->f3->CONTAINER->make(BazService::class, ['x' => 1337]);
    expect($baz->getX())
        ->toBe(5)->and($baz2->getX())
        ->toBe(1337, 'Making with new custom args');
});


it('resolves an Interface', function () {
    $this->f3->CONTAINER->set(MailerInterface::class, Mailer::class);
    $mailer = $this->f3->CONTAINER->get(MailerInterface::class);
    expect($mailer)->toBeInstanceOf(Mailer::class);
});

it('calls a factory closure', function () {
    $this->f3->CONTAINER->set(MailerInterface::class, Mailer::class);

    $executed = 0;
    $this->f3->CONTAINER->set(Mailer::class, function () use (&$executed) {
        $executed++;
        return new Mailer();
    });
    $this->f3->CONTAINER->make(MailerInterface::class);
    $this->f3->CONTAINER->make(MailerInterface::class);
    $this->f3->make(MailerInterface::class);
    expect($executed)->toBe(3);
});

test('Singleton Factory Closure called', function () {
    $executed = 0;
    $this->f3->CONTAINER->singleton(Mailer3::class, function () use (&$executed) {
        $executed++;
        return new Mailer3();
    });
    $this->f3->CONTAINER->get(Mailer3::class);
    $this->f3->CONTAINER->get(Mailer3::class);
    $this->f3->make(Mailer3::class);
    expect($executed)->toBe(1);
});

test('Named service instance', function () {
    $this->f3->CONTAINER->singleton('$bar', function () {
        return $this->f3->CONTAINER->make(BazService::class, ['x' => 2048]);
    });
    /** @var BazService $nbar1 */
    $nbar1 = $this->f3->CONTAINER->get('$bar');
    expect($nbar1)
        ->toBeInstanceOf(BazService::class)
        ->and($nbar1->getX())->toBe(2048);

    $nbar2 = $this->f3->CONTAINER->get('$bar');
    expect($nbar2)->toBe($nbar1, 'Named singleton service');
});

test('Service injected to class constructor', function () {
    $customer = $this->f3->CONTAINER->get(Customer::class);
    $customer->first_name = 'John';
    $this->f3->route('GET /page/container-test', [ContainerControllerTest::class, 'index']);
    $response = $this->f3->mock('GET /page/container-test');
    expect($response)
        ->toBe('')
        ->and($customer->first_name)->toBe('John');

    $this->f3->CONTAINER->singleton(Customer::class, $customer);
    $response = $this->f3->mock('GET /page/container-test');
    expect($response)->toBe('John', 'Singleton service injected');
});

test('Service injected to route handler', function () {
    $this->f3->CONTAINER->set(MailerInterface::class, Mailer::class);
    $this->f3->route('GET /page/container-test-2', [ContainerControllerTest::class, 'post']);
    $response = $this->f3->mock('GET /page/container-test-2');
    expect($response)->toBe(Mailer::class);
});

test('Service injected to route closure', function () {
    $service = $this->f3->CONTAINER->get(SingletonPrefabService::class);
    $service->foo = 'baaaar';

    $this->f3->CONTAINER->singleton(SingletonService::class);
    $service2 = $this->f3->CONTAINER->get(SingletonService::class);
    $service2->foo = 'foo0';

    $this->f3->route(
        'POST /page/container-test-3',
        function (F3\Base $f3, SingletonPrefabService $service, SingletonService $service2) {
            expect($f3->PATH)
                ->toBe('/page/container-test-3')
                ->and($service->foo)->toBe('baaaar')
                ->and($service2->foo)->toBe('foo0');
        },
    );
    $this->f3->mock('POST /page/container-test-3');
});

describe('make() with PSR11', function () {

    test('alias usage', function () {
        $bar4 = $this->f3->make(BarService::class);
        expect($bar4)->toBeInstanceOf(BarService::class);
    });

    test('service not cached', function () {
        $mailer1 = $this->f3->make(Mailer::class);
        $mailer2 = $this->f3->make(Mailer::class);
        expect($mailer1)
            ->toBeInstanceOf(Mailer::class)
            ->and($mailer1)->not()->toBe($mailer2);
    });

    it('respects Prefab', function () {
        $mailer1 = $this->f3->make(MailerSingleton::class);
        $mailer2 = $this->f3->make(MailerSingleton::class);
        expect($mailer1)
            ->toBeInstanceOf(MailerSingleton::class)
            ->and($mailer1)->toBe($mailer2);
    });
});

test('Closure CONTAINER', function () {
    $this->f3->CONTAINER = function (string $classString, array $args = []) {
        expect($classString)
            ->toBe(BarService::class)
            ->and($args)->toBe(['foo' => 'bar']);
        return new BarService(new FooService());
    };
    $bar5 = $this->f3->make(BarService::class, ['foo' => 'bar']);
    expect($bar5)->toBeInstanceOf(BarService::class);
});

describe('make() without CONTAINER', function () {

    test('CONTAINER reset', function () {
        $this->f3->clear('CONTAINER');
        expect($this->f3->CONTAINER)->toBeEmpty();
    });

    test('creates service', function () {
        $this->f3->clear('CONTAINER');
        $foo = $this->f3->make(FooService::class);
        expect($foo)->toBeInstanceOf(FooService::class);
    });

    test('service not cached', function () {
        $this->f3->clear('CONTAINER');
        $mailer1 = $this->f3->make(Mailer::class);
        $mailer2 = $this->f3->make(Mailer::class);
        expect($mailer1)
            ->toBeInstanceOf(Mailer::class)
            ->and($mailer1)->not()->toBe($mailer2);
    });

    it('respects Prefab', function () {
        $this->f3->clear('CONTAINER');
        $mailer1 = $this->f3->make(MailerSingleton::class);
        $mailer2 = $this->f3->make(MailerSingleton::class);
        expect($mailer1)
            ->toBeInstanceOf(MailerSingleton::class)
            ->and($mailer1)->toBe($mailer2);
    });

    it('throws if not instantiable', function () {
        expect(function () {
            $this->f3->make(NoConstruct::class);
        })->toThrow(\Exception::class, 'is not instantiable');
    });

    it('throws if not resolvable', function () {
        expect(function () {
            $this->f3->make(UnknownParam::class);
        })->toThrow(\Exception::class, 'Cannot resolve class dependency');
    });

    it('uses custom arguments', function () {
        expect($this->f3->make(UnknownParam::class, ['foo' => 123]))
            ->toBeInstanceOf(UnknownParam::class)
            ->toHaveProperty('foo', 123);
    });

    it('ignores unknown types without default', function () {
        expect($this->f3->make(UnknownParamType::class))
            ->toBeInstanceOf(UnknownParamType::class);
    });

});

class FooService {}

class BarService
{
    function __construct(
        protected FooService $foo,
    ) {}
}

class BarService2 extends BarService {}

class BazService extends BarService
{
    function __construct(
        protected FooService $foo,
        protected ?int $x = 5,
    ) {
        parent::__construct($foo);
    }

    function getX(): ?int
    {
        return $this->x;
    }
}

class SingletonService
{
    public string $foo;
}

class NoConstruct
{
    private function __construct() { }
}

class UnknownParam
{
    public function __construct(
        public int $foo,
    ) { }
}

class UnknownParamType
{
    public function __construct(
        protected $foo,
    ) { }
}

class SingletonPrefabService extends SingletonService
{
    use F3\Prefab;
}

interface MailerInterface {}

class Mailer implements MailerInterface {}

class Mailer3 implements MailerInterface {}

trait PrefabTrait
{
    use \F3\Prefab;
}

class MailerSingleton extends Mailer
{
    use PrefabTrait;
}

class ContainerControllerTest
{
    public function __construct(
        protected Customer $app,
        protected F3\Base $f3
    ) {}

    public function index(F3\Base $f3, $params)
    {
        return $this->app->first_name;
    }

    public function post(MailerInterface $mailer)
    {
        return get_class($mailer);
    }
}

class Customer
{
    public ?string $first_name = '';
    public ?string $last_name;
    public string $email;
}