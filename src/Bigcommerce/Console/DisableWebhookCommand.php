<?php

namespace VerveCommerce\Bigcommerce\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use VerveCommerce\Bigcommerce\Facades\BigCommerce;

class DisableWebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bigcommerce:disable-webhook {id} {--delete : Disable the webhook instead of deleting it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disables or deletes a webhook';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $id = $this->argument('id');

        if (!$this->option('delete')) {
            BigCommerce::updateWebhook($id, (object)['is_active' => false]);
            $this->line('<info>Webhook successfully disabled.</info>');
        }
        else {
            $response = BigCommerce::deleteWebhook($id);
            $this->line('<info>Webhook successfully deleted.</info>');
        }
    }
}
