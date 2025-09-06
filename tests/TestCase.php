<?php

namespace Tests;

use F3\Base;
use F3\Registry;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected Base $f3;

    protected function setUp(): void
    {
        $this->f3 = \F3\Base::instance();
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        restore_exception_handler();
        // this ensures that the framework is re-booted for every new test -> test(...) or it(...)
        Registry::reset();
        parent::tearDown();
    }
}
