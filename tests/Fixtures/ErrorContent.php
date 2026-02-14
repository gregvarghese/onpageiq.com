<?php

namespace Tests\Fixtures;

use App\Services\Browser\PageContent;

/**
 * Test fixtures with intentional spelling, grammar, and other errors.
 */
class ErrorContent
{
    /**
     * Content with intentional spelling errors.
     */
    public static function withSpellingErrors(): PageContent
    {
        $text = <<<'TEXT'
Welcome to our websiet! We are a leading compnay in the tech industy.

Our servises include web develpoment, mobile app creation, and cloud infrastucture.
We prvide excelent custmer support to all our clents.

Our team of expereinced profesionals will help you achive your business golas.
Contact us today to lern more about how we can help your busness grow.
TEXT;

        return new PageContent(
            url: 'https://test-spelling.example.com',
            html: "<html><body>{$text}</body></html>",
            text: $text,
            title: 'Test Page with Spelling Errors',
            meta: ['description' => 'A test page with intentional spelling errors'],
            wordCount: str_word_count($text)
        );
    }

    /**
     * Content with intentional grammar errors.
     */
    public static function withGrammarErrors(): PageContent
    {
        $text = <<<'TEXT'
Welcome to our company. We provides the best services in the industry.

Their going to love our products. Its the best choice for you're business.
Each of the team members have their own expertise. The data show that we succeeds.

Me and my team works hard every day. Between you and I, this is the best solution.
He don't know about the meeting. She have been working here for years.
TEXT;

        return new PageContent(
            url: 'https://test-grammar.example.com',
            html: "<html><body>{$text}</body></html>",
            text: $text,
            title: 'Test Page with Grammar Errors',
            meta: ['description' => 'A test page with intentional grammar errors'],
            wordCount: str_word_count($text)
        );
    }

    /**
     * Content with SEO issues.
     */
    public static function withSeoIssues(): PageContent
    {
        $html = <<<'HTML'
<html>
<head>
    <title>This is a very long title that exceeds the recommended sixty character limit for SEO optimization purposes and should be flagged</title>
</head>
<body>
    <h2>Welcome</h2>
    <p>This page has no H1 tag, which is bad for SEO.</p>
    <h4>Features</h4>
    <p>Skipped heading levels from H2 to H4.</p>
</body>
</html>
HTML;

        $text = 'Welcome. This page has no H1 tag, which is bad for SEO. Features. Skipped heading levels from H2 to H4.';

        return new PageContent(
            url: 'https://test-seo.example.com',
            html: $html,
            text: $text,
            title: 'This is a very long title that exceeds the recommended sixty character limit for SEO optimization purposes and should be flagged',
            meta: [], // Missing meta description
            wordCount: str_word_count($text)
        );
    }

    /**
     * Content with readability issues.
     */
    public static function withReadabilityIssues(): PageContent
    {
        $text = <<<'TEXT'
The implementation of synergistic paradigm shifts through the utilization of cutting-edge blockchain-enabled artificial intelligence machine learning algorithms necessitates a comprehensive understanding of the multifaceted technological landscape that encompasses distributed ledger technology, neural network architectures, and advanced cryptographic methodologies, all of which must be integrated seamlessly within the existing enterprise infrastructure to facilitate optimal operational efficiency and maximize stakeholder value creation while simultaneously minimizing potential cybersecurity vulnerabilities and ensuring regulatory compliance across multiple jurisdictions.

This paragraph contains excessive jargon and is far too long for comfortable reading, making it difficult for users to understand the core message being conveyed without significant cognitive effort and multiple re-readings of the content.
TEXT;

        return new PageContent(
            url: 'https://test-readability.example.com',
            html: "<html><body>{$text}</body></html>",
            text: $text,
            title: 'Test Page with Readability Issues',
            meta: ['description' => 'A test page with readability problems'],
            wordCount: str_word_count($text)
        );
    }

    /**
     * Content with multiple types of errors.
     */
    public static function withMixedErrors(): PageContent
    {
        $text = <<<'TEXT'
Welcom to our compnay! We provides excelent servises.

Their going to love what we offer. Its the best sollution for you're busness needs.

The implementation of synergistic paradigm shifts through the utilization of cutting-edge blockchain-enabled artificial intelligence machine learning algorithms necessitates comprehensive understanding.
TEXT;

        $html = <<<HTML
<html>
<head>
    <title>This title is way too long and should be flagged by the SEO checker for exceeding recommended limits</title>
</head>
<body>
    <h3>Welcome</h3>
    {$text}
</body>
</html>
HTML;

        return new PageContent(
            url: 'https://test-mixed.example.com',
            html: $html,
            text: $text,
            title: 'This title is way too long and should be flagged by the SEO checker for exceeding recommended limits',
            meta: [],
            wordCount: str_word_count($text)
        );
    }

    /**
     * Clean content with no errors.
     */
    public static function clean(): PageContent
    {
        $text = <<<'TEXT'
Welcome to our company! We provide excellent services to all our customers.

Our team of experienced professionals is ready to help you achieve your goals.
Contact us today to learn more about how we can help your business grow.
TEXT;

        $html = <<<HTML
<html>
<head>
    <title>Welcome to Our Company</title>
    <meta name="description" content="We provide excellent services to all our customers.">
</head>
<body>
    <h1>Welcome to Our Company</h1>
    {$text}
</body>
</html>
HTML;

        return new PageContent(
            url: 'https://test-clean.example.com',
            html: $html,
            text: $text,
            title: 'Welcome to Our Company',
            meta: ['description' => 'We provide excellent services to all our customers.'],
            wordCount: str_word_count($text)
        );
    }
}
