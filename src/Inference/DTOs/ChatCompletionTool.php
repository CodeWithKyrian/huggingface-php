<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\DTOs;

class ChatCompletionTool
{
    /**
     * @param array<string, mixed> $function
     */
    public function __construct(
        public string $type,
        public array $function,
    ) {}

    /**
     * @param null|array<string, mixed> $parameters
     */
    public static function function(string $name, ?string $description = null, ?array $parameters = null): self
    {
        $function = [
            'name' => $name,
        ];

        if (null !== $description) {
            $function['description'] = $description;
        }

        if (null !== $parameters) {
            $function['parameters'] = $parameters;
        }

        return new self('function', $function);
    }

    public function description(string $description): self
    {
        $this->function['description'] = $description;

        return $this;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function parameters(array $parameters): self
    {
        $this->function['parameters'] = $parameters;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'function' => $this->function,
        ];
    }
}
