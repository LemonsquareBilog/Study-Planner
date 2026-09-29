<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class ApiDocumentationController extends Controller
{
    /**
     * Get the OpenAPI 3.0 specification.
     */
    public function openapi(Request $request): JsonResponse
    {
        $spec = $this->generateOpenApiSpec();
        
        // Return JSON or YAML based on Accept header
        if ($request->accepts('application/yaml') || $request->query('format') === 'yaml') {
            return response($this->toYaml($spec))
                ->header('Content-Type', 'application/yaml');
        }

        return response()->json($spec);
    }

    /**
     * Get the Swagger UI HTML.
     */
    public function swaggerUi(Request $request)
    {
        $specUrl = route('api.docs.openapi');
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation - Personalized Study Planner</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui.css" />
    <style>
        html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin: 0; background: #fafafa; }
        .swagger-ui .topbar { background-color: #2563eb; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: "{$specUrl}",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                tryItOutEnabled: true,
                filter: true,
                showExtensions: true,
                showCommonExtensions: true,
            });
            window.ui = ui;
        };
    </script>
</body>
</html>
HTML;
    }

    /**
     * Get the ReDoc HTML.
     */
    public function redoc(Request $request)
    {
        $specUrl = route('api.docs.openapi');
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Reference - Personalized Study Planner</title>
    <style>
        body { margin: 0; padding: 0; }
    </style>
</head>
<body>
    <redoc spec-url="{$specUrl}"></redoc>
    <script src="https://cdn.jsdelivr.net/npm/redoc@next/bundles/redoc.standalone.js"></script>
</body>
</html>
HTML;
    }

    /**
     * Generate the OpenAPI 3.0 specification.
     */
    protected function generateOpenApiSpec(): array
    {
        $config = Config::get('api-docs', []);
        
        $spec = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => $config['title'] ?? 'Personalized Study Planner API',
                'description' => $config['description'] ?? 'API documentation for the Personalized Study Planner application',
                'version' => $config['version'] ?? '1.0.0',
                'contact' => [
                    'name' => 'Study Planner Team',
                    'email' => 'support@studyplanner.example.com',
                ],
                'license' => [
                    'name' => 'MIT',
                    'url' => 'https://opensource.org/licenses/MIT',
                ],
            ],
            'servers' => $config['servers'] ?? [],
            'tags' => $config['tags'] ?? [],
            'components' => $config['components'] ?? [],
            'security' => $config['default_security'] ? [['bearer' => []]] : [],
            'paths' => $this->generatePaths(),
        ];

        return $spec;
    }

    /**
     * Generate paths from routes.
     */
    protected function generatePaths(): array
    {
        $paths = [];
        $routes = Route::getRoutes();

        foreach ($routes as $route) {
            // Skip non-API routes
            if (!Str::startsWith($route->uri(), 'api/')) {
                continue;
            }

            // Skip health endpoints
            if (Str::startsWith($route->uri(), 'health')) {
                continue;
            }

            // Skip broadcasting auth
            if (Str::startsWith($route->uri(), 'broadcasting')) {
                continue;
            }

            $path = $this->convertRouteToOpenApiPath($route->uri());
            $methods = $route->methods();
            
            // Skip HEAD methods
            $methods = array_filter($methods, fn($m) => $m !== 'HEAD');
            
            if (empty($methods)) {
                continue;
            }

            if (!isset($paths[$path])) {
                $paths[$path] = [];
            }

            foreach ($methods as $method) {
                $method = strtolower($method);
                $paths[$path][$method] = $this->generateOperation($route, $method);
            }
        }

        return $paths;
    }

    /**
     * Convert Laravel route to OpenAPI path.
     */
    protected function convertRouteToOpenApiPath(string $uri): string
    {
        // Remove leading slash if present
        $uri = ltrim($uri, '/');
        
        // Convert {param} to {param}
        $uri = preg_replace('/\{([^}]+)\}/', '{$1}', $uri);
        
        return '/' . $uri;
    }

    /**
     * Generate OpenAPI operation from route.
     */
    protected function generateOperation($route, string $method): array
    {
        $action = $route->getAction();
        $controller = $action['controller'] ?? null;
        
        $operation = [
            'summary' => $this->generateSummary($route),
            'description' => $this->generateDescription($route),
            'operationId' => $this->generateOperationId($route, $method),
            'tags' => $this->generateTags($route),
            'parameters' => $this->generateParameters($route),
            'responses' => $this->generateResponses($route),
            'security' => $this->generateSecurity($route),
        ];

        // Add request body for non-GET methods
        if (!in_array($method, ['get', 'head', 'delete'])) {
            $operation['requestBody'] = $this->generateRequestBody($route);
        }

        return $operation;
    }

    /**
     * Generate operation summary.
     */
    protected function generateSummary($route): string
    {
        $name = $route->getName() ?? '';
        $parts = explode('.', $name);
        
        if (count($parts) >= 2) {
            $resource = ucfirst(str_replace('-', ' ', $parts[count($parts) - 2]));
            $action = ucfirst(str_replace('-', ' ', end($parts)));
            return "{$action} {$resource}";
        }

        return ucfirst(str_replace(['-', '_'], ' ', $route->uri()));
    }

    /**
     * Generate operation description.
     */
    protected function generateDescription($route): string
    {
        $action = $route->getAction();
        $controller = $action['controller'] ?? '';
        
        if ($controller && method_exists($controller[0], $controller[1])) {
            $reflection = new \ReflectionMethod($controller[0], $controller[1]);
            $docComment = $reflection->getDocComment();
            
            if ($docComment) {
                // Extract first paragraph of docblock
                $lines = explode("\n", $docComment);
                $description = [];
                $inDescription = false;
                
                foreach ($lines as $line) {
                    $line = trim($line, " *\t\r\n");
                    if (str_starts_with($line, '@')) {
                        break;
                    }
                    if ($inDescription || strlen($line) > 0) {
                        $inDescription = true;
                        $description[] = $line;
                    }
                }
                
                if (!empty($description)) {
                    return implode(' ', $description);
                }
            }
        }

        return $this->generateSummary($route);
    }

    /**
     * Generate operation ID.
     */
    protected function generateOperationId($route, string $method): string
    {
        $name = $route->getName() ?? '';
        $parts = explode('.', $name);
        
        if (count($parts) >= 2) {
            $resource = Str::camel($parts[count($parts) - 2]);
            $action = Str::camel(end($parts));
            return $method . Str::studly($action) . Str::studly($resource);
        }

        return $method . Str::studly(Str::slug($route->uri(), ''));
    }

    /**
     * Generate tags for operation.
     */
    protected function generateTags($route): array
    {
        $name = $route->getName() ?? '';
        
        if (Str::startsWith($name, 'v1.auth.')) return ['Authentication'];
        if (Str::startsWith($name, 'v1.auth.me')) return ['User Profile'];
        if (Str::startsWith($name, 'v1.student.dashboard')) return ['Dashboard'];
        if (Str::startsWith($name, 'v1.student.study-preference')) return ['Study Preferences'];
        if (Str::startsWith($name, 'v1.student.subjects')) return ['Subjects'];
        if (Str::startsWith($name, 'v1.student.topics')) return ['Subjects'];
        if (Str::startsWith($name, 'v1.student.tasks')) return ['Tasks'];
        if (Str::startsWith($name, 'v1.student.study-plan')) return ['Study Plans'];
        if (Str::startsWith($name, 'v1.student.quizzes')) return ['Quizzes'];
        if (Str::startsWith($name, 'v1.student.quiz-attempts')) return ['Quizzes'];
        if (Str::startsWith($name, 'v1.student.recommendations')) return ['Recommendations'];
        if (Str::startsWith($name, 'v1.student.progress')) return ['Progress'];
        if (Str::startsWith($name, 'v1.student.notifications')) return ['Notifications'];
        if (Str::startsWith($name, 'v1.admin.resources')) return ['Admin - Resources'];
        if (Str::startsWith($name, 'v1.admin.users')) return ['Admin - Users'];
        if (Str::startsWith($name, 'v1.admin.system-settings')) return ['Admin - Settings'];
        if (Str::startsWith($name, 'v1.admin.dashboard')) return ['Admin - Dashboard'];
        if (Str::startsWith($name, 'v1.admin.analytics')) return ['Admin - Dashboard'];
        if (Str::startsWith($name, 'health')) return ['Health'];

        return ['Other'];
    }

    /**
     * Generate parameters.
     */
    protected function generateParameters($route): array
    {
        $parameters = [];
        $uri = $route->uri();
        
        // Path parameters
        preg_match_all('/\{([^}]+)\}/', $uri, $matches);
        foreach ($matches[1] as $param) {
            $parameters[] = [
                'name' => $param,
                'in' => 'path',
                'required' => true,
                'schema' => ['type' => 'integer'],
                'description' => "The {$param} ID",
            ];
        }

        // Query parameters for common patterns
        if (in_array($route->getActionMethod(), ['index'])) {
            $parameters = array_merge($parameters, [
                [
                    'name' => 'page',
                    'in' => 'query',
                    'description' => 'Page number',
                    'schema' => ['type' => 'integer', 'minimum' => 1, 'default' => 1],
                ],
                [
                    'name' => 'per_page',
                    'in' => 'query',
                    'description' => 'Items per page',
                    'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 20],
                ],
            ]);
        }

        return $parameters;
    }

    /**
     * Generate responses.
     */
    protected function generateResponses($route): array
    {
        $responses = [
            '200' => [
                'description' => 'Successful response',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => ['type' => 'object'],
                                'message' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Add common error responses for authenticated routes
        $middleware = $route->gatherMiddleware();
        if (in_array('auth:sanctum', $middleware)) {
            $responses['401'] = ['$ref' => '#/components/responses/Unauthorized'];
            $responses['403'] = ['$ref' => '#/components/responses/Forbidden'];
        }

        // Add validation error for mutating operations
        $method = $route->methods()[0] ?? '';
        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            $responses['422'] = ['$ref' => '#/components/responses/ValidationError'];
        }

        $responses['404'] = ['$ref' => '#/components/responses/NotFound'];
        $responses['429'] = ['$ref' => '#/components/responses/RateLimited'];
        $responses['500'] = [
            'description' => 'Internal server error',
            'content' => [
                'application/json' => [
                    'schema' => ['$ref' => '#/components/schemas/Error'],
                ],
            ],
        ];

        return $responses;
    }

    /**
     * Generate security requirements.
     */
    protected function generateSecurity($route): array
    {
        $middleware = $route->gatherMiddleware();
        
        if (in_array('auth:sanctum', $middleware)) {
            return [['bearer' => []]];
        }

        return [];
    }

    /**
     * Generate request body.
     */
    protected function generateRequestBody($route): array
    {
        $action = $route->getAction();
        $controller = $action['controller'] ?? null;
        
        if ($controller && method_exists($controller[0], $controller[1])) {
            $reflection = new \ReflectionMethod($controller[0], $controller[1]);
            $params = $reflection->getParameters();
            
            foreach ($params as $param) {
                $type = $param->getType();
                if ($type && $type->getName() === \Illuminate\Http\Request::class) {
                    // This is a Request object - would need FormRequest analysis
                    return [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [],
                                ],
                            ],
                        ],
                    ];
                }
            }
        }

        return [
            'required' => true,
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                    ],
                ],
            ],
        ];
    }

    /**
     * Convert array to YAML.
     */
    protected function toYaml(array $data): string
    {
        return $this->arrayToYaml($data);
    }

    protected function arrayToYaml(array $data, int $indent = 0): string
    {
        $yaml = '';
        $indentStr = str_repeat('  ', $indent);

        foreach ($data as $key => $value) {
            $yaml .= $indentStr . $key . ': ';
            
            if (is_array($value)) {
                if (empty($value)) {
                    $yaml .= "[]\n";
                } elseif (array_keys($value) === range(0, count($value) - 1)) {
                    // Indexed array
                    $yaml .= "\n";
                    foreach ($value as $item) {
                        $yaml .= $indentStr . '  - ' . $this->yamlValue($item, $indent + 2) . "\n";
                    }
                } else {
                    // Associative array
                    $yaml .= "\n";
                    $yaml .= $this->arrayToYaml($value, $indent + 1);
                }
            } else {
                $yaml .= $this->yamlValue($value) . "\n";
            }
        }

        return $yaml;
    }

    protected function yamlValue($value, int $indent = 0): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_null($value)) {
            return 'null';
        }
        if (is_numeric($value)) {
            return (string) $value;
        }
        if (is_string($value)) {
            // Escape special characters
            if (preg_match('/[:{}[]",]/', $value) || Str::startsWith($value, ['-', '?', '|', '>'])) {
                return '"' . addcslashes($value, '"\\') . '"';
            }
            return $value;
        }
        if (is_array($value)) {
            return $this->arrayToYaml($value, $indent);
        }
        return 'null';
    }
}