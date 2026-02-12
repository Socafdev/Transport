<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class UniquePerEntreprise extends Constraint
{
    public string $message = 'Cette valeur existe déjà pour votre entreprise';

    public array $fields = [];

    // You can use #[HasNamedArguments] to make some constraint options required.
    // All configurable options must be passed to the constructor.
    public function __construct(
        array $fields = [],        // ✅ maintenant c’est un tableau
        ?string $message = null,
        public string $mode = 'strict',
        ?array $groups = null,
        mixed $payload = null,
    ) {
         $this->fields = $fields;

        if ($message) {
            $this->message = $message;
        }
        parent::__construct([], $groups, $payload);
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * indique à Symfony que fields est obligatoire lors de l’utilisation de la contrainte.
     * @return string[]
     */
    public function getRequiredOptions(): array
    {
        return ['fields'];
    }
}