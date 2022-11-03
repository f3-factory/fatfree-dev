<?php

namespace App\Controller;

use F3\Base;
use F3\Test;

class Service extends BaseController {

    public function get(Base $f3) {
        $test = new Test();

        $f3->CONTAINER = \F3\Service::instance();
        $test->expect($f3->CONTAINER instanceof \F3\Service, 'Container initialized');

        $bar = $f3->CONTAINER->get(BarService::class);
        $test->expect($bar instanceof BarService, 'Service injected');

        $bar2 = $f3->CONTAINER->get(BarService::class);
        $test->expect($bar === $bar2, 'Service cached');

        $bar3 = $f3->CONTAINER->make(BarService::class);
        $test->expect($bar !== $bar3, 'New service created');

        /** @var BazService $baz */
        $baz = $f3->CONTAINER->make(BazService::class);
        /** @var BazService $baz2 */
        $baz2 = $f3->CONTAINER->make(BazService::class, ['x' => 1337]);
        $test->expect($baz->getX() === 5 && $baz2->getX() === 1337, 'Making with new custom args');

        $f3->CONTAINER->set(MailerInterface::class, Mailer::class);
        $mailer = $f3->CONTAINER->get(MailerInterface::class);
        $test->expect($mailer instanceof Mailer, 'Interface resolved');

        $executed = false;
        $f3->CONTAINER->set(Mailer::class, function() use(&$executed) {
            $executed = TRUE;
            return new Mailer();
        });
        $mailer2 = $f3->CONTAINER->make(MailerInterface::class);
        $test->expect($mailer2 instanceof Mailer && $executed === TRUE, 'Factory Closure called');

        $f3->CONTAINER->set('$bar', function() use($f3) {
            return $f3->CONTAINER->make(BazService::class, ['x' => 2048]);
        });
        /** @var Mailer $mailer3 */
        $nbar1 = $f3->CONTAINER->get('$bar');
        $test->expect($nbar1 instanceof BazService && $nbar1->getX() === 2048, 'Named service instance');
        $nbar2 = $f3->CONTAINER->get('$bar');
        $test->expect($nbar1 === $nbar2, 'Named service cached');

        $bar4 = $f3->make(BarService::class);
        $test->expect($bar4 instanceof BarService, 'make() alias usage');

        $executed = false;
        $f3->CONTAINER = function(string $class, array $args = []) use(&$executed) {
            $executed = $class;
            return new BarService(new FooService());
        };
        $bar5 = $f3->make(BarService::class);
        $test->expect($bar5 instanceof BarService && $executed === BarService::class, 'Closure CONTAINER');

        $f3->clear('CONTAINER');
        $test->expect(empty($f3->CONTAINER), 'CONTAINER reset');

        $foo = $f3->make(FooService::class);
        $test->expect($foo instanceof FooService, 'make() without CONTAINER');

        $mailer1 = $f3->make(Mailer2::class);
        $mailer2 = $f3->make(Mailer2::class);
        $test->expect($mailer1 instanceof Mailer2 && $mailer1 === $mailer2, 'make() without CONTAINER respects Prefab');

        $f3->set('results',$test->results());
    }
}


class FooService {}
class BarService {
    function __construct(
        protected FooService $foo,
    ) {}
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

interface MailerInterface {}

class Mailer implements MailerInterface {}

trait Xyz {
    use \F3\Prefab;
}
class Mailer2 extends Mailer {
    use Xyz;
}
