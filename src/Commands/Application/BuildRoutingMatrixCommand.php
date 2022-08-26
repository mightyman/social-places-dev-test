<?php

namespace App\Commands\Application;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\RouterInterface;
use TypeError;

/**
 * Class BuildRoutingMatrixCommand
 * @package App\Commands\Application
 */
class BuildRoutingMatrixCommand extends Command
{
    protected static $defaultName = 'app:build-frontend-routing';

    public function __construct(
        private readonly RouterInterface $router,
        private readonly ParameterBagInterface $parameterBag
    ) {
        parent::__construct();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int {
        try {
            $routesNamesToPaths = ['GET' => [], 'POST' => [], 'PUT' => [], 'PATCH' => [], 'DELETE' => []];
            $routesCollection = $this->router->getRouteCollection()->all();
            foreach ($routesCollection as $routeName => $route) {
                if (str_starts_with($routeName, 'api_')) {
                    $methods = $route->getMethods();
                    if (empty($methods)) {
                        $methods = array_keys($routesNamesToPaths);
                    }
                    foreach ($methods as $method) {
                        $routesNamesToPaths[$method][$routeName] = $this->normalizePathForVueRouter($route->getPath());
                    }
                }
            }
            $projectDir = $this->parameterBag->get('kernel.project_dir');
            file_put_contents("{$projectDir}/assets/js/classes/routes.json", json_encode($routesNamesToPaths, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
        } catch (Exception|TypeError $e) {
            $message = "Something went wrong while building the file";
            throw new Exception($message);
        }

        return Command::SUCCESS;
    }

    /**
     * Handles transforming paths to make them work with vue
     */
    private function normalizePathForVueRouter(string $path): string {
        //While Symfony uses {param}, vue router uses :param
        return preg_replace("#\{(\w+)\}#", ":$1", $path);
    }
}
