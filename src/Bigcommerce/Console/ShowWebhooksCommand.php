<?php

namespace VerveCommerce\Bigcommerce\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use VerveCommerce\Bigcommerce\Facades\BigCommerce;

class ShowWebhooksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bigcommerce:list-webhooks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lists all of the webhooks that are registered with BigCommerce';

    /**
     * An array of all the webhooks.
     *
     * @var \Illuminate\Routing\RouteCollection
     */
    protected $webhooks;

    /**
     * The table headers for the command.
     *
     * @var array
     */
    protected $headers = ['ID', 'Scope', 'Destination', 'Headers', 'Active'];

    public function __construct()
    {
        parent::__construct();

        $this->webhooks = Bigcommerce::listWebhooks();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (count($this->webhooks) === 0) {
            return $this->error('Your store doesn\'t have any webhooks.');
        }

        $this->displayWebhooks($this->getWebhooks());
    }

    /**
     * Display the webhook information on the console.
     *
     * @param  array $hooks
     * @return void
     */
    protected function displayWebhooks(array $hooks)
    {
        $this->table($this->headers, $hooks);
    }

    protected function getWebhooks()
    {
        $webhooks = collect($this->webhooks)->map(function ($webhook) {
            return $this->getWebhookInfo($webhook);
        })->all();

        $webhooks = $this->sortWebhooks('scope', $webhooks);

        return array_filter($webhooks);
    }

    protected function getWebhookInfo($webhook)
    {
        return [
            'id' => $webhook->id,
            'scope' => $webhook->scope,
            'destination' => $webhook->destination,
            'headers' => collect($webhook->headers)->map(function ($v, $k) {
                return "$k: $v";
            })->implode(PHP_EOL),
            'active' => $webhook->is_active ? 'Y' : 'N',
        ];
    }

    /**
     * Sort the webhooks by a given element.
     *
     * @param  string $sort
     * @param  array $webhooks
     * @return array
     */
    protected function sortWebhooks($sort, $webhooks)
    {
        return Arr::sort($webhooks, function ($webhook) use ($sort) {
            return $webhook[$sort];
        });
    }
}
