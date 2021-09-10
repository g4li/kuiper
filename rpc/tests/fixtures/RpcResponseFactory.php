<?php

declare(strict_types=1);

namespace kuiper\rpc\fixtures;

use kuiper\rpc\client\RpcResponseFactoryInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponse;
use kuiper\rpc\RpcResponseInterface;
use Psr\Http\Message\ResponseInterface;

class RpcResponseFactory implements RpcResponseFactoryInterface
{
    private $result;

    /**
     * @param mixed $result
     */
    public function setResult($result): void
    {
        $this->result = $result;
    }

    public function createResponse(RpcRequestInterface $request, ResponseInterface $response): RpcResponseInterface
    {
        $request = $request->withRpcMethod($request->getRpcMethod()->withResult($this->result));

        return new RpcResponse($request, $response);
    }
}
