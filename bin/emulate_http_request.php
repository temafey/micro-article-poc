<?php
/**
 * HTTP Request Emulator for Symfony based on curl command
 *
 * This script parses a curl command and emulates the corresponding HTTP request
 * for debugging Symfony applications with Xdebug support
 *
 * Compatible with PHP 8.3
 */

declare(strict_types=1);

// Пути к файлам Symfony (настройте под свой проект)
$SYMFONY_PATH = [
    'index' => '/app/public/index.php',
    'root' => '/app/public'
];
$curlCommand = "curl --location 'http://localhost/api/v1/tasks' \
--header 'Content-Type: application/x-www-form-urlencoded' \
--form 'title=\"task 1\"' \
--form 'description=\"task description 1\"' \
--form 'status=\"todo\"'";

//$curlCommand = "curl --location 'http://localhost/api/v1/users' \
//--header 'Content-Type: application/x-www-form-urlencoded' \
//--form 'name=\"user 1\"' \
//--form 'email=\"user1@email.com\"'";

$uuidTask = "";
$uuidUser = "";

//
//$curlCommand = "curl --location --request PUT 'http://localhost/api/v1/tasks/".$uuidTask."/status' \
//--header 'Content-Type: application/x-www-form-urlencoded' \
//--data '{
//  \"status\": \"in_progress\"
//}'";
//
//$curlCommand = "curl --location --request PUT 'http://localhost/api/v1/tasks/".$uuidTask."/status' \
//--header 'Content-Type: application/x-www-form-urlencoded' \
//--data '{
//  \"status\": \"done\"
//}'";
//
//$curlCommand = "curl --location --request PUT 'http://localhost/api/v1/tasks/".$uuidTask."/assign' \
//--header 'Content-Type: application/x-www-form-urlencoded' \
//--data '{
//  \"assigneeId\": \"".$uuidUser."\"
//}'";
//
//$curlCommand = "curl --location 'http://localhost/api/v1/users' \
//--header 'Content-Type: application/x-www-form-urlencoded' \
//--form 'name=\"user 3\"' \
//--form 'email=\"user3@email.com\"'";
//
//$curlCommand = "curl --location 'http://localhost/api/v1/tasks'";
//$curlCommand = "curl --location 'http://localhost/api/v1/tasks?assigneeId=1f01bca9-7e99-68ae-a1c2-decba94b787f&status=todo'";
//$curlCommand = "curl --location 'http://localhost/api/v1/tasks/1f01bce5-fdc5-6650-a656-decba94b787f'";
//$curlCommand = "curl --location 'http://localhost/api/v1/users?name=user%201&email=user1%40email.com'";

$parsedCurl = parseCurlCommand($curlCommand);
emulateHttpRequest($parsedCurl, $SYMFONY_PATH);

/**
 * Parses a curl command string
 */
function parseCurlCommand(string $curlCommand): array
{
    // Result with default values
    $result = [
        'method' => 'GET',
        'url' => '',
        'headers' => [],
        'data' => '',
        'form_data' => [],
        'is_multipart' => false
    ];

    // Process multiline commands
    $curlCommand = preg_replace('/\\\[\r\n]+\s*/s', ' ', $curlCommand);
    $curlCommand = trim($curlCommand);

    // Extract URL
    if (preg_match('/--location\s+[\'"]([^\'"\s]+)[\'"]/i', $curlCommand, $urlMatches)) {
        $result['url'] = $urlMatches[1];
    } elseif (preg_match('/curl\s+[\'"]([^\'"\s]+)[\'"]/i', $curlCommand, $urlMatches)) {
        $result['url'] = $urlMatches[1];
    }

    // Extract headers
    preg_match_all('/--header\s+[\'"]([^\'"]+)[\'"]/', $curlCommand, $headerMatches);
    if (!empty($headerMatches[1])) {
        foreach ($headerMatches[1] as $headerLine) {
            if (str_contains($headerLine, ':')) {
                [$name, $value] = explode(':', $headerLine, 2);
                $result['headers'][trim($name)] = trim($value);
            }
        }
    }

    // EXTRACT FORM DATA - COMPLETELY NEW APPROACH
    // First check if we have --form parameters
    if (strpos($curlCommand, '--form') !== false) {
        //$result['is_multipart'] = true;

        // Force method to POST when using form data
        $result['method'] = 'POST';

        // Force Content-Type for multipart
        $result['headers']['Content-Type'] = 'multipart/form-data';

        // Extract each form parameter with its value
        // This pattern handles 'name="value"' format
        preg_match_all('/--form\s+\'([^=]+)=\\\\?"([^"]+)\\\\?"\'/i', $curlCommand, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $name = trim($match[1]);
            $value = $match[2];  // Direct value without quotes

            echo "DEBUG: Extracted form field: name='$name', value='$value'\n";

            // Add to form_data directly with right structure
            $result['form_data'][$name] = $value;
        }

        // If we found form data, directly use it in $_POST
        if (!empty($result['form_data'])) {
            $_POST = $result['form_data'];
            $_REQUEST = array_merge($_GET ?? [], $_POST);
        }
    }

    // If we still have no form data, check for --data
    if (empty($result['form_data'])) {
        if (preg_match('/--data\s+\'([^\']+)\'/', $curlCommand, $dataMatch)) {
            $result['data'] = $dataMatch[1];
        } elseif (preg_match('/--data\s+"([^"]+)"/', $curlCommand, $dataMatch)) {
            $result['data'] = $dataMatch[1];
        }
    }

    return $result;
}

/**
 * Emulates HTTP request for Symfony with special attention to input data
 */
function emulateHttpRequest(array $parsedCurl, array $symfonyPath): void
{
    // Check URL
    if (empty($parsedCurl['url'])) {
        throw new InvalidArgumentException("URL not found in curl command");
    }

    $urlParts = parse_url($parsedCurl['url']);
    if ($urlParts === false) {
        throw new InvalidArgumentException("Invalid URL: {$parsedCurl['url']}");
    }

    // Basic request parameters
    $path = $urlParts['path'] ?? '/';
    $query = $urlParts['query'] ?? '';
    $host = $urlParts['host'] ?? 'localhost';
    $port = $urlParts['port'] ?? ($urlParts['scheme'] ?? 'http') === 'https' ? 443 : 80;

    // Set up $_SERVER
    $_SERVER = [
        'REQUEST_METHOD' => $parsedCurl['method'],
        'HTTP_HOST' => $host,
        'SERVER_NAME' => $host,
        'SERVER_PORT' => (string)$port,
        'REQUEST_URI' => $path . ($query ? '?' . $query : ''),
        'PATH_INFO' => $path,
        'QUERY_STRING' => $query,
        'DOCUMENT_ROOT' => $symfonyPath['root'],
        'SCRIPT_FILENAME' => $symfonyPath['index'],
        'SCRIPT_NAME' => '/index.php',
        'PHP_SELF' => '/index.php',
        'REMOTE_ADDR' => '127.0.0.1',
        'SERVER_PROTOCOL' => 'HTTP/1.1',
        'HTTP_USER_AGENT' => 'PHP/Curl Emulator (PHP 8.3)',
        'PHP_IDE_CONFIG' => 'serverName=symfony',
        'APP_ENV' => 'dev',
        'APP_DEBUG' => '1'
    ];

    // Prepare GET and POST data
    $_GET = [];
    if ($query) {
        parse_str($query, $_GET);
    }

    // If not already set in the parser
    if (!isset($_POST)) {
        $_POST = $parsedCurl['form_data'] ?? [];
    }

    $_REQUEST = array_merge($_GET, $_POST);
    $_FILES = [];

    // Handle multipart form data
    if ($parsedCurl['is_multipart'] && !empty($parsedCurl['form_data'])) {
        // Generate a boundary
        $boundary = '----WebKitFormBoundary' . bin2hex(random_bytes(8));

        // Set the correct Content-Type with boundary
        $_SERVER['CONTENT_TYPE'] = 'multipart/form-data; boundary=' . $boundary;
        $parsedCurl['headers']['Content-Type'] = 'multipart/form-data; boundary=' . $boundary;

        // Build multipart body for stream wrappers later, but DO NOT set parsedCurl['data']
        $multipartBody = '';

        // Process each form field
        foreach ($parsedCurl['form_data'] as $name => $value) {
            $multipartBody .= '--' . $boundary . "\r\n";
            $multipartBody .= 'Content-Disposition: form-data; name="' . $name . '"' . "\r\n\r\n";
            $multipartBody .= $value . "\r\n";
        }

        // Close the multipart body
        $multipartBody .= '--' . $boundary . '--' . "\r\n";

        // We only use multipartBody for content length and stream wrappers,
        // but NOT for setting parsedCurl['data'] to avoid double processing
        $_SERVER['CONTENT_LENGTH'] = (string)strlen($multipartBody);

        // We'll use this for the stream wrapper setup later
        $GLOBALS['_SYMFONY_RAW_POST_DATA'] = $multipartBody;
    }
    // Handle regular data (only when NOT multipart)
    else if (!empty($parsedCurl['data'])) {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            parse_str($parsedCurl['data'], $_POST);
            $_REQUEST = array_merge($_REQUEST, $_POST);
        } elseif (str_contains($contentType, 'application/json')) {
            $jsonData = json_decode($parsedCurl['data'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                $_POST = $jsonData;
                $_REQUEST = array_merge($_REQUEST, $_POST);
            }
        }

        $_SERVER['CONTENT_LENGTH'] = (string)strlen($parsedCurl['data']);
    }

    // Set headers
    foreach ($parsedCurl['headers'] as $name => $value) {
        $headerKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $_SERVER[$headerKey] = $value;

        // Special handling for Content-Type and Content-Length
        if (strtolower($name) === 'content-type') {
            $_SERVER['CONTENT_TYPE'] = $value;
        } elseif (strtolower($name) === 'content-length') {
            $_SERVER['CONTENT_LENGTH'] = $value;
        }
    }

    // Define class for intercepting php://input
    class SymfonyInputStreamWrapper
    {
        private static $data = '';
        private $position = 0;
        public $context;

        public static function setData($data)
        {
            self::$data = $data;
        }

        public function stream_open($path, $mode, $options, &$opened_path)
        {
            return true;
        }

        public function stream_read($count)
        {
            $ret = substr(self::$data, $this->position, $count);
            $this->position += strlen($ret);
            return $ret;
        }

        public function stream_eof()
        {
            return $this->position >= strlen(self::$data);
        }

        public function stream_stat()
        {
            return [
                'size' => strlen(self::$data),
            ];
        }

        public function stream_seek($offset, $whence)
        {
            switch ($whence) {
                case SEEK_SET:
                    $this->position = $offset;
                    break;
                case SEEK_CUR:
                    $this->position += $offset;
                    break;
                case SEEK_END:
                    $this->position = strlen(self::$data) + $offset;
                    break;
            }
            return true;
        }

        public function stream_tell()
        {
            return $this->position;
        }
    }

    // Set up stream wrapper for multipart or regular data
    $inputData = '';
    if ($parsedCurl['is_multipart'] && isset($GLOBALS['_SYMFONY_RAW_POST_DATA'])) {
        $inputData = $GLOBALS['_SYMFONY_RAW_POST_DATA'];
    } elseif (!empty($parsedCurl['data'])) {
        $inputData = $parsedCurl['data'];
    }

    if (!empty($inputData)) {
        SymfonyInputStreamWrapper::setData($inputData);
        if (!in_array('symfonyinput', stream_get_wrappers())) {
            stream_wrapper_register('symfonyinput', 'SymfonyInputStreamWrapper');
        }
        $GLOBALS['HTTP_RAW_POST_DATA'] = $inputData;

        // Create a temporary file with data
        $tempFile = tmpfile();
        fwrite($tempFile, $inputData);
        rewind($tempFile);
        $GLOBALS['_SYMFONY_INPUT_STREAM'] = $tempFile;

        // Override php wrapper
        if (function_exists('stream_wrapper_unregister') && function_exists('stream_wrapper_restore')) {
            @stream_wrapper_unregister('php');
            @stream_wrapper_register('php', 'SymfonyInputStreamWrapper');
        }
    }

    // Output request information
    echo <<<EOT
===== HTTP REQUEST EMULATION =====
Method: {$parsedCurl['method']}
URL: {$parsedCurl['url']}
Path: $path

EOT;

    if (!empty($parsedCurl['headers'])) {
        echo "Headers:\n";
        foreach ($parsedCurl['headers'] as $name => $value) {
            echo "  $name: $value\n";
        }
        echo "\n";
    }

    if (!empty($parsedCurl['form_data'])) {
        echo "Form Data:\n";
        foreach ($parsedCurl['form_data'] as $name => $value) {
            echo "  $name: $value\n";
        }
        echo "\n";
    } elseif (!empty($parsedCurl['data'])) {
        echo "Request data (first 500 chars): " . substr($parsedCurl['data'], 0, 500) . "...\n\n";
    }

    echo "POST Variables:\n";
    print_r($_POST);

    echo "\nFILES Variables:\n";
    print_r($_FILES);

    echo "\nXdebug: Enabled (XDEBUG_SESSION=PHPSTORM)\n";
    echo "============================\n\n";

    // Check Symfony file
    if (!file_exists($symfonyPath['index'])) {
        throw new RuntimeException(
            "Symfony index file not found at: {$symfonyPath['index']}\n" .
            "Please specify the correct path at the beginning of the script in the \$SYMFONY_PATH variable"
        );
    }

    // Create a Symfony Request object directly
    try {
        // Include Symfony autoloader
        $autoloadPath = dirname($symfonyPath['index']) . '/../vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
        }

        // Create Request if possible
        if (class_exists('Symfony\Component\HttpFoundation\Request')) {
            echo "Creating Symfony Request object directly...\n";

            // Create Request with our parsed data
            $request = new \Symfony\Component\HttpFoundation\Request(
                $_GET,          // query parameters
                $_POST,         // request parameters (this is our key data)
                [],             // attributes
                $_COOKIE ?? [], // cookies
                $_FILES,        // files
                $_SERVER,       // server
                $inputData      // content - use the appropriate data source
            );

            // Override the default request
            \Symfony\Component\HttpFoundation\Request::setFactory(function () use ($request) {
                return $request;
            });

            echo "Symfony Request object successfully created\n\n";
        }
    } catch (Throwable $e) {
        echo "Could not create Symfony Request: " . $e->getMessage() . "\n";
        echo "Continuing with standard method...\n\n";
    }

    echo "Starting Symfony application...\n\n";

    try {
        // Use output buffering
        ob_start();

        // Run Symfony
        require_once $symfonyPath['index'];
        $output = ob_get_clean();

        // Analyze headers
        $responseHeaders = [];
        foreach (headers_list() as $header) {
            $responseHeaders[] = $header;
        }

        echo "===== APPLICATION RESPONSE =====\n";
        if (!empty($responseHeaders)) {
            echo "Response headers:\n";
            foreach ($responseHeaders as $header) {
                echo "  $header\n";
            }
            echo "\n";
        }

        echo "Response body:\n$output\n";
    } catch (Throwable $e) {
        echo "===== APPLICATION ERROR =====\n";
        echo sprintf(
            "Exception type: %s\nMessage: %s\nFile: %s:%d\n\nStack trace:\n%s\n",
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
    } finally {
        // Clean up resources
        if (in_array('symfonyinput', stream_get_wrappers())) {
            @stream_wrapper_unregister('symfonyinput');
        }

        // Restore php wrapper if it was modified
        if (function_exists('stream_wrapper_restore')) {
            @stream_wrapper_restore('php');
        }

        // Close temporary file if it was opened
        if (isset($GLOBALS['_SYMFONY_INPUT_STREAM']) && is_resource($GLOBALS['_SYMFONY_INPUT_STREAM'])) {
            fclose($GLOBALS['_SYMFONY_INPUT_STREAM']);
        }

        echo "\n===== EMULATION COMPLETED =====\n";
    }
}