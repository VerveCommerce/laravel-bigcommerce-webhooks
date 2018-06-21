<?php

namespace VerveCommerce\Bigcommerce\Console;

use Illuminate\Console\Command;
use VerveCommerce\Bigcommerce\Facades\BigCommerce;

class BatchUpdateWebhooksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bigcommerce:update-webhooks
        {--new-key= : Update the secret key.}
        {--dest=default : The destination (prefix) on which to operate.}
        {--new-dest= : The new destination to use.}
        {--disable : Disable all webhooks matching the query.}
        {--disabled : Only work on disabled webhooks.}
        {--enabled : Only work on enabled webhooks.}
        {--enable : Enable all webhooks matching the query.}
        {--all : Apply to all webhooks.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform some batch updates of webhooks';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $allHooks = collect(BigCommerce::listWebhooks());

        if (!count($allHooks)) {
            return $this->error('There are no webhooks defined on your store.');
        }

        if ($this->option('disable') && $this->option('enable')) {
            return $this->error('You can only enable or disable, not both!');
        }

        if (!$this->option('all')) {
            if ($this->hasOption('dest') && ($url = $this->option('dest')) == 'default') {
                $url = config('bigcommerce.webhook_url_base');
            }
            tap($allHooks)
                ->filter(function ($item) use ($url) {
                    return strpos($item->destination, $url) !== false;
                });
        }

        if ($this->option('disable')) {
            tap($allHooks)
                ->transform(function ($hook) {
                    $hook->is_active = false;
                    return $hook;
                });
        }

        if ($this->option('enable')) {
            tap($allHooks)
                ->transform(function ($hook) {
                    $hook->is_active = true;
                    return $hook;
                });
        }

        if ($newKey = $this->option('new-key')) {
            if (empty($newKey)) {
                return $this->error('You must specify a key.');
            }

            tap($allHooks)->transform(function ($hook) use ($newKey) {
                $hook->headers->{'X-Bcl-Secret'} = $newKey;
                return $hook;
            });
        }

        if ($newDest = $this->option('new-dest')) {
            if (empty($newDest)) {
                return $this->error('You must specify a new destination.');
            }

            tap($allHooks)->transform(function ($hook) use ($newDest) {
                $hook->destination = $newDest;
                return $hook;
            });
        }

        $allHooks->each(function ($hook) {
            $changes = [
                'destination' => $hook->destination,
                'headers' => (array)$hook->headers,
                'is_active' => $hook->is_active
            ];
            $hook = Bigcommerce::updateWebhook($hook->id, $changes);
        });
    }
}
