<?php
declare(strict_types=1);

namespace MyServiceTest;

class UnitTestCase extends TestCaseAbstract
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpEventEngine();
    }
}
