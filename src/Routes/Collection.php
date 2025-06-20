<?php

namespace Lord\Laroute\Routes;

use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollectionInterface;
use Illuminate\Support\Arr;
use Lord\Laroute\Routes\Exceptions\ZeroRoutesException;

class Collection extends \Illuminate\Support\Collection
{
    public function __construct(RouteCollectionInterface $routes, $filter, $namespace)
    {
        $this->items = $this->parseRoutes($routes, $filter, $namespace);
    }

    /**
     * Parse the routes into a jsonable output.
     *
     * @param RouteCollectionInterface $routes
     * @param string $filter
     * @param string $namespace
     *
     * @return array
     * @throws ZeroRoutesException
     */
    protected function parseRoutes(RouteCollectionInterface $routes, $filter, $namespace)
    {
        $this->guardAgainstZeroRoutes($routes);

        return collect($routes)
            ->sortBy(fn ($route) => $route->uri()) // Sort routes by their URI for stable order
            ->map(fn ($route) => $this->getRouteInformation($route, $filter, $namespace))
            ->filter() // Remove falsey results
            ->values() // Reindex array numerically
            ->all();
    }

    /**
     * Throw an exception if there aren't any routes to process
     *
     * @param RouteCollectionInterface $routes
     *
     * @throws ZeroRoutesException
     */
    protected function guardAgainstZeroRoutes(RouteCollectionInterface $routes)
    {
        if (count($routes) < 1) {
            throw new ZeroRoutesException("You don't have any routes!");
        }
    }

    /**
     * Get the route information for a given route.
     *
     * @param $route \Illuminate\Routing\Route
     * @param $filter string
     * @param $namespace string
     *
     * @return array
     */
    protected function getRouteInformation(Route $route, $filter, $namespace)
    {
        $host    = $route->domain();
        $methods = $route->methods();
        $uri     = $route->uri();
        $name    = $route->getName();
        $action  = $route->getActionName();
        $laroute = Arr::get($route->getAction(), 'laroute', null);

        if(!empty($namespace)) {
            $a = $route->getAction();

            if(isset($a['controller'])) {
                $action = str_replace($namespace.'\\', '', $action);
            }
        }

        switch ($filter) {
            case 'all':
                if($laroute === false) return null;
                break;
            case 'only':
                if($laroute !== true) return null;
                break;
        }

        return compact('host', 'methods', 'uri', 'name', 'action');
    }

}
