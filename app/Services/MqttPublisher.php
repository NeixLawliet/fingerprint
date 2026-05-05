<?php

namespace App\Services;

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Illuminate\Support\Facades\Log;

class MqttPublisher
{
    /**
     * Publish satu pesan ke MQTT broker (Mosquitto).
     * Fire-and-forget — jika broker mati, hanya di-log, tidak throw exception.
     */
    public static function publish(string $topic, array $data, int $qos = 0): void
    {
        try {
            $host     = env('MQTT_HOST', '127.0.0.1');
            $port     = (int) env('MQTT_PORT', 1883);
            $clientId = env('MQTT_CLIENT_ID', 'pringer-laravel') . '-' . uniqid();

            $settings = (new ConnectionSettings)
                ->setConnectTimeout(3)
                ->setSocketTimeout(3)
                ->setKeepAliveInterval(10);

            $mqtt = new MqttClient($host, $port, $clientId);
            $mqtt->connect($settings);
            $mqtt->publish($topic, json_encode($data), $qos);
            $mqtt->disconnect();

        } catch (\Throwable $e) {
            Log::error('[MQTT] Publish gagal: ' . $e->getMessage(), [
                'topic' => $topic,
                'data'  => $data,
            ]);
        }
    }
}
