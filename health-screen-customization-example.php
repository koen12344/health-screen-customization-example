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

function hsce_add_mbstring_test($tests){
	return array_merge_recursive($tests, [
		'direct' => [
			'hsce_custom_test' => [
				'label' => __('My custom plugin test', 'health-screen-customization-example'),
				'test' => 'hsce_do_test',
				'skip_cron' => false, //Do not run this test on cron
			]
		]
	]);
}
add_filter('site_status_tests', 'hsce_add_mbstring_test');


function hsce_do_test() {
	$mbstring_enabled = extension_loaded('mbstring') && function_exists('mb_strimwidth');
	$status = $mbstring_enabled ? 'good' : 'recommended'; //Also available: critical
	$label = $mbstring_enabled ? __('mbstring is enabled', 'health-screen-customization-example') : __('mbstring is not enabled', 'health-screen-customization-example');
	$description = sprintf('<p>%s</p>', $mbstring_enabled ?
		__('Description when test successful', 'health-screen-customization-example') :
		__('Description when test failed', 'health-screen-customization-example')
	);

	return [
		'label' => $label,
		'status' => $status,
		'badge' => [
			'label' => __('My Custom label', 'health-screen-customization-example'),
			'color' => 'blue', //Choice of blue, green, red, orange, purple or gray
		],
		'description' => $description,
		'actions' => '', //Link or button (HTML) where the user can find additional info
		'test' => 'hsce_custom_test'
	];
}


function hsce_add_async_test($tests){
	return array_merge_recursive($tests, [
		'async' => [
			'hsce_custom_async_test' => [
				'label' => __('My custom async test', 'health-screen-customization-example'),
				'test' => 'hsce-async-test',
				'skip_cron' => false,
				'has_rest' => false,
				'async_direct_test' => 'do_hsce_async_test',
			]
		]
	]);
}
add_filter('site_status_tests', 'hsce_add_async_test');


function do_hsce_async_test(){
	if (defined('DOING_AJAX') && DOING_AJAX &&
		!wp_verify_nonce( $_REQUEST['_wpnonce'], 'health-check-site-status' )
	) {
		wp_send_json_error();
	}

	$long_test = function(){ sleep(2); return true; };
	$result = $long_test();

	$status = $result ? 'good' : 'recommended';
	$label = $result ? __('Async test success', 'health-screen-customization-example') : __('Async test fail', 'health-screen-customization-example');
	$description = sprintf('<p>%s</p>', $result ?
		__('Description when test successful', 'health-screen-customization-example') :
		__('Description when test failed', 'health-screen-customization-example')
	);

	$test = [
		'label' => $label,
		'status' => $status,
		'badge' => [
			'label' => __('My Custom label', 'health-screen-customization-example'),
			'color' => 'blue',
		],
		'description' => $description,
		'actions' => '',
		'test' => 'hsce_custom_async_test'
	];

	if (defined('DOING_AJAX') && DOING_AJAX) {
		wp_send_json_success($test);
	}

	return $test;
}
add_action('wp_ajax_health-check-hsce-async-test', 'do_hsce_async_test');


function hsce_add_debug_info( $debug_info ) {
	$debug_info['my-plugin-slug'] = [
		'label'    => __( 'My Plugin Custom Info Section', 'health-screen-customization-example'),
		'fields'   => [
			'mysetting' => [
				'label'    => __( 'My custom setting', 'health-screen-customization-example'),
				'value'   => get_option( 'my-plugin-setting', __( 'Setting not found', 'health-screen-customization-example') ),
				'private' => false,
			],
		],
	];

	return $debug_info;
}
add_filter( 'debug_information', 'hsce_add_debug_info' );
