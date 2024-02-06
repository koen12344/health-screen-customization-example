<?php
/**
 * Plugin Name:     Site Health Screen Customization Example
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     Example plugin to show how you can add custom tests & sections to the Site Health screen
 * Author:          Koen Reus
 * Author URI:      https://koenreus.com
 * Text Domain:     health-screen-customization-example
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Health_Screen_Customization_Example
 */

/*
 * Adding a direct test that will be executed immediately when opening the Site Health page
 */

/**
 * Register the test with WordPress
 *
 * @param $tests
 *
 * @return array
 */
function hsce_add_mbstring_test($tests): array {
	return array_merge_recursive($tests, [
		'direct' => [
			'hsce_custom_test' => [
				'label' => __('My custom plugin direct test', 'health-screen-customization-example'),
				'test' => 'hsce_do_test',
				'skip_cron' => false, //Do not run this test on cron
			]
		]
	]);
}
add_filter('site_status_tests', 'hsce_add_mbstring_test');

/**
 * Callback function for the direct test, as referenced by the 'test' parameter in hsce_add_mbstring_test() above
 *
 * @return array
 */
function hsce_do_test(): array {
	$test_successful = true; //Obviously you'll need to add some logic to your test here
	$status = $test_successful ? 'good' : 'recommended'; //Also available: critical
	$label = $test_successful ? __('Direct test success', 'health-screen-customization-example') : __('Direct test failed', 'health-screen-customization-example');
	$description = sprintf('<p>%s</p>', $test_successful ?
		__('Description when test successful', 'health-screen-customization-example') :
		__('Description when test failed', 'health-screen-customization-example')
	);

	return [
		'label' => $label,
		'status' => $status,
		'badge' => [
			'label' => __('My Custom Direct test label', 'health-screen-customization-example'),
			'color' => 'green', //Choice of blue, green, red, orange, purple or gray
		],
		'description' => $description,
		'actions' => sprintf('<a href="#">%s</a>', __('Check the plugin log')), //Link or button (HTML) where the user can find additional info
		'test' => 'hsce_custom_test'
	];
}


// ---

/*
 * Register a long-running async test to be executed by the REST API
 */

/**
 * Register the REST endpoint for our test
 *
 * @return void
 */
function hsce_register_rest_route(){
	register_rest_route('/hsce/v1/', 'rest_test',
		[
			'methods' => WP_REST_Server::READABLE,
			'callback' => 'hsce_do_async_rest_test',
			'permission_callback' => function() { return current_user_can('manage_options'); }
		]
	);
}
add_filter('rest_api_init', 'hsce_register_rest_route');


/**
 * Register our REST-based test with WordPress
 *
 * @param $tests
 *
 * @return array
 */
function hsce_add_async_rest_test($tests): array {
	return array_merge_recursive($tests, [
		'async' => [
			'hsce_custom_async_rest_test' => [
				'label' => __('My custom rest async test', 'health-screen-customization-example'),
				'test' => esc_url(get_rest_url(null, '/hsce/v1/rest_test')),
				'skip_cron' => false, //When this is set to false, wp-cron will run this test weekly in the background
				'has_rest' => true,
				'async_direct_test' => 'hsce_rest_long_running_test', //Direct call to the test function for when skip_cron=false
			]
		]
	]);
}
add_filter('site_status_tests', 'hsce_add_async_rest_test');

/**
 * An example test that will take two seconds to complete
 *
 * @return array
 */
function hsce_rest_long_running_test(): array {
	$long_test = function(){ sleep(2); return true; };
	$result = $long_test();

	$status = $result ? 'good' : 'recommended';
	$label = $result ? __('Async REST test success', 'health-screen-customization-example') : __('Async REST test fail', 'health-screen-customization-example');
	$description = sprintf('<p>%s</p>', $result ?
		__('Description when test successful', 'health-screen-customization-example') :
		__('Description when test failed', 'health-screen-customization-example')
	);

	return [
		'label' => $label,
		'status' => $status,
		'badge' => [
			'label' => __('My Custom REST test label', 'health-screen-customization-example'),
			'color' => 'orange',
		],
		'description' => $description,
		'actions' => '',
		'test' => 'hsce_custom_async_rest_test'
	];
}

/**
 * Handler for our REST endpoint
 *
 * @param WP_REST_Request $request
 *
 * @return WP_REST_Response
 */
function hsce_do_async_rest_test(WP_REST_Request $request): WP_REST_Response {
	//WordPress takes care of validating our nonce (X-WP-Nonce header), so we don't have to validate it here
	return new WP_REST_Response(hsce_rest_long_running_test());
}


// ---

/*
 * Register a long-running async test to be handled by the wp_ajax_{action} hook
 */

/**
 * Register the wp_ajax async test with WordPress
 *
 * @param $tests
 *
 * @return array
 */
function hsce_add_async_test($tests){
	return array_merge_recursive($tests, [
		'async' => [
			'hsce_custom_async_test' => [
				'label' => __('My custom async wp_ajax_ test', 'health-screen-customization-example'),
				'test' => 'hsce-async-test', //Note, use dashes (-) here instead of underscores (_) or it will break
				'skip_cron' => false, //Whether the test should NOT be performed by wp-cron in the background regularly (weekly by default)
				'has_rest' => false,
				'async_direct_test' => 'hsce_do_async_test', //callback to perform the test directly (used by wp-cron)
			]
		]
	]);
}
add_filter('site_status_tests', 'hsce_add_async_test');

/**
 * Perform the test.
 *
 * @return array
 */
function hsce_do_async_test(): array {
	$long_test = function(){ sleep(2); return true; };
	$test_successful = $long_test(); //Your long-running test logic should be here

	$status = $test_successful ? 'good' : 'recommended'; // or 'critical' if things have gone real bad
	$label = $test_successful ? __('Async wp_ajax_ test success', 'health-screen-customization-example') : __('Async wp_ajax_ test fail', 'health-screen-customization-example');
	$description = sprintf('<p>%s</p>', $test_successful ?
		__('Description when test successful', 'health-screen-customization-example') :
		__('Description when test failed', 'health-screen-customization-example')
	);

	return [
		'label' => $label,
		'status' => $status,
		'badge' => [
			'label' => __('My Custom Async label', 'health-screen-customization-example'),
			'color' => 'purple',
		],
		'description' => $description,
		'actions' => '',
		'test' => 'hsce_custom_async_test'
	];
}

/**
 * Respond to the wp_ajax_{$action} request
 *
 * @return void
 */
function hsce_ajax_async_test(){
	//Check the nonce, the request parameter and action are automatically generated by WP
	if(!wp_verify_nonce($_REQUEST['_wpnonce'], 'health-check-site-status')){
		wp_send_json_error();
	}
	wp_send_json_success(hsce_do_async_test());
}

add_action('wp_ajax_health-check-hsce-async-test', 'hsce_ajax_async_test');

// ---

/*
 * Adding a custom section to the "Site Health Info" tab
 */

/**
 * Register the section with WordPress
 *
 * @param $debug_info
 *
 * @return mixed
 */
function hsce_add_debug_info( $debug_info ) {
	$debug_info['my-plugin-slug'] = [
		'label'    => __( 'My Plugin Custom Info Section', 'health-screen-customization-example'),
		'fields'   => [
			'mysetting' => [
				'label'    => __( 'My custom setting', 'health-screen-customization-example'),
				'value'   => get_option( 'my-plugin-setting', __( 'Setting not found', 'health-screen-customization-example') ),
				'private' => false, //When this is set to true, the value won't be copied when the user clicks "Copy site info to clipboard"
			],
		],
	];

	return $debug_info;
}
add_filter( 'debug_information', 'hsce_add_debug_info' );
