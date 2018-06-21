<?php

namespace VerveCommerce\Bigcommerce\Console;

use Illuminate\Console\Command;
use VerveCommerce\Bigcommerce\Facades\BigCommerce;
use VerveCommerce\Bigcommerce\Webhooks;
use Symfony\Component\Console\Input\InputOption;

class CreateWebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bigcommerce:create-webhook {scope}
        {--disable : Disable the webhook upon creation.}
        {--header=* : Adds additional headers to the webhook entry.}
        {--destination= : Override the default destination.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register a webhook with BigCommerce';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $scope = $this->argument('scope');
        $active = !$this->option('disable');
        $headers = [];

        if ($this->hasOption('header')) {
            $inputHeaders = $this->option('header');
            foreach ($inputHeaders as $input) {
                list($key, $value) = preg_split('#: ?#', $input);
                $headers[$key] = $value;
            }
        }

        try {
            if (Webhooks::create($scope, null, $active, $headers)) {
                $this->line('<info>Webhook for [' . $scope . '] successfully created.</info>');
            }
            else {
                $this->error('Could not create webhook.');
            }
        }
        catch (\Exception $e) {
            throw $e;
        }
    }
}
