<?php
declare(strict_types=1);
namespace App\Controllers;
use Elang\Database\Database;
use Elang\Http\{Response, HttpException};
use Elang\Validation\Validator;
use Symfony\Component\HttpFoundation\{Request, JsonResponse};
use Symfony\Component\Validator\Constraints as Assert;
final class PlaygroundController
{
    public function hello(Request $request): JsonResponse
    {
        $name = $request->query->all()['name'] ?? 'World';
        if (!is_string($name) || mb_strlen($name) > 80) throw new HttpException(400, 'Name must be a string of up to 80 characters.');
        return Response::json(['message' => 'Hello, '.$name.'!', 'framework' => 'Elang', 'status' => 'Ready to build.']);
    }
    public function validate(Request $request, Validator $validator): JsonResponse
    {
        if (!str_contains($request->headers->get('Content-Type', ''), 'application/json')) throw new HttpException(415, 'Send an application/json request.');
        try { $data = json_decode($request->getContent(), true, 32, JSON_THROW_ON_ERROR); }
        catch (\JsonException) { throw new HttpException(400, 'Invalid JSON body.'); }
        if (!is_array($data) || array_is_list($data) && $data !== []) throw new HttpException(400, 'Expected a JSON object.');
        $valid = $validator->validate($data, ['name' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Length(min: 2, max: 80)], 'email' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Email()]]);
        return Response::json(['message' => 'Looks good. Your input is valid.', 'data' => $valid]);
    }
    public function database(Database $database): JsonResponse
    {
        $rows = $database->table('examples')->orderBy('id', 'ASC')->setMaxResults(3)->fetchAllAssociative();
        return Response::json(['data' => $rows, 'source' => 'Live SQLite database']);
    }
}
