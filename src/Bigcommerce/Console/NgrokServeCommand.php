<?php

namespace VerveCommerce\Bigcommerce\Console;

use Illuminate\Console\Command;
use Illuminate\Support\ProcessUtils;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;

class NgrokServeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ngrok-serve';

    protected $shouldRun = true;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Serve the application on the PHP development server and proxy with ngrok';

    /**
     * Execute the console command.
     *
     * @return int
     *
     * @throws \Exception
     */
    public function handle()
    {
        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, array(&$this, 'handleShutdown'));
        pcntl_signal(SIGINT, array(&$this, 'handleShutdown'));

        chdir(public_path());

        $this->line('<info>Starting ngrok tunnel. Please wait.</info>');

        $ngrok = (new Process($this->ngrokcommand()));
        $ngrok->start();

        sleep(1);

        if (!$ngrok->isRunning()) {
            $this->line('<error>Unable to start ngrok tunnel:</error>');
            $this->line('<error>' . trim($ngrok->getErrorOutput()) . '</error>');
            return;
        }

        $secureTunnelInfo = null;

        for ($retries = 10; $retries > 0; $retries--) {
            $tunnelInfo = json_decode(file_get_contents('http://127.0.0.1:4040/api/tunnels'));

            if (count($tunnelInfo->tunnels) === 0) {
                sleep(1);
                continue;
            }

            foreach ($tunnelInfo->tunnels as $tunnel) {
                if ($tunnel->proto === 'https') {
                    $secureTunnelInfo = $tunnel;
                }
            }
        }

        if (!$secureTunnelInfo) {
            $this->line('<error>Unable to get tunnel info. Aborting.</error>');
            $ngrok->stop();
            return;
        }

        $this->line("<info>ngrok proxy server started and accessible on:</info> <{$secureTunnelInfo->public_url}>");
        $this->line("<info>ngrok console available on:</info> <http://127.0.0.1:4040/>");

        $server = (new Process($this->serverCommand(), null, ['BIGCOMMERCE_NGROK_URL' => $secureTunnelInfo->public_url]));
        $server->enableOutput()->start();

        sleep(1);

        if (!$server->isRunning()) {
            $this->line('<error>Unable to start local development server:</error>');
            $this->line('<error>' . trim($server->getErrorOutput()) . '</error>');
            $ngrok->stop();
            return;
        }

        $this->line("<info>Laravel development server started:</info> <http://{$this->host()}:{$this->port()}>");

        $this->call('bigcommerce:set-ngrok-url', [
            'url' => $secureTunnelInfo->public_url
        ]);

        $this->line('<comment>Press Ctrl-C to shut everything down.</comment>');
        while ($this->shouldRun) {
            sleep(1);
        }

        $this->line('<info>Closing down tunnel and local server.</info>');

        $server->stop();
        $ngrok->stop();
    }

    /**
     * Get the full server command.
     *
     * @return string
     */
    protected function serverCommand()
    {
        return sprintf('%s -S %s:%s %s',
            ProcessUtils::escapeArgument((new PhpExecutableFinder)->find(false)),
            $this->host(),
            $this->port(),
            ProcessUtils::escapeArgument(base_path('server.php'))
        );
    }

    /**
     * Get the full server command.
     *
     * @return string
     */
    protected function ngrokCommand()
    {
        $subDomain = null;

        if ($this->input->getOption('reuse')) {
            $url = $this->laravel['config']['bigcommerce.webhook_url_base'];
            if (preg_match('#https://([a-z0-9-]*).ngrok.io#', $url, $matches)) {
                $subDomain='-subdomain=' . $matches[1];
            }
            else {
                throw new \InvalidArgumentException('Invalid URL for ngrok.');
            }
        }

        return sprintf('%s %s %s %s',
            ProcessUtils::escapeArgument((new ExecutableFinder)->find('ngrok')),
            'http',
            $subDomain ?? '',
            $this->port()
        );
    }

    public function handleShutdown() {
        $this->line('<info>Shutting down.</info>');
        $this->shouldRun = false;
    }

    /**
     * Get the host for the command.
     *
     * @return string
     */
    protected function host()
    {
        return $this->input->getOption('host');
    }

    /**
     * Get the port for the command.
     *
     * @return string
     */
    protected function port()
    {
        return $this->input->getOption('port');
    }


    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['host', null, InputOption::VALUE_OPTIONAL, 'The host address to serve the application on.', '127.0.0.1'],

            ['port', null, InputOption::VALUE_OPTIONAL, 'The port to serve the application on.', 8000],

            ['reuse', null, InputOption::VALUE_NONE, 'Whether or not to reuse the existing ngrok URL (paid plans only).', null],
        ];
    }
}
