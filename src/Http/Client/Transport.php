<?php
declare(strict_types=1);
namespace Elang\Http\Client;
interface Transport { public function send(Request $request): Response; }
