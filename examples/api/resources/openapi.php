<?php
declare(strict_types=1);
$note=['type'=>'object','properties'=>['id'=>['type'=>'integer'],'title'=>['type'=>'string'],'body'=>['type'=>'string'],'completed'=>['type'=>'boolean'],'created_at'=>['type'=>'integer']],'required'=>['id','title','body','completed','created_at']];
$input=['type'=>'object','properties'=>['title'=>['type'=>'string','minLength'=>1,'maxLength'=>160],'body'=>['type'=>'string','maxLength'=>5000]],'required'=>['title']];
$error=['description'=>'Request rejected. The JSON body contains message and request_id, and errors for validation failures.'];
$headers=[['in'=>'header','name'=>'Idempotency-Key','required'=>false,'schema'=>['type'=>'string','minLength'=>8,'maxLength'=>128],'description'=>'Reuse this key only when retrying the identical write.']];
$id=['in'=>'path','name'=>'id','required'=>true,'schema'=>['type'=>'integer','minimum'=>1]];
$body=['required'=>true,'content'=>['application/json'=>['schema'=>['$ref'=>'#/components/schemas/NoteInput']]]];
$one=['description'=>'One note.','content'=>['application/json'=>['schema'=>['type'=>'object','properties'=>['data'=>['$ref'=>'#/components/schemas/Note']]]]]];
$errors=['400'=>$error,'401'=>$error,'403'=>$error,'404'=>$error,'409'=>$error,'415'=>$error,'422'=>$error,'429'=>$error];
$document=['openapi'=>'3.1.0','info'=>['title'=>'Elang Notes API','version'=>'0.1.0-dev','description'=>'A working authenticated API starter. Notes belong to the authenticated user.'],'security'=>[['bearerAuth'=>[]]],'paths'=>[
    '/api/notes'=>['get'=>['summary'=>'List owned notes','parameters'=>[['in'=>'query','name'=>'page','schema'=>['type'=>'integer','minimum'=>1]]],'responses'=>['200'=>['description'=>'Paginated data with total, current_page, per_page, and last_page metadata.']]+$errors], 'post'=>['summary'=>'Create an owned note','parameters'=>$headers,'requestBody'=>$body,'responses'=>['201'=>$one]+$errors]],
    '/api/notes/{id}'=>['parameters'=>[$id],'get'=>['summary'=>'Read an owned note','responses'=>['200'=>$one]+$errors],'put'=>['summary'=>'Update an owned note','parameters'=>$headers,'requestBody'=>$body,'responses'=>['200'=>$one]+$errors],'delete'=>['summary'=>'Delete an owned note','parameters'=>$headers,'responses'=>['204'=>['description'=>'Deleted.']]+$errors]],
    '/api/notes/events'=>['get'=>['summary'=>'Stream private note events over SSE','parameters'=>[['in'=>'header','name'=>'Last-Event-ID','schema'=>['type'=>'string','pattern'=>'^[0-9]+$']]],'responses'=>['200'=>['description'=>'An SSE stream. Reconnect after 20 seconds with the last event ID.','content'=>['text/event-stream'=>['schema'=>['type'=>'string']]]]]+$errors]],
    '/api/realtime-token'=>['post'=>['summary'=>'Issue a short-lived WebSocket capability for the current user','responses'=>['200'=>['description'=>'token, expires_in, protocol and path. A write-scoped API token also grants note.create.']]+$errors]],
], 'components'=>['securitySchemes'=>['bearerAuth'=>['type'=>'http','scheme'=>'bearer']],'schemas'=>['Note'=>$note,'NoteInput'=>$input]]];

$schemas=&$document['components']['schemas'];
$schemas['User']=['type'=>'object','properties'=>['id'=>['type'=>'integer'],'name'=>['type'=>'string'],'email'=>['type'=>'string'],'role'=>['type'=>'string'],'verified'=>['type'=>'boolean']],'required'=>['id','name','email','role','verified']];
$schemas['Session']=['type'=>'object','properties'=>['user'=>['anyOf'=>[['$ref'=>'#/components/schemas/User'],['type'=>'null']]],'csrfToken'=>['type'=>'string'],'registration'=>['type'=>'boolean'],'mailAvailable'=>['type'=>'boolean'],'mfaRequired'=>['type'=>'boolean']],'required'=>['user','csrfToken','registration','mailAvailable','mfaRequired']];
$schemas['NotesPage']=['type'=>'object','properties'=>['data'=>['type'=>'array','items'=>['$ref'=>'#/components/schemas/Note']],'meta'=>['type'=>'object','properties'=>array_fill_keys(['total','per_page','current_page','last_page'],['type'=>'integer']),'required'=>['total','per_page','current_page','last_page']]],'required'=>['data','meta']];
$document['paths']['/api/notes']['get']['responses']['200']['content']['application/json']['schema']=['$ref'=>'#/components/schemas/NotesPage'];
$sessionResponse=['description'=>'Current session and rotated CSRF token.','content'=>['application/json'=>['schema'=>['$ref'=>'#/components/schemas/Session']]]];
$document['paths']['/api/session']['get']=['operationId'=>'getSession','summary'=>'Read cookie session and CSRF token','security'=>[],'responses'=>['200'=>$sessionResponse]+$errors];
foreach(['login'=>['email','password'],'register'=>['name','email','password','password_confirmation'],'mfa'=>['code'],'logout'=>[]] as $action=>$fields) {
    $op=['operationId'=>$action.'Session','summary'=>ucfirst($action).' using an HttpOnly session cookie','security'=>[],'parameters'=>[['in'=>'header','name'=>'X-CSRF-Token','required'=>true,'schema'=>['type'=>'string']]],'responses'=>['200'=>$sessionResponse]+$errors];
    if($action==='register') $op['responses']=['201'=>$sessionResponse]+$errors;
    if($action==='login') $op['responses']['202']=$sessionResponse;
    if($fields) $op['requestBody']=['required'=>true,'content'=>['application/json'=>['schema'=>['type'=>'object','properties'=>array_fill_keys($fields,['type'=>'string']),'required'=>$fields]]]];
    $document['paths']['/api/session/'.$action]['post']=$op;
}
$ids=['/api/notes'=>['get'=>'listNotes','post'=>'createNote'],'/api/notes/{id}'=>['get'=>'getNote','put'=>'updateNote','delete'=>'deleteNote'],'/api/notes/events'=>['get'=>'noteEvents'],'/api/realtime-token'=>['post'=>'realtimeToken']];
foreach($ids as $path=>$methods) foreach($methods as $method=>$id) $document['paths'][$path][$method]['operationId']=$id;
$document['security']=[['bearerAuth'=>[]],['sessionCookie'=>[]]];$document['components']['securitySchemes']['sessionCookie']=['type'=>'apiKey','in'=>'cookie','name'=>'elang_session'];
$document['info']['description'].=' Same-origin frontends can use /api/session and X-CSRF-Token on every unsafe cookie-authenticated request. Bearer tokens remain available for server/mobile clients.';
return $document;
