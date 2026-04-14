<?php

namespace Core;

#[\Attribute(\Attribute::TARGET_CLASS)]
class ApiDoc
{
    public function __construct(
        public string $summary     = '',
        public string $description = '',
        public ?array $request     = null,
        public array  $responses   = [],
    ) {}

    public function toArray(): array
    {
        return [
            'summary'     => $this->summary,
            'description' => $this->description,
            'request'     => $this->request,
            'responses'   => $this->responses,
        ];
    }
}