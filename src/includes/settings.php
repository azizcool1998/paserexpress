<?php

/**
 * SETTINGS SYSTEM (FINAL)
 * Penyimpanan konfigurasi admin berbasis file JSON.
 * Disimpan di: /config/settings.json
 */

function settings_path() {
    return dirname(__DIR__) . "/config/settings.json";
}

function load_settings() {
    $file = settings_path();

    if (!file_exists($file)) {
        return [
            "auto_backup_enabled" => false,
            "auto_backup_interval" => "none",

            "theme" => "auto", // auto | dark | light

            "maintenance_mode" => false,
            "maintenance_message" => "PaserExpress sedang dalam perbaikan. Harap kembali lagi nanti."
        ];
    }

    $json = file_get_contents($file);
    $data = json_decode($json, true);

    if (!is_array($data)) return [];

    return $data;
}

function save_settings($arr) {
    $file = settings_path();
    file_put_contents($file, json_encode($arr, JSON_PRETTY_PRINT));
    return true;
}
