<?php
declare(strict_types=1);
namespace Elang\Http\Client;
use GuzzleHttp\{Client,ClientInterface};
final class GuzzleTransport implements Transport
{
    private ClientInterface $client;
    public function __construct(?ClientInterface $client = null) { $this->client = $client ?? new Client(); }
    public function send(Request $request): Response
    {
        try {
            $response = $this->client->request($request->method, $request->url, ['headers'=>$request->headers,'body'=>$request->body,'timeout'=>$request->timeout,'read_timeout'=>$request->timeout,'connect_timeout'=>$request->connectTimeout,'verify'=>true,'http_errors'=>false,'allow_redirects'=>false,'cookies'=>false,'stream'=>true]);
            $stream = $response->getBody(); $body = '';
            try {
                $declared = $response->getHeaderLine('Content-Length');
                if (ctype_digit($declared) && (float) $declared > $request->maxResponseBytes) throw new \LengthException('The HTTP response exceeds the configured size limit.');
                while (!$stream->eof()) {
                    $body .= $stream->read(8192);
                    if (strlen($body) > $request->maxResponseBytes) throw new \LengthException('The HTTP response exceeds the configured size limit.');
                }
            } finally { $stream->close(); }
            return new Response($response->getStatusCode(), $body, $response->getHeaders());
        } catch (\GuzzleHttp\Exception\TransferException $error) { throw new ConnectionException('The outbound HTTP connection failed.', 0, $error); }
    }
}
