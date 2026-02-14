<?php

namespace Database\Seeders;

use App\Models\IndustryDictionary;
use App\Models\IndustryDictionaryWord;
use Illuminate\Database\Seeder;

class IndustryDictionarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dictionaries = $this->getDictionaries();

        foreach ($dictionaries as $slug => $data) {
            $dictionary = IndustryDictionary::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'is_active' => true,
                ]
            );

            // Clear existing words and re-add
            $dictionary->words()->delete();

            // Use array_unique to remove duplicates
            $uniqueWords = array_unique(array_map(fn ($w) => strtolower(trim($w)), $data['words']));

            foreach ($uniqueWords as $word) {
                if (empty($word)) {
                    continue;
                }

                IndustryDictionaryWord::create([
                    'industry_dictionary_id' => $dictionary->id,
                    'word' => $word,
                ]);
            }

            $dictionary->updateWordCount();
        }
    }

    /**
     * Get the industry dictionaries with their word lists.
     *
     * @return array<string, array{name: string, description: string, words: array<string>}>
     */
    protected function getDictionaries(): array
    {
        return [
            'medical' => [
                'name' => 'Medical & Healthcare',
                'description' => 'Medical terminology, drug names, anatomy terms, and healthcare vocabulary.',
                'words' => [
                    // General medical terms
                    'healthcare', 'telehealth', 'telemedicine', 'ehealth', 'mhealth',
                    'ehr', 'emr', 'hipaa', 'phi', 'ephi', 'fda', 'cdc', 'nih', 'who',
                    'icd', 'cpt', 'ndc', 'npi', 'cms', 'hhs', 'aco', 'hmo', 'ppo',
                    'copay', 'copayment', 'coinsurance', 'deductible', 'formulary',
                    'preauthorization', 'preauth', 'precertification', 'referral',
                    'outpatient', 'inpatient', 'ambulatory', 'triage', 'prognosis',
                    'diagnosis', 'diagnoses', 'etiology', 'pathology', 'oncology',
                    'cardiology', 'neurology', 'dermatology', 'radiology', 'pediatrics',
                    'geriatrics', 'orthopedics', 'urology', 'nephrology', 'pulmonology',
                    'gastroenterology', 'endocrinology', 'rheumatology', 'hematology',
                    'immunology', 'ophthalmology', 'otolaryngology', 'anesthesiology',

                    // Anatomy
                    'cardiovascular', 'gastrointestinal', 'musculoskeletal', 'neurological',
                    'respiratory', 'genitourinary', 'integumentary', 'lymphatic',
                    'endocrine', 'hepatic', 'renal', 'pulmonary', 'cerebral', 'spinal',

                    // Procedures and treatments
                    'laparoscopy', 'laparoscopic', 'arthroscopy', 'endoscopy', 'colonoscopy',
                    'mammography', 'ultrasound', 'mri', 'ct', 'xray', 'biopsy', 'dialysis',
                    'chemotherapy', 'radiotherapy', 'immunotherapy', 'transplant',
                    'angioplasty', 'catheterization', 'intubation', 'ventilation',

                    // Medications and pharmacology
                    'pharmacology', 'pharmacokinetics', 'pharmacodynamics', 'bioavailability',
                    'dosage', 'contraindication', 'contraindicated', 'prophylaxis',
                    'prophylactic', 'antibiotic', 'antiviral', 'antifungal', 'analgesic',
                    'antipyretic', 'antihistamine', 'anticoagulant', 'antidepressant',
                    'benzodiazepine', 'corticosteroid', 'nsaid', 'nsaids', 'opioid',
                    'acetaminophen', 'ibuprofen', 'aspirin', 'metformin', 'lisinopril',
                    'atorvastatin', 'omeprazole', 'amlodipine', 'metoprolol', 'losartan',

                    // Conditions
                    'hypertension', 'hypotension', 'tachycardia', 'bradycardia', 'arrhythmia',
                    'fibrillation', 'myocardial', 'ischemia', 'ischemic', 'thrombosis',
                    'embolism', 'aneurysm', 'atherosclerosis', 'cardiomyopathy',
                    'diabetes', 'diabetic', 'hyperglycemia', 'hypoglycemia', 'insulin',
                    'alzheimer', 'parkinson', 'dementia', 'epilepsy', 'seizure',
                    'migraine', 'neuropathy', 'sclerosis', 'fibromyalgia', 'arthritis',
                    'osteoporosis', 'osteoarthritis', 'scoliosis', 'herniation',

                    // Lab and diagnostics
                    'hematocrit', 'hemoglobin', 'platelet', 'leukocyte', 'erythrocyte',
                    'lymphocyte', 'neutrophil', 'antibody', 'antigen', 'pathogen',
                    'serology', 'cytology', 'histology', 'urinalysis', 'electrolyte',
                    'creatinine', 'bilirubin', 'albumin', 'lipid', 'triglyceride',
                    'cholesterol', 'ldl', 'hdl', 'glucose', 'hba1c', 'tsh', 'psa',
                ],
            ],

            'automotive' => [
                'name' => 'Automotive',
                'description' => 'Automotive industry terminology, vehicle parts, and dealership vocabulary.',
                'words' => [
                    // General automotive
                    'automotive', 'automobile', 'vehicular', 'oem', 'aftermarket',
                    'dealership', 'dealerships', 'automaker', 'automakers', 'carmaker',
                    'msrp', 'vin', 'odometer', 'mileage', 'mpg', 'kph', 'mph',
                    'drivetrain', 'powertrain', 'driveline', 'chassis', 'bodywork',

                    // Vehicle types
                    'sedan', 'coupe', 'hatchback', 'convertible', 'roadster', 'suv',
                    'crossover', 'cuv', 'minivan', 'pickup', 'truck', 'semi', 'rv',
                    'motorhome', 'camper', 'atv', 'utv', 'motorcycle', 'sportbike',

                    // Engine and transmission
                    'horsepower', 'torque', 'displacement', 'turbo', 'turbocharged',
                    'turbocharger', 'supercharger', 'supercharged', 'intercooler',
                    'carburetor', 'efi', 'mfi', 'tbi', 'throttle', 'camshaft',
                    'crankshaft', 'piston', 'cylinder', 'valvetrain', 'timing',
                    'compression', 'ignition', 'sparkplug', 'coilpack', 'injector',
                    'transmission', 'transaxle', 'gearbox', 'clutch', 'flywheel',
                    'torqueconverter', 'cvt', 'dct', 'dsg', 'awd', 'fwd', 'rwd',
                    'xdrive', 'quattro', 'syncro', '4matic', 'haldex',

                    // Electrical and electronics
                    'ecu', 'ecm', 'pcm', 'tcm', 'bcm', 'abs', 'esp', 'tcs', 'vsc',
                    'esc', 'tpms', 'obd', 'canbus', 'infotainment', 'telematics',
                    'gps', 'bluetooth', 'carplay', 'androidauto', 'hud', 'lidar',
                    'radar', 'adas', 'lkas', 'acc', 'aeb', 'bsm', 'ldw', 'lka',

                    // Suspension and steering
                    'suspension', 'strut', 'coilover', 'damper', 'shocks', 'springs',
                    'sway', 'stabilizer', 'bushing', 'ballpoint', 'tierod', 'linkage',
                    'steering', 'rack', 'pinion', 'eps', 'hydraulic', 'alignment',
                    'camber', 'caster', 'toe', 'wheelbase', 'trackwidth',

                    // Brakes
                    'brakes', 'rotor', 'caliper', 'pads', 'shoes', 'drum', 'disc',
                    'hydraulic', 'pneumatic', 'regenerative', 'antilock',

                    // Body and exterior
                    'bumper', 'fender', 'hood', 'bonnet', 'trunk', 'tailgate', 'hatch',
                    'grille', 'fascia', 'spoiler', 'diffuser', 'splitter', 'skirt',
                    'molding', 'trim', 'weatherstrip', 'sunroof', 'moonroof', 'panoramic',

                    // Warranty and service
                    'warranty', 'warranties', 'powertrain', 'bumpertobumper',
                    'comprehensive', 'limited', 'extended', 'cpo', 'preowned',
                    'certified', 'reconditioned', 'refurbished', 'recall', 'tsb',
                    'maintenance', 'servicing', 'inspection', 'diagnostic', 'dyno',
                ],
            ],

            'legal' => [
                'name' => 'Legal',
                'description' => 'Legal terminology, court procedures, and law practice vocabulary.',
                'words' => [
                    // General legal terms
                    'jurisprudence', 'jurisdiction', 'jurisdictional', 'adjudicate',
                    'adjudication', 'arbitration', 'arbitrator', 'mediation', 'mediator',
                    'litigation', 'litigant', 'litigator', 'lawsuit', 'claimant',
                    'plaintiff', 'defendant', 'appellant', 'appellee', 'respondent',
                    'petitioner', 'complainant', 'prosecutor', 'prosecution',
                    'indictment', 'arraignment', 'deposition', 'interrogatory',
                    'subpoena', 'summons', 'warrant', 'affidavit', 'attestation',

                    // Court and proceedings
                    'courtroom', 'tribunal', 'magistrate', 'judiciary', 'judicial',
                    'appellate', 'verdict', 'judgment', 'ruling', 'decree', 'injunction',
                    'restraining', 'acquittal', 'conviction', 'sentencing', 'probation',
                    'parole', 'incarceration', 'restitution', 'damages', 'punitive',
                    'compensatory', 'settlement', 'plea', 'bargain', 'mistrial',

                    // Legal documents
                    'contract', 'contractual', 'covenant', 'addendum', 'amendment',
                    'memorandum', 'pleading', 'brief', 'motion', 'filing', 'docket',
                    'statute', 'statutory', 'ordinance', 'bylaw', 'regulation',
                    'compliance', 'noncompliance', 'precedent', 'stare', 'decisis',

                    // Legal concepts
                    'liability', 'negligence', 'malpractice', 'malfeasance', 'tort',
                    'breach', 'infringement', 'defamation', 'libel', 'slander',
                    'fiduciary', 'indemnity', 'indemnification', 'subrogation',
                    'escrow', 'lien', 'encumbrance', 'easement', 'eminent', 'domain',
                    'probate', 'intestate', 'testate', 'beneficiary', 'trustee',

                    // Intellectual property
                    'intellectual', 'trademark', 'copyright', 'patent', 'infringement',
                    'licensing', 'royalty', 'proprietary', 'confidentiality', 'nda',

                    // Latin terms
                    'habeas', 'corpus', 'pro', 'bono', 'prima', 'facie', 'ipso',
                    'facto', 'quid', 'quo', 'subpoena', 'duces', 'tecum', 'voir',
                    'dire', 'nolo', 'contendere', 'amicus', 'curiae', 'certiorari',
                    'mandamus', 'caveat', 'emptor', 'bona', 'fide', 'de', 'facto',
                    'de', 'jure', 'ex', 'parte', 'in', 'absentia', 'mens', 'rea',
                    'actus', 'reus', 'res', 'judicata', 'collateral', 'estoppel',
                ],
            ],

            'finance' => [
                'name' => 'Finance & Banking',
                'description' => 'Financial terminology, banking vocabulary, and investment terms.',
                'words' => [
                    // General finance
                    'fintech', 'forex', 'cryptocurrency', 'crypto', 'blockchain',
                    'defi', 'nft', 'tokenization', 'digitalization', 'monetization',
                    'monetize', 'capitalization', 'liquidity', 'solvency', 'insolvency',
                    'bankruptcy', 'restructuring', 'refinancing', 'amortization',
                    'depreciation', 'appreciation', 'valuation', 'appraisal',

                    // Banking
                    'banking', 'finserv', 'neobank', 'challenger', 'fdic', 'sipc',
                    'ach', 'swift', 'iban', 'bic', 'routing', 'clearinghouse',
                    'overdraft', 'chargeback', 'disbursement', 'remittance', 'escrow',
                    'custodian', 'custodial', 'fiduciary', 'trustee', 'beneficiary',
                    'signatory', 'cosigner', 'guarantor', 'collateral', 'lien',

                    // Investments
                    'portfolio', 'diversification', 'rebalancing', 'hedging', 'arbitrage',
                    'equity', 'equities', 'etf', 'etfs', 'mutual', 'index', 'reit',
                    'ipo', 'spac', 'otc', 'nasdaq', 'nyse', 'dow', 'russell', 'sp500',
                    'bullish', 'bearish', 'volatility', 'vix', 'beta', 'alpha',
                    'sharpe', 'sortino', 'drawdown', 'benchmark', 'outperform',
                    'underperform', 'overweight', 'underweight', 'accumulate',

                    // Trading
                    'trading', 'brokerage', 'broker', 'dealer', 'marketmaker',
                    'bid', 'ask', 'spread', 'slippage', 'execution', 'settlement',
                    'clearing', 'margin', 'leverage', 'derivative', 'option', 'futures',
                    'swap', 'cfd', 'forex', 'pip', 'lot', 'position', 'long', 'short',
                    'stoploss', 'takeprofit', 'trailing', 'limit', 'market',

                    // Accounting
                    'gaap', 'ifrs', 'ebitda', 'ebit', 'ebita', 'roce', 'roe', 'roa',
                    'roi', 'irr', 'npv', 'dcf', 'wacc', 'capex', 'opex', 'cogs',
                    'receivables', 'payables', 'accrual', 'deferral', 'prepaid',
                    'ledger', 'reconciliation', 'audit', 'attestation', 'assurance',

                    // Credit and lending
                    'creditworthiness', 'fico', 'vantagescore', 'experian', 'equifax',
                    'transunion', 'apr', 'apy', 'dti', 'ltv', 'pmi', 'heloc',
                    'prequalification', 'preapproval', 'underwriting', 'origination',
                    'servicing', 'forbearance', 'deferment', 'delinquency', 'default',

                    // Compliance
                    'kyc', 'aml', 'cft', 'fatca', 'fincen', 'ofac', 'pci', 'dss',
                    'sox', 'sarbanes', 'oxley', 'dodd', 'frank', 'mifid', 'gdpr',
                    'compliance', 'regulatory', 'fiduciary', 'suitability', 'disclosure',
                ],
            ],

            'tech' => [
                'name' => 'Technology',
                'description' => 'Software development, IT infrastructure, and technology vocabulary.',
                'words' => [
                    // Programming languages and frameworks
                    'javascript', 'typescript', 'python', 'golang', 'rust', 'kotlin',
                    'swift', 'scala', 'elixir', 'clojure', 'haskell', 'erlang',
                    'ruby', 'perl', 'lua', 'dart', 'julia', 'fortran', 'cobol',
                    'nodejs', 'deno', 'bun', 'expressjs', 'fastify', 'nestjs',
                    'django', 'flask', 'fastapi', 'rails', 'sinatra', 'phoenix',
                    'springboot', 'quarkus', 'micronaut', 'dotnet', 'aspnet', 'blazor',

                    // Frontend
                    'frontend', 'html', 'css', 'scss', 'sass', 'less', 'postcss',
                    'tailwindcss', 'bootstrap', 'bulma', 'chakra', 'antd', 'mui',
                    'react', 'reactjs', 'preact', 'vue', 'vuejs', 'nuxt', 'nuxtjs',
                    'angular', 'svelte', 'sveltekit', 'solidjs', 'qwik', 'astro',
                    'nextjs', 'remix', 'gatsby', 'vite', 'webpack', 'rollup', 'esbuild',
                    'babel', 'eslint', 'prettier', 'stylelint', 'husky', 'lint',

                    // Backend and infrastructure
                    'backend', 'api', 'restful', 'graphql', 'grpc', 'websocket',
                    'microservices', 'monolith', 'serverless', 'faas', 'baas', 'paas',
                    'iaas', 'saas', 'kubernetes', 'k8s', 'docker', 'containerd',
                    'podman', 'helm', 'terraform', 'pulumi', 'ansible', 'chef',
                    'puppet', 'saltstack', 'vagrant', 'packer', 'consul', 'vault',
                    'nginx', 'apache', 'caddy', 'traefik', 'envoy', 'istio', 'linkerd',

                    // Cloud providers
                    'aws', 'azure', 'gcp', 'digitalocean', 'linode', 'vultr', 'hetzner',
                    'cloudflare', 'vercel', 'netlify', 'heroku', 'railway', 'render',
                    'lambda', 'fargate', 'ecs', 'eks', 'ec2', 's3', 'rds', 'dynamodb',
                    'cloudfront', 'route53', 'iam', 'cognito', 'sqs', 'sns', 'kinesis',

                    // Databases
                    'mysql', 'postgresql', 'postgres', 'mariadb', 'sqlite', 'mssql',
                    'oracle', 'mongodb', 'cassandra', 'couchdb', 'couchbase', 'neo4j',
                    'redis', 'memcached', 'elasticsearch', 'opensearch', 'solr',
                    'clickhouse', 'timescale', 'influxdb', 'prometheus', 'grafana',
                    'prisma', 'drizzle', 'typeorm', 'sequelize', 'knex', 'objection',

                    // DevOps and CI/CD
                    'devops', 'devsecops', 'gitops', 'cicd', 'jenkins', 'circleci',
                    'travisci', 'github', 'gitlab', 'bitbucket', 'codecov', 'sonarqube',
                    'argocd', 'fluxcd', 'spinnaker', 'tekton', 'buildkite', 'drone',
                    'observability', 'apm', 'tracing', 'datadog', 'newrelic', 'splunk',
                    'dynatrace', 'sentry', 'rollbar', 'bugsnag', 'loggly', 'papertrail',

                    // Security
                    'cybersecurity', 'infosec', 'appsec', 'netsec', 'opsec', 'soc',
                    'siem', 'xdr', 'edr', 'mdr', 'soar', 'pentest', 'bugbounty',
                    'owasp', 'cve', 'cvss', 'nist', 'iso27001', 'soc2', 'pci',
                    'oauth', 'oidc', 'saml', 'jwt', 'jwe', 'jws', 'mfa', 'totp',
                    'fido', 'webauthn', 'passkey', 'rbac', 'abac', 'acl', 'iam',
                    'encryption', 'hashing', 'salting', 'bcrypt', 'argon2', 'scrypt',
                    'aes', 'rsa', 'ecdsa', 'ed25519', 'tls', 'ssl', 'https', 'mtls',
                ],
            ],

            'marketing' => [
                'name' => 'Marketing',
                'description' => 'Digital marketing, advertising, and brand management terminology.',
                'words' => [
                    // Digital marketing
                    'martech', 'adtech', 'programmatic', 'rtb', 'dsp', 'ssp', 'dmp',
                    'cdp', 'crm', 'clv', 'ltv', 'cac', 'roas', 'roi', 'kpi', 'okr',
                    'attribution', 'multitouch', 'firstclick', 'lastclick', 'linear',
                    'incrementality', 'holdout', 'abtest', 'abtesting', 'multivariate',

                    // SEO and content
                    'seo', 'sem', 'serp', 'serps', 'ppc', 'cpc', 'cpm', 'cpa', 'cpl',
                    'ctr', 'impressions', 'pageview', 'pageviews', 'sessions', 'users',
                    'bounce', 'bouncerate', 'dwell', 'dwelltime', 'engagement',
                    'organic', 'paid', 'earned', 'owned', 'backlink', 'backlinks',
                    'dofollow', 'nofollow', 'canonicalization', 'canonical', 'sitemap',
                    'robots', 'crawlability', 'indexability', 'schema', 'richsnippet',
                    'longtail', 'shorttail', 'keyword', 'keywords', 'keyphrase',

                    // Social media
                    'socialmedia', 'smm', 'influencer', 'microinfluencer', 'ugc',
                    'hashtag', 'trending', 'viral', 'virality', 'shareability',
                    'facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'youtube',
                    'pinterest', 'snapchat', 'reddit', 'discord', 'twitch', 'threads',
                    'reels', 'shorts', 'stories', 'livestream', 'livestreaming',
                    'follower', 'followers', 'following', 'subscriber', 'subscribers',

                    // Email marketing
                    'email', 'newsletter', 'drip', 'nurture', 'autoresponder',
                    'mailchimp', 'sendgrid', 'klaviyo', 'hubspot', 'marketo', 'pardot',
                    'deliverability', 'openrate', 'clickrate', 'unsubscribe', 'optout',
                    'optin', 'doubleoptin', 'singleoptin', 'spam', 'dkim', 'spf', 'dmarc',
                    'segmentation', 'personalization', 'dynamiccontent', 'merge',

                    // Analytics and tracking
                    'analytics', 'googleanalytics', 'ga4', 'gtm', 'tagmanager',
                    'mixpanel', 'amplitude', 'heap', 'segment', 'rudderstack',
                    'hotjar', 'fullstory', 'logrocket', 'heatmap', 'heatmaps',
                    'clickmap', 'scrollmap', 'funnel', 'funnels', 'cohort', 'cohorts',
                    'retention', 'churn', 'reactivation', 'winback', 'upsell', 'crosssell',

                    // Advertising
                    'advertising', 'ad', 'ads', 'adwords', 'adsense', 'adroll',
                    'facebook', 'facebookads', 'instagram', 'instagramads', 'meta',
                    'google', 'googleads', 'youtube', 'youtubeads', 'display',
                    'native', 'nativeads', 'banner', 'interstitial', 'preroll',
                    'midroll', 'postroll', 'skippable', 'nonskippable', 'bumper',
                    'retargeting', 'remarketing', 'lookalike', 'audience', 'audiences',
                    'targeting', 'geotargeting', 'geofencing', 'contextual', 'behavioral',

                    // Branding and content
                    'branding', 'rebrand', 'rebranding', 'brandbook', 'styleguide',
                    'positioning', 'differentiation', 'usp', 'valueproposition',
                    'storytelling', 'copywriting', 'copywriter', 'contentmarketing',
                    'thoughtleadership', 'whitepaper', 'ebook', 'webinar', 'podcast',
                    'infographic', 'meme', 'gif', 'carousel', 'cta', 'calltoaction',
                ],
            ],
        ];
    }
}
