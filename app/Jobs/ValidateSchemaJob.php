<?php

namespace App\Jobs;

use App\Models\SchemaValidation;
use App\Models\Url;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ValidateSchemaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 60;

    public function __construct(
        public Url $url,
        public ?int $scanId = null,
        public ?string $htmlContent = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $html = $this->htmlContent;

        if (! $html) {
            try {
                $response = Http::timeout(30)->get($this->url->url);
                $html = $response->body();
            } catch (\Exception $e) {
                Log::warning('Failed to fetch page for schema validation', [
                    'url_id' => $this->url->id,
                    'error' => $e->getMessage(),
                ]);

                return;
            }
        }

        // Clear previous validations for this URL
        $this->url->schemaValidations()->delete();

        // Extract and validate JSON-LD schemas
        $this->validateJsonLdSchemas($html);

        // Extract and validate Microdata
        $this->validateMicrodata($html);

        Log::info('Schema validation completed', [
            'url_id' => $this->url->id,
            'schemas_found' => $this->url->schemaValidations()->count(),
        ]);
    }

    /**
     * Extract and validate JSON-LD structured data.
     */
    protected function validateJsonLdSchemas(string $html): void
    {
        preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si', $html, $matches);

        foreach ($matches[1] as $jsonLd) {
            $data = json_decode(trim($jsonLd), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                SchemaValidation::create([
                    'url_id' => $this->url->id,
                    'scan_id' => $this->scanId,
                    'schema_type' => 'Unknown (Invalid JSON)',
                    'schema_format' => 'json-ld',
                    'is_valid' => false,
                    'errors' => ['Invalid JSON: '.json_last_error_msg()],
                    'raw_schema' => substr($jsonLd, 0, 5000),
                ]);

                continue;
            }

            // Handle @graph arrays
            $schemas = isset($data['@graph']) ? $data['@graph'] : [$data];

            foreach ($schemas as $schema) {
                $this->validateSchema($schema, 'json-ld', $jsonLd);
            }
        }
    }

    /**
     * Extract and validate Microdata.
     */
    protected function validateMicrodata(string $html): void
    {
        // Find elements with itemscope
        preg_match_all('/<[^>]+itemscope[^>]*itemtype=["\']([^"\']+)["\'][^>]*>/i', $html, $matches);

        foreach ($matches[1] as $itemType) {
            $schemaType = basename(parse_url($itemType, PHP_URL_PATH));

            SchemaValidation::create([
                'url_id' => $this->url->id,
                'scan_id' => $this->scanId,
                'schema_type' => $schemaType,
                'schema_format' => 'microdata',
                'is_valid' => true, // Basic detection, no deep validation
                'errors' => null,
                'warnings' => ['Microdata detected - consider migrating to JSON-LD for better tooling support'],
                'raw_schema' => null,
            ]);
        }
    }

    /**
     * Validate a single schema object.
     *
     * @param  array<string, mixed>  $schema
     */
    protected function validateSchema(array $schema, string $format, string $rawSchema): void
    {
        $type = $schema['@type'] ?? 'Unknown';
        $errors = [];
        $warnings = [];

        // Type-specific validation
        switch ($type) {
            case 'Organization':
                $this->validateOrganization($schema, $errors, $warnings);
                break;
            case 'WebSite':
                $this->validateWebSite($schema, $errors, $warnings);
                break;
            case 'Article':
            case 'NewsArticle':
            case 'BlogPosting':
                $this->validateArticle($schema, $errors, $warnings);
                break;
            case 'Product':
                $this->validateProduct($schema, $errors, $warnings);
                break;
            case 'LocalBusiness':
                $this->validateLocalBusiness($schema, $errors, $warnings);
                break;
            case 'BreadcrumbList':
                $this->validateBreadcrumb($schema, $errors, $warnings);
                break;
            case 'FAQPage':
                $this->validateFAQ($schema, $errors, $warnings);
                break;
            default:
                // Generic validation
                if (! isset($schema['@type'])) {
                    $errors[] = 'Missing required @type property';
                }
        }

        SchemaValidation::create([
            'url_id' => $this->url->id,
            'scan_id' => $this->scanId,
            'schema_type' => is_array($type) ? implode(', ', $type) : $type,
            'schema_format' => $format,
            'is_valid' => empty($errors),
            'errors' => empty($errors) ? null : $errors,
            'warnings' => empty($warnings) ? null : $warnings,
            'raw_schema' => substr($rawSchema, 0, 5000),
        ]);
    }

    /**
     * Validate Organization schema.
     *
     * @param  array<string, mixed>  $schema
     * @param  array<string>  $errors
     * @param  array<string>  $warnings
     */
    protected function validateOrganization(array $schema, array &$errors, array &$warnings): void
    {
        if (empty($schema['name'])) {
            $errors[] = 'Organization is missing required "name" property';
        }
        if (empty($schema['url'])) {
            $warnings[] = 'Organization should have a "url" property';
        }
        if (empty($schema['logo'])) {
            $warnings[] = 'Organization should have a "logo" property for rich results';
        }
    }

    /**
     * Validate WebSite schema.
     *
     * @param  array<string, mixed>  $schema
     * @param  array<string>  $errors
     * @param  array<string>  $warnings
     */
    protected function validateWebSite(array $schema, array &$errors, array &$warnings): void
    {
        if (empty($schema['url'])) {
            $errors[] = 'WebSite is missing required "url" property';
        }
        if (empty($schema['name'])) {
            $warnings[] = 'WebSite should have a "name" property';
        }
    }

    /**
     * Validate Article schema.
     *
     * @param  array<string, mixed>  $schema
     * @param  array<string>  $errors
     * @param  array<string>  $warnings
     */
    protected function validateArticle(array $schema, array &$errors, array &$warnings): void
    {
        if (empty($schema['headline'])) {
            $errors[] = 'Article is missing required "headline" property';
        }
        if (empty($schema['author'])) {
            $warnings[] = 'Article should have an "author" property';
        }
        if (empty($schema['datePublished'])) {
            $warnings[] = 'Article should have a "datePublished" property';
        }
        if (empty($schema['image'])) {
            $warnings[] = 'Article should have an "image" property for rich results';
        }
    }

    /**
     * Validate Product schema.
     *
     * @param  array<string, mixed>  $schema
     * @param  array<string>  $errors
     * @param  array<string>  $warnings
     */
    protected function validateProduct(array $schema, array &$errors, array &$warnings): void
    {
        if (empty($schema['name'])) {
            $errors[] = 'Product is missing required "name" property';
        }
        if (empty($schema['image'])) {
            $warnings[] = 'Product should have an "image" property';
        }
        if (empty($schema['offers'])) {
            $warnings[] = 'Product should have an "offers" property for rich results';
        }
    }

    /**
     * Validate LocalBusiness schema.
     *
     * @param  array<string, mixed>  $schema
     * @param  array<string>  $errors
     * @param  array<string>  $warnings
     */
    protected function validateLocalBusiness(array $schema, array &$errors, array &$warnings): void
    {
        if (empty($schema['name'])) {
            $errors[] = 'LocalBusiness is missing required "name" property';
        }
        if (empty($schema['address'])) {
            $warnings[] = 'LocalBusiness should have an "address" property';
        }
        if (empty($schema['telephone'])) {
            $warnings[] = 'LocalBusiness should have a "telephone" property';
        }
    }

    /**
     * Validate BreadcrumbList schema.
     *
     * @param  array<string, mixed>  $schema
     * @param  array<string>  $errors
     * @param  array<string>  $warnings
     */
    protected function validateBreadcrumb(array $schema, array &$errors, array &$warnings): void
    {
        if (empty($schema['itemListElement'])) {
            $errors[] = 'BreadcrumbList is missing required "itemListElement" property';
        }
    }

    /**
     * Validate FAQPage schema.
     *
     * @param  array<string, mixed>  $schema
     * @param  array<string>  $errors
     * @param  array<string>  $warnings
     */
    protected function validateFAQ(array $schema, array &$errors, array &$warnings): void
    {
        if (empty($schema['mainEntity'])) {
            $errors[] = 'FAQPage is missing required "mainEntity" property';
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Schema validation job failed', [
            'url_id' => $this->url->id,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return [
            'schema',
            'url:'.$this->url->id,
            'project:'.$this->url->project_id,
        ];
    }
}
