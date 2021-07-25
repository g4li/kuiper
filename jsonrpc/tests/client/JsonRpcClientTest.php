<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\client;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use kuiper\annotations\AnnotationReader;
use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\reflection\ReflectionDocBlockFactory;
use kuiper\rpc\client\ProxyGenerator;
use kuiper\rpc\client\RpcResponseNormalizer;
use kuiper\rpc\fixtures\HelloService;
use kuiper\rpc\fixtures\User;
use kuiper\rpc\fixtures\UserService;
use kuiper\rpc\transporter\HttpTransporter;
use kuiper\serializer\normalizer\ExceptionNormalizer;
use kuiper\serializer\Serializer;
use Laminas\Diactoros\RequestFactory;
use PHPUnit\Framework\TestCase;

class JsonRpcClientTest extends TestCase
{
    public function testSimple()
    {
        $proxyGenerator = new ProxyGenerator(new ReflectionDocBlockFactory());
        $generatedClass = $proxyGenerator->generate(HelloService::class);
        $generatedClass->eval();
        $class = $generatedClass->getClassName();
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'jsonrpc' => JsonRpcRequestInterface::JSONRPC_VERSION,
                'id' => 1,
                'result' => ['hello world'],
            ])),
        ]);
        $requests = [];
        $history = Middleware::history($requests);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $transporter = new HttpTransporter($client);
        $rpcMethodFactory = new JsonRpcMethodFactory();
        $httpFactory = new HttpFactory();
        $requestFactory = new JsonRpcRequestFactory(new RequestFactory(), $httpFactory, $rpcMethodFactory, 1);
        $responseFactory = new SimpleJsonRpcResponseFactory();
        /** @var HelloService $proxy */
        $proxy = new $class(new JsonRpcClient($transporter, $requestFactory, $responseFactory));
        $result = $proxy->hello('world');
        $this->assertEquals($result, 'hello world');
        $request = json_decode((string) $requests[0]['request']->getBody(), true);
        $this->assertEquals('kuiper.rpc.fixtures.HelloService.hello', $request['method']);
        $this->assertEquals(['world'], $request['params']);
        // echo $request->getBody();
    }

    public function testNormalizer()
    {
        $reflectionDocBlockFactory = new ReflectionDocBlockFactory();
        $proxyGenerator = new ProxyGenerator($reflectionDocBlockFactory);
        $generatedClass = $proxyGenerator->generate(UserService::class);
        $generatedClass->eval();
        $class = $generatedClass->getClassName();

        $user = new User();
        $user->setId(1);
        $user->setName('john');

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'jsonrpc' => JsonRpcRequestInterface::JSONRPC_VERSION,
                'id' => 1,
                'result' => [$user],
            ])),
            new Response(200, [], json_encode([
                'jsonrpc' => JsonRpcRequestInterface::JSONRPC_VERSION,
                'id' => 2,
                'result' => [[$user], 2],
            ])),
            new Response(200, [], json_encode([
                'jsonrpc' => JsonRpcRequestInterface::JSONRPC_VERSION,
                'id' => 3,
                'result' => [null],
            ])),
        ]);
        $requests = [];
        $history = Middleware::history($requests);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $transporter = new HttpTransporter($client);
        $httpFactory = new HttpFactory();
        $rpcMethodFactory = new JsonRpcMethodFactory();
        $requestFactory = new JsonRpcRequestFactory(new RequestFactory(), $httpFactory, $rpcMethodFactory, 1);
        $normalizer = new Serializer(AnnotationReader::getInstance(), $reflectionDocBlockFactory);
        $responseFactory = new JsonRpcResponseFactory(new RpcResponseNormalizer($normalizer, $reflectionDocBlockFactory), new ExceptionNormalizer());
        /** @var UserService $proxy */
        $proxy = new $class(new JsonRpcClient($transporter, $requestFactory, $responseFactory));
        $result = $proxy->findUser(1);
        $this->assertInstanceOf(User::class, $result);

        $request = json_decode((string) $requests[0]['request']->getBody(), true);
        $this->assertEquals('kuiper.rpc.fixtures.UserService.findUser', $request['method']);
        $this->assertEquals([1], $request['params']);

        $users = $proxy->findAllUser($total);
        $this->assertEquals(2, $total);
        $this->assertCount(1, $users);

        $user = new User();
        $user->setId(2);
        $user->setName('mary');
        $ret = $proxy->saveUser($user);
        $this->assertNull($ret);
        $request = json_decode((string) $requests[2]['request']->getBody(), true);
        // print_r($request);
        $this->assertEquals('kuiper.rpc.fixtures.UserService.saveUser', $request['method']);
        // echo $request->getBody();
    }

    public function testNoOutParam()
    {
        $reflectionDocBlockFactory = new ReflectionDocBlockFactory();
        $proxyGenerator = new ProxyGenerator($reflectionDocBlockFactory);
        $generatedClass = $proxyGenerator->generate(UserService::class);
        $generatedClass->eval();
        $class = $generatedClass->getClassName();

        $user = new User();
        $user->setId(1);
        $user->setName('john');

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'jsonrpc' => JsonRpcRequestInterface::JSONRPC_VERSION,
                'id' => 1,
                'result' => $user,
            ])),
        ]);
        $requests = [];
        $history = Middleware::history($requests);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $transporter = new HttpTransporter($client);
        $httpFactory = new HttpFactory();
        $rpcMethodFactory = new JsonRpcMethodFactory();
        $requestFactory = new JsonRpcRequestFactory(new RequestFactory(), $httpFactory, $rpcMethodFactory, 1);
        $normalizer = new Serializer(AnnotationReader::getInstance(), $reflectionDocBlockFactory);
        $responseFactory = new NoOutParamJsonRpcResponseFactory(new RpcResponseNormalizer($normalizer, $reflectionDocBlockFactory), new ExceptionNormalizer());
        /** @var UserService $proxy */
        $proxy = new $class(new JsonRpcClient($transporter, $requestFactory, $responseFactory));
        $result = $proxy->findUser(1);
        $this->assertInstanceOf(User::class, $result);

        $request = json_decode((string) $requests[0]['request']->getBody(), true);
        $this->assertEquals('kuiper.rpc.fixtures.UserService.findUser', $request['method']);
        $this->assertEquals([1], $request['params']);

        // echo $request->getBody();
    }
}