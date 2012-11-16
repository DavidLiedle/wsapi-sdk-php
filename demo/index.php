<?php

/**
 *
 * @author Jeffrey D. King
 * @copyright 2012- Weather Source, LLC
 * @since Version 1
 *
 */

/*  set API connection variables  */

$base_url = 'https://api.weathersource.dev';
$version  = 'v1';
$key      = '123450';  // add your API subscription key here


/*  initiate our API class instance  */

require_once(__DIR__.'/../sdk/weather_source_api.sdk.php');
$api = new Weather_Source_API( $base_url, $version, $key );


/*  set request variables  */

$request_method     = 'GET';
$request_path       = 'history';
$request_parameters = array(
                          'period'            => 'day',
                          'latitude_eq'       => '42.8706',
                          'longitude_eq'      => '-70.9168',
                          'timestamp_between' => '2011-01-01,2011-01-05',
                          'fields'            => 'tempMax',
                      );


/*  make API request  */

$json_response = $api->request( $request_method, $request_path, $request_parameters );

print_r( $json_response );

/*  do something with the response  */

print_r( json_decode($json_response, TRUE) );

?>