<?php
return [
    'home_assistant_url' => 'http://127.0.0.1:8123',
    'home_assistant_token' => '',
    'timezone' => 'America/New_York',
    'entities' => [
        'indoor_temperature' => 'sensor.downstairs_temperature',
        'upstairs_temperature' => 'sensor.upstairs_temperature',
        'outdoor_temperature' => 'sensor.outdoor_temperature',
        'indoor_humidity' => 'sensor.downstairs_humidity',
        'thermostat' => 'climate.downstairs',
        'hvac_action' => 'sensor.hvac_action',
        'runtime_today_hours' => 'sensor.hvac_runtime_today',
        'runtime_7d_hours' => 'sensor.hvac_runtime_7d',
        'energy_today_kwh' => 'sensor.hvac_energy_today',
    ],
    'comfort' => [
        'cooling_setpoint_f' => 72,
        'temperature_low_f' => 68,
        'temperature_high_f' => 76,
        'humidity_low_pct' => 35,
        'humidity_high_pct' => 60,
    ],
];
