<?php
declare(strict_types=1);
namespace Elang\Validation;
final class ValidationException extends \Elang\Http\HttpException
{
    public function __construct(public readonly array $errors) { parent::__construct(422, 'The submitted data is invalid.'); }
}
