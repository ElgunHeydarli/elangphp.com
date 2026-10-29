<?php
declare(strict_types=1);
// Sequential HTTP latency on two explicitly started local fixtures. No production claim.
$options = getopt('', ['elang:', 'laravel:', 'requests::', 'rounds::', 'output::']);
$urls = ['elang' => $options['elang'] ?? 'http://127.0.0.1:8098/hello', 'laravel' => $options['laravel'] ?? 'http://127.0.0.1:8099/hello'];
foreach ($urls as $url) {
    if (!in_array(parse_url($url, PHP_URL_HOST), ['127.0.0.1', 'localhost', '::1'], true)) throw new RuntimeException('Benchmark endpoints must be local loopback URLs.');
}
$requests = filter_var($options['requests'] ?? 500, FILTER_VALIDATE_INT);
$rounds = filter_var($options['rounds'] ?? 3, FILTER_VALIDATE_INT);
if (!$requests || $requests < 10 || $requests > 10000 || !$rounds || $rounds < 1 || $rounds > 10) throw new InvalidArgumentException('Use 10..10000 requests and 1..10 rounds.');
function measure(string $url, int $count): array
{
    $handle = curl_init($url);
    curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_FOLLOWLOCATION => false]);
    $runtime = ''; $memory = 0;
    curl_setopt($handle, CURLOPT_HEADERFUNCTION, function ($handle, string $header) use (&$runtime, &$memory): int {
        if (stripos($header, 'X-Benchmark-Runtime:') === 0) $runtime = trim(substr($header, strlen('X-Benchmark-Runtime:')));
        if (stripos($header, 'X-Benchmark-Peak-Memory:') === 0) $memory = max($memory, (int) trim(substr($header, strlen('X-Benchmark-Peak-Memory:'))));
        return strlen($header);
    });
    $times = []; $start = hrtime(true);
    for ($i = 0; $i < $count; ++$i) {
        $body = curl_exec($handle); $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        if ($status !== 200 || $body !== '{"message":"Hello, World!"}') throw new RuntimeException('Fixture response mismatch; comparison aborted. Status '.$status);
        $times[] = curl_getinfo($handle, CURLINFO_TOTAL_TIME) * 1000;
    }
    $seconds = (hrtime(true) - $start) / 1e9; curl_close($handle); sort($times);
    return ['server_runtime' => $runtime, 'max_pre_response_php_memory_bytes' => $memory, 'requests' => $count, 'seconds' => round($seconds, 6), 'requests_per_second' => round($count / $seconds, 2), 'median_ms' => round($times[(int) floor(count($times) / 2)], 4), 'p95_ms' => round($times[max(0, (int) ceil(count($times) * .95) - 1)], 4)];
}
foreach ($urls as $url) measure($url, 30);
$results = [];
for ($round = 1; $round <= $rounds; ++$round) {
    $order = $round % 2 ? array_keys($urls) : array_reverse(array_keys($urls));
    $row = ['round' => $round];
    foreach ($order as $name) $row[$name] = measure($urls[$name], $requests);
    if ($row['elang']['server_runtime'] !== $row['laravel']['server_runtime']) throw new RuntimeException('Server PHP versions or OPcache settings differ; comparison aborted.');
    $results[] = $row;
}
$versions = ['elang' => '0.1.0-dev'];
$lock = __DIR__.'/laravel/composer.lock';
if (is_file($lock)) foreach (json_decode(file_get_contents($lock), true)['packages'] as $package) if ($package['name'] === 'laravel/framework') $versions['laravel'] = $package['version'];
$report = ['generated_at' => gmdate('c'), 'scope' => 'Single-worker PHP development server; same small stateless JSON route; concurrency 1; 30 warm-up requests per fixture. Not a production or whole-framework comparison.', 'client_php' => PHP_VERSION, 'framework_versions' => $versions, 'server_settings' => 'Start both fixture servers using the identical PHP binary and OPcache CLI settings documented in benchmarks/README.md.', 'rounds' => $results];
$json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
if (isset($options['output'])) file_put_contents($options['output'], $json);
echo $json;
