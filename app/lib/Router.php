<?php
/**
 * Router Class - Handles routing for Canabidiol Commerce Platform
 */
class Router {
    private $routes = [];
    private $namedRoutes = [];
    private $basePath = '';
    private $middleware = [];
    private $errorHandlers = [];
    
    public function __construct($basePath = '') {
        $this->basePath = rtrim($basePath, '/');
    }
    
    /**
     * Add a route
     */
    public function add($method, $path, $handler, $name = null) {
        $route = [
            'method' => strtoupper($method),
            'path' => $this->basePath . $path,
            'pattern' => $this->compilePattern($path),
            'handler' => $handler,
            'middleware' => []
        ];
        
        $this->routes[] = $route;
        
        if ($name) {
            $this->namedRoutes[$name] = $route;
        }
        
        return $this;
    }
    
    /**
     * Compile route pattern to regex
     */
    private function compilePattern($path) {
        // Escape forward slashes
        $pattern = preg_quote($path, '#');
        
        // Convert route parameters to named capture groups
        $pattern = preg_replace('/\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\}/', '(?P<$1>[^/]+)', $pattern);
        
        // Add start and end anchors
        return '#^' . $pattern . '$#';
    }
    
    /**
     * Add GET route
     */
    public function get($path, $handler, $name = null) {
        return $this->add('GET', $path, $handler, $name);
    }
    
    /**
     * Add POST route
     */
    public function post($path, $handler, $name = null) {
        return $this->add('POST', $path, $handler, $name);
    }
    
    /**
     * Add PUT route
     */
    public function put($path, $handler, $name = null) {
        return $this->add('PUT', $path, $handler, $name);
    }
    
    /**
     * Add DELETE route
     */
    public function delete($path, $handler, $name = null) {
        return $this->add('DELETE', $path, $handler, $name);
    }
    
    /**
     * Add middleware to route
     */
    public function middleware($middleware) {
        if (!empty($this->routes)) {
            $lastRoute = &$this->routes[count($this->routes) - 1];
            $lastRoute['middleware'][] = $middleware;
        } else {
            $this->middleware[] = $middleware;
        }
        return $this;
    }
    
    /**
     * Group routes with common attributes
     */
    public function group($attributes, $callback) {
        $previousBasePath = $this->basePath;
        $previousMiddleware = $this->middleware;
        
        if (isset($attributes['prefix'])) {
            $this->basePath .= $attributes['prefix'];
        }
        
        if (isset($attributes['middleware'])) {
            $this->middleware = array_merge($this->middleware, (array)$attributes['middleware']);
        }
        
        call_user_func($callback, $this);
        
        $this->basePath = $previousBasePath;
        $this->middleware = $previousMiddleware;
    }
    
    /**
     * Add error handler
     */
    public function error($code, $handler) {
        $this->errorHandlers[$code] = $handler;
        return $this;
    }
    
    /**
     * Generate URL for named route
     */
    public function url($name, $params = []) {
        if (!isset($this->namedRoutes[$name])) {
            throw new Exception("Route name '{$name}' not found");
        }
        
        $route = $this->namedRoutes[$name];
        $path = $route['path'];
        
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', $value, $path);
        }
        
        return $path;
    }
    
    /**
     * Match current request to route
     */
    public function match() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Handle HEAD requests as GET
        if ($method === 'HEAD') {
            $method = 'GET';
        }
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method && $route['method'] !== 'ANY') {
                continue;
            }
            
            if (preg_match($route['pattern'], $uri, $matches)) {
                // Filter out numeric keys from matches
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                // Add middleware from group and route
                $middleware = array_merge($this->middleware, $route['middleware']);
                
                return [
                    'handler' => $route['handler'],
                    'params' => $params,
                    'middleware' => $middleware
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Run the router
     */
    public function run() {
        try {
            $match = $this->match();
            
            if ($match) {
                // Execute middleware stack
                foreach ($match['middleware'] as $middleware) {
                    if (is_callable($middleware)) {
                        $result = call_user_func($middleware);
                        if ($result === false) {
                            throw new Exception("Middleware blocked request", 403);
                        }
                    } elseif (is_string($middleware)) {
                        // Handle class-based middleware
                        $middlewareInstance = new $middleware();
                        if (method_exists($middlewareInstance, 'handle')) {
                            $result = $middlewareInstance->handle();
                            if ($result === false) {
                                throw new Exception("Middleware blocked request", 403);
                            }
                        }
                    }
                }
                
                // Execute route handler
                $handler = $match['handler'];
                $params = $match['params'];
                
                if (is_callable($handler)) {
                    call_user_func_array($handler, $params);
                } elseif (is_string($handler) && strpos($handler, '@') !== false) {
                    list($controller, $method) = explode('@', $handler);
                    $controllerInstance = new $controller();
                    call_user_func_array([$controllerInstance, $method], $params);
                } else {
                    throw new Exception("Invalid route handler", 500);
                }
                
                return;
            }
            
            throw new Exception("Route not found", 404);
            
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $this->handleError($code, $e);
        }
    }
    
    /**
     * Handle errors
     */
    private function handleError($code, $exception = null) {
        if (isset($this->errorHandlers[$code])) {
            $handler = $this->errorHandlers[$code];
            
            if (is_callable($handler)) {
                call_user_func($handler, $exception);
            } elseif (is_string($handler)) {
                if (strpos($handler, '@') !== false) {
                    list($controller, $method) = explode('@', $handler);
                    $controllerInstance = new $controller();
                    call_user_func([$controllerInstance, $method], $exception);
                } else {
                    $this->renderErrorPage($code);
                }
            } else {
                $this->renderErrorPage($code);
            }
        } else {
            $this->renderErrorPage($code);
        }
    }
    
    /**
     * Render error page
     */
    private function renderErrorPage($code) {
        $errorPages = [
            403 => 'errors/403.php',
            404 => 'errors/404.php',
            500 => 'errors/500.php'
        ];
        
        if (isset($errorPages[$code])) {
            http_response_code($code);
            require __DIR__ . '/../../app/views/' . $errorPages[$code];
        } else {
            http_response_code($code);
            echo "Error {$code}";
        }
        exit;
    }
}