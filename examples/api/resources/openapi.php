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
return ['openapi'=>'3.1.0','info'=>['title'=>'Elang Notes API','version'=>'0.1.0-dev','description'=>'A working authenticated API starter. Notes belong to the authenticated user.'],'security'=>[['bearerAuth'=>[]]],'paths'=>[
    '/api/notes'=>['get'=>['summary'=>'List owned notes','parameters'=>[['in'=>'query','name'=>'page','schema'=>['type'=>'integer','minimum'=>1]]],'responses'=>['200'=>['description'=>'Paginated data with total, current_page, per_page, and last_page metadata.']]+$errors], 'post'=>['summary'=>'Create an owned note','parameters'=>$headers,'requestBody'=>$body,'responses'=>['201'=>$one]+$errors]],
    '/api/notes/{id}'=>['parameters'=>[$id],'get'=>['summary'=>'Read an owned note','responses'=>['200'=>$one]+$errors],'put'=>['summary'=>'Update an owned note','parameters'=>$headers,'requestBody'=>$body,'responses'=>['200'=>$one]+$errors],'delete'=>['summary'=>'Delete an owned note','parameters'=>$headers,'responses'=>['204'=>['description'=>'Deleted.']]+$errors]],
    '/api/notes/events'=>['get'=>['summary'=>'Stream private note events over SSE','parameters'=>[['in'=>'header','name'=>'Last-Event-ID','schema'=>['type'=>'string','pattern'=>'^[0-9]+$']]],'responses'=>['200'=>['description'=>'An SSE stream. Reconnect after 20 seconds with the last event ID.','content'=>['text/event-stream'=>['schema'=>['type'=>'string']]]]]+$errors]],
    '/api/realtime-token'=>['post'=>['summary'=>'Issue a short-lived WebSocket capability for the current user','responses'=>['200'=>['description'=>'token, expires_in, protocol and path. A write-scoped API token also grants note.create.']]+$errors]],
], 'components'=>['securitySchemes'=>['bearerAuth'=>['type'=>'http','scheme'=>'bearer']],'schemas'=>['Note'=>$note,'NoteInput'=>$input]]];
