<?php

namespace Folklore\Image\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Routing\Router;
use Illuminate\Http\Request;
use Folklore\Image\Contracts\UrlGenerator;
use Folklore\Image\Contracts\RouteResolver;
use Folklore\Image\Jobs\CreateUrlCache;

class CreateUrlCacheCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'image:create_url_cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a cache for a specific url and filters';

    protected $router;
    protected $urlGenerator;
    protected $routeResolver;
    protected $dispatcher;

    public function __construct(
        Router $router,
        UrlGenerator $urlGenerator,
        RouteResolver $routeResolver,
        Dispatcher $dispatcher
    ) {
        parent::__construct();

        $this->router = $router;
        $this->urlGenerator = $urlGenerator;
        $this->routeResolver = $routeResolver;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $queue = $this->option('queue');
        $url = $this->argument('url');
        $filters = $this->option('filters');
        $routeName = $this->option('route');

        if ($queue) {
            $this->dispatcher->dispatch(new CreateUrlCache($url, $filters, $routeName));
        } else {
            $this->dispatcher->dispatchNow(new CreateUrlCache($url, $filters, $routeName));
        }

        $route = !empty($routeName) ? $this->router->getRoutes()->getByName($routeName) : null;
        $routeConfig = !is_null($route) ? $this->routeResolver->getConfigFromRoute($route) : [];
        $finalUrl = $this->urlGenerator->make($url, array_merge($filters, !empty($route) ? [
            'pattern' => data_get($routeConfig, 'pattern', [])
        ] : []));

        $this->line('<info>Created:</info> '.$finalUrl.' for image '.$url);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['url', InputArgument::REQUIRED, 'The url of the image'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['filters', 'f', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The filters to apply on the image'],
            ['route', 'r', InputOption::VALUE_REQUIRED, 'The route name', 'image'],
            ['queue', null, InputOption::VALUE_NONE, 'Dispatch the job on the queue'],
        ];
    }
}
