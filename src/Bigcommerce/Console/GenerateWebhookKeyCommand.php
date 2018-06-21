<?php

namespace VerveCommerce\Bigcommerce\Console;

use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Console\ConfirmableTrait;

class GenerateWebhookKeyCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bigcommerce:generate-key
                    {--show : Display the key instead of modifying files}
                    {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the webhook secret key';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $key = $this->generateRandomKey();

        if ($this->option('show')) {
            return $this->line('<comment>' . $key . '</comment>');
        }

        // Next, we will replace the application key in the environment file so it is
        // automatically setup for this developer. This key gets generated using a
        // secure random byte generator and is later base64 encoded for storage.
        if (!$this->setKeyInEnvironmentFile($key)) {
            return;
        }

        $this->laravel['config']['bigcommerce.webhook_secret'] = $key;

        $this->info("Webhook secret [$key] set successfully.");
    }

    /**
     * Generate a random key for the application.
     *
     * @return string
     */
    protected function generateRandomKey()
    {
        return md5(
            Encrypter::generateKey($this->laravel['config']['app.cipher'])
        );
    }

    /**
     * Set the application key in the environment file.
     *
     * @param  string $key
     * @return bool
     */
    protected function setKeyInEnvironmentFile($key)
    {
        $currentKey = $this->laravel['config']['bigcommerce.webhook_secret'];

        if (strlen($currentKey) !== 0 && (!$this->confirmToProceed())) {
            return false;
        }

        if (strlen($currentKey) !== 0) {
            $this->alert('Previous key [' . $currentKey . '] has been overwritten. Existing webhooks using this key will fail. You should update the keys or disable them.');
        }

        $this->writeNewEnvironmentFileWith($key);

        return true;
    }

    /**
     * Write a new environment file with the given key.
     *
     * @param  string $key
     * @return void
     */
    protected function writeNewEnvironmentFileWith($key)
    {
        file_put_contents($this->laravel->environmentFilePath(), preg_replace(
            $this->keyReplacementPattern(),
            'BIGCOMMERCE_WEBHOOK_SECRET=' . $key,
            file_get_contents($this->laravel->environmentFilePath())
        ));
    }

    /**
     * Get a regex pattern that will match env BIGCOMMERCE_WEBHOOK_SECRET with any random key.
     *
     * @return string
     */
    protected function keyReplacementPattern()
    {
        $escaped = preg_quote('=' . $this->laravel['config']['bigcommerce.webhook_secret'], '/');

        return "/^BIGCOMMERCE_WEBHOOK_SECRET{$escaped}/m";
    }
}
