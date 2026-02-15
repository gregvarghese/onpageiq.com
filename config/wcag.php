<?php

use App\Enums\AuditCategory;
use App\Enums\ImpactLevel;
use App\Enums\WcagLevel;

return [

    /*
    |--------------------------------------------------------------------------
    | WCAG 2.1 Success Criteria
    |--------------------------------------------------------------------------
    |
    | Complete listing of WCAG 2.1 success criteria with metadata for
    | automated accessibility auditing. Each criterion includes:
    | - name: Human-readable name
    | - level: WCAG conformance level (A, AA, AAA)
    | - category: Primary user impact category
    | - impact: Default impact level when criterion fails
    | - automated: Whether the criterion can be automatically tested
    | - description: Brief description of the criterion
    | - documentation_url: Link to official WCAG understanding document
    |
    */

    'criteria' => [

        /*
        |----------------------------------------------------------------------
        | Principle 1: Perceivable
        |----------------------------------------------------------------------
        */

        // Guideline 1.1 - Text Alternatives
        '1.1.1' => [
            'name' => 'Non-text Content',
            'level' => WcagLevel::A,
            'category' => AuditCategory::Vision,
            'impact' => ImpactLevel::Critical,
            'automated' => true,
            'description' => 'All non-text content has a text alternative that serves the equivalent purpose.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/non-text-content.html',
            'techniques' => ['G94', 'G95', 'H37', 'H36', 'H67'],
        ],

        // Guideline 1.2 - Time-based Media
        '1.2.1' => [
            'name' => 'Audio-only and Video-only (Prerecorded)',
            'level' => WcagLevel::A,
            'category' => AuditCategory::Hearing,
            'impact' => ImpactLevel::Serious,
            'automated' => false,
            'description' => 'Prerecorded audio-only and video-only content has alternatives.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/audio-only-and-video-only-prerecorded.html',
            'techniques' => ['G158', 'G159', 'G166'],
        ],

        '1.2.2' => [
            'name' => 'Captions (Prerecorded)',
            'level' => WcagLevel::A,
            'category' => AuditCategory::Hearing,
            'impact' => ImpactLevel::Critical,
            'automated' => 'partial',
            'description' => 'Captions are provided for prerecorded audio content in synchronized media.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/captions-prerecorded.html',
            'techniques' => ['G87', 'G93', 'H95'],
        ],

        '1.2.3' => [
            'name' => 'Audio Description or Media Alternative (Prerecorded)',
            'level' => WcagLevel::A,
            'category' => AuditCategory::Vision,
            'impact' => ImpactLevel::Serious,
            'automated' => false,
            'description' => 'Audio description or full text alternative is provided for prerecorded video.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/audio-description-or-media-alternative-prerecorded.html',
            'techniques' => ['G69', 'G78', 'G173'],
        ],

        '1.2.4' => [
            'name' => 'Captions (Live)',
            'level' => WcagLevel::AA,
            'category' => AuditCategory::Hearing,
            'impact' => ImpactLevel::Critical,
            'automated' => false,
            'description' => 'Captions are provided for all live audio content in synchronized media.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/captions-live.html',
            'techniques' => ['G9', 'G93'],
        ],

        '1.2.5' => [
            'name' => 'Audio Description (Prerecorded)',
            'level' => WcagLevel::AA,
            'category' => AuditCategory::Vision,
            'impact' => ImpactLevel::Serious,
            'automated' => false,
            'description' => 'Audio description is provided for all prerecorded video content.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/audio-description-prerecorded.html',
            'techniques' => ['G78', 'G173', 'G8'],
        ],

        // Guideline 1.3 - Adaptable
        '1.3.1' => [
            'name' => 'Info and Relationships',
            'level' => WcagLevel::A,
            'category' => AuditCategory::Vision,
            'impact' => ImpactLevel::Serious,
            'automated' => true,
            'description' => 'Information and relationships conveyed through presentation can be programmatically determined.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/info-and-relationships.html',
            'techniques' => ['G115', 'G117', 'G140', 'H42', 'H48', 'H51', 'H71', 'H73', 'H85', 'H97'],
        ],

        '1.3.2' => [
            'name' => 'Meaningful Sequence',
            'level' => WcagLevel::A,
            'category' => AuditCategory::Vision,
            'impact' => ImpactLevel::Serious,
            'automated' => 'partial',
            'description' => 'When content sequence affects meaning, a correct reading sequence can be programmatically determined.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/meaningful-sequence.html',
            'techniques' => ['G57', 'C6', 'C8', 'C27'],
        ],

        '1.3.3' => [
            'name' => 'Sensory Characteristics',
            'level' => WcagLevel::A,
            'category' => AuditCategory::General,
            'impact' => ImpactLevel::Moderate,
            'automated' => false,
            'description' => 'Instructions do not rely solely on sensory characteristics of components.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/sensory-characteristics.html',
            'techniques' => ['G96'],
        ],

        '1.3.4' => [
            'name' => 'Orientation',
            'level' => WcagLevel::AA,
            'category' => AuditCategory::Motor,
            'impact' => ImpactLevel::Serious,
            'automated' => true,
            'description' => 'Content does not restrict its view and operation to a single display orientation.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/orientation.html',
            'techniques' => ['G214'],
        ],

        '1.3.5' => [
            'name' => 'Identify Input Purpose',
            'level' => WcagLevel::AA,
            'category' => AuditCategory::Cognitive,
            'impact' => ImpactLevel::Moderate,
            'automated' => true,
            'description' => 'The purpose of input fields collecting user information can be programmatically determined.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/identify-input-purpose.html',
            'techniques' => ['H98'],
        ],

        '1.3.6' => [
            'name' => 'Identify Purpose',
            'level' => WcagLevel::AAA,
            'category' => AuditCategory::Cognitive,
            'impact' => ImpactLevel::Minor,
            'automated' => 'partial',
            'description' => 'The purpose of UI components, icons, and regions can be programmatically determined.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/identify-purpose.html',
            'techniques' => ['ARIA11'],
        ],

        // Guideline 1.4 - Distinguishable
        '1.4.1' => [
            'name' => 'Use of Color',
            'level' => WcagLevel::A,
            'category' => AuditCategory::Vision,
            'impact' => ImpactLevel::Serious,
            'automated' => 'partial',
            'description' => 'Color is not used as the only visual means of conveying information.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/use-of-color.html',
            'techniques' => ['G14', 'G111', 'G182', 'G183'],
        ],

        '1.4.2' => [
            'name' => 'Audio Control',
            'level' => WcagLevel::A,
            'category' => AuditCategory::Hearing,
            'impact' => ImpactLevel::Serious,
            'automated' => true,
            'description' => 'Audio that plays automatically can be paused, stopped, or volume controlled.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/audio-control.html',
            'techniques' => ['G60', 'G170', 'G171'],
        ],

        '1.4.3' => [
            'name' => 'Contrast (Minimum)',
            'level' => WcagLevel::AA,
            'category' => AuditCategory::Vision,
            'impact' => ImpactLevel::Serious,
            'automated' => true,
            'description' => 'Text has a contrast ratio of at least 4.5:1 (3:1 for large text).',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/contrast-minimum.html',
            'techniques' => ['G18', 'G145', 'G174'],
            'thresholds' => [
                'normal_text' => 4.5,
                'large_text' => 3.0,
                'large_text_size' => 18, // pt, or 14pt bold
            ],
        ],

        '1.4.4' => [
            'name' => 'Resize Text',
            'level' => WcagLevel::AA,
            'category' => AuditCategory::Vision,
            'impact' => ImpactLevel::Serious,
            'automated' => true,
            'description' => 'Text can be resized up to 200% without loss of content or functionality.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/resize-text.html',
            'techniques' => ['G142', 'G178', 'G179', 'C28'],
        ],

        '1.4.5' => [
            'name' => 'Images of Text',
            'level' => WcagLevel::AA,
            'category' => AuditCategory::Vision,
            'impact' => ImpactLevel::Moderate,
            'automated' => 'partial',
            'description' => 'Text is used instead of images of text (with exceptions).',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/images-of-text.html',
            'techniques' => ['C22', 'C30', 'G140'],
        ],

        '1.4.10' => [
            'name' => 'Reflow',
            'level' => WcagLevel::AA,
            'category' => AuditCategory::Vision,
            'impact' => ImpactLevel::Serious,
            'automated' => true,
            'description' => 'Content can be presented without horizontal scrolling at 320px width.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/reflow.html',
            'techniques' => ['C31', 'C32', 'C33', 'C38'],
            'thresholds' => [
                'min_width' => 320,
                'min_height' => 256,
            ],
        ],

        '1.4.11' => [
            'name' => 'Non-text Contrast',
            'level' => WcagLevel::AA,
            'category' => AuditCategory::Vision,
            'impact' => ImpactLevel::Serious,
            'automated' => true,
            'description' => 'UI components and graphical objects have a contrast ratio of at least 3:1.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/non-text-contrast.html',
            'techniques' => ['G195', 'G207', 'G209'],
            'thresholds' => [
                'ui_components' => 3.0,
                'graphical_objects' => 3.0,
            ],
        ],

        '1.4.12' => [
            'name' => 'Text Spacing',
            'level' => WcagLevel::AA,
            'category' => AuditCategory::Vision,
            'impact' => ImpactLevel::Moderate,
            'automated' => true,
            'description' => 'No loss of content when text spacing is adjusted.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/text-spacing.html',
            'techniques' => ['C35', 'C36'],
            'thresholds' => [
                'line_height' => 1.5,    // 1.5x font size
                'paragraph_spacing' => 2, // 2x font size
                'letter_spacing' => 0.12, // 0.12x font size
                'word_spacing' => 0.16,   // 0.16x font size
            ],
        ],

        '1.4.13' => [
            'name' => 'Content on Hover or Focus',
            'level' => WcagLevel::AA,
            'category' => AuditCategory::Motor,
            'impact' => ImpactLevel::Moderate,
            'automated' => 'partial',
            'description' => 'Additional content triggered by hover or focus is dismissible, hoverable, and persistent.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/content-on-hover-or-focus.html',
            'techniques' => ['SCR39'],
        ],

        /*
        |----------------------------------------------------------------------
        | Principle 2: Operable
        |----------------------------------------------------------------------
        */

        // Guideline 2.1 - Keyboard Accessible
        '2.1.1' => [
            'name' => 'Keyboard',
            'level' => WcagLevel::A,
            'category' => AuditCategory::Motor,
            'impact' => ImpactLevel::Critical,
            'automated' => 'partial',
            'description' => 'All functionality is operable through a keyboard interface.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/keyboard.html',
            'techniques' => ['G202', 'H91', 'SCR2', 'SCR20', 'SCR35'],
        ],

        '2.1.2' => [
            'name' => 'No Keyboard Trap',
            'level' => WcagLevel::A,
            'category' => AuditCategory::Motor,
            'impact' => ImpactLevel::Critical,
            'automated' => true,
            'description' => 'Keyboard focus can be moved away from any component using only a keyboard.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/no-keyboard-trap.html',
            'techniques' => ['G21', 'F10'],
        ],

        '2.1.4' => [
            'name' => 'Character Key Shortcuts',
            'level' => WcagLevel::A,
            'category' => AuditCategory::Motor,
            'impact' => ImpactLevel::Moderate,
            'automated' => 'partial',
            'description' => 'Character key shortcuts can be turned off, remapped, or are only active on focus.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/character-key-shortcuts.html',
            'techniques' => ['G217'],
        ],

        // Guideline 2.2 - Enough Time
        '2.2.1' => [
            'name' => 'Timing Adjustable',
            'level' => WcagLevel::A,
            'category' => AuditCategory::Cognitive,
            'impact' => ImpactLevel::Critical,
            'automated' => 'partial',
            'description' => 'Time limits can be turned off, adjusted, or extended.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/timing-adjustable.html',
            'techniques' => ['G133', 'G180', 'G198', 'SCR16', 'SCR33'],
        ],

        '2.2.2' => [
            'name' => 'Pause, Stop, Hide',
            'level' => WcagLevel::A,
            'category' => AuditCategory::Cognitive,
            'impact' => ImpactLevel::Serious,
            'automated' => true,
            'description' => 'Moving, blinking, or scrolling content can be paused, stopped, or hidden.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/pause-stop-hide.html',
            'techniques' => ['G4', 'G11', 'G152', 'G186', 'G187', 'SCR22', 'SCR33'],
        ],

        // Guideline 2.3 - Seizures and Physical Reactions
        '2.3.1' => [
            'name' => 'Three Flashes or Below Threshold',
            'level' => WcagLevel::A,
            'category' => AuditCategory::General,
            'impact' => ImpactLevel::Critical,
            'automated' => 'partial',
            'description' => 'Content does not flash more than three times per second.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/three-flashes-or-below-threshold.html',
            'techniques' => ['G15', 'G19', 'G176'],
        ],

        // Guideline 2.4 - Navigable
        '2.4.1' => [
            'name' => 'Bypass Blocks',
            'level' => WcagLevel::A,
            'category' => AuditCategory::Motor,
            'impact' => ImpactLevel::Moderate,
            'automated' => true,
            'description' => 'A mechanism exists to bypass blocks of content repeated on multiple pages.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/bypass-blocks.html',
            'techniques' => ['G1', 'G123', 'G124', 'H69', 'ARIA11', 'SCR28'],
        ],

        '2.4.2' => [
            'name' => 'Page Titled',
            'level' => WcagLevel::A,
            'category' => AuditCategory::General,
            'impact' => ImpactLevel::Serious,
            'automated' => true,
            'description' => 'Web pages have titles that describe topic or purpose.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/page-titled.html',
            'techniques' => ['G88', 'H25'],
        ],

        '2.4.3' => [
            'name' => 'Focus Order',
            'level' => WcagLevel::A,
            'category' => AuditCategory::Motor,
            'impact' => ImpactLevel::Serious,
            'automated' => 'partial',
            'description' => 'Components receive focus in an order that preserves meaning and operability.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/focus-order.html',
            'techniques' => ['G59', 'H4', 'C27', 'SCR26', 'SCR27', 'SCR37'],
        ],

        '2.4.4' => [
            'name' => 'Link Purpose (In Context)',
            'level' => WcagLevel::A,
            'category' => AuditCategory::Cognitive,
            'impact' => ImpactLevel::Moderate,
            'automated' => 'partial',
            'description' => 'The purpose of each link can be determined from the link text or context.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/link-purpose-in-context.html',
            'techniques' => ['G53', 'G91', 'H24', 'H30', 'H33', 'H77', 'H78', 'H79', 'H80', 'H81', 'ARIA7', 'ARIA8'],
        ],

        '2.4.5' => [
            'name' => 'Multiple Ways',
            'level' => WcagLevel::AA,
            'category' => AuditCategory::Cognitive,
            'impact' => ImpactLevel::Moderate,
            'automated' => false,
            'description' => 'More than one way is available to locate a page within a set of pages.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/multiple-ways.html',
            'techniques' => ['G63', 'G64', 'G125', 'G126', 'G161', 'G185'],
        ],

        '2.4.6' => [
            'name' => 'Headings and Labels',
            'level' => WcagLevel::AA,
            'category' => AuditCategory::Cognitive,
            'impact' => ImpactLevel::Moderate,
            'automated' => 'partial',
            'description' => 'Headings and labels describe topic or purpose.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/headings-and-labels.html',
            'techniques' => ['G130', 'G131'],
        ],

        '2.4.7' => [
            'name' => 'Focus Visible',
            'level' => WcagLevel::AA,
            'category' => AuditCategory::Motor,
            'impact' => ImpactLevel::Serious,
            'automated' => true,
            'description' => 'Keyboard focus indicator is visible.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/focus-visible.html',
            'techniques' => ['G149', 'G165', 'G195', 'C15', 'C40', 'SCR31'],
        ],

        // Guideline 2.5 - Input Modalities
        '2.5.1' => [
            'name' => 'Pointer Gestures',
            'level' => WcagLevel::A,
            'category' => AuditCategory::Motor,
            'impact' => ImpactLevel::Serious,
            'automated' => 'partial',
            'description' => 'Multi-point or path-based gestures have single-pointer alternatives.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/pointer-gestures.html',
            'techniques' => ['G215', 'G216'],
        ],

        '2.5.2' => [
            'name' => 'Pointer Cancellation',
            'level' => WcagLevel::A,
            'category' => AuditCategory::Motor,
            'impact' => ImpactLevel::Moderate,
            'automated' => 'partial',
            'description' => 'Functions using single pointer can be cancelled, or use up-event with abort/undo.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/pointer-cancellation.html',
            'techniques' => ['G210', 'G211', 'G212'],
        ],

        '2.5.3' => [
            'name' => 'Label in Name',
            'level' => WcagLevel::A,
            'category' => AuditCategory::Motor,
            'impact' => ImpactLevel::Serious,
            'automated' => true,
            'description' => 'For components with visible text labels, the accessible name contains the visible text.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/label-in-name.html',
            'techniques' => ['G208', 'G211'],
        ],

        '2.5.4' => [
            'name' => 'Motion Actuation',
            'level' => WcagLevel::A,
            'category' => AuditCategory::Motor,
            'impact' => ImpactLevel::Serious,
            'automated' => false,
            'description' => 'Functions operated by device motion can be operated by UI and motion can be disabled.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/motion-actuation.html',
            'techniques' => ['G213'],
        ],

        '2.5.5' => [
            'name' => 'Target Size',
            'level' => WcagLevel::AAA,
            'category' => AuditCategory::Motor,
            'impact' => ImpactLevel::Moderate,
            'automated' => true,
            'description' => 'Target size for pointer inputs is at least 44×44 CSS pixels.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/target-size.html',
            'techniques' => [],
            'thresholds' => [
                'min_size' => 44, // CSS pixels
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Principle 3: Understandable
        |----------------------------------------------------------------------
        */

        // Guideline 3.1 - Readable
        '3.1.1' => [
            'name' => 'Language of Page',
            'level' => WcagLevel::A,
            'category' => AuditCategory::General,
            'impact' => ImpactLevel::Serious,
            'automated' => true,
            'description' => 'The default human language of each page can be programmatically determined.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/language-of-page.html',
            'techniques' => ['H57'],
        ],

        '3.1.2' => [
            'name' => 'Language of Parts',
            'level' => WcagLevel::AA,
            'category' => AuditCategory::General,
            'impact' => ImpactLevel::Moderate,
            'automated' => 'partial',
            'description' => 'The language of each passage or phrase can be programmatically determined.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/language-of-parts.html',
            'techniques' => ['H58'],
        ],

        // Guideline 3.2 - Predictable
        '3.2.1' => [
            'name' => 'On Focus',
            'level' => WcagLevel::A,
            'category' => AuditCategory::Cognitive,
            'impact' => ImpactLevel::Moderate,
            'automated' => 'partial',
            'description' => 'Receiving focus does not initiate a change of context.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/on-focus.html',
            'techniques' => ['G107'],
        ],

        '3.2.2' => [
            'name' => 'On Input',
            'level' => WcagLevel::A,
            'category' => AuditCategory::Cognitive,
            'impact' => ImpactLevel::Moderate,
            'automated' => 'partial',
            'description' => 'Changing UI component settings does not automatically cause a change of context.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/on-input.html',
            'techniques' => ['G80', 'G13', 'SCR19', 'H32', 'H84'],
        ],

        '3.2.3' => [
            'name' => 'Consistent Navigation',
            'level' => WcagLevel::AA,
            'category' => AuditCategory::Cognitive,
            'impact' => ImpactLevel::Moderate,
            'automated' => false,
            'description' => 'Navigation mechanisms repeated on multiple pages occur in the same relative order.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/consistent-navigation.html',
            'techniques' => ['G61'],
        ],

        '3.2.4' => [
            'name' => 'Consistent Identification',
            'level' => WcagLevel::AA,
            'category' => AuditCategory::Cognitive,
            'impact' => ImpactLevel::Moderate,
            'automated' => false,
            'description' => 'Components with the same functionality are identified consistently.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/consistent-identification.html',
            'techniques' => ['G197'],
        ],

        // Guideline 3.3 - Input Assistance
        '3.3.1' => [
            'name' => 'Error Identification',
            'level' => WcagLevel::A,
            'category' => AuditCategory::Cognitive,
            'impact' => ImpactLevel::Serious,
            'automated' => 'partial',
            'description' => 'Input errors are automatically detected and described to the user in text.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/error-identification.html',
            'techniques' => ['G83', 'G84', 'G85', 'SCR18', 'SCR32', 'ARIA18', 'ARIA19', 'ARIA21'],
        ],

        '3.3.2' => [
            'name' => 'Labels or Instructions',
            'level' => WcagLevel::A,
            'category' => AuditCategory::Cognitive,
            'impact' => ImpactLevel::Serious,
            'automated' => true,
            'description' => 'Labels or instructions are provided when user input is required.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/labels-or-instructions.html',
            'techniques' => ['G89', 'G131', 'G162', 'G167', 'G184', 'H44', 'H65', 'H71', 'H90', 'ARIA1', 'ARIA6', 'ARIA9', 'ARIA17'],
        ],

        '3.3.3' => [
            'name' => 'Error Suggestion',
            'level' => WcagLevel::AA,
            'category' => AuditCategory::Cognitive,
            'impact' => ImpactLevel::Moderate,
            'automated' => false,
            'description' => 'If an error is detected and suggestions are known, they are provided to the user.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/error-suggestion.html',
            'techniques' => ['G83', 'G85', 'G177', 'SCR18', 'SCR32', 'ARIA2', 'ARIA18'],
        ],

        '3.3.4' => [
            'name' => 'Error Prevention (Legal, Financial, Data)',
            'level' => WcagLevel::AA,
            'category' => AuditCategory::Cognitive,
            'impact' => ImpactLevel::Serious,
            'automated' => false,
            'description' => 'For pages with legal, financial, or user data submissions, actions are reversible, checked, or confirmed.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/error-prevention-legal-financial-data.html',
            'techniques' => ['G98', 'G99', 'G155', 'G164', 'G168'],
        ],

        /*
        |----------------------------------------------------------------------
        | Principle 4: Robust
        |----------------------------------------------------------------------
        */

        // Guideline 4.1 - Compatible
        '4.1.1' => [
            'name' => 'Parsing',
            'level' => WcagLevel::A,
            'category' => AuditCategory::General,
            'impact' => ImpactLevel::Moderate,
            'automated' => true,
            'description' => 'Elements have complete start and end tags, are nested correctly, have unique IDs, and no duplicate attributes.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/parsing.html',
            'techniques' => ['G134', 'G192', 'H74', 'H75', 'H88', 'H93', 'H94'],
        ],

        '4.1.2' => [
            'name' => 'Name, Role, Value',
            'level' => WcagLevel::A,
            'category' => AuditCategory::Vision,
            'impact' => ImpactLevel::Critical,
            'automated' => true,
            'description' => 'For all UI components, name and role can be programmatically determined; states and values can be set.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/name-role-value.html',
            'techniques' => ['G108', 'G135', 'H64', 'H88', 'H91', 'ARIA4', 'ARIA5', 'ARIA14', 'ARIA16'],
        ],

        '4.1.3' => [
            'name' => 'Status Messages',
            'level' => WcagLevel::AA,
            'category' => AuditCategory::Vision,
            'impact' => ImpactLevel::Moderate,
            'automated' => 'partial',
            'description' => 'Status messages can be programmatically determined without receiving focus.',
            'documentation_url' => 'https://www.w3.org/WAI/WCAG21/Understanding/status-messages.html',
            'techniques' => ['ARIA19', 'ARIA22', 'ARIA23', 'G199'],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Criteria Groupings
    |--------------------------------------------------------------------------
    */

    'levels' => [
        'A' => [
            '1.1.1', '1.2.1', '1.2.2', '1.2.3', '1.3.1', '1.3.2', '1.3.3',
            '1.4.1', '1.4.2', '2.1.1', '2.1.2', '2.1.4', '2.2.1', '2.2.2',
            '2.3.1', '2.4.1', '2.4.2', '2.4.3', '2.4.4', '2.5.1', '2.5.2',
            '2.5.3', '2.5.4', '3.1.1', '3.2.1', '3.2.2', '3.3.1', '3.3.2',
            '4.1.1', '4.1.2',
        ],
        'AA' => [
            '1.2.4', '1.2.5', '1.3.4', '1.3.5', '1.4.3', '1.4.4', '1.4.5',
            '1.4.10', '1.4.11', '1.4.12', '1.4.13', '2.4.5', '2.4.6', '2.4.7',
            '3.1.2', '3.2.3', '3.2.4', '3.3.3', '3.3.4', '4.1.3',
        ],
        'AAA' => [
            '1.3.6', '2.5.5',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Priority Criteria
    |--------------------------------------------------------------------------
    |
    | These are the highest-priority AA criteria that should always be tested.
    |
    */

    'priority_aa' => [
        '1.4.3',  // Contrast (Minimum)
        '1.4.11', // Non-text Contrast
        '2.4.7',  // Focus Visible
        '1.3.4',  // Orientation
    ],

    /*
    |--------------------------------------------------------------------------
    | Framework Mappings
    |--------------------------------------------------------------------------
    |
    | Map WCAG criteria to other compliance frameworks.
    |
    */

    'framework_mappings' => [
        'section508' => [
            // Section 508 maps directly to WCAG 2.0 Level A and AA
            'requires_wcag_aa' => true,
        ],
        'en301549' => [
            // EN 301 549 requires WCAG 2.1 Level AA
            'requires_wcag_aa' => true,
            'additional_requirements' => [
                // Additional non-WCAG requirements from EN 301 549
            ],
        ],
        'ada' => [
            // ADA typically interpreted as WCAG 2.0/2.1 Level AA
            'requires_wcag_aa' => true,
        ],
    ],

];
