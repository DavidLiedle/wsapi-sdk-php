<?php

/**
 *  Weather Source API PHP SDK
 *
 *  Requires PHP version 5.3.0 or greater
 *
 *  @author     Jeffrey D. King
 *  @copyright  2012– Weather Source, LLC
 *  @version    3.0
 *  @todo       Add logic to ramp up large jobs to allow load balancers to scale
 */



/**
 *  Class to manage requests to and responses from the Weather Source API
 */
class Weather_Source_API_Requests {


    /**
     *  @access  private
     *  @static
     *  @var     string  The base API URI.
     */
    static private $base_uri;


    /**
     *  @access  private
     *  @static
     *  @var     array  The API version.
     */
    static private $version;


    /**
     *  @access  private
     *  @static
     *  @var     array  The API key
     */
    static private $key;


    /**
     *  @access  private
     *  @static
     *  @var     boolean  Return diagnostic information with response?
     */
    static private $return_diagnostics;


    /**
     *  @access  private
     *  @static
     *  @var     boolean  Suppress all HTTP response codes (i.e. force a 200 response)?
     */
    static private $suppress_response_codes;


    /**
     *  @access  private
     *  @static
     *  @var     string  Defines the unit type to return for relevant observations. Allowed
     *                   values are 'imperial' and 'metric'.
     */
    static private $distance_unit;


    /**
     *  @access  private
     *  @static
     *  @var     string  Defines the unit type to return for relevant observations. Allowed
     *                   values are 'fahrenheit' and 'celsius'.
     */
    static private $temperature_unit;


    /**
     *  @access  private
     *  @static
     *  @var     boolean  Should all errors be written to a log file?
     */
    static private $log_errors;


    /**
     *  @access  private
     *  @static
     *  @var     string  Log file directory location.
     */
    static private $error_log_directory;


    /**
     *  @access  private
     *  @static
     *  @var     array  All API field names that return results as inches.
     */
    static private $inch_fields = array( 'precip', 'precipMax', 'precipAvg', 'precipMin',
                                         'snowfall', 'snowfallMax', 'snowfallAvg', 'snowfallMin' );


    /**
     *  @access  private
     *  @static
     *  @var     array  All API field names that return results as miles per hour.
     */
    static private $mph_fields = array( 'windSpd', 'windSpdMax', 'windSpdAvg', 'windSpdMin',
                                        'prevailWindSpd' );


    /**
     *  @access  private
     *  @static
     *  @var     array  All API field names that return results as degrees fahrenheit.
     */
    static private $fahrenheit_fields = array( 'temp', 'tempMax', 'tempAvg', 'tempMin', 'dewPt',
                                               'dewPtMax', 'dewPtAvg', 'dewPtMin', 'feelsLike',
                                               'feelsLikeMax', 'feelsLikeAvg', 'feelsLikeMin',
                                               'wetBulb', 'wetBulbMax', 'wetBulbAvg', 'wetBulbMin' );


    /**
     *  @access  private
     *  @static
     *  @var     array  A key/value inverse of self::$inch_fields for fast lookups.
     */
    static private $inch_keys;


    /**
     *  @access  private
     *  @static
     *  @var     array  A key/value inverse of self::$mph_fields for fast lookups.
     */
    static private $mph_keys;


    /**
     *  @access  private
     *  @static
     *  @var     array  A key/value inverse of self::$fahrenheit_fields for fast lookups.
     */
    static private $fahrenheit_keys;


    /**
     *  @access  private
     *  @static
     *  @var     array  The unique identifier for this instances Curl_Node.
     */
    private $curl_node;


    /**
     *  Initiate a class instance and add Weather Source API request to multithreaded
     *  cURL handler
     *
     *  @access  public
     *  @param   string    $method         [REQUIRED]  The HTTP method for the request
     *                                                 (allowed: 'GET', 'POST', 'PUT',
     *                                                 'DELETE')
     *  @param   string    $resource_path  [REQUIRED]  The resource path for the request (i.e.
     *                                                 'history_by_postal_code')
     *  @param   array     $parameters     [REQUIRED]  The resource parameters
     *  @param   callable  $callback       [OPTIONAL]  If provided, the user defined callback
     *                                                 function called as this individual
     *                                                 request completes. You don't need to
     *                                                 wait for everything to finish!
     *  @param   boolean   $debug          [OPTIONAL]  Log debug info. Defaults to FALSE.
     *  @return  NULL
    **/
    public function __construct( $method, $resource_path, $parameters, $callback = '', $debug = FALSE ) {

        if( empty(self::$base_uri) ) {

            require_once( __DIR__ . '/config.php' );

            self::$base_uri                = defined('WSAPI_BASE_URI') ? (string) WSAPI_BASE_URI : 'https://api.weathersource.com';
            self::$version                 = defined('WSAPI_VERSION') ? (string) WSAPI_VERSION : 'v1';
            self::$key                     = defined('WSAPI_KEY') ? (string) WSAPI_KEY : '';
            self::$return_diagnostics      = defined('WSAPI_RETURN_DIAGNOSTICS') ? (boolean) WSAPI_RETURN_DIAGNOSTICS : FALSE;
            self::$suppress_response_codes = defined('WSAPI_SUPPRESS_RESPONSE_CODES') ? (boolean) WSAPI_SUPPRESS_RESPONSE_CODES : FALSE;
            self::$distance_unit           = defined('WSSDK_DISTANCE_UNIT') ? (boolean) WSSDK_DISTANCE_UNIT : 'imperial';
            self::$temperature_unit        = defined('WSSDK_TEMPERATURE_UNIT') ? (boolean) WSSDK_TEMPERATURE_UNIT : 'fahrenheit';
            self::$log_errors              = defined('WSSDK_LOG_ERRORS') ? (boolean) WSSDK_LOG_ERRORS : FALSE;
            self::$error_log_directory     = defined('WSSDK_ERROR_LOG_DIRECTORY') ? (string) WSSDK_ERROR_LOG_DIRECTORY : 'error_logs/';
            self::$inch_keys               = array_flip(self::$inch_fields);
            self::$mph_keys                = array_flip(self::$mph_fields);
            self::$fahrenheit_keys         = array_flip(self::$fahrenheit_fields);
            $max_threads                   = defined('WSSDK_MAX_THREADS') ? (integer) WSSDK_MAX_THREADS : 10;
            $max_requests_per_minute       = defined('WSSDK_MAX_REQUESTS_PER_MINUTE') ? (integer) WSSDK_MAX_REQUESTS_PER_MINUTE : 10;
            $request_retry_count           = defined('WSSDK_REQUEST_RETRY_ON_ERROR_COUNT') ? (integer) WSSDK_REQUEST_RETRY_ON_ERROR_COUNT : 5;
            $request_retry_delay           = defined('WSSDK_REQUEST_RETRY_ON_ERROR_DELAY') ? (integer) WSSDK_REQUEST_RETRY_ON_ERROR_DELAY : 2;
            $scaling_initial_requests_per_minute = defined('WSSDK_SCALING_INITIAL_REQUESTS_PER_MINUTE') ? (integer) WSSDK_SCALING_INITIAL_REQUESTS_PER_MINUTE : 1000;
            $scaling_double_capacity_minutes     = defined('WSSDK_SCALING_DOUBLE_CAPACITY_MINUTES') ? (integer) WSSDK_SCALING_DOUBLE_CAPACITY_MINUTES : 7;

            Curl_Node::set_max_requests_per_minute($max_requests_per_minute);
            Curl_Node::set_max_threads($max_threads);
            Curl_Node::set_max_retries($request_retry_count);
            Curl_Node::set_scaling_initial_requests_per_minute($scaling_initial_requests_per_minute);
            Curl_Node::set_scaling_double_capacity_minutes($scaling_double_capacity_minutes);
            Curl_Node::set_debug($debug);
        }


        /*  assemble our request URL  */

        $url = self::$base_uri . '/' . self::$version . '/' . self::$key . '/' . $resource_path . '.json';


        /*  append meta parameters  */

        $parameters['_method'] = strtolower($method);
        if( self::$return_diagnostics ) {
            $parameters['_diagnostics'] = '1';
        }
        if( self::$suppress_response_codes ) {
            $parameters['_suppress_response_codes'] = '1';
        }


        /*  form cURL opts  */

        $opts = array(
            CURLOPT_URL                  => self::$base_uri,
            CURLOPT_POST                 => count($parameters),
            CURLOPT_POSTFIELDS           => http_build_query($parameters, '', '&'),
            CURLOPT_RETURNTRANSFER       => TRUE,
            CURLOPT_HEADER               => FALSE,
            CURLOPT_TIMEOUT              => 60,
            CURLOPT_CONNECTTIMEOUT       => 5,
            CURLOPT_DNS_CACHE_TIMEOUT    => 15,
            CURLOPT_DNS_USE_GLOBAL_CACHE => FALSE,
            CURLOPT_HTTPHEADER           => array('Expect:'), // prevent HTTP 100:Continue responses
        );

        $this->curl_node = new Curl_Node($url, $opts, array( 'Weather_Source_API_Requests', 'process_result' ), array('callback' => $callback ) );
    }


    /**
     *  Wait for all outstanding nodes to complete
     *
     *  @access  public
     *  @static
     *  @return  NULL
     */
    static public function finish() {

        Curl_Node::finish();
    }


    /**
     *  Get the status of a node associated with the cURL handle $handle
     *
     *  @access  public
     *  @return  string  Possible values: "queued", "processing", "complete", "unknown"
    **/
    public function get_status() {

        return $this->curl_node->get_status();
    }


    /**
     *  Get all results from all completed requests
     *
     *  @access  public
     *  @return  If request has completed, returns an associative array containing these keys:
     *           'response' (string), 'http_code' (string), 'latency' (float), 'url' (string),
     *           'opts' (array). If request has not completed, returns FALSE
    **/
    public function get_result() {

        $result = $this->curl_node->get_result();
        return $result['response'];
    }


    /**
     *  Return results for all completed request nodes
     *
     *  @access  public
     *  @static
     *  @return  array
     */
    static public function get_results() {

        $raw_results = Curl_Node::get_results();

        //strip out all the metadata, and just return the response
        $results     = array();

        foreach( $raw_results as $raw_result ) {
            $results[] = $raw_result['response'];
        }

        return $results;
    }


    /**
     *  Get debug info.
     *
     *  @access  public
     *  @static
     *  @return  string  All debug information.
     */
    static public function get_debug_info() {

        return Curl_Node::get_debug_info();
    }


    /**
     *  User defined callback function to process results as the individual request completes
     *
     *  @access  public
     *  @static
     *  @param   $response   string  [REQUIRED]  The response to the cURL response. Prepend
     *                                           with '&' to pass by reference.
     *  @param   $metadata   string  [REQUIRED]  User defined metadata associated with the
     *                                           request
     *  @param   $http_code  string  [REQUIRED]  The HTTP code generated by the cURL request
     *  @param   $latency    float   [REQUIRED]  Seconds elapsed since node added accurate to
     *                                           the nearest microsecond
     *  @param   $url        string  [REQUIRED]  User provided URL
     *  @param   $opts       array   [REQUIRED]  The cURL transfer options
     *  @return  NULL
    **/
    static public function process_result( &$response, &$metadata, &$http_code, &$latency, &$url, &$opts ) {

        $response_str = $response;
        $response     = json_decode($response, TRUE);
        $response     = is_array($response) ? $response : array();

        // backfill any missing error messages
        if( $http_code != 200 ) {

            if( self::$return_diagnostics ) {
                if( !isset($response['diagnostics']) ) {
                    $response['diagnostics'] = array();
                }
                if( !isset($response['response']) ) {
                    $response['response'] = array();
                }
                if( !isset($response['response']['response_code']) ) {
                    $response['response']['response_code'] = $http_code;
                }
                if( !isset($response['response']['message']) ) {
                    if( is_string($response_str) ) {
                        $response['response']['message'] = self::http_response_message( $http_code, $response_str );
                    } else {
                        $response['response']['message'] = self::http_response_message( $http_code, '' );
                    }
                }
            } else {
                if( !isset($response['response_code']) ) {
                    $response['response_code'] = $http_code;
                }
                if( !isset($response['message']) ) {
                    if( is_string($response_str) ) {
                        $response['message'] = self::http_response_message( $http_code, $response_str );
                    } else {
                        $response['message'] = self::http_response_message( $http_code, '' );
                    }
                }
            }

            if( self::$log_errors === TRUE ) {
                $request_uri   = $url . '?' . $opts[CURLOPT_POSTFIELDS];
                $error_message = self::$return_diagnostics ? $response['response']['message'] : $response['message'];
                self::write_to_error_log( $request_uri, $http_code, $error_message );
            }
        }

        // convert response if user has specified alternate scales (i.e. metric or celsius)
        self::scale_response( $response );

        if( !empty($metadata['callback']) && is_callable($metadata['callback']) ) {

            // we will for a $result array that allows $node['response'] to be passed by reference to user defined callback
            $callback_params = array(
                &$response,
                &$http_code,
                &$latency,
                &$url,
                &$opts
            );
            call_user_func_array( $metadata['callback'], $callback_params );
        }

        // remove metadata from results
        $metadata = NULL;
    }


    /**
     *  Return the error message for the most recent request
     *
     *  @access  public
     *  @static
     *  @return  string  path to error log directory
     */
    static public function get_error_log_directory() {

        return __DIR__ . '/' . self::$error_log_directory;
    }


    /**
     *  Set the HTTP Status Code for the most recent request
     *
     *  @access  private
     *  @static
     *  @param   integer  $request_uri    [REQUIRED]  The API request URI
     *  @param   string   $http_code      [REQUIRED]  The HTTP Response Code
     *  @param   string   $error_message  [REQUIRED]  The error message
     *  @return  NULL
     */
    static private function write_to_error_log( $request_uri, $http_code, $error_message ) {

        // modify $request_uri to more readable format
        $request_uri = urldecode($request_uri);


        // compose our error message
        $timestamp = date('c');
        $error_message = "[{$timestamp}] [Error {$http_code} | {$error_message}] [{$request_uri}]\r\n";

        // assemble our path parts
        $directory = self::$error_log_directory;
        $directory = substr($directory, -1) == '/' ? $directory : $directory . '/';
        if( substr($directory, 0, 1) != '/' ) {
            // this is a relative path
            $directory = __DIR__ . '/' . $directory;
        }

        // make sure the error log directory exists
        if( !is_dir($directory) ) {
            mkdir($directory);
        }

        // assemble our error log filename
        $filename = $directory . 'wsapi_errors_' . date('Ymd') . '.log';

        // write to the error log
        $file_pointer = fopen($filename, 'a+');
        fwrite($file_pointer, $error_message);
        fclose($file_pointer);
    }


    /**
     *  Get the HTTP Response Message for a givin HTTP Response Code
     *
     *  @access  private
     *  @static
     *  @param   integer  $http_code   [REQUIRED]  The HTTP Response Code for most
     *                                             recent request
     *  @param   string   $curl_error  [OPTIONAL]  The text of the cURL error when
     *                                             $http_code == 0
     *  @return  string   HTTP Response Message
     */
    static private function http_response_message( $http_code, $curl_error = '' ) {

        if( !is_null($http_code) ) {
            switch( $http_code ) {
                case 0:   $text = 'Connection Error' . $curl_error; break;
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
            $text = 'Unknown status'; break;
        }

        return $text;
    }


    /**
     *  Convert to preferred scales
     *
     *  @access  private
     *  @static
     *  @param   array  $response  [REQUIRED]  The response array
     *  @return  NULL
     *
     */
    static private function scale_response( &$response ) {

        if( self::$distance_unit == 'metric' || self::$temperature_unit == 'celsius' ) {
            array_walk_recursive( $response, array('self', 'scale_value') );
        }
    }


    /**
     *  Scale individual values (this is a callback function for array_walk_recursive)
     *
     *  @access  private
     *  @static
     *  @param   array  $response  [REQUIRED]  The response array
     *  @param   array  $response  [REQUIRED]  The response array
     *  @return  NULL
     */
    static private function scale_value( &$value, &$key ) {

        if( is_numeric($value) ) {
            if( isset(self::$inch_keys[$key]) && self::$distance_unit == 'metric' ) {
                $value = self::convert_inches_to_centimeters($value);
            } elseif( isset(self::$mph_keys[$key]) && self::$distance_unit == 'metric' ) {
                $value = self::convert_mph_to_kmph($value);
            } elseif( isset(self::$fahrenheit_keys[$key]) && self::$temperature_unit == 'celsius' ) {
                $value = self::convert_fahrenheit_to_celsius($value);
            }
        }
    }


    /**
     *  Convert inches to centimeters
     *
     *  @access  private
     *  @static
     *  @param   float  $inches  [REQUIRED]
     *  @return  float  centimeter conversion value
     */
    static private function convert_inches_to_centimeters( $inches ) {

        return round( $inches * 2.54, 2 );
    }


    /**
     *  Convert mph to km/hour
     *
     *  @access  private
     *  @static
     *  @param   float  $mph  [REQUIRED]
     *  @return  float  km/hour conversion value
     */
    static private function convert_mph_to_kmph( $mph ) {

        return round( $mph * 1.60934, 1 );
    }


    /**
     *  Convert degrees fahrenheit to degrees celsius
     *
     *  @access  private
     *  @static
     *  @param   float  $fahrenheit  [REQUIRED]
     *  @return  float  celsius conversion value
     */
    static private function convert_fahrenheit_to_celsius( $fahrenheit ) {

        return round( (($fahrenheit-32)*5)/9, 1 );
    }

}

class_alias('Weather_Source_API_Requests', 'Weather_Source_API_Request');

require_once( __DIR__ . '/curl_node.php');

?>