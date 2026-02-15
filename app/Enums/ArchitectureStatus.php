<?php

namespace App\Enums;

enum ArchitectureStatus: string
{
    case Pending = 'pending';
    case Crawling = 'crawling';
    case Analyzing = 'analyzing';
    case Ready = 'ready';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Crawling => 'Crawling',
            self::Analyzing => 'Analyzing',
            self::Ready => 'Ready',
            self::Failed => 'Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Crawling => 'blue',
            self::Analyzing => 'yellow',
            self::Ready => 'green',
            self::Failed => 'red',
        };
    }

    public function isProcessing(): bool
    {
        return in_array($this, [self::Crawling, self::Analyzing]);
    }

    public function isComplete(): bool
    {
        return in_array($this, [self::Ready, self::Failed]);
    }
}
