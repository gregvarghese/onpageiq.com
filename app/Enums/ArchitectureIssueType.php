<?php

namespace App\Enums;

enum ArchitectureIssueType: string
{
    case OrphanPage = 'orphan_page';
    case DeepPage = 'deep_page';
    case BrokenLink = 'broken_link';
    case RedirectChain = 'redirect_chain';
    case LowLinkEquity = 'low_link_equity';
    case MissingFromSitemap = 'missing_from_sitemap';
    case ExcessiveOutboundLinks = 'excessive_outbound_links';
    case NoInternalLinks = 'no_internal_links';
    case CircularRedirect = 'circular_redirect';
    case ThinContent = 'thin_content';
    case DuplicateContent = 'duplicate_content';

    public function label(): string
    {
        return match ($this) {
            self::OrphanPage => 'Orphan Page',
            self::DeepPage => 'Deep Page',
            self::BrokenLink => 'Broken Link',
            self::RedirectChain => 'Redirect Chain',
            self::LowLinkEquity => 'Low Link Equity',
            self::MissingFromSitemap => 'Missing from Sitemap',
            self::ExcessiveOutboundLinks => 'Excessive Outbound Links',
            self::NoInternalLinks => 'No Internal Links',
            self::CircularRedirect => 'Circular Redirect',
            self::ThinContent => 'Thin Content',
            self::DuplicateContent => 'Duplicate Content',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::OrphanPage => 'Page has no internal links pointing to it',
            self::DeepPage => 'Page requires too many clicks from homepage',
            self::BrokenLink => 'Link points to a non-existent page',
            self::RedirectChain => 'Multiple redirects before reaching final URL',
            self::LowLinkEquity => 'Page receives very little link equity from other pages',
            self::MissingFromSitemap => 'Page exists but is not in the XML sitemap',
            self::ExcessiveOutboundLinks => 'Page has too many outbound links diluting equity',
            self::NoInternalLinks => 'Page has no internal links to other pages',
            self::CircularRedirect => 'Redirect loop detected',
            self::ThinContent => 'Page has very little content',
            self::DuplicateContent => 'Page content is duplicated elsewhere on the site',
        };
    }

    public function severity(): ImpactLevel
    {
        return match ($this) {
            self::BrokenLink, self::CircularRedirect => ImpactLevel::Critical,
            self::OrphanPage, self::RedirectChain => ImpactLevel::Serious,
            self::DeepPage, self::LowLinkEquity, self::MissingFromSitemap => ImpactLevel::Moderate,
            self::ExcessiveOutboundLinks, self::NoInternalLinks, self::ThinContent => ImpactLevel::Minor,
            self::DuplicateContent => ImpactLevel::Serious,
        };
    }

    public function category(): string
    {
        return match ($this) {
            self::OrphanPage, self::DeepPage, self::LowLinkEquity => 'seo',
            self::BrokenLink, self::RedirectChain, self::CircularRedirect => 'technical',
            self::MissingFromSitemap => 'sitemap',
            self::ExcessiveOutboundLinks, self::NoInternalLinks => 'linking',
            self::ThinContent, self::DuplicateContent => 'content',
        };
    }
}
