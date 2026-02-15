<?php

namespace App\Enums;

enum LinkType: string
{
    case Navigation = 'navigation';
    case Content = 'content';
    case Footer = 'footer';
    case Sidebar = 'sidebar';
    case Header = 'header';
    case Breadcrumb = 'breadcrumb';
    case Pagination = 'pagination';
    case External = 'external';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Navigation => 'Navigation',
            self::Content => 'Content',
            self::Footer => 'Footer',
            self::Sidebar => 'Sidebar',
            self::Header => 'Header',
            self::Breadcrumb => 'Breadcrumb',
            self::Pagination => 'Pagination',
            self::External => 'External',
            self::Unknown => 'Unknown',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Navigation => '#3B82F6', // blue
            self::Content => '#10B981',    // green
            self::Footer => '#6B7280',     // gray
            self::Sidebar => '#8B5CF6',    // purple
            self::Header => '#EC4899',     // pink
            self::Breadcrumb => '#F59E0B', // amber
            self::Pagination => '#06B6D4', // cyan
            self::External => '#EF4444',   // red
            self::Unknown => '#9CA3AF',    // gray
        };
    }

    public function isInternal(): bool
    {
        return $this !== self::External;
    }
}
