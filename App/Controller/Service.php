<?php

namespace App\Controller;

use App\Hive\Customer;
use F3\Base;
use F3\Prefab;
use F3\Registry;
use F3\Test;

class Service extends BaseController {

    public function get(Base $f3) {
        $test = new Test();

        $f3->CONTAINER = \F3\Service::instance();
        $test->expect($f3->CONTAINER instanceof \F3\Service, 'Container initialized');

        $bar = $f3->CONTAINER->get(BarService::class);
        $test->expect($bar instanceof BarService, 'Service injected');

        $bar2 = $f3->CONTAINER->get(BarService::class);
        $test->expect($bar !== $bar2, 'Service not cached');

        Registry::clear(BarServic2::class);
        $f3->CONTAINER->singleton(BarServic2::class);
        $obj1 = $f3->CONTAINER->get(BarServic2::class);
        $obj2 = $f3->CONTAINER->get(BarServic2::class);
        $test->expect($obj1 === $obj2, 'Singleton Service cached');

        $bar3 = $f3->CONTAINER->make(BarService::class);
        $test->expect($bar !== $bar3, 'New service created');

        $bar3 = $f3->CONTAINER->make(BarServic2::class);
        $test->expect($bar !== $bar3, 'New singleton service created');

        /** @var BazService $baz */
        $baz = $f3->CONTAINER->make(BazService::class);
        /** @var BazService $baz2 */
        $baz2 = $f3->CONTAINER->make(BazService::class, ['x' => 1337]);
        $test->expect($baz->getX() === 5 && $baz2->getX() === 1337, 'Making with new custom args');

        $f3->CONTAINER->set(MailerInterface::class, Mailer::class);
        $mailer = $f3->CONTAINER->get(MailerInterface::class);
        $test->expect($mailer instanceof Mailer, 'Interface resolved');

        $executed = 0;
        $f3->CONTAINER->set(Mailer::class, function() use(&$executed) {
            $executed++;
            return new Mailer();
        });
        $f3->CONTAINER->make(MailerInterface::class);
        $f3->CONTAINER->make(MailerInterface::class);
        $f3->make(MailerInterface::class);
        $test->expect($executed === 3, 'Factory Closure called');

        $executed2 = 0;
        Registry::clear(Mailer3::class);
        $f3->CONTAINER->singleton(Mailer3::class, function() use(&$executed2) {
            $executed2++;
            return new Mailer3();
        });
        $f3->CONTAINER->get(Mailer3::class);
        $f3->CONTAINER->get(Mailer3::class);
        $f3->make(Mailer3::class);
        $test->expect($executed2 === 1, 'Singleton Factory Closure called');

        Registry::clear('$bar');
        $f3->CONTAINER->singleton('$bar', function() use($f3) {
            return $f3->CONTAINER->make(BazService::class, ['x' => 2048]);
        });
        /** @var BazService $nbar1 */
        $nbar1 = $f3->CONTAINER->get('$bar');
        $test->expect($nbar1 instanceof BazService && $nbar1->getX() === 2048, 'Named service instance');
        $nbar2 = $f3->CONTAINER->get('$bar');
        $test->expect($nbar1 === $nbar2, 'Named singleton service');

        /** @var Customer $customer */
        $customer = $f3->CONTAINER->get(Customer::class);
        $customer->first_name = 'John';
        $customer->last_name = 'Wick';

        $f3->route('GET /page/container-test', 'App\Controller\ContainerControllerTest->index');
        $response = $f3->mock('GET /page/container-test');
        $test->expect($response === '' && $customer->first_name = 'John', 'Service injected to class constructor');

        $f3->route('POST /page/container-test-2', 'App\Controller\ContainerControllerTest->post');
        $response = $f3->mock('POST /page/container-test-2');
        $test->expect($response === Mailer::class, 'Service injected to route handler');

        $service = $f3->CONTAINER->get(SingletonPrefabService::class);
        $service->foo = 'baaaar';

        $f3->CONTAINER->singleton(SingletonService::class);
        $service2 = $f3->CONTAINER->get(SingletonService::class);
        $service2->foo = 'foo0';

        $f3->route('POST /page/container-test-3', function(Base $f3, SingletonPrefabService $service, SingletonService $service2) use($test) {
            $test->expect($f3->PATH === '/page/container-test-3'
                && $service->foo === 'baaaar' && $service2->foo === 'foo0', 'Service injected to route closure');
        });
        $f3->mock('POST /page/container-test-3');

        $bar4 = $f3->make(BarService::class);
        $test->expect($bar4 instanceof BarService, 'make() alias usage');

        $mailer1 = $f3->make(MailerSingleton::class);
        $mailer2 = $f3->make(MailerSingleton::class);
        $test->expect($mailer1 instanceof MailerSingleton && $mailer1 === $mailer2, 'make() respects Prefab');

        $mailer11 = $f3->make(Mailer::class);
        $mailer22 = $f3->make(Mailer::class);
        $test->expect($mailer11 instanceof Mailer && $mailer11 !== $mailer22, 'make() ignores Prefab');

        $executedFn = false;
        $f3->CONTAINER = function(string $class, array $args = []) use(&$executedFn) {
            $executedFn = $class;
            return new BarService(new FooService());
        };
        $bar5 = $f3->make(BarService::class);
        $test->expect($bar5 instanceof BarService && $executedFn === BarService::class, 'Closure CONTAINER');

        $f3->clear('CONTAINER');
        $test->expect(empty($f3->CONTAINER), 'CONTAINER reset');

        $foo = $f3->make(FooService::class);
        $test->expect($foo instanceof FooService, 'make() without CONTAINER');

        $mailer1 = $f3->make(MailerSingleton::class);
        $mailer2 = $f3->make(MailerSingleton::class);
        $test->expect($mailer1 instanceof MailerSingleton && $mailer1 === $mailer2, 'make() without CONTAINER respects Prefab');

        $mailer1 = $f3->make(Mailer::class);
        $mailer2 = $f3->make(Mailer::class);
        $test->expect($mailer1 instanceof Mailer && $mailer1 !== $mailer2, 'make() without CONTAINER ignores Prefab');
        $f3->set('results',$test->results());

        $f3->CONTAINER = \F3\Service::instance();
    }
}


class FooService {}
class BarService {
    function __construct(
        protected FooService $foo,
    ) {}
}

class BarServic2 extends BarService {

}

class BazService extends BarService {
    function __construct(
        protected FooService $foo,
        protected ?int $x = 5,
    ) {
        parent::__construct($foo);
    }

    function getX(): ?int {
        return $this->x;
    }
}

class SingletonService {
    public string $foo;
}
class SingletonPrefabService extends SingletonService {
    use Prefab;
}

interface MailerInterface {}

class Mailer implements MailerInterface {}
class Mailer3 implements MailerInterface {}

trait PrefabTrait {
    use \F3\Prefab;
}
class MailerSingleton extends Mailer {
    use PrefabTrait;
}

class ContainerControllerTest {
    public function __construct(
        protected Customer $app,
        protected Base $f3
    ) {}
    public function index(Base $f3, $params) {
        return $this->app->first_name;
    }
    public function post(MailerInterface $mailer) {
        return get_class($mailer);
    }
}
