<?php

declare(strict_types=1);

namespace kuiper\rpc\client;

use kuiper\reflection\ReflectionDocBlockFactory;
use kuiper\rpc\fixtures\UserService;
use PHPUnit\Framework\TestCase;

class ProxyGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $generator = new ProxyGenerator(new ReflectionDocBlockFactory());
        $result = $generator->generate(UserService::class);
        $this->assertNotNull($result->getCode());
        // echo $result->getCode();
        $this->assertEquals(file_get_contents(dirname(__DIR__).'/fixtures/UserServiceProxy.txt'), $result->getCode());
    }
}
