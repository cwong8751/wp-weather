<?php
/*
Plugin Name: Weather
Version: 1.0
Description: Simple weather plugin for WordPress
Author: Carl 
Author URI: test
License: test
License URI: test
Text Domain: weather
*/

// Security: die if called directly
if (!defined('ABSPATH')) { die; }

// Define variables 
$url = "https://api.open-meteo.com/v1/forecast?latitude=";
$url_param = "&current=temperature_2m&temperature_unit=fahrenheit&wind_speed_unit=mph&precipitation_unit=inch&forecast_days=1";

// Helper function to read weather from API 
function decode_weather($response) {
    $data = json_decode($response, true);

    if ($data != null) {
        $temp = $data['current']['temperature_2m'];
        return $temp;
    } else {
        return false;
    }
}

// Define callback function 
function weather_callback($lat, $lon) {
    global $url, $url_param; // Make sure to use the global variables

    $request_url = $url . $lat . "&longitude=" . $lon . $url_param;

    $options = [
        'http' => [
            'method' => 'GET',
            'header' => 'Content-type: application/x-www-form-urlencoded',
        ],
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($request_url, false, $context);

    $temp = decode_weather($response);

    if ($temp !== false) { // Corrected the condition
        echo "It's " . $temp . "F in St. Louis, MO";
    } else {
        echo "Plugin error";
    }
}

// Add callback as action, run on wp_loaded
add_action('wp_loaded', function () {
    $lat = 38.627;
    $lon = -90.199;

    weather_callback($lat, $lon);
});
?>
