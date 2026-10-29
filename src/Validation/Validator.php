<?php
declare(strict_types=1);
namespace Elang\Validation;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraint;
final class Validator
{
    public function validate(array $data, array $rules): array
    {
        $validator = Validation::createValidator();
        $errors = []; $validated = [];
        foreach ($rules as $field => $constraints) {
            $value = $data[$field] ?? null;
            foreach ((array) $constraints as $constraint) {
                if (!$constraint instanceof Constraint) throw new \InvalidArgumentException('Validation rules must be Symfony Constraint objects.');
                foreach ($validator->validate($value, $constraint) as $violation) $errors[$field][] = $violation->getMessage();
            }
            if (array_key_exists($field, $data)) $validated[$field] = $value;
        }
        if ($errors) throw new ValidationException($errors);
        return $validated;
    }
}
