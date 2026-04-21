<?php

declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Foundation\Application;

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$projectRoot = dirname(__DIR__);
$envFile = $projectRoot.DIRECTORY_SEPARATOR.'.env';
$configCacheFile = $projectRoot.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'config.php';
$runReadTest = ($_GET['run'] ?? '1') !== '0';
$runWriteTest = ($_GET['write'] ?? '0') === '1';

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function mask_value(?string $value, int $keepStart = 4, int $keepEnd = 4): string
{
    $value = (string) $value;

    if ($value === '') {
        return '(empty)';
    }

    $length = strlen($value);

    if ($length <= ($keepStart + $keepEnd)) {
        return str_repeat('*', $length);
    }

    return substr($value, 0, $keepStart).str_repeat('*', max(8, $length - $keepStart - $keepEnd)).substr($value, -$keepEnd);
}

function display_value(?string $value, bool $mask = false): string
{
    $value = (string) $value;

    if ($value === '') {
        return '(empty)';
    }

    return $mask ? mask_value($value) : $value;
}

function parse_dotenv_file(string $path): array
{
    if (! is_file($path) || ! is_readable($path)) {
        return [];
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);

    if ($lines === false) {
        return [];
    }

    $values = [];

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        if (! preg_match('/^(?:export\s+)?([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/', $line, $matches)) {
            continue;
        }

        $key = $matches[1];
        $rawValue = trim($matches[2]);

        if ($rawValue === '') {
            $values[$key] = '';

            continue;
        }

        $quote = $rawValue[0];
        $lastChar = substr($rawValue, -1);

        if (($quote === '"' || $quote === "'") && $lastChar === $quote) {
            $rawValue = substr($rawValue, 1, -1);

            if ($quote === '"') {
                $rawValue = strtr($rawValue, [
                    '\n' => "\n",
                    '\r' => "\r",
                    '\t' => "\t",
                    '\"' => '"',
                    '\\\\' => '\\',
                ]);
            }
        } else {
            $commentPos = strpos($rawValue, ' #');

            if ($commentPos !== false) {
                $rawValue = substr($rawValue, 0, $commentPos);
            }

            $rawValue = trim($rawValue);
        }

        $values[$key] = $rawValue;
    }

    return $values;
}

function env_snapshot(string $key, array $dotenvValues): array
{
    $sources = [];
    $getenvValue = getenv($key);

    if ($getenvValue !== false) {
        $sources['getenv'] = (string) $getenvValue;
    }

    if (array_key_exists($key, $_ENV)) {
        $sources['_ENV'] = (string) $_ENV[$key];
    }

    if (array_key_exists($key, $_SERVER)) {
        $sources['_SERVER'] = (string) $_SERVER[$key];
    }

    if (array_key_exists($key, $dotenvValues)) {
        $sources['.env'] = (string) $dotenvValues[$key];
    }

    $effective = '';
    $source = 'missing';

    foreach (['getenv', '_ENV', '_SERVER', '.env'] as $candidate) {
        if (! array_key_exists($candidate, $sources)) {
            continue;
        }

        $effective = (string) $sources[$candidate];
        $source = $candidate;

        if ($effective !== '') {
            break;
        }
    }

    return [
        'key' => $key,
        'effective' => $effective,
        'source' => $source,
        'sources' => $sources,
    ];
}

function normalize_azure_storage_url(string $storageUrl, string $accountName): string
{
    $storageUrl = trim($storageUrl);

    if ($storageUrl !== '') {
        return rtrim($storageUrl, '/');
    }

    if ($accountName !== '') {
        return 'https://'.$accountName.'.blob.core.windows.net';
    }

    return '';
}

function encode_blob_path(string $path): string
{
    $path = trim($path, '/');

    if ($path === '') {
        return '';
    }

    $segments = array_map(
        static fn (string $segment): string => rawurlencode($segment),
        explode('/', $path)
    );

    return implode('/', $segments);
}

function azure_public_blob_url(string $baseUrl, string $container, string $blobPath): string
{
    $baseUrl = rtrim($baseUrl, '/');
    $container = trim($container, '/');
    $blobPath = encode_blob_path($blobPath);

    return $baseUrl.'/'.$container.'/'.$blobPath;
}

function build_canonicalized_headers(array $headers): string
{
    $canonicalized = [];

    foreach ($headers as $name => $value) {
        $lowerName = strtolower(trim((string) $name));

        if (! str_starts_with($lowerName, 'x-ms-')) {
            continue;
        }

        $canonicalized[$lowerName] = preg_replace('/\s+/', ' ', trim((string) $value)) ?? trim((string) $value);
    }

    ksort($canonicalized);

    $result = '';

    foreach ($canonicalized as $name => $value) {
        $result .= $name.':'.$value."\n";
    }

    return $result;
}

function build_canonicalized_resource(string $accountName, string $resourcePath, array $query): string
{
    $resource = '/'.$accountName;
    $resourcePath = trim($resourcePath, '/');

    if ($resourcePath !== '') {
        $resource .= '/'.$resourcePath;
    }

    if ($query === []) {
        return $resource;
    }

    $normalizedQuery = [];

    foreach ($query as $name => $value) {
        $lowerName = strtolower((string) $name);
        $values = is_array($value) ? $value : [$value];
        $normalizedValues = array_map(static fn (mixed $item): string => (string) $item, $values);
        sort($normalizedValues, SORT_STRING);
        $normalizedQuery[$lowerName] = $normalizedValues;
    }

    ksort($normalizedQuery, SORT_STRING);

    foreach ($normalizedQuery as $name => $values) {
        $resource .= "\n".$name.':'.implode(',', $values);
    }

    return $resource;
}

function parse_response_headers(string $rawHeaders): array
{
    $headers = [];
    $blocks = preg_split("/\r\n\r\n|\n\n/", trim($rawHeaders));

    if ($blocks === false || $blocks === []) {
        return $headers;
    }

    $lastBlock = (string) end($blocks);
    $lines = preg_split("/\r\n|\n|\r/", $lastBlock);

    if ($lines === false) {
        return $headers;
    }

    foreach ($lines as $line) {
        if (! str_contains($line, ':')) {
            continue;
        }

        [$name, $value] = explode(':', $line, 2);
        $headers[trim($name)] = trim($value);
    }

    return $headers;
}

function header_value(array $headers, string $name): string
{
    foreach ($headers as $headerName => $value) {
        if (strcasecmp((string) $headerName, $name) === 0) {
            return (string) $value;
        }
    }

    return '';
}

function xml_error_summary(string $body): string
{
    if ($body === '') {
        return '';
    }

    $code = '';
    $message = '';

    if (preg_match('/<Code>([^<]+)<\/Code>/', $body, $codeMatches)) {
        $code = trim($codeMatches[1]);
    }

    if (preg_match('/<Message>([^<]+)<\/Message>/', $body, $messageMatches)) {
        $message = trim($messageMatches[1]);
    }

    return trim($code.' '.$message);
}

function azure_blob_request(
    array $config,
    string $method,
    string $resourcePath,
    array $query = [],
    array $extraHeaders = [],
    string $body = '',
    ?string $contentType = null
): array {
    if (! extension_loaded('curl')) {
        return [
            'ok' => false,
            'error' => 'The curl extension is not loaded.',
        ];
    }

    $accountName = trim((string) ($config['account_name'] ?? ''));
    $accountKey = trim((string) ($config['account_key'] ?? ''));
    $baseUrl = rtrim((string) ($config['storage_url'] ?? ''), '/');

    if ($accountName === '' || $accountKey === '' || $baseUrl === '') {
        return [
            'ok' => false,
            'error' => 'Missing required Azure settings.',
        ];
    }

    $method = strtoupper($method);
    $resourcePath = trim($resourcePath, '/');
    $encodedPath = encode_blob_path($resourcePath);
    $url = $baseUrl;

    if ($encodedPath !== '') {
        $url .= '/'.$encodedPath;
    }

    if ($query !== []) {
        $url .= '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    $contentLength = $body === '' ? '' : (string) strlen($body);
    $requestHeaders = array_merge(
        [
            'x-ms-date' => gmdate('D, d M Y H:i:s').' GMT',
            'x-ms-version' => '2023-11-03',
        ],
        $extraHeaders
    );

    $canonicalizedHeaders = build_canonicalized_headers($requestHeaders);
    $canonicalizedResource = build_canonicalized_resource($accountName, $encodedPath, $query);

    $stringToSign = implode("\n", [
        $method,
        '',
        '',
        $contentLength,
        '',
        $contentType ?? '',
        '',
        '',
        '',
        '',
        '',
        '',
    ])."\n".$canonicalizedHeaders.$canonicalizedResource;

    $decodedKey = base64_decode($accountKey, true);

    if ($decodedKey === false) {
        return [
            'ok' => false,
            'error' => 'AZURE_STORAGE_KEY is not valid base64.',
        ];
    }

    $signature = base64_encode(hash_hmac('sha256', $stringToSign, $decodedKey, true));
    $requestHeaders['Authorization'] = 'SharedKey '.$accountName.':'.$signature;
    $requestHeaders['Expect'] = '';

    if ($contentType !== null && $contentType !== '') {
        $requestHeaders['Content-Type'] = $contentType;
    }

    if ($contentLength !== '') {
        $requestHeaders['Content-Length'] = $contentLength;
    }

    $curlHeaders = [];

    foreach ($requestHeaders as $name => $value) {
        $curlHeaders[] = $name.': '.$value;
    }

    $ch = curl_init($url);

    if ($ch === false) {
        return [
            'ok' => false,
            'error' => 'Unable to initialize curl.',
        ];
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

    if ($method === 'HEAD') {
        curl_setopt($ch, CURLOPT_NOBODY, true);
    } elseif ($body !== '') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    $rawResponse = curl_exec($ch);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $effectiveUrl = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    if ($rawResponse === false) {
        return [
            'ok' => false,
            'error' => 'curl_exec failed.',
            'curl_errno' => $curlErrno,
            'curl_error' => $curlError,
            'url' => $url,
            'request_headers' => $requestHeaders,
            'string_to_sign' => $stringToSign,
        ];
    }

    $rawHeaders = substr($rawResponse, 0, $headerSize);
    $responseBody = substr($rawResponse, $headerSize);
    $responseHeaders = parse_response_headers($rawHeaders);

    return [
        'ok' => $statusCode >= 200 && $statusCode < 300,
        'status_code' => $statusCode,
        'url' => $effectiveUrl,
        'request_headers' => $requestHeaders,
        'response_headers' => $responseHeaders,
        'response_body' => $responseBody,
        'string_to_sign' => $stringToSign,
        'error_summary' => xml_error_summary($responseBody),
    ];
}

function run_dns_check(string $host): array
{
    if ($host === '') {
        return [
            'ok' => false,
            'message' => 'Host is empty.',
        ];
    }

    $resolved = gethostbyname($host);
    $ok = $resolved !== $host || filter_var($host, FILTER_VALIDATE_IP) !== false;

    return [
        'ok' => $ok,
        'message' => $ok ? 'Resolved to '.$resolved : 'DNS lookup did not resolve '.$host,
        'resolved' => $resolved,
    ];
}

function parse_blob_names(string $xml): array
{
    if ($xml === '' || ! extension_loaded('simplexml')) {
        return [];
    }

    $document = @simplexml_load_string($xml);

    if ($document === false || ! isset($document->Blobs)) {
        return [];
    }

    $names = [];

    foreach ($document->Blobs->Blob as $blob) {
        if (isset($blob->Name)) {
            $names[] = (string) $blob->Name;
        }
    }

    return $names;
}

function detect_issues(array $azureConfig, array $envMap, array $laravelState): array
{
    $issues = [];

    if ($azureConfig['account_name'] === '') {
        $issues[] = 'AZURE_STORAGE_NAME is empty.';
    }

    if ($azureConfig['account_key'] === '') {
        $issues[] = 'AZURE_STORAGE_KEY is empty.';
    }

    if ($azureConfig['container'] === '') {
        $issues[] = 'AZURE_STORAGE_CONTAINER is empty.';
    }

    if ($azureConfig['storage_url'] === '') {
        $issues[] = 'AZURE_STORAGE_URL is empty and could not be inferred from AZURE_STORAGE_NAME.';
    }

    $path = (string) parse_url($azureConfig['storage_url'], PHP_URL_PATH);

    if ($path !== '' && $path !== '/') {
        $issues[] = 'AZURE_STORAGE_URL should be the blob service root only. Do not include the container path.';
    }

    $host = (string) parse_url($azureConfig['storage_url'], PHP_URL_HOST);

    if ($host !== '' && $azureConfig['account_name'] !== '' && stripos($host, $azureConfig['account_name']) === false) {
        $issues[] = 'AZURE_STORAGE_URL host does not appear to match AZURE_STORAGE_NAME.';
    }

    if (($azureConfig['filesystems_disk'] ?? '') !== 'azure') {
        $issues[] = 'FILESYSTEM_DISK is not "azure". Actual uploads may still be using another disk.';
    }

    if (($azureConfig['community_upload_disk'] ?? '') !== 'azure') {
        $issues[] = 'COMMUNITY_UPLOAD_DISK is not "azure". Community uploads may not use Azure.';
    }

    if ($laravelState['booted'] && $laravelState['config_cache_exists']) {
        $issues[] = 'Laravel config cache exists. If .env was changed recently, run "php artisan optimize:clear" before testing the app.';
    }

    foreach ([
        'AZURE_STORAGE_NAME' => 'filesystems.disks.azure.name',
        'AZURE_STORAGE_KEY' => 'filesystems.disks.azure.key',
        'AZURE_STORAGE_CONTAINER' => 'filesystems.disks.azure.container',
        'AZURE_STORAGE_URL' => 'filesystems.disks.azure.storage_url',
    ] as $envKey => $configKey) {
        $envValue = trim((string) ($envMap[$envKey]['effective'] ?? ''));
        $configValue = trim((string) ($laravelState['config'][$configKey] ?? ''));

        if ($laravelState['booted'] && $envValue !== '' && $configValue !== '' && $envValue !== $configValue) {
            $issues[] = $envKey.' differs from Laravel config "'.$configKey.'". Cached config is a likely cause.';
        }
    }

    return $issues;
}

$dotenvValues = parse_dotenv_file($envFile);
$trackedEnvKeys = [
    'APP_ENV',
    'FILESYSTEM_DISK',
    'COMMUNITY_UPLOAD_DISK',
    'AZURE_STORAGE_NAME',
    'AZURE_STORAGE_KEY',
    'AZURE_STORAGE_CONTAINER',
    'AZURE_STORAGE_URL',
];

$envMap = [];

foreach ($trackedEnvKeys as $key) {
    $envMap[$key] = env_snapshot($key, $dotenvValues);
}

$laravelState = [
    'available' => false,
    'booted' => false,
    'error' => null,
    'config' => [],
    'config_cache_exists' => is_file($configCacheFile),
    'config_cache_file' => $configCacheFile,
    'config_cache_mtime' => is_file($configCacheFile) ? gmdate('Y-m-d H:i:s', (int) filemtime($configCacheFile)).' UTC' : null,
];

$autoloadPath = $projectRoot.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';
$bootstrapPath = $projectRoot.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'app.php';

if (is_file($autoloadPath) && is_file($bootstrapPath)) {
    $laravelState['available'] = true;

    try {
        require_once $autoloadPath;
        $app = require $bootstrapPath;

        if ($app instanceof Application) {
            $kernel = $app->make(Kernel::class);
            $kernel->bootstrap();

            $configRepo = $app['config'];

            $laravelState['booted'] = true;
            $laravelState['config'] = [
                'app.env' => (string) $configRepo->get('app.env', ''),
                'filesystems.default' => (string) $configRepo->get('filesystems.default', ''),
                'community.uploads.disk' => (string) $configRepo->get('community.uploads.disk', ''),
                'filesystems.disks.azure.name' => (string) $configRepo->get('filesystems.disks.azure.name', ''),
                'filesystems.disks.azure.key' => (string) $configRepo->get('filesystems.disks.azure.key', ''),
                'filesystems.disks.azure.container' => (string) $configRepo->get('filesystems.disks.azure.container', ''),
                'filesystems.disks.azure.storage_url' => (string) $configRepo->get('filesystems.disks.azure.storage_url', ''),
                'filesystems.disks.azure.url' => (string) $configRepo->get('filesystems.disks.azure.url', ''),
            ];
        } else {
            $laravelState['error'] = 'bootstrap/app.php did not return a Laravel application instance.';
        }
    } catch (Throwable $e) {
        $laravelState['error'] = $e->getMessage();
    }
}

$envAccountName = trim((string) ($envMap['AZURE_STORAGE_NAME']['effective'] ?? ''));
$envAccountKey = trim((string) ($envMap['AZURE_STORAGE_KEY']['effective'] ?? ''));
$envContainer = trim((string) ($envMap['AZURE_STORAGE_CONTAINER']['effective'] ?? 'uploads'));
$envStorageUrl = normalize_azure_storage_url(
    (string) ($envMap['AZURE_STORAGE_URL']['effective'] ?? ''),
    $envAccountName
);

$azureConfig = $laravelState['booted']
    ? [
        'source' => 'Laravel config',
        'account_name' => trim((string) ($laravelState['config']['filesystems.disks.azure.name'] ?? '')),
        'account_key' => trim((string) ($laravelState['config']['filesystems.disks.azure.key'] ?? '')),
        'container' => trim((string) ($laravelState['config']['filesystems.disks.azure.container'] ?? 'uploads')),
        'storage_url' => normalize_azure_storage_url(
            (string) ($laravelState['config']['filesystems.disks.azure.storage_url'] ?? ''),
            trim((string) ($laravelState['config']['filesystems.disks.azure.name'] ?? ''))
        ),
        'public_url' => trim((string) ($laravelState['config']['filesystems.disks.azure.url'] ?? '')),
        'filesystems_disk' => trim((string) ($laravelState['config']['filesystems.default'] ?? '')),
        'community_upload_disk' => trim((string) ($laravelState['config']['community.uploads.disk'] ?? '')),
    ]
    : [
        'source' => 'env fallback',
        'account_name' => $envAccountName,
        'account_key' => $envAccountKey,
        'container' => $envContainer !== '' ? $envContainer : 'uploads',
        'storage_url' => $envStorageUrl,
        'public_url' => $envStorageUrl !== '' ? $envStorageUrl.'/'.($envContainer !== '' ? $envContainer : 'uploads') : '',
        'filesystems_disk' => trim((string) ($envMap['FILESYSTEM_DISK']['effective'] ?? '')),
        'community_upload_disk' => trim((string) ($envMap['COMMUNITY_UPLOAD_DISK']['effective'] ?? '')),
    ];

$azureConfig['container_url'] = $azureConfig['storage_url'] !== ''
    ? rtrim($azureConfig['storage_url'], '/').'/'.trim((string) $azureConfig['container'], '/')
    : '';

$issues = detect_issues($azureConfig, $envMap, $laravelState);
$readTest = null;
$writeTest = null;
$dnsCheck = null;

if ($runReadTest) {
    $host = (string) parse_url((string) $azureConfig['storage_url'], PHP_URL_HOST);
    $dnsCheck = run_dns_check($host);

    $readTest = azure_blob_request(
        $azureConfig,
        'GET',
        (string) $azureConfig['container'],
        [
            'restype' => 'container',
            'comp' => 'list',
            'maxresults' => '5',
        ]
    );

    if (($readTest['ok'] ?? false) === true) {
        $readTest['blob_names'] = parse_blob_names((string) ($readTest['response_body'] ?? ''));
    }
}

if ($runWriteTest) {
    $blobName = 'debug/azure-debug-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(3)).'.txt';
    $body = implode("\n", [
        'Azure Blob debug write test',
        'Generated at: '.gmdate('Y-m-d H:i:s').' UTC',
        'HTTP host: '.($_SERVER['HTTP_HOST'] ?? 'unknown'),
        'Config source: '.$azureConfig['source'],
        'Container: '.(string) $azureConfig['container'],
        '',
    ]);

    $putResponse = azure_blob_request(
        $azureConfig,
        'PUT',
        (string) $azureConfig['container'].'/'.$blobName,
        [],
        [
            'x-ms-blob-type' => 'BlockBlob',
        ],
        $body,
        'text/plain; charset=utf-8'
    );

    $deleteResponse = null;

    if (($putResponse['ok'] ?? false) === true) {
        $deleteResponse = azure_blob_request(
            $azureConfig,
            'DELETE',
            (string) $azureConfig['container'].'/'.$blobName
        );
    }

    $writeTest = [
        'blob_name' => $blobName,
        'blob_url' => $azureConfig['storage_url'] !== ''
            ? azure_public_blob_url((string) $azureConfig['storage_url'], (string) $azureConfig['container'], $blobName)
            : '',
        'put' => $putResponse,
        'delete' => $deleteResponse,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Azure Blob Debug</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f5f7fb;
            --card: #ffffff;
            --line: #d8e0ec;
            --text: #1e293b;
            --muted: #5b6472;
            --good: #0f766e;
            --good-bg: #ecfdf5;
            --warn: #b45309;
            --warn-bg: #fff7ed;
            --bad: #b91c1c;
            --bad-bg: #fef2f2;
            --accent: #0f4c81;
            --shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: linear-gradient(180deg, #eef4ff 0%, var(--bg) 45%, #f9fafb 100%);
            color: var(--text);
            font: 14px/1.6 "Segoe UI", Arial, sans-serif;
        }

        .wrap {
            max-width: 1200px;
            margin: 0 auto;
            padding: 28px 18px 40px;
        }

        .hero,
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            box-shadow: var(--shadow);
        }

        .hero {
            padding: 24px;
            margin-bottom: 18px;
        }

        .hero h1 {
            margin: 0 0 10px;
            font-size: 28px;
            line-height: 1.2;
        }

        .muted {
            color: var(--muted);
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        .actions a {
            text-decoration: none;
            border-radius: 999px;
            padding: 10px 16px;
            border: 1px solid var(--accent);
            color: var(--accent);
            background: #fff;
            font-weight: 600;
        }

        .actions a.primary {
            background: var(--accent);
            color: #fff;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 18px;
            margin-bottom: 18px;
        }

        .card {
            padding: 18px;
        }

        h2 {
            margin: 0 0 12px;
            font-size: 18px;
        }

        h3 {
            margin: 18px 0 10px;
            font-size: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            text-align: left;
            padding: 10px 12px;
            border-top: 1px solid var(--line);
            vertical-align: top;
        }

        th {
            width: 34%;
            color: var(--muted);
            font-weight: 600;
            background: #fbfdff;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            padding: 5px 12px;
            font-weight: 700;
            font-size: 12px;
        }

        .ok {
            background: var(--good-bg);
            color: var(--good);
        }

        .warn {
            background: var(--warn-bg);
            color: var(--warn);
        }

        .bad {
            background: var(--bad-bg);
            color: var(--bad);
        }

        .stack {
            display: grid;
            gap: 12px;
        }

        .callout {
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid transparent;
        }

        .callout.ok {
            border-color: #a7f3d0;
        }

        .callout.warn {
            border-color: #fdba74;
        }

        .callout.bad {
            border-color: #fecaca;
        }

        pre {
            margin: 0;
            padding: 14px;
            background: #0f172a;
            color: #e2e8f0;
            border-radius: 12px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-word;
        }

        code {
            font-family: Consolas, "Courier New", monospace;
            font-size: 12px;
        }

        ul {
            margin: 0;
            padding-left: 20px;
        }

        .section {
            margin-bottom: 18px;
        }

        .mono {
            font-family: Consolas, "Courier New", monospace;
            font-size: 12px;
        }

        @media (max-width: 720px) {
            .hero h1 {
                font-size: 24px;
            }

            th,
            td {
                display: block;
                width: 100%;
            }

            th {
                border-bottom: 0;
                padding-bottom: 4px;
            }

            td {
                padding-top: 0;
            }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <section class="hero">
            <div class="badge <?php echo $laravelState['booted'] ? 'ok' : 'warn'; ?>">
                <?php echo $laravelState['booted'] ? 'Using Laravel config' : 'Using env fallback'; ?>
            </div>
            <h1>Azure Blob Debug Page</h1>
            <p class="muted">
                This page checks Azure Blob configuration, environment loading, container access, and an optional write/delete roundtrip.
                It shows raw env values, Laravel effective config, and the actual Azure API result side by side.
            </p>
            <p class="muted">
                Remove this file after testing. It exposes deployment diagnostics and partially masked secret metadata.
            </p>
            <div class="actions">
                <a class="primary" href="?run=1">Run read-only test</a>
                <a href="?run=1&amp;write=1">Run write test and auto-delete</a>
                <a href="?run=0">Refresh without Azure calls</a>
            </div>
        </section>

        <?php if ($issues !== []) { ?>
            <section class="card section">
                <h2>Detected Issues</h2>
                <div class="stack">
                    <?php foreach ($issues as $issue) { ?>
                        <div class="callout warn"><?php echo h($issue); ?></div>
                    <?php } ?>
                </div>
            </section>
        <?php } ?>

        <section class="grid">
            <div class="card">
                <h2>Runtime</h2>
                <table>
                    <tr>
                        <th>Generated At</th>
                        <td><?php echo h(gmdate('Y-m-d H:i:s')); ?> UTC</td>
                    </tr>
                    <tr>
                        <th>PHP Version</th>
                        <td><?php echo h(PHP_VERSION); ?></td>
                    </tr>
                    <tr>
                        <th>SAPI</th>
                        <td><?php echo h(PHP_SAPI); ?></td>
                    </tr>
                    <tr>
                        <th>curl</th>
                        <td><?php echo extension_loaded('curl') ? 'loaded' : 'missing'; ?></td>
                    </tr>
                    <tr>
                        <th>openssl</th>
                        <td><?php echo extension_loaded('openssl') ? 'loaded' : 'missing'; ?></td>
                    </tr>
                    <tr>
                        <th>simplexml</th>
                        <td><?php echo extension_loaded('simplexml') ? 'loaded' : 'missing'; ?></td>
                    </tr>
                    <tr>
                        <th>.env File</th>
                        <td class="mono"><?php echo h($envFile); ?><?php echo is_file($envFile) ? ' (found)' : ' (missing)'; ?></td>
                    </tr>
                    <tr>
                        <th>Config Cache</th>
                        <td class="mono">
                            <?php if ($laravelState['config_cache_exists']) { ?>
                                <?php echo h($laravelState['config_cache_file']); ?><br>
                                <span class="muted">Last modified: <?php echo h((string) $laravelState['config_cache_mtime']); ?></span>
                            <?php } else { ?>
                                not present
                            <?php } ?>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="card">
                <h2>Laravel Bootstrap</h2>
                <table>
                    <tr>
                        <th>Autoload</th>
                        <td><?php echo is_file($autoloadPath) ? 'found' : 'missing'; ?></td>
                    </tr>
                    <tr>
                        <th>Bootstrap File</th>
                        <td><?php echo is_file($bootstrapPath) ? 'found' : 'missing'; ?></td>
                    </tr>
                    <tr>
                        <th>Laravel Boot</th>
                        <td>
                            <span class="badge <?php echo $laravelState['booted'] ? 'ok' : 'warn'; ?>">
                                <?php echo $laravelState['booted'] ? 'booted' : 'not booted'; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>APP_ENV</th>
                        <td><?php echo h((string) ($laravelState['config']['app.env'] ?? '(unavailable)')); ?></td>
                    </tr>
                    <tr>
                        <th>Error</th>
                        <td><?php echo h((string) ($laravelState['error'] ?? '(none)')); ?></td>
                    </tr>
                    <tr>
                        <th>Azure Config Source</th>
                        <td><?php echo h((string) $azureConfig['source']); ?></td>
                    </tr>
                </table>
            </div>
        </section>

        <section class="card section">
            <h2>Effective Azure Settings</h2>
            <table>
                <tr>
                    <th>FILESYSTEM_DISK</th>
                    <td><?php echo h(display_value((string) ($azureConfig['filesystems_disk'] ?? ''))); ?></td>
                </tr>
                <tr>
                    <th>COMMUNITY_UPLOAD_DISK</th>
                    <td><?php echo h(display_value((string) ($azureConfig['community_upload_disk'] ?? ''))); ?></td>
                </tr>
                <tr>
                    <th>AZURE_STORAGE_NAME</th>
                    <td><?php echo h(display_value((string) ($azureConfig['account_name'] ?? ''))); ?></td>
                </tr>
                <tr>
                    <th>AZURE_STORAGE_KEY</th>
                    <td><?php echo h(display_value((string) ($azureConfig['account_key'] ?? ''), true)); ?></td>
                </tr>
                <tr>
                    <th>AZURE_STORAGE_CONTAINER</th>
                    <td><?php echo h(display_value((string) ($azureConfig['container'] ?? ''))); ?></td>
                </tr>
                <tr>
                    <th>AZURE_STORAGE_URL</th>
                    <td class="mono"><?php echo h(display_value((string) ($azureConfig['storage_url'] ?? ''))); ?></td>
                </tr>
                <tr>
                    <th>Container URL</th>
                    <td class="mono"><?php echo h(display_value((string) ($azureConfig['container_url'] ?? ''))); ?></td>
                </tr>
                <tr>
                    <th>Laravel Public URL</th>
                    <td class="mono"><?php echo h(display_value((string) ($azureConfig['public_url'] ?? ''))); ?></td>
                </tr>
            </table>
        </section>

        <section class="card section">
            <h2>Env Snapshots</h2>
            <table>
                <tr>
                    <th>Key</th>
                    <th>Effective Value</th>
                    <th>Picked From</th>
                    <th>All Sources</th>
                </tr>
                <?php foreach ($envMap as $key => $snapshot) { ?>
                    <?php $mask = $key === 'AZURE_STORAGE_KEY'; ?>
                    <tr>
                        <td class="mono"><?php echo h($key); ?></td>
                        <td><?php echo h(display_value((string) ($snapshot['effective'] ?? ''), $mask)); ?></td>
                        <td><?php echo h((string) ($snapshot['source'] ?? 'missing')); ?></td>
                        <td class="mono">
                            <?php if (($snapshot['sources'] ?? []) === []) { ?>
                                (none)
                            <?php } else { ?>
                                <?php foreach ($snapshot['sources'] as $sourceName => $sourceValue) { ?>
                                    <div><?php echo h($sourceName); ?> = <?php echo h(display_value((string) $sourceValue, $mask)); ?></div>
                                <?php } ?>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </section>

        <?php if ($laravelState['booted']) { ?>
            <section class="card section">
                <h2>Laravel Config Snapshot</h2>
                <table>
                    <?php foreach ($laravelState['config'] as $key => $value) { ?>
                        <tr>
                            <th class="mono"><?php echo h($key); ?></th>
                            <td class="mono">
                                <?php echo h(str_contains($key, '.key') ? display_value($value, true) : display_value($value)); ?>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            </section>
        <?php } ?>

        <?php if ($runReadTest) { ?>
            <section class="grid">
                <div class="card">
                    <h2>DNS Check</h2>
                    <?php if ($dnsCheck !== null) { ?>
                        <div class="callout <?php echo ($dnsCheck['ok'] ?? false) ? 'ok' : 'bad'; ?>">
                            <?php echo h((string) ($dnsCheck['message'] ?? '')); ?>
                        </div>
                    <?php } ?>
                </div>

                <div class="card">
                    <h2>Read-Only Azure Test</h2>
                    <?php if ($readTest === null) { ?>
                        <div class="callout warn">Read-only test did not run.</div>
                    <?php } elseif (($readTest['ok'] ?? false) === true) { ?>
                        <div class="callout ok">
                            Container list call succeeded with HTTP <?php echo h((string) ($readTest['status_code'] ?? '')); ?>.
                        </div>
                        <?php if (($readTest['blob_names'] ?? []) !== []) { ?>
                            <h3>First blobs returned</h3>
                            <ul>
                                <?php foreach ((array) $readTest['blob_names'] as $blobName) { ?>
                                    <li class="mono"><?php echo h($blobName); ?></li>
                                <?php } ?>
                            </ul>
                        <?php } else { ?>
                            <p class="muted">No blobs were returned, or the container is currently empty.</p>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="callout bad">
                            Azure call failed.
                            <?php if (isset($readTest['status_code'])) { ?>
                                HTTP <?php echo h((string) $readTest['status_code']); ?>.
                            <?php } ?>
                            <?php if (! empty($readTest['error'])) { ?>
                                <?php echo h((string) $readTest['error']); ?>
                            <?php } ?>
                            <?php if (! empty($readTest['error_summary'])) { ?>
                                <?php echo h((string) $readTest['error_summary']); ?>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            </section>

            <?php if ($readTest !== null) { ?>
                <section class="card section">
                    <h2>Read Test Details</h2>
                    <table>
                        <tr>
                            <th>Request URL</th>
                            <td class="mono"><?php echo h((string) ($readTest['url'] ?? '')); ?></td>
                        </tr>
                        <tr>
                            <th>Status Code</th>
                            <td><?php echo h((string) ($readTest['status_code'] ?? '')); ?></td>
                        </tr>
                        <tr>
                            <th>x-ms-request-id</th>
                            <td class="mono"><?php echo h(header_value((array) ($readTest['response_headers'] ?? []), 'x-ms-request-id')); ?></td>
                        </tr>
                    </table>
                    <h3>Request Headers</h3>
                    <pre><code><?php
                    $safeRequestHeaders = $readTest['request_headers'] ?? [];
                if (isset($safeRequestHeaders['Authorization'])) {
                    $safeRequestHeaders['Authorization'] = 'SharedKey '.($azureConfig['account_name'] !== '' ? $azureConfig['account_name'] : '[missing]').':[masked]';
                }
                echo h(print_r($safeRequestHeaders, true));
                ?></code></pre>
                    <h3>Response Headers</h3>
                    <pre><code><?php echo h(print_r($readTest['response_headers'] ?? [], true)); ?></code></pre>
                    <h3>Response Body</h3>
                    <pre><code><?php echo h(substr((string) ($readTest['response_body'] ?? ''), 0, 4000)); ?></code></pre>
                </section>
            <?php } ?>
        <?php } ?>

        <?php if ($runWriteTest && $writeTest !== null) { ?>
            <section class="card section">
                <h2>Write Test</h2>
                <table>
                    <tr>
                        <th>Blob Name</th>
                        <td class="mono"><?php echo h((string) $writeTest['blob_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Blob URL</th>
                        <td class="mono"><?php echo h((string) $writeTest['blob_url']); ?></td>
                    </tr>
                </table>

                <h3>PUT Result</h3>
                <?php if (($writeTest['put']['ok'] ?? false) === true) { ?>
                    <div class="callout ok">
                        Upload succeeded with HTTP <?php echo h((string) ($writeTest['put']['status_code'] ?? '')); ?>.
                    </div>
                <?php } else { ?>
                    <div class="callout bad">
                        Upload failed.
                        <?php if (isset($writeTest['put']['status_code'])) { ?>
                            HTTP <?php echo h((string) $writeTest['put']['status_code']); ?>.
                        <?php } ?>
                        <?php if (! empty($writeTest['put']['error'])) { ?>
                            <?php echo h((string) $writeTest['put']['error']); ?>
                        <?php } ?>
                        <?php if (! empty($writeTest['put']['error_summary'])) { ?>
                            <?php echo h((string) $writeTest['put']['error_summary']); ?>
                        <?php } ?>
                    </div>
                <?php } ?>
                <pre><code><?php
                $safePutHeaders = $writeTest['put']['request_headers'] ?? [];
            if (isset($safePutHeaders['Authorization'])) {
                $safePutHeaders['Authorization'] = 'SharedKey '.($azureConfig['account_name'] !== '' ? $azureConfig['account_name'] : '[missing]').':[masked]';
            }
            echo h(print_r($safePutHeaders, true));
            ?></code></pre>

                <h3>DELETE Result</h3>
                <?php if (($writeTest['delete']['ok'] ?? false) === true) { ?>
                    <div class="callout ok">
                        Cleanup succeeded with HTTP <?php echo h((string) ($writeTest['delete']['status_code'] ?? '')); ?>.
                    </div>
                <?php } elseif ($writeTest['delete'] === null) { ?>
                    <div class="callout warn">Cleanup did not run because upload failed.</div>
                <?php } else { ?>
                    <div class="callout bad">
                        Cleanup failed.
                        <?php if (isset($writeTest['delete']['status_code'])) { ?>
                            HTTP <?php echo h((string) $writeTest['delete']['status_code']); ?>.
                        <?php } ?>
                        <?php if (! empty($writeTest['delete']['error'])) { ?>
                            <?php echo h((string) $writeTest['delete']['error']); ?>
                        <?php } ?>
                        <?php if (! empty($writeTest['delete']['error_summary'])) { ?>
                            <?php echo h((string) $writeTest['delete']['error_summary']); ?>
                        <?php } ?>
                    </div>
                <?php } ?>
            </section>
        <?php } ?>
    </div>
</body>
</html>
