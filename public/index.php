<?php
/**
 * Front Controller
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

try {
    // Load environment variables from parent directory (outside web root)
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../..');
    $dotenv->load();
} catch (\Exception $e) {
    die('Error loading .env file: ' . $e->getMessage() . '<br>Make sure .env exists in: ' . realpath(__DIR__ . '/../..'));
}

// Detect base path from REQUEST_URI
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];
$basePath = rtrim(dirname($scriptName), '/');

// Define BASE_PATH constant for use in views
define('BASE_PATH', $basePath);

// Helper function for asset URLs
function asset($path) {
    return BASE_PATH . '/' . ltrim($path, '/');
}

// Helper function for route URLs
function route($path) {
    return BASE_PATH . '/' . ltrim($path, '/');
}

// Get the request URI and clean it
$uri = parse_url($requestUri, PHP_URL_PATH);

// Remove base path prefix if present (for subdirectory installations)
if ($basePath !== '') {
    $uri = preg_replace('#^' . preg_quote($basePath, '#') . '#', '', $uri);
}

$method = $_SERVER['REQUEST_METHOD'];

// Debug logging (comment out in production)
$debug = ($_ENV['APP_ENV'] ?? 'production') === 'development';
if ($debug) {
    error_log("Request: $method $uri (Original: $requestUri, Base: $basePath)");
}

// Route handlers
$routes = [
    'GET' => [
        '/' => function() {
            require __DIR__ . '/../src/Views/dashboard.php';
        },
        '/campaigns' => function() {
            $controller = new \App\Controllers\CampaignController();
            $controller->index();
        },
        '/campaigns/(\d+)' => function($id) {
            $controller = new \App\Controllers\CampaignController();
            $controller->view((int)$id);
        },
        '/campaigns/(\d+)/upload' => function($id) {
            $controller = new \App\Controllers\CampaignController();
            $controller->upload((int)$id);
        },
        '/sessions/(\d+)' => function($id) {
            $controller = new \App\Controllers\SessionController();
            $controller->view((int)$id);
        },
        '/sessions/(\d+)/status' => function($id) {
            $controller = new \App\Controllers\SessionController();
            $controller->status((int)$id);
        },
        '/sessions/(\d+)/export' => function($id) {
            $controller = new \App\Controllers\SessionController();
            $controller->exportMarkdown((int)$id);
        },
    ],
    'POST' => [
        '/campaigns' => function() {
            $controller = new \App\Controllers\CampaignController();
            $controller->create();
        },
        '/campaigns/(\d+)' => function($id) {
            $controller = new \App\Controllers\CampaignController();
            $controller->update((int)$id);
        },
        '/campaigns/(\d+)/sessions' => function($id) {
            $controller = new \App\Controllers\SessionController();
            $controller->upload();
        },
    ],
    'DELETE' => [
        '/sessions/(\d+)' => function($id) {
            $controller = new \App\Controllers\SessionController();
            $controller->delete((int)$id);
        },
    ],
];

// Match route
$matched = false;

try {
    if (isset($routes[$method])) {
        foreach ($routes[$method] as $pattern => $handler) {
            $regex = '#^' . $pattern . '$#';
            if (preg_match($regex, $uri, $matches)) {
                array_shift($matches); // Remove full match
                
                if ($debug) {
                    error_log("Matched route: $pattern with params: " . json_encode($matches));
                }
                
                call_user_func_array($handler, $matches);
                $matched = true;
                break;
            }
        }
    }

    if (!$matched) {
        if ($debug) {
            error_log("No route matched for: $method $uri");
        }
        http_response_code(404);
        require __DIR__ . '/../src/Views/404.php';
    }
} catch (\PDOException $e) {
    // Database errors
    http_response_code(500);
    if ($debug) {
        die('<h1>Database Error</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>');
    } else {
        die('<h1>Database Error</h1><p>Please check your database configuration.</p>');
    }
} catch (\Exception $e) {
    // General errors
    http_response_code(500);
    if ($debug) {
        die('<h1>Application Error</h1><pre>' . htmlspecialchars($e->getMessage()) . "\n\n" . htmlspecialchars($e->getTraceAsString()) . '</pre>');
    } else {
        die('<h1>Application Error</h1><p>An error occurred. Please try again later.</p>');
    }
}
