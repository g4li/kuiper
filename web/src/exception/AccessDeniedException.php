<?php
namespace kuiper\web\exception;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AccessDeniedException extends HttpException
{
    /**
     * @var int
     */
    protected $statusCode = 403;
}
