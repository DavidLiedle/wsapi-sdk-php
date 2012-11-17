<?php

/**
 *
 * @author Jeffrey D. King
 * @copyright 2012- Weather Source, LLC
 * @since Version 1.2
 *
 */


/*  initiate our API class instance  */

require_once( __DIR__ . '/../sdk/weather_source_api.php' );

$api = new Weather_Source_API( $return_diagnostics = FALSE, $suppress_response_codes = FALSE );


/*  set request variables  */

$request_method     = 'GET';
$request_path       = 'history_by_postal_code';
$request_parameters = array(
                          'period'            => 'day',
                          'postal_code_eq'    => '22222',
                          'country_eq'        => 'US',
                          'timestamp_between' => '2011-01-01,2011-01-05',
                          'fields'            => 'tempMax',
                      );


/*  make API request  */

$response = $api->request( $request_method, $request_path, $request_parameters );


/*  do something with the response  */

if( !$api->is_ok() ) {
	echo "<p>";
	echo "<strong>ERROR " . $api->get_response_code() . "</strong><br />";
	echo $api->get_error_message();
	echo "</p>";
}

echo "<pre>\$response = ";
print_r( $response );
echo "</pre>";

?>