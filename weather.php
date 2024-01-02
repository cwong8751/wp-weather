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

// define plugin settings page for user to change lat and lon values 
function init_weather_settings(){
	// register settings with wordpress 
	add_option('latitude', '38.627');
	add_option('longitude', '-90.199');
	
	register_setting('weather_settings', 'latitude', 'floatval');
	register_setting('weather_settings', 'longitude', 'floatval');
}

// register the hook 
add_action('admin_init', 'init_weather_settings');

// function adds settings page to wordpress
function add_settings_page(){
	// add the settings page itself
	add_options_page('Weather Settings', 'Weather', 'manage_options', 'weather_settings', 'weather_settings_page');
}

// register the hook
add_action('admin_menu', 'add_settings_page');

// function displays settings page (what it looks like)
function weather_settings_page() {
    ?>
    <div class="wrap">
        <h2>Weather Settings</h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php
            settings_fields('weather_settings');
            do_settings_sections('weather_settings_group');
            ?>
            <input type="hidden" name="action" value="save_weather_options">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Latitude</th>
                    <td><input type="text" name="latitude" value="<?php echo esc_attr(get_option('latitude')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Longitude</th>
                    <td><input type="text" name="longitude" value="<?php echo esc_attr(get_option('longitude')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button('Save Changes'); ?>
        </form>
    </div>
    <?php
}

add_action('admin_post_save_weather_options', 'save_weather_options');
function save_weather_options() {
    if (isset($_POST['latitude'])) {
        update_option('latitude', floatval($_POST['latitude']));
    }

    if (isset($_POST['longitude'])) {
        update_option('longitude', floatval($_POST['longitude']));
    }

    wp_redirect(admin_url('options-general.php?page=weather_settings&updated=true'));
    exit;
}

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

// Helper function read api key from file
function get_api_key(){
	$file_name = 'secret.txt';
	
	// check file existence
	if(file_exists($file_name)){
		$key = file_get_contents($file_name);
		echo $key;
		return $key;
	}
	
	echo "Failed to get api key from file";
	return false;
}

// Helper function to get location based on longitude and latitude 
function decode_coordinates($lat, $lon){
	// query api key first
	// $key = get_api_key();
	$key = '65937c3eb0226029015167tqe32f5bd';
	
	if($key != false){
		// make api call to reverse geocode api 
		$url = 'https://geocode.maps.co/reverse?lat=' . $lat . '&lon=' . $lon . '&api_key=' . $key;
		
	    $options = [
	        'http' => [
	            'method' => 'GET',
	            'header' => 'Content-type: application/x-www-form-urlencoded',
	        ],
	    ];

	    $context = stream_context_create($options);
	    $response = file_get_contents($url, false, $context);
		
		// decode to get city and country 
		$data = json_decode($response, true);
		
		if($data != null){
			// get city and country 
			$city = $data['address']['city'];
			$country = $data['address']['country'];
			
			return array($city, $country);
		}
		
		echo "Failed to get reverse geocode info";
		return false;
	}
	
	return false;
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

	// decode weather 
    $temp = decode_weather($response);
	
	// get the city and country
	$geocode = decode_coordinates($lat, $lon);
	$city = $geocode[0];
	$country = $geocode[1];
	
	if($geocode !== false && $temp !== false){
		echo "It's " . $temp . " F at " . $city . "," . $country;
	}
	else{
		echo 'Something went wrong';
	}
}

// Add callback as action, run on wp_loaded
add_action('wp_loaded', function () {
    $lat = floatval(get_option('latitude', '38.627')); // gets values from settings page instead of predefined ones 
    $lon = floatval(get_option('longitude', '-90.199')); // gets values from settings page instead of predefined ones 

    weather_callback($lat, $lon);
});
?>
