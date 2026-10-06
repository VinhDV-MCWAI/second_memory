<?php

declare(strict_types=1);

namespace App\Enums;

enum TypeOfMethod: int
{
    case GET = 0;
    case POST = 1;
    case PUT = 2;
    case PATCH = 3;
    case DELETE = 4;

    public static function fromName(string $method): ?self
    {
        return match (strtoupper($method)) {
            'GET' => self::GET,
            'POST' => self::POST,
            'PUT' => self::PUT,
            'PATCH' => self::PATCH,
            'DELETE' => self::DELETE,
            default => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::GET => 'GET',
            self::POST => 'POST',
            self::PUT => 'PUT',
            self::PATCH => 'PATCH',
            self::DELETE => 'DELETE',
        };
    }
}
