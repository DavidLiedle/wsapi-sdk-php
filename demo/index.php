<?php

/**
 *
 * @author Jeffrey D. King
 * @copyright 2012- Weather Source, LLC
 * @since v1
 *
 */


/*  initiate our API class instance  */

require_once(__DIR__.'/../sdk/weather_source_api.sdk.php');
$api = new Weather_Source_API( $base_url, $version, $key );


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

$json_response = $api->request( $request_method, $request_path, $request_parameters );

print_r( $json_response );

/*  do something with the response  */

print_r( json_decode($json_response, TRUE) );

?>