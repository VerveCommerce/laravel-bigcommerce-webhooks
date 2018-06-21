<?php

namespace VerveCommerce\Bigcommerce\Console;

use Illuminate\Console\Command;

class SetNgrokUrlCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bigcommerce:set-ngrok-url {url}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets the development URL for local oAuth app development';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $url = $this->argument('url');
        if (! $this->setUrlInEnvironmentFile($url)) {
            return;
        }

        $this->info("ngrok URL <$url> set successfully.");
    }

    /**
     * Set the application key in the environment file.
     *
     * @param  string  $key
     * @return bool
     */
    protected function setUrlInEnvironmentFile($url)
    {
        $currentKey = $this->laravel['config']['bigcommerce.webhook_url_base'];

        if (strlen($currentKey) !== 0) {
            $this->warn('A URL is currently set. This will cause all previously registered webhooks to no longer work!');
        }

        $this->writeNewEnvironmentFileWith($url);

        return true;
    }

    /**
     * Write a new environment file with the given key.
     *
     * @param  string  $key
     * @return void
     */
    protected function writeNewEnvironmentFileWith($url)
    {
        file_put_contents($this->laravel->environmentFilePath(), preg_replace(
            $this->keyReplacementPattern(),
            'BIGCOMMERCE_NGROK_URL='.$url,
            file_get_contents($this->laravel->environmentFilePath())
        ));
    }

    /**
     * Get a regex pattern that will match env APP_KEY with any random key.
     *
     * @return string
     */
    protected function keyReplacementPattern()
    {
        $escaped = preg_quote('='.$this->laravel['config']['bigcommerce.webhook_url_base'], '/');

        return "/^BIGCOMMERCE_NGROK_URL{$escaped}/m";
    }
}
