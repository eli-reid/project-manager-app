<?php

namespace Database\Seeders;

use App\Core\Settings\Models\SettingsSqlite;
use App\Core\Settings\Services\DomainSettingsSynchronizer;
use Illuminate\Database\Seeder;

/**
 * SettingsSeeder
 *
 * Seeds default application settings into the settings SQLite database.
 * Includes settings for: app, company, mail, mailgun, postmark, aws, cpanel,
 * weather, zoom, projects, timecards, financials, documents, session, system,
 * security, and features.
 *
 * Run with: php artisan db:seed --class=SettingsSeeder
 */
class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure database is initialized
        $model = new SettingsSqlite;
        $model->ensureSettingsDatabase();

        // Seed all setting groups
        $allSettings = [
            ...$this->getApplicationSettings(),
            ...$this->getCompanySettings(),
            ...$this->getMailSettings(),
            ...$this->getMailgunSettings(),
            ...$this->getPostmarkSettings(),
            ...$this->getAwsSettings(),
            ...$this->getCpanelSettings(),
            ...$this->getWeatherSettings(),
            ...$this->getZoomSettings(),
            ...$this->getProjectSettings(),
            ...$this->getTimecardSettings(),
            ...$this->getFinancialSettings(),
            ...$this->getDocumentSettings(),
            ...$this->getSessionSettings(),
            ...$this->getSystemSettings(),
            ...$this->getSecuritySettings(),
            ...$this->getFeatureSettings(),
        ];

        foreach ($allSettings as $setting) {
            SettingsSqlite::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        // Sync settings declared by each domain in app/Domains/*/config/settings.php.
        $domainChanges = app(DomainSettingsSynchronizer::class)->sync();

        $this->command->info('✓ Settings seeded successfully: '.count($allSettings).' base settings');
        $this->command->info('✓ Domain settings synchronized: '.$domainChanges.' changes');
    }

    /**
     * Application settings
     */
    protected function getApplicationSettings(): array
    {
        return [
            [
                'key' => 'app.name',
                'value' => env('APP_NAME', 'Project Manager'),
                'display_name' => 'Application Name',
                'description' => 'The name of your application',
                'type' => 'text',
                'group' => 'app',
                'order' => 1,
                'is_visible' => true,
                'is_public' => true,
                'is_required' => true,
                'encrypted' => false,
            ],
            [
                'key' => 'app.url',
                'value' => env('APP_URL', 'http://localhost'),
                'display_name' => 'Application URL',
                'description' => 'The URL of your application',
                'type' => 'url',
                'group' => 'app',
                'order' => 2,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => true,
                'encrypted' => false,
            ],
            [
                'key' => 'app.timezone',
                'value' => env('APP_TIMEZONE', 'UTC'),
                'display_name' => 'Timezone',
                'description' => 'Application timezone for date/time operations',
                'type' => 'select',
                'group' => 'app',
                'options' => $this->getTimezoneOptions(),
                'order' => 3,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => true,
                'encrypted' => false,
            ],
            [
                'key' => 'app.debug',
                'value' => env('APP_DEBUG', 'false'),
                'display_name' => 'Debug Mode',
                'description' => 'Enable debug mode for development',
                'type' => 'select',
                'group' => 'app',
                'options' => json_encode(['true' => 'Enabled', 'false' => 'Disabled']),
                'order' => 4,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => true,
                'encrypted' => false,
            ],
        ];
    }

    /**
     * Company information settings
     */
    protected function getCompanySettings(): array
    {
        return [
            [
                'key' => 'company.name',
                'value' => '',
                'display_name' => 'Company Name',
                'description' => 'Your company or organization name',
                'type' => 'text',
                'group' => 'company',
                'order' => 1,
                'is_visible' => true,
                'is_public' => true,
                'is_required' => true,
                'encrypted' => false,
            ],
            [
                'key' => 'company.email',
                'value' => '',
                'display_name' => 'Company Email',
                'description' => 'Primary email address for your organization',
                'type' => 'email',
                'group' => 'company',
                'order' => 2,
                'is_visible' => true,
                'is_public' => true,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'company.phone',
                'value' => '',
                'display_name' => 'Company Phone',
                'description' => 'Company phone number',
                'type' => 'text',
                'group' => 'company',
                'order' => 3,
                'is_visible' => true,
                'is_public' => true,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'company.address',
                'value' => '',
                'display_name' => 'Company Address',
                'description' => 'Company address for reports and documents',
                'type' => 'textarea',
                'group' => 'company',
                'order' => 4,
                'is_visible' => true,
                'is_public' => true,
                'is_required' => false,
                'encrypted' => false,
            ],
        ];
    }

    /**
     * Mail (SMTP) settings
     */
    protected function getMailSettings(): array
    {
        return [
            [
                'key' => 'mail.host',
                'value' => env('MAIL_HOST', 'localhost'),
                'display_name' => 'Mail Host',
                'description' => 'SMTP server hostname',
                'type' => 'text',
                'group' => 'mail',
                'order' => 1,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'mail.port',
                'value' => env('MAIL_PORT', '587'),
                'display_name' => 'Mail Port',
                'description' => 'SMTP server port',
                'type' => 'number',
                'group' => 'mail',
                'order' => 2,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'mail.username',
                'value' => env('MAIL_USERNAME', ''),
                'display_name' => 'Mail Username',
                'description' => 'SMTP authentication username',
                'type' => 'text',
                'group' => 'mail',
                'order' => 3,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'mail.password',
                'value' => env('MAIL_PASSWORD', ''),
                'display_name' => 'Mail Password',
                'description' => 'SMTP authentication password',
                'type' => 'password',
                'group' => 'mail',
                'order' => 4,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => true,
            ],
            [
                'key' => 'mail.encryption',
                'value' => env('MAIL_ENCRYPTION', 'tls'),
                'display_name' => 'Mail Encryption',
                'description' => 'SMTP encryption method',
                'type' => 'select',
                'group' => 'mail',
                'options' => json_encode(['tls' => 'TLS', 'ssl' => 'SSL', '' => 'None']),
                'order' => 5,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'mail.from_address',
                'value' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'display_name' => 'Mail From Address',
                'description' => 'Email address used for system notifications',
                'type' => 'email',
                'group' => 'mail',
                'order' => 6,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'mail.from_name',
                'value' => env('MAIL_FROM_NAME', 'Project Manager'),
                'display_name' => 'Mail From Name',
                'description' => 'Name used for system emails',
                'type' => 'text',
                'group' => 'mail',
                'order' => 7,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
        ];
    }

    /**
     * Mailgun settings
     */
    protected function getMailgunSettings(): array
    {
        return [
            [
                'key' => 'mailgun.domain',
                'value' => env('MAILGUN_DOMAIN', ''),
                'display_name' => 'Mailgun Domain',
                'description' => 'Mailgun email service domain',
                'type' => 'text',
                'group' => 'mailgun',
                'order' => 1,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'mailgun.secret',
                'value' => env('MAILGUN_SECRET', ''),
                'display_name' => 'Mailgun Secret',
                'description' => 'Mailgun API secret key',
                'type' => 'password',
                'group' => 'mailgun',
                'order' => 2,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => true,
            ],
            [
                'key' => 'mailgun.endpoint',
                'value' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
                'display_name' => 'Mailgun Endpoint',
                'description' => 'Mailgun API endpoint URL',
                'type' => 'text',
                'group' => 'mailgun',
                'order' => 3,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
        ];
    }

    /**
     * Postmark settings
     */
    protected function getPostmarkSettings(): array
    {
        return [
            [
                'key' => 'postmark.token',
                'value' => env('POSTMARK_TOKEN', ''),
                'display_name' => 'Postmark Token',
                'description' => 'Postmark API token',
                'type' => 'password',
                'group' => 'postmark',
                'order' => 1,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => true,
            ],
        ];
    }

    /**
     * AWS/SES settings
     */
    protected function getAwsSettings(): array
    {
        return [
            [
                'key' => 'aws.key',
                'value' => env('AWS_ACCESS_KEY_ID', ''),
                'display_name' => 'AWS Access Key',
                'description' => 'Amazon AWS access key ID',
                'type' => 'password',
                'group' => 'aws',
                'order' => 1,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => true,
            ],
            [
                'key' => 'aws.secret',
                'value' => env('AWS_SECRET_ACCESS_KEY', ''),
                'display_name' => 'AWS Secret Key',
                'description' => 'Amazon AWS secret access key',
                'type' => 'password',
                'group' => 'aws',
                'order' => 2,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => true,
            ],
            [
                'key' => 'aws.region',
                'value' => env('AWS_DEFAULT_REGION', 'us-east-1'),
                'display_name' => 'AWS Region',
                'description' => 'Amazon AWS region',
                'type' => 'select',
                'group' => 'aws',
                'options' => json_encode([
                    'us-east-1' => 'US East (N. Virginia)',
                    'us-west-2' => 'US West (Oregon)',
                    'eu-west-1' => 'EU (Ireland)',
                ]),
                'order' => 3,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
        ];
    }

    /**
     * cPanel settings
     */
    protected function getCpanelSettings(): array
    {
        return [
            [
                'key' => 'cpanel.url',
                'value' => env('CPANEL_URL', ''),
                'display_name' => 'cPanel URL',
                'description' => 'cPanel server URL (e.g., https://cpanel.example.com)',
                'type' => 'url',
                'group' => 'cpanel',
                'order' => 1,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'cpanel.username',
                'value' => env('CPANEL_USERNAME', ''),
                'display_name' => 'cPanel Username',
                'description' => 'cPanel API username',
                'type' => 'text',
                'group' => 'cpanel',
                'order' => 2,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'cpanel.api_token',
                'value' => env('CPANEL_API_TOKEN', ''),
                'display_name' => 'cPanel API Token',
                'description' => 'cPanel API token for authentication',
                'type' => 'password',
                'group' => 'cpanel',
                'order' => 3,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => true,
            ],
            [
                'key' => 'cpanel.domain',
                'value' => env('CPANEL_DOMAIN', ''),
                'display_name' => 'cPanel Domain',
                'description' => 'Domain for email accounts',
                'type' => 'text',
                'group' => 'cpanel',
                'order' => 4,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'cpanel.port',
                'value' => env('CPANEL_PORT', '2083'),
                'display_name' => 'cPanel Port',
                'description' => 'cPanel API port (default: 2083)',
                'type' => 'number',
                'group' => 'cpanel',
                'order' => 5,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'cpanel.webmail_port',
                'value' => env('CPANEL_WEBMAIL_PORT', '2096'),
                'display_name' => 'Webmail Port',
                'description' => 'Webmail access port (default: 2096)',
                'type' => 'number',
                'group' => 'cpanel',
                'order' => 6,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'cpanel.webmail_url',
                'value' => env('CPANEL_WEBMAIL_URL', ''),
                'display_name' => 'Webmail URL',
                'description' => 'Webmail access URL',
                'type' => 'url',
                'group' => 'cpanel',
                'order' => 7,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'cpanel.email_quota',
                'value' => env('CPANEL_DEFAULT_EMAIL_QUOTA', '250'),
                'display_name' => 'Default Email Quota (MB)',
                'description' => 'Default email storage quota in megabytes',
                'type' => 'number',
                'group' => 'cpanel',
                'order' => 8,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'cpanel.auto_create_emails',
                'value' => env('CPANEL_AUTO_CREATE_EMAILS', 'false'),
                'display_name' => 'Auto Create Emails',
                'description' => 'Automatically create email accounts for new users',
                'type' => 'select',
                'group' => 'cpanel',
                'options' => json_encode(['true' => 'Yes', 'false' => 'No']),
                'order' => 9,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'cpanel.sync_passwords',
                'value' => env('CPANEL_SYNC_USER_PASSWORDS', 'false'),
                'display_name' => 'Sync User Passwords',
                'description' => 'Sync user password changes to cPanel email accounts',
                'type' => 'select',
                'group' => 'cpanel',
                'options' => json_encode(['true' => 'Yes', 'false' => 'No']),
                'order' => 10,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'cpanel.verify_ssl',
                'value' => env('CPANEL_VERIFY_SSL', 'true'),
                'display_name' => 'Verify SSL Certificates',
                'description' => 'Verify SSL certificates when connecting to cPanel',
                'type' => 'select',
                'group' => 'cpanel',
                'options' => json_encode(['true' => 'Yes', 'false' => 'No']),
                'order' => 11,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
        ];
    }

    /**
     * Weather API settings
     */
    protected function getWeatherSettings(): array
    {
        return [
            [
                'key' => 'weather.api_key',
                'value' => env('WEATHERAPI_KEY', ''),
                'display_name' => 'WeatherAPI Key',
                'description' => 'WeatherAPI.com API key for weather data',
                'type' => 'password',
                'group' => 'weather',
                'order' => 1,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => true,
            ],
            [
                'key' => 'weather.base_url',
                'value' => 'https://api.weatherapi.com/v1',
                'display_name' => 'WeatherAPI Base URL',
                'description' => 'WeatherAPI base URL',
                'type' => 'text',
                'group' => 'weather',
                'order' => 2,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
        ];
    }

    /**
     * Zoom API settings
     */
    protected function getZoomSettings(): array
    {
        return [
            [
                'key' => 'zoom.api_key',
                'value' => env('ZOOM_API_KEY', ''),
                'display_name' => 'Zoom API Key',
                'description' => 'Zoom API key for integration',
                'type' => 'password',
                'group' => 'zoom',
                'order' => 1,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => true,
            ],
            [
                'key' => 'zoom.api_secret',
                'value' => env('ZOOM_API_SECRET', ''),
                'display_name' => 'Zoom API Secret',
                'description' => 'Zoom API secret key',
                'type' => 'password',
                'group' => 'zoom',
                'order' => 2,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => true,
            ],
            [
                'key' => 'zoom.account_id',
                'value' => env('ZOOM_ACCOUNT_ID', ''),
                'display_name' => 'Zoom Account ID',
                'description' => 'Zoom account ID for OAuth',
                'type' => 'text',
                'group' => 'zoom',
                'order' => 3,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'zoom.client_id',
                'value' => env('ZOOM_CLIENT_ID', ''),
                'display_name' => 'Zoom Client ID',
                'description' => 'Zoom OAuth client ID',
                'type' => 'text',
                'group' => 'zoom',
                'order' => 4,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'zoom.client_secret',
                'value' => env('ZOOM_CLIENT_SECRET', ''),
                'display_name' => 'Zoom Client Secret',
                'description' => 'Zoom OAuth client secret',
                'type' => 'password',
                'group' => 'zoom',
                'order' => 5,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => true,
            ],
            [
                'key' => 'zoom.webhook_secret',
                'value' => env('ZOOM_WEBHOOK_SECRET', ''),
                'display_name' => 'Zoom Webhook Secret',
                'description' => 'Zoom webhook verification secret token',
                'type' => 'password',
                'group' => 'zoom',
                'order' => 6,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => true,
            ],
            [
                'key' => 'zoom.base_url',
                'value' => 'https://api.zoom.us/v2',
                'display_name' => 'Zoom API Base URL',
                'description' => 'Zoom API base URL',
                'type' => 'url',
                'group' => 'zoom',
                'order' => 7,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
        ];
    }

    /**
     * Project settings
     */
    protected function getProjectSettings(): array
    {
        return [
            [
                'key' => 'projects.default_status',
                'value' => 'Active',
                'display_name' => 'Default Project Status',
                'description' => 'Default status for new projects',
                'type' => 'select',
                'group' => 'projects',
                'options' => json_encode(['Active' => 'Active', 'Planning' => 'Planning', 'On Hold' => 'On Hold', 'Completed' => 'Completed']),
                'order' => 1,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => true,
                'encrypted' => false,
            ],
            [
                'key' => 'projects.auto_numbering',
                'value' => 'true',
                'display_name' => 'Auto-Generate Project Numbers',
                'description' => 'Automatically generate project numbers',
                'type' => 'select',
                'group' => 'projects',
                'options' => json_encode(['true' => 'Yes', 'false' => 'No']),
                'order' => 2,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'projects.number_prefix',
                'value' => 'PRJ-',
                'display_name' => 'Project Number Prefix',
                'description' => 'Prefix for auto-generated project numbers',
                'type' => 'text',
                'group' => 'projects',
                'order' => 3,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'projects.default_burden_rate',
                'value' => '35',
                'display_name' => 'Default Burden Rate (%)',
                'description' => 'Default burden rate for labor calculations (percentage)',
                'type' => 'number',
                'group' => 'projects',
                'order' => 4,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
        ];
    }

    /**
     * Timecard settings
     */
    protected function getTimecardSettings(): array
    {
        return [
            [
                'key' => 'timecards.default_hours',
                'value' => '8',
                'display_name' => 'Default Hours Per Day',
                'description' => 'Default hours per day for timecards',
                'type' => 'number',
                'group' => 'timecards',
                'order' => 1,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'timecards.overtime_threshold',
                'value' => '40',
                'display_name' => 'Overtime Threshold (hours/week)',
                'description' => 'Hours per week before overtime kicks in',
                'type' => 'number',
                'group' => 'timecards',
                'order' => 2,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'timecards.approval_required',
                'value' => 'true',
                'display_name' => 'Require Approval',
                'description' => 'Require supervisor approval for timecards',
                'type' => 'select',
                'group' => 'timecards',
                'options' => json_encode(['true' => 'Yes', 'false' => 'No']),
                'order' => 3,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
        ];
    }

    /**
     * Financial settings
     */
    protected function getFinancialSettings(): array
    {
        return [
            [
                'key' => 'financials.currency',
                'value' => 'USD',
                'display_name' => 'Default Currency',
                'description' => 'Default currency for financial calculations',
                'type' => 'select',
                'group' => 'financials',
                'options' => json_encode(['USD' => 'US Dollar ($)', 'EUR' => 'Euro (€)', 'GBP' => 'British Pound (£)', 'CAD' => 'Canadian Dollar (C$)']),
                'order' => 1,
                'is_visible' => true,
                'is_public' => true,
                'is_required' => true,
                'encrypted' => false,
            ],
            [
                'key' => 'financials.symbol',
                'value' => '$',
                'display_name' => 'Currency Symbol',
                'description' => 'Symbol to display for currency',
                'type' => 'text',
                'group' => 'financials',
                'order' => 2,
                'is_visible' => true,
                'is_public' => true,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'financials.tax_rate',
                'value' => '0',
                'display_name' => 'Default Tax Rate (%)',
                'description' => 'Default tax rate percentage',
                'type' => 'number',
                'group' => 'financials',
                'order' => 3,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'financials.overtime_threshold',
                'value' => '40',
                'display_name' => 'Overtime Threshold (hours/week)',
                'description' => 'Hours per week before overtime rate applies',
                'type' => 'number',
                'group' => 'financials',
                'order' => 4,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'financials.double_time_threshold',
                'value' => '60',
                'display_name' => 'Double Time Threshold (hours/week)',
                'description' => 'Hours per week before double time rate applies',
                'type' => 'number',
                'group' => 'financials',
                'order' => 5,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
        ];
    }

    /**
     * Document management settings
     */
    protected function getDocumentSettings(): array
    {
        return [
            [
                'key' => 'documents.allowed_types',
                'value' => 'pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,gif,webp,svg',
                'display_name' => 'Allowed File Types',
                'description' => 'Comma-separated list of allowed file extensions',
                'type' => 'text',
                'group' => 'documents',
                'order' => 1,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'documents.max_file_size',
                'value' => '10240',
                'display_name' => 'Maximum File Size (KB)',
                'description' => 'Maximum file size in kilobytes',
                'type' => 'number',
                'group' => 'documents',
                'order' => 2,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'documents.enable_versioning',
                'value' => 'false',
                'display_name' => 'Enable File Versioning',
                'description' => 'Enable versioning for uploaded documents',
                'type' => 'select',
                'group' => 'documents',
                'options' => json_encode(['true' => 'Yes', 'false' => 'No']),
                'order' => 3,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'documents.storage_disk',
                'value' => 'local',
                'display_name' => 'Document Storage Disk',
                'description' => 'Storage disk for project documents',
                'type' => 'select',
                'group' => 'documents',
                'options' => json_encode(['local' => 'Local Storage', 's3' => 'Amazon S3', 'public' => 'Public Storage']),
                'order' => 4,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
        ];
    }

    /**
     * Session settings
     */
    protected function getSessionSettings(): array
    {
        return [
            [
                'key' => 'session.driver',
                'value' => env('SESSION_DRIVER', 'file'),
                'display_name' => 'Session Driver',
                'description' => 'Session storage driver (file, database, redis, etc.)',
                'type' => 'select',
                'group' => 'session',
                'options' => json_encode(['file' => 'File', 'database' => 'Database', 'redis' => 'Redis', 'memcached' => 'Memcached', 'array' => 'Array (Testing)']),
                'order' => 1,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'session.lifetime',
                'value' => env('SESSION_LIFETIME', '120'),
                'display_name' => 'Session Lifetime (minutes)',
                'description' => 'Minutes before session expires',
                'type' => 'number',
                'group' => 'session',
                'order' => 2,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'session.expire_on_close',
                'value' => env('SESSION_EXPIRE_ON_CLOSE', 'false'),
                'display_name' => 'Expire On Browser Close',
                'description' => 'Expire session when browser closes',
                'type' => 'select',
                'group' => 'session',
                'options' => json_encode(['true' => 'Yes', 'false' => 'No']),
                'order' => 3,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'session.encrypt',
                'value' => env('SESSION_ENCRYPT', 'false'),
                'display_name' => 'Encrypt Session Data',
                'description' => 'Encrypt all session data',
                'type' => 'select',
                'group' => 'session',
                'options' => json_encode(['true' => 'Yes', 'false' => 'No']),
                'order' => 4,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
        ];
    }

    /**
     * System settings
     */
    protected function getSystemSettings(): array
    {
        return [
            [
                'key' => 'system.date_format',
                'value' => 'Y-m-d',
                'display_name' => 'Date Format',
                'description' => 'Date format used throughout the system',
                'type' => 'select',
                'group' => 'system',
                'options' => json_encode(['Y-m-d' => '2024-01-15', 'm/d/Y' => '01/15/2024', 'd/m/Y' => '15/01/2024', 'M j, Y' => 'Jan 15, 2024']),
                'order' => 1,
                'is_visible' => true,
                'is_public' => true,
                'is_required' => true,
                'encrypted' => false,
            ],
            [
                'key' => 'system.time_format',
                'value' => 'H:i',
                'display_name' => 'Time Format',
                'description' => 'Time format used throughout the system',
                'type' => 'select',
                'group' => 'system',
                'options' => json_encode(['H:i' => '14:30 (24-hour)', 'g:i A' => '2:30 PM (12-hour)']),
                'order' => 2,
                'is_visible' => true,
                'is_public' => true,
                'is_required' => true,
                'encrypted' => false,
            ],
            [
                'key' => 'system.locale',
                'value' => 'en',
                'display_name' => 'Default Language',
                'description' => 'Default language for the application',
                'type' => 'select',
                'group' => 'system',
                'options' => json_encode(['en' => 'English', 'es' => 'Spanish', 'fr' => 'French']),
                'order' => 3,
                'is_visible' => true,
                'is_public' => true,
                'is_required' => true,
                'encrypted' => false,
            ],
            [
                'key' => 'system.work_hours_per_day',
                'value' => '8',
                'display_name' => 'Default Work Hours Per Day',
                'description' => 'Standard number of work hours per day',
                'type' => 'number',
                'group' => 'system',
                'order' => 4,
                'is_visible' => true,
                'is_public' => true,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'system.log_level',
                'value' => env('LOG_LEVEL', 'error'),
                'display_name' => 'Log Level',
                'description' => 'Logging level for application logs',
                'type' => 'select',
                'group' => 'system',
                'options' => json_encode([
                    'debug' => 'Debug (Development only - very verbose)',
                    'info' => 'Info (Log general information)',
                    'notice' => 'Notice (Normal but significant events)',
                    'warning' => 'Warning (Recommended for production)',
                    'error' => 'Error (Production - only errors and above)',
                    'critical' => 'Critical (Only critical issues)',
                ]),
                'order' => 5,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => true,
                'encrypted' => false,
            ],
            [
                'key' => 'system.cache_enabled',
                'value' => config('settings-db.cache.enabled', 'true') ? 'true' : 'false',
                'display_name' => 'Enable Settings Cache',
                'description' => 'Cache settings for improved performance',
                'type' => 'select',
                'group' => 'system',
                'options' => json_encode(['true' => 'Enabled', 'false' => 'Disabled']),
                'order' => 6,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'system.cache_ttl',
                'value' => (string) config('settings-db.cache.ttl', 3600),
                'display_name' => 'Cache TTL (seconds)',
                'description' => 'How long to cache settings (in seconds)',
                'type' => 'number',
                'group' => 'system',
                'order' => 7,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'system.log_changes',
                'value' => env('SETTINGS_LOG_CHANGES', 'false') ? 'true' : 'false',
                'display_name' => 'Log Settings Changes',
                'description' => 'Log all settings changes for audit trail',
                'type' => 'select',
                'group' => 'system',
                'options' => json_encode(['true' => 'Enabled', 'false' => 'Disabled']),
                'order' => 8,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
        ];
    }

    /**
     * Security settings
     */
    protected function getSecuritySettings(): array
    {
        return [
            [
                'key' => 'security.session_timeout',
                'value' => '120',
                'display_name' => 'Session Timeout (minutes)',
                'description' => 'Minutes of inactivity before session expires',
                'type' => 'number',
                'group' => 'security',
                'order' => 1,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'security.password_min_length',
                'value' => '8',
                'display_name' => 'Minimum Password Length',
                'description' => 'Minimum required password length',
                'type' => 'number',
                'group' => 'security',
                'order' => 2,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'security.require_password_complexity',
                'value' => 'true',
                'display_name' => 'Require Password Complexity',
                'description' => 'Require passwords to include uppercase, lowercase, numbers, and symbols',
                'type' => 'select',
                'group' => 'security',
                'options' => json_encode(['true' => 'Yes', 'false' => 'No']),
                'order' => 3,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
        ];
    }

    /**
     * Feature toggle settings
     */
    protected function getFeatureSettings(): array
    {
        return [
            [
                'key' => 'features.maintenance_mode',
                'value' => 'false',
                'display_name' => 'Maintenance Mode',
                'description' => 'Enable maintenance mode to take application offline',
                'type' => 'select',
                'group' => 'features',
                'options' => json_encode(['true' => 'Enabled', 'false' => 'Disabled']),
                'order' => 1,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'features.new_user_registration',
                'value' => 'true',
                'display_name' => 'Allow New User Registration',
                'description' => 'Allow new users to register accounts',
                'type' => 'select',
                'group' => 'features',
                'options' => json_encode(['true' => 'Enabled', 'false' => 'Disabled']),
                'order' => 2,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'features.email_verification',
                'value' => 'true',
                'display_name' => 'Require Email Verification',
                'description' => 'Require users to verify their email before accessing the app',
                'type' => 'select',
                'group' => 'features',
                'options' => json_encode(['true' => 'Enabled', 'false' => 'Disabled']),
                'order' => 3,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'features.notifications',
                'value' => 'true',
                'display_name' => 'Enable Notifications',
                'description' => 'Enable system notifications',
                'type' => 'select',
                'group' => 'features',
                'options' => json_encode(['true' => 'Enabled', 'false' => 'Disabled']),
                'order' => 4,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'features.time_tracking',
                'value' => 'true',
                'display_name' => 'Enable Time Tracking',
                'description' => 'Enable time tracking features',
                'type' => 'select',
                'group' => 'features',
                'options' => json_encode(['true' => 'Enabled', 'false' => 'Disabled']),
                'order' => 5,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'features.reporting',
                'value' => 'true',
                'display_name' => 'Enable Reporting',
                'description' => 'Enable reporting features',
                'type' => 'select',
                'group' => 'features',
                'options' => json_encode(['true' => 'Enabled', 'false' => 'Disabled']),
                'order' => 6,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
        ];
    }

    /**
     * Get array of timezone options for select field
     */
    protected function getTimezoneOptions(): string
    {
        $timezones = [
            'UTC' => 'UTC (Coordinated Universal Time)',
            'America/New_York' => 'Eastern Time (US & Canada)',
            'America/Chicago' => 'Central Time (US & Canada)',
            'America/Denver' => 'Mountain Time (US & Canada)',
            'America/Los_Angeles' => 'Pacific Time (US & Canada)',
            'America/Anchorage' => 'Alaska Time',
            'America/Adak' => 'Hawaii-Aleutian Time',
            'Europe/London' => 'Greenwich Mean Time',
            'Europe/Paris' => 'Central European Time',
            'Europe/Moscow' => 'Moscow Standard Time',
            'Asia/Dubai' => 'Gulf Standard Time',
            'Asia/Kolkata' => 'Indian Standard Time',
            'Asia/Bangkok' => 'Indochina Time',
            'Asia/Shanghai' => 'China Standard Time',
            'Asia/Seoul' => 'Korea Standard Time',
            'Asia/Tokyo' => 'Japan Standard Time',
            'Australia/Sydney' => 'Australian Eastern Time',
            'Pacific/Auckland' => 'New Zealand Standard Time',
        ];

        return json_encode($timezones);
    }
}
