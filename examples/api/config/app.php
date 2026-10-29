<?php
declare(strict_types=1);
return ['name'=>'Elang Notes API','starter'=>'api','cors'=>['origins'=>array_filter(array_map('trim',explode(',',\Elang\Support\Config::env('FRONTEND_URL','http://localhost:3000'))))],'realtime'=>['origins'=>array_filter(array_map('trim',explode(',',\Elang\Support\Config::env('FRONTEND_URL','http://localhost:3000'))))],'auth'=>['registration'=>true,'home'=>'/dashboard','token_scopes'=>['notes:read','notes:write']]];
