<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$configFile = '/etc/climateiq/config.php';
$config = is_file($configFile) ? require $configFile : require dirname(__DIR__, 2) . '/config.example.php';
date_default_timezone_set($config['timezone'] ?? 'America/New_York');

function haState(string $base, string $token, string $entity): ?array {
    if ($base === '' || $token === '' || $entity === '') return null;
    $url = rtrim($base, '/') . '/api/states/' . rawurlencode($entity);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 6,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($body === false || $code !== 200) return null;
    $decoded = json_decode($body, true);
    return is_array($decoded) ? $decoded : null;
}

function numericState(?array $state): ?float {
    if (!$state) return null;
    $value = $state['state'] ?? null;
    return is_numeric($value) ? round((float)$value, 1) : null;
}

$base = (string)($config['home_assistant_url'] ?? '');
$token = (string)($config['home_assistant_token'] ?? '');
$entities = $config['entities'] ?? [];
$states = [];
foreach ($entities as $key => $entity) {
    $states[$key] = haState($base, $token, (string)$entity);
}

$thermostat = $states['thermostat'] ?? null;
$thermostatAttrs = $thermostat['attributes'] ?? [];
$indoor = numericState($states['indoor_temperature'] ?? null);
$upstairs = numericState($states['upstairs_temperature'] ?? null);
$outdoor = numericState($states['outdoor_temperature'] ?? null);
$humidity = numericState($states['indoor_humidity'] ?? null);
$setpoint = isset($thermostatAttrs['temperature']) && is_numeric($thermostatAttrs['temperature'])
    ? round((float)$thermostatAttrs['temperature'], 1)
    : (float)($config['comfort']['cooling_setpoint_f'] ?? 72);
$action = strtolower((string)(
    ($states['hvac_action']['state'] ?? null)
    ?? ($thermostatAttrs['hvac_action'] ?? null)
    ?? ($thermostat['state'] ?? 'unknown')
));

$runtimeToday = numericState($states['runtime_today_hours'] ?? null);
$runtime7d = numericState($states['runtime_7d_hours'] ?? null);
$energyToday = numericState($states['energy_today_kwh'] ?? null);
$live = count(array_filter($states)) > 0;

if (!$live) {
    $hour = (int)date('G');
    $indoor = 73.2;
    $upstairs = 75.1;
    $outdoor = 88.0;
    $humidity = 48.0;
    $setpoint = 72.0;
    $action = $hour >= 12 && $hour <= 21 ? 'cooling' : 'idle';
    $runtimeToday = 6.4;
    $runtime7d = 43.8;
    $energyToday = 18.7;
}

$tempGap = ($indoor !== null && $setpoint !== null) ? round($indoor - $setpoint, 1) : null;
$floorGap = ($upstairs !== null && $indoor !== null) ? round($upstairs - $indoor, 1) : null;
$comfortScore = 100;
if ($tempGap !== null) $comfortScore -= min(45, abs($tempGap) * 12);
if ($humidity !== null) {
    if ($humidity < 35) $comfortScore -= min(25, (35 - $humidity) * 2);
    if ($humidity > 60) $comfortScore -= min(35, ($humidity - 60) * 2);
}
$comfortScore = max(0, (int)round($comfortScore));

$story = 'Your home climate is stable.';
if ($action === 'cooling' && $tempGap > 1.5) {
    $story = 'The system is actively cooling, but the indoor temperature remains above the target.';
} elseif ($action === 'cooling') {
    $story = 'The system is cooling and the home is close to the selected temperature.';
} elseif ($tempGap !== null && $tempGap > 2) {
    $story = 'The indoor temperature is drifting above the target while the system is not actively cooling.';
} elseif ($humidity !== null && $humidity > 60) {
    $story = 'Temperature is acceptable, but indoor humidity is reducing comfort.';
}

echo json_encode([
    'ok' => true,
    'live' => $live,
    'generated_at' => date(DATE_ATOM),
    'system' => [
        'action' => $action,
        'mode' => (string)($thermostat['state'] ?? ($live ? 'unknown' : 'cool')),
        'setpoint_f' => $setpoint,
    ],
    'temperature' => [
        'indoor_f' => $indoor,
        'upstairs_f' => $upstairs,
        'outdoor_f' => $outdoor,
        'target_gap_f' => $tempGap,
        'floor_gap_f' => $floorGap,
    ],
    'humidity' => ['indoor_pct' => $humidity],
    'runtime' => [
        'today_hours' => $runtimeToday,
        'seven_day_hours' => $runtime7d,
        'daily_average_hours' => $runtime7d !== null ? round($runtime7d / 7, 1) : null,
    ],
    'energy' => ['today_kwh' => $energyToday],
    'comfort' => ['score' => $comfortScore],
    'story' => $story,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
