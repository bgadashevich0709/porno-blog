<?php

namespace App\Common\Validator;

use App\Common\Container\Container;
use App\Common\Validator\Constraint\ConstraintInterface;
use App\Common\Validator\Constraint\ConstraintValidatorInterface;
use App\Common\Validator\Exception\ValidationException;
use ReflectionClass;

readonly class Validator
{
    /**
     * Передаем контейнер, чтобы валидаторы могли инжектить в себя другие сервисы (например, БД), если нужно
     */
    public function __construct(
        private ?Container $container = null
    ) {}

    /**
     * Валидирует объект на основе зарегистрированных классов-валидаторов
     * @throws ValidationException
     */
    public function validate(object $object): void
    {
        $reflection = new ReflectionClass($object);
        $errors = [];

        foreach ($reflection->getProperties() as $property) {
            $propertyName = $property->getName();
            $value = $property->getValue($object);

            foreach ($property->getAttributes(ConstraintInterface::class, \ReflectionAttribute::IS_INSTANCEOF) as $attributeReflection) {
                /** @var ConstraintInterface $constraint */
                $constraint = $attributeReflection->newInstance();
                $validatorClass = $constraint->getValidatorClass();

                /** @var ConstraintValidatorInterface $validator */
                $validator = $this->container
                    ? $this->container->get($validatorClass)
                    : new $validatorClass();

                $errorMessage = $validator->validate($value, $constraint);

                if ($errorMessage !== null) {
                    $errors[$propertyName][] = $errorMessage;
                }
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}
