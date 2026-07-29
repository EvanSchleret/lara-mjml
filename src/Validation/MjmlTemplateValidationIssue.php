<?php

namespace EvanSchleret\LaraMjml\Validation;

final class MjmlTemplateValidationIssue
{
    public function __construct(
        public readonly int $line,
        public readonly string $message,
        public readonly string $tagName,
    ) {}

    public function formattedMessage(): string
    {
        return "Line {$this->line}: {$this->message}";
    }

    public function toArray(): array
    {
        return [
            'line' => $this->line,
            'message' => $this->message,
            'tag' => $this->tagName,
        ];
    }
}
