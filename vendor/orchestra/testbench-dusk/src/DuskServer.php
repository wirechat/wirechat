<?php

namespace Orchestra\Testbench\Dusk;

use Orchestra\Testbench\Dusk\Exceptions\UnableToStartServer;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

use function Illuminate\Filesystem\join_paths;
use function Orchestra\Testbench\defined_environment_variables;

/**
 * @internal
 */
class DuskServer
{
    /**
     * Process pointer reference.
     *
     * @var \Symfony\Component\Process\Process|null
     */
    protected $process;

    /**
     * Array of file pointers.
     *
     * @var array
     */
    protected $pipes;

    /**
     * Server host.
     *
     * @var string
     */
    protected $host;

    /**
     * Server port.
     *
     * @var int
     */
    protected $port;

    /**
     * Server process timeout.
     *
     * @var int
     */
    protected $timeout;

    /**
     * Laravel working path.
     *
     * @var string|null
     */
    protected $basePath = null;

    /**
     * Laravel working URL.
     *
     * @var string|null
     */
    protected $baseUrl = null;

    /**
     * List of local IPv6 hosts.
     *
     * @var array<int, string>
     */
    protected $localIpv6Hosts = ['::0', '[::0]'];

    /**
     * Construct a new server.
     *
     * @param  string  $host
     * @param  int  $port
     * @param  int  $timeout
     */
    public function __construct($host = '127.0.0.1', $port = 8001, $timeout = 6000)
    {
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
    }

    /**
     * Set Laravel working path.
     *
     * @param  string|null  $basePath
     * @param  string|null  $baseUrl
     * @return void
     */
    public function setLaravel(?string $basePath = null, ?string $baseUrl = null): void
    {
        $this->basePath = $basePath;
        $this->baseUrl = $baseUrl;
    }

    /**
     * Store some temp contents in a file for later use.
     *
     * @param  mixed  $content
     * @return void
     */
    public function stash($content): void
    {
        file_put_contents($this->temp(), json_encode($content));
    }

    /**
     * Prepare the path of the temp file for a particular server.
     *
     * @return string
     */
    protected function temp(): string
    {
        return join_paths(
            \dirname(__DIR__),
            'tmp',
            sprintf('%s__%d', ! \in_array($this->host, $this->localIpv6Hosts) ? $this->host : 'localhost', $this->port),
        );
    }

    /**
     * Retrieve the contents of the relevant file.
     *
     * @param  string|null  $key
     * @return mixed
     */
    public function getStash(?string $key = null)
    {
        $content = json_decode((string) file_get_contents($this->temp()), true);

        return $key ? (isset($content[$key]) ? $content[$key] : null) : $content;
    }

    /**
     * Start a php server in a separate process.
     *
     * @return void
     *
     * @throws \Orchestra\Testbench\Dusk\Exceptions\UnableToStartServer
     */
    public function start(): void
    {
        $this->stop();
        $this->startServer();

        // We register the below, so if php is exited early, the child
        // process for the server is closed down, rather than left
        // hanging around for the user to close themselves.
        register_shutdown_function(function () {
            $this->stop();
        });
    }

    /**
     * Stop the php server.
     *
     * @return void
     */
    public function stop(): void
    {
        if (! isset($this->process)) {
            return;
        }

        $this->process->stop();
    }

    /**
     * Stop the php server.
     *
     * @return void
     */
    public function restart(): void
    {
        $this->stop();
        $this->clearOutput();
        $this->startServer();
    }

    /**
     * Clear the php server output.
     *
     * @return void
     */
    public function clearOutput(): void
    {
        if (isset($this->process)) {
            $this->process->clearOutput();
            $this->process->clearErrorOutput();
        }
    }

    /**
     * Start the server. Execute the command and open a
     * pointer to it. Tuck away the output as it's
     * not relevant for us during our testing.
     *
     * @return void
     *
     * @throws \Orchestra\Testbench\Dusk\Exceptions\UnableToStartServer
     */
    protected function startServer(): void
    {
        $this->guardServerStarting();

        $this->process = new Process(
            command: $this->prepareCommand(),
            cwd: join_paths($this->basePath(), 'public'),
            env: array_merge(defined_environment_variables(), [
                'APP_BASE_PATH' => $this->basePath(),
                'APP_URL' => $this->baseUrl(),
            ]),
            timeout: $this->timeout
        );

        $this->process->start();
    }

    /**
     * Verify that there isn't an existing server on the host and port
     * that we want to use.  Sometimes a server can be left oped when
     * PHP drops out, or the user may have another service running.
     *
     * @return void
     *
     * @throws \Orchestra\Testbench\Dusk\Exceptions\UnableToStartServer
     */
    protected function guardServerStarting()
    {
        /** @var resource|null $socket */
        $socket = rescue(function () {
            $errorNumber = 0;
            $errorString = '';
            $timeout = 1;

            return @fsockopen($this->host, $this->port, $errorNumber, $errorString, $timeout);
        }, null, false);

        if ($socket) {
            fclose($socket);
            throw new UnableToStartServer($this->host.':'.$this->port);
        }
    }

    /**
     * Prepare the command for starting the PHP server.
     *
     * @return array
     */
    protected function prepareCommand(): array
    {
        return [
            (string) (new PhpExecutableFinder())->find(false),
            '-S',
            sprintf('%s:%s', $this->host, $this->port),
            join_paths(__DIR__, 'server.php'),
            '-t',
            join_paths($this->basePath(), 'public'),
        ];
    }

    /**
     * Figure out the path to the laravel application path for Testbench purposes.
     *
     * @return string
     */
    public function basePath(): string
    {
        return $this->basePath ?? default_skeleton_path();
    }

    /**
     * Figure out the path to the laravel application URL for testbench purposes.
     *
     * @return string
     */
    public function baseUrl(): string
    {
        return $this->baseUrl ?? sprintf('http://%s:%d', $this->host, $this->port);
    }

    /**
     * Get the current process.
     *
     * @return \Symfony\Component\Process\Process|null
     */
    public function getProcess()
    {
        return $this->process;
    }
}
