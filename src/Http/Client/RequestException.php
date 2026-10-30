<?php
declare(strict_types=1);
namespace Elang\Http\Client;
final class RequestException extends \RuntimeException
{
    public function __construct(public readonly Response $response) { parent::__construct('The remote server returned HTTP '.$response->status.'.', $response->status); }
}
