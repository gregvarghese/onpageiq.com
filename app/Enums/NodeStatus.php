<?php

namespace App\Enums;

enum NodeStatus: string
{
    case Ok = 'ok';
    case Redirect = 'redirect';
    case ClientError = 'client_error';
    case ServerError = 'server_error';
    case Timeout = 'timeout';
    case Orphan = 'orphan';
    case Deep = 'deep';

    public function label(): string
    {
        return match ($this) {
            self::Ok => 'OK',
            self::Redirect => 'Redirect',
            self::ClientError => 'Client Error (4xx)',
            self::ServerError => 'Server Error (5xx)',
            self::Timeout => 'Timeout',
            self::Orphan => 'Orphan Page',
            self::Deep => 'Deep Page',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Ok => 'green',
            self::Redirect => 'yellow',
            self::ClientError => 'red',
            self::ServerError => 'red',
            self::Timeout => 'orange',
            self::Orphan => 'purple',
            self::Deep => 'blue',
        };
    }

    public function isError(): bool
    {
        return in_array($this, [self::ClientError, self::ServerError, self::Timeout]);
    }

    public function isWarning(): bool
    {
        return in_array($this, [self::Redirect, self::Orphan, self::Deep]);
    }

    public static function fromHttpStatus(int $status): self
    {
        return match (true) {
            $status >= 200 && $status < 300 => self::Ok,
            $status >= 300 && $status < 400 => self::Redirect,
            $status >= 400 && $status < 500 => self::ClientError,
            $status >= 500 => self::ServerError,
            default => self::Ok,
        };
    }
}
