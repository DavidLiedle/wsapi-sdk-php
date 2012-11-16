<?php


/**
 * Weather Source API PHP SDK
 *
 * Requires PHP version 5.3.0 or greater
 *
 * @api
 * @author Jeffrey D. King
 * @copyright 2012- Weather Source, LLC
 * @version v1
 *
 */
class Weather_Source_API {

    private

        // config options
        $base_uri,
        $version,
        $key,
        $return_diagnostics,
        $suppress_response_codes,
        $log_errors,
        $error_log_directory,
        $request_retry_count,
        $request_retry_delay,

        // properties
        $root_directory,
        $status_code;


    /**
     *  Initiate our class instance
     *
     *  @param boolean $return_diagnostics       OPTIONAL  Return diagnostic information with the response?
     *  @param boolean $suppress_response_codes  OPTIONAL  Suppress error codes and always return a 200 HTTP response?
     *
     *  @return NULL
     */
    public function __construct( $return_diagnostics = FALSE, $suppress_response_codes = FALSE ) {

        $this->return_diagnostics      = $return_diagnostics;
        $this->suppress_response_codes = $suppress_response_codes;

        $this->root_directory          = (__DIR__ == '/') ? __DIR__ : __DIR__.'/';

        require_once( $this->root_directory . '/config.php' );

        $this->base_uri                = defined('WSAPI_BASE_URI') ? (string) WSAPI_BASE_URI : 'https://api.weathersource.com';
        $this->version                 = defined('WSAPI_VERSION') ? (string) WSAPI_VERSION : 'v1';
        $this->key                     = defined('WSAPI_KEY') ? (string) WSAPI_KEY : '';
        $this->log_errors              = defined('WSAPI_LOG_ERRORS') ? (boolean) WSAPI_LOG_ERRORS : FALSE;
        $this->error_log_directory     = defined('WSAPI_ERROR_LOG_DIRECTORY') ? (string) WSAPI_ERROR_LOG_DIRECTORY : '/error_logs/';
        $this->request_retry_count     = defined('WSAPI_REQUEST_RETRY_ON_ERROR_COUNT') ? (integer) WSAPI_REQUEST_RETRY_ON_ERROR_COUNT : 5;
        $this->request_retry_delay     = defined('WSAPI_REQUEST_RETRY_ON_ERROR_DELAY') ? (integer) WSAPI_REQUEST_RETRY_ON_ERROR_DELAY : 2;
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

        $uri = $this->base_uri . '/' . $this->version . '/' . $this->key . '/' . $resource_path . '.' . strtolower($format);
        curl_setopt( $ch, CURLOPT_URL, $uri );
        curl_setopt( $ch, CURLOPT_POST, count($parameters) );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query($parameters) );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );


        /*  execute post  */

        for( $i=0; $i < $this->request_retry_count; $i++ ) {

            $response = curl_exec($ch);

            $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if( !in_array($status_code, array(500,502,503,504)) ) {
                break;
            }
        }


        /*  set status code  */
        $this->set_status_code( $status_code );


        /*  close connection  */

        curl_close($ch);


        /*  write to error log if appropriate  */

        if( $status_code != 200 && $this->log_errors === TRUE ) {
            $request_uri = $uri . http_build_query($parameters);
            $this->write_to_error_log( $request_uri, $status_code, $response, $format, $jsonp_callback );
        }


        /*  return response  */

        return $response;
    }


    /**
     *  Return the HTTP Status Code for the most recent request
     *
     *  @return  integer  the HTTP Status Code for the most recent request (NULL if no previous request)
     */
    public function get_status_code() {
        return $this->status_code;
    }


    /**
     *  Set the HTTP Status Code for the most recent request
     *
     *  @param  integer  $status_code  REQUIRED  The HTTP Status Code for most recent request
     *
     *  @return NULL
     */
    protected function set_status_code( $status_code ) {
        $this->status_code = $status_code;
    }


    /**
     *  Set the HTTP Status Code for the most recent request
     *
     *  @param  integer  $status_code  REQUIRED  The HTTP Status Code for most recent request
     *
     *  @return NULL
     */
    protected function write_to_error_log( $request_uri, $status_code, $response, $format, $jsonp_callback ) {

        // get the current timestamp
        $timestamp = date('c');

        // get the http status message
        $response_arr = json_decode($response, TRUE);
        if( is_array($response_arr) && !empty( $response_arr['message'] ) ) {
            $http_status_message = $response_arr['message'];
        } else {
            $http_status_message = $this->http_status_message($status_code);
        }

        $error_message = "[{$timestamp}] [Error {$status_code} | {$http_status_message}] [{$request_uri}]\r\n";

        $error_log_directory = $this->error_log_directory;
        $error_log_directory = substr($error_log_directory, 0, 1) == '/' ? substr($error_log_directory, 1) : $error_log_directory;
        $error_log_directory = substr($error_log_directory, -1) == '/' ? $error_log_directory : $error_log_directory . '/';
        $error_log_directory = $this->root_directory . $error_log_directory;

        if( !is_dir($error_log_directory) ) {
            mkdir($error_log_directory);
        }

        $error_log_filename = $error_log_directory . 'wsapi_errors.log';

        $file_pointer = fopen($error_log_filename, 'a+');
        fwrite($file_pointer, $error_message);
        fclose($file_pointer);

    }


    /**
     *  Get the HTTP Status Message for a givin HTTP Status Code
     *
     *  @param  integer  $status_code  REQUIRED  The HTTP Status Code for most recent request
     *
     *  @return string HTTP Status Message
     */
    private function http_status_message( $status_code ) {

        if( !is_null($status_code) ) {
            switch ($code) {
                case 100: $text = 'Continue'; break;
                case 101: $text = 'Switching Protocols'; break;
                case 200: $text = 'OK'; break;
                case 201: $text = 'Created'; break;
                case 202: $text = 'Accepted'; break;
                case 203: $text = 'Non-Authoritative Information'; break;
                case 204: $text = 'No Content'; break;
                case 205: $text = 'Reset Content'; break;
                case 206: $text = 'Partial Content'; break;
                case 300: $text = 'Multiple Choices'; break;
                case 301: $text = 'Moved Permanently'; break;
                case 302: $text = 'Moved Temporarily'; break;
                case 303: $text = 'See Other'; break;
                case 304: $text = 'Not Modified'; break;
                case 305: $text = 'Use Proxy'; break;
                case 400: $text = 'Bad Request'; break;
                case 401: $text = 'Unauthorized'; break;
                case 402: $text = 'Payment Required'; break;
                case 403: $text = 'Forbidden'; break;
                case 404: $text = 'Not Found'; break;
                case 405: $text = 'Method Not Allowed'; break;
                case 406: $text = 'Not Acceptable'; break;
                case 407: $text = 'Proxy Authentication Required'; break;
                case 408: $text = 'Request Time-out'; break;
                case 409: $text = 'Conflict'; break;
                case 410: $text = 'Gone'; break;
                case 411: $text = 'Length Required'; break;
                case 412: $text = 'Precondition Failed'; break;
                case 413: $text = 'Request Entity Too Large'; break;
                case 414: $text = 'Request-URI Too Large'; break;
                case 415: $text = 'Unsupported Media Type'; break;
                case 500: $text = 'Internal Server Error'; break;
                case 501: $text = 'Not Implemented'; break;
                case 502: $text = 'Bad Gateway'; break;
                case 503: $text = 'Service Unavailable'; break;
                case 504: $text = 'Gateway Time-out'; break;
                case 505: $text = 'HTTP Version not supported'; break;
                default:  $text = 'Unknown status'; break;
            }
        } else {
            $text = 'Unknown status';
        }

        return $text;
    }

}























?>