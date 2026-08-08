<?php
declare(strict_types=1);

namespace Ersaal\API;

class Response
{
    private int $statusCode;
    private array $data;

    public function __construct(int $statusCode, array $data = [])
    {
        $this->statusCode = $statusCode;
        $this->data = $data;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getData(): array
    {
        return $this->data;
    }
    
    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
    
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function getProjectName(): ?string
    {
        // 1. Check top-level 'project_name' (actual Ersaal API ProjectController response)
        if (!empty($this->data['project_name']) && is_string($this->data['project_name'])) {
            return trim($this->data['project_name']);
        }
        // 2. Fallback for 'data.project_name'
        if (!empty($this->data['data']['project_name']) && is_string($this->data['data']['project_name'])) {
            return trim($this->data['data']['project_name']);
        }
        // 3. Fallback for 'data.name'
        if (!empty($this->data['data']['name']) && is_string($this->data['data']['name'])) {
            return trim($this->data['data']['name']);
        }
        // 4. Fallback for top-level 'name'
        if (!empty($this->data['name']) && is_string($this->data['name'])) {
            return trim($this->data['name']);
        }
        
        return null;
    }
}

