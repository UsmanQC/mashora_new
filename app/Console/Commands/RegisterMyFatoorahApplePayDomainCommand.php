<?php

namespace App\Console\Commands;

use App\Services\MyFatoorahEmbeddedV3Service;
use Illuminate\Console\Command;

class RegisterMyFatoorahApplePayDomainCommand extends Command
{
    protected $signature = 'myfatoorah:register-apple-pay-domain
                            {domain? : Domain to register, e.g. awaan.io (defaults to APP_URL host)}';

    protected $description = 'Call MyFatoorah RegisterApplePayDomain after hosting the Apple Pay verification file';

    public function handle(MyFatoorahEmbeddedV3Service $myFatoorah): int
    {
        $domain = (string) ($this->argument('domain') ?: parse_url((string) config('app.url'), PHP_URL_HOST));

        if ($domain === '') {
            $this->components->error('Pass a domain, e.g. php artisan myfatoorah:register-apple-pay-domain awaan.io');

            return self::FAILURE;
        }

        $mode = (bool) config('myfatoorah.is_test') ? 'test' : 'live';
        $api = $myFatoorah->apiBaseUrl();

        $this->components->info("Registering Apple Pay domain [{$domain}] via {$api} ({$mode}).");
        $this->line('Ensure this URL returns HTTP 200 first:');
        $this->line("  https://{$domain}/.well-known/apple-developer-merchantid-domain-association");

        $result = $myFatoorah->registerApplePayDomain($domain);

        if (! ($result['ok'] ?? false)) {
            $this->components->error($result['message'] ?? 'Registration failed.');

            if (isset($result['body'])) {
                $this->line(is_string($result['body']) ? $result['body'] : json_encode($result['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }

            return self::FAILURE;
        }

        $this->components->info('Domain registered: '.($result['message'] ?? 'OK'));
        $this->line('Next: set MYFATOORAH_REGISTER_APPLE_PAY=true and run php artisan config:clear');

        return self::SUCCESS;
    }
}
