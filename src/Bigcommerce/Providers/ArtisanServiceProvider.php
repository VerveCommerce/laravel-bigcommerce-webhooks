<?php

namespace VerveCommerce\Bigcommerce\Providers;

use Illuminate\Support\ServiceProvider;
use VerveCommerce\Bigcommerce\Console\BatchUpdateWebhooksCommand;
use VerveCommerce\Bigcommerce\Console\CreateWebhookCommand;
use VerveCommerce\Bigcommerce\Console\DisableWebhookCommand;
use VerveCommerce\Bigcommerce\Console\GenerateWebhookKeyCommand;
use VerveCommerce\Bigcommerce\Console\NgrokServeCommand;
use VerveCommerce\Bigcommerce\Console\SetNgrokUrlCommand;
use VerveCommerce\Bigcommerce\Console\ShowWebhooksCommand;

class ArtisanServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                BatchUpdateWebhooksCommand::class,
                CreateWebhookCommand::class,
                DisableWebhookCommand::class,
                GenerateWebhookKeyCommand::class,
                NgrokServeCommand::class,
                SetNgrokUrlCommand::class,
                ShowWebhooksCommand::class,
            ]);
        }
    }
}
