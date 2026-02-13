<?php

namespace App\Enums;

/**
 * Categories for AI usage tracking.
 */
enum AIUsageCategory: string
{
    case DocumentAnalysis = 'document_analysis';
    case SpellingCheck = 'spelling_check';
    case GrammarCheck = 'grammar_check';
    case SeoAnalysis = 'seo_analysis';
    case ReadabilityAnalysis = 'readability_analysis';
    case ContentExtraction = 'content_extraction';
    case Summarization = 'summarization';
    case Classification = 'classification';
    case General = 'general';

    /**
     * Get all category values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::DocumentAnalysis => 'Document Analysis',
            self::SpellingCheck => 'Spelling Check',
            self::GrammarCheck => 'Grammar Check',
            self::SeoAnalysis => 'SEO Analysis',
            self::ReadabilityAnalysis => 'Readability Analysis',
            self::ContentExtraction => 'Content Extraction',
            self::Summarization => 'Summarization',
            self::Classification => 'Classification',
            self::General => 'General',
        };
    }

    /**
     * Get all categories as options array for forms.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
