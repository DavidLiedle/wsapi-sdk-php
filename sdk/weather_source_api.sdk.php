<?php


/**
 * Weather Source API PHP SDK
 *
 * Requires PHP version 5.3.0 or greater
 *
 * @api
 * @author Jeffrey D. King
 * @copyright 2012- Weather Source, LLC
 * @version 1
 *
 */
class Weather_Source_API {

    private
        $base_url,
        $version,
        $key,
        $return_diagnostics,
        $suppress_response_codes,
        $root_directory,
        $log_errors,
        $error_log_directory,
        $request_retry_count;


    /**
     *  Initiate our class instance
     *
     *  @param string  $base_url                 REQUIRED  The API URL, i.e. 'https://api.weathersource.com'
     *  @param string  $version                  REQUIRED  The API version, i.e. 'v1'
     *  @param string  $key                      REQUIRED  The API subscription key
     *  @param boolean $return_diagnostics       OPTIONAL  Return diagnostic information with the response?
     *  @param boolean $suppress_response_codes  OPTIONAL  Suppress error codes and always return a 200 HTTP response?
     *
     *  @return none
     */
    public function __construct($base_url, $version, $key, $return_diagnostics = FALSE, $suppress_response_codes = FALSE ) {

        $this->base_url                = $base_url;
        $this->version                 = $version;
        $this->key                     = $key;
        $this->return_diagnostics      = $return_diagnostics;
        $this->suppress_response_codes = $suppress_response_codes;

        $this->root_directory          = (__DIR__ == '/') ? __DIR__ : __DIR__.'/';

        require_once( $this->root_directory . '/config.php' );

        $this->log_errors              = defined('WSAPI_LOG_ERRORS') ? (boolean) WSAPI_LOG_ERRORS : FALSE;
        $this->error_log_directory     = defined('WSAPI_ERROR_LOG_DIRECTORY') ? (string) WSAPI_ERROR_LOG_DIRECTORY : "/error_logs/";
        $this->request_retry_count     = defined('WSAPI_REQUEST_RETRY_ON_ERROR_COUNT') ? (integer) WSAPI_REQUEST_RETRY_ON_ERROR_COUNT : 5;
    }


    /**
     *  Sends request to the Weather Source API
     *
     *  @param  string  $method          REQUIRED  The HTTP method for the request (allowed: 'GET', 'POST', 'PUT', 'DELETE')
     *  @param  string  $resource_path   REQUIRED  The resource path for the request (i.e. 'history_by_postal_code')
     *  @param  array   $parameters      REQUIRED  The resource parameters
     *  @param  string  $format          OPTIONAL  Defaults to 'JSON' (allowed: 'JSON')
     *  @param  string  $jsonp_callback  OPTIONAL  A JSONP callback function. Defaults to NULL.
     *
     *  @return string  The API response
     */
    public function request( $method, $resource_path, $parameters, $format = 'JSON', $jsonp_callback = NULL ) {


        /*  append meta parameters  */

        $parameters['_method'] = strtolower($method);

        if( $this->return_diagnostics ) {
            $parameters['_diagnostics'] = '1';
        }

        if( $this->suppress_response_codes ) {
            $parameters['_suppress_response_codes'] = '1';
        }

        if( $jsonp_callback ) {
            $parameters['_callback'] = $jsonp_callback;
        }


        /*  open connection  */

        $ch = curl_init();


        /*  set the url, number of POST vars, POST data  */

        $url = $this->base_url . '/' . $this->version . '/' . $this->key . '/' . $resource_path . '.' . strtolower($format);
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_POST, count($parameters) );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query($parameters) );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );

        /*  execute post  */

        $response = curl_exec($ch);


        /*  close connection  */

        curl_close($ch);


        /*  return response  */

        return $response;
    }
}
?>