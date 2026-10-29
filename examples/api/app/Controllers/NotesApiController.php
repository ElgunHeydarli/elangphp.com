<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Models\Note;
use Elang\Auth\User;
use Elang\Data\{Models,PageRequest,Repository};
use Elang\Http\{Response,HttpException};
use Elang\Validation\Validator;
use Symfony\Component\HttpFoundation\{Request,Response as HttpResponse};
use Symfony\Component\Validator\Constraints as Assert;
final class NotesApiController
{
    public function __construct(private Models $models,private Validator $validator,private \Elang\Database\Database $database,private \Elang\Realtime\Events $events) {}
    private function repository(Request $request): Repository
    {
        $user=$request->attributes->get('_user');if(!$user instanceof User) throw new HttpException(401,'A valid bearer token is required.');
        return $this->models->of(Note::class)->where('owner_id',$user->id);
    }
    private function payload(Request $request): array
    {
        if(!str_contains($request->headers->get('Content-Type',''),'application/json')) throw new HttpException(415,'Use application/json.');
        try { $data=json_decode($request->getContent(),true,32,JSON_THROW_ON_ERROR); }catch(\JsonException){throw new HttpException(400,'Invalid JSON.');}
        if(!is_array($data)||($data!==[]&&array_is_list($data))) throw new HttpException(400,'Expected a JSON object.');
        $valid=$this->validator->validate($data,['title'=>[new Assert\NotBlank(),new Assert\Type('string'),new Assert\Length(max:160)],'body'=>[new Assert\Type('string'),new Assert\Length(max:5000)]]);
        $valid['body']??='';return $valid;
    }
    public function index(Request $request): HttpResponse { return Response::json($this->repository($request)->orderBy('id','DESC')->paginate(15,PageRequest::number($request->query->all()['page']??1))); }
    public function show(Request $request,int $id): HttpResponse { $note=$this->repository($request)->find($id)??throw new HttpException(404,'Note not found.');return Response::json(['data'=>$note]); }
    public function create(Request $request): HttpResponse
    {
        $repository=$this->repository($request);$user=$request->attributes->get('_user');$payload=$this->payload($request);$note=$this->database->connection()->transactional(function()use($repository,$payload,$user) { $note=$repository->create($payload+['owner_id'=>$user->id,'completed'=>0,'created_at'=>time()]);$this->events->publish('user:'.$user->id.':notes','note.created',['note'=>$note->toArray()]);return $note; });return Response::json(['data'=>$note],201,['Location'=>'/api/notes/'.$note->id]);
    }
    public function update(Request $request,int $id): HttpResponse
    {
        $repository=$this->repository($request);if(!$repository->find($id)) throw new HttpException(404,'Note not found.');
        $payload=$this->payload($request);$note=$this->database->connection()->transactional(function()use($repository,$payload,$request,$id) { $note=$repository->update($id,$payload)??throw new HttpException(409,'The note changed during this request.');$this->events->publish('user:'.$request->attributes->get('_user')->id.':notes','note.updated',['note'=>$note->toArray()]);return $note; });return Response::json(['data'=>$note]);
    }
    public function delete(Request $request,int $id): HttpResponse
    {
        $this->database->connection()->transactional(function()use($request,$id):void { if(!$this->repository($request)->delete($id)) throw new HttpException(404,'Note not found.');$this->events->publish('user:'.$request->attributes->get('_user')->id.':notes','note.deleted',['id'=>$id]); });return new HttpResponse('',204);
    }
}
