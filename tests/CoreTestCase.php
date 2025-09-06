<?php

namespace Tests;

use F3\Base;
use F3\Registry;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class CoreTestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        // clean up error handlers if the framework was booted
        if (Registry::exists(Base::class)) {
            restore_error_handler();
            restore_exception_handler();
        }
        Registry::reset();
        parent::tearDown();
    }
}
