<?php

/**
 *
 * Weather Source API PHP SDK
 *
 * Requires PHP version 5.3.0 or greater
 *
 * TODO: If suppress errors is on, we need to check the response for an error code and
 *       grab the error number and text for logging purposes.
 *
 * @api
 * @author Jeffrey D. King
 * @copyright 2012- Weather Source, LLC
 * @version 1.7
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
        $distance_unit,
        $temperature_unit,
        $log_errors,
        $error_log_directory,
        $request_retry_count,
        $request_retry_delay,

        // properties
        $root_directory,
        $response_code,
        $error_message = '',
        $is_ok = FALSE,

        // stores
        $inch_fields       = array( 'precip', 'precipMax', 'precipAvg', 'precipMin',
                                    'snowfall', 'snowfallMax', 'snowfallAvg', 'snowfallMin' ),
        $mph_fields        = array( 'windSpd', 'windSpdMax', 'windSpdAvg', 'windSpdMin', 'prevailWindSpd' ),
        $fahrenheit_fields = array( 'temp', 'tempMax', 'tempAvg', 'tempMin',
                                    'dewPt', 'dewPtMax', 'dewPtAvg', 'dewPtMin',
                                    'feelsLike', 'feelsLikeMax', 'feelsLikeAvg', 'feelsLikeMin',
                                    'wetBulb', 'wetBulbMax', 'wetBulbAvg', 'wetBulbMin' ),
        $inch_keys,        // flipped $inch_fields for efficient lookups
        $mph_keys,         // flipped $mph_fields for efficient lookups
        $fahrenheit_keys;  // flipped $fahrenheit_fields for efficient lookups


    /**
     *
     *  Initiate our class instance
     *
     *  @return NULL
     */
    public function __construct() {

        $this->root_directory          = (__DIR__ == '/') ? __DIR__ : __DIR__.'/';

        require_once( $this->root_directory . '/config.php' );

        $this->base_uri                = defined('WSAPI_BASE_URI') ? (string) WSAPI_BASE_URI : 'https://api.weathersource.com';
        $this->version                 = defined('WSAPI_VERSION') ? (string) WSAPI_VERSION : 'v1';
        $this->key                     = defined('WSAPI_KEY') ? (string) WSAPI_KEY : '';
        $this->return_diagnostics      = defined('WSAPI_RETURN_DIAGNOSTICS') ? (boolean) WSAPI_RETURN_DIAGNOSTICS : FALSE;
        $this->suppress_response_codes = defined('WSAPI_SUPPRESS_RESPONSE_CODES') ? (boolean) WSAPI_SUPPRESS_RESPONSE_CODES : FALSE;
        $this->distance_unit           = defined('WSAPI_DISTANCE_UNIT') ? (boolean) WSAPI_DISTANCE_UNIT : 'imperial';
        $this->temperature_unit        = defined('WSAPI_TEMPERATURE_UNIT') ? (boolean) WSAPI_TEMPERATURE_UNIT : 'fahrenheit';
        $this->log_errors              = defined('WSAPI_LOG_ERRORS') ? (boolean) WSAPI_LOG_ERRORS : FALSE;
        $this->error_log_directory     = defined('WSAPI_ERROR_LOG_DIRECTORY') ? (string) WSAPI_ERROR_LOG_DIRECTORY : 'error_logs/';
        $this->request_retry_count     = defined('WSAPI_REQUEST_RETRY_ON_ERROR_COUNT') ? (integer) WSAPI_REQUEST_RETRY_ON_ERROR_COUNT : 5;
        $this->request_retry_delay     = defined('WSAPI_REQUEST_RETRY_ON_ERROR_DELAY') ? (integer) WSAPI_REQUEST_RETRY_ON_ERROR_DELAY : 2;

        $this->inch_keys       = array_flip($this->inch_fields);
        $this->mph_keys        = array_flip($this->inch_fields);
        $this->fahrenheit_keys = array_flip($this->inch_fields);
    }


    /**
     *
     *  Sends request to the Weather Source API
     *
     *  @param  string  $method          REQUIRED  The HTTP method for the request (allowed: 'GET', 'POST', 'PUT', 'DELETE')
     *  @param  string  $resource_path   REQUIRED  The resource path for the request (i.e. 'history_by_postal_code')
     *  @param  array   $parameters      REQUIRED  The resource parameters
     *
     *  @return string  The API response
     */
    public function request( $method, $resource_path, $parameters ) {

        /*  reset  response_code and error_message  */
        $this->set_response_code( NULL );
        $this->set_error_message( NULL );


        /*  append meta parameters  */

        $parameters['_method'] = strtolower($method);

        if( $this->return_diagnostics ) {
            $parameters['_diagnostics'] = '1';
        }

        if( $this->suppress_response_codes ) {
            $parameters['_suppress_response_codes'] = '1';
        }


        /*  open connection  */

        $ch = curl_init();


        /*  set the url, number of POST vars, POST data  */

        $uri = $this->base_uri . '/' . $this->version . '/' . $this->key . '/' . $resource_path . '.json';
        curl_setopt( $ch, CURLOPT_URL, $uri );
        curl_setopt( $ch, CURLOPT_POST, count($parameters) );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query($parameters, '', '&') );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 60 );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5 );
        curl_setopt( $ch, CURLOPT_DNS_CACHE_TIMEOUT, 15 );
        curl_setopt( $ch, CURLOPT_DNS_USE_GLOBAL_CACHE, FALSE );

        /*  execute post  */

        for( $i=0; $i < $this->request_retry_count; $i++ ) {

            $json_response = curl_exec($ch);
            $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error    = curl_error($ch);
            $curl_error    = !empty($curl_error) ? ': ' . $curl_error : '';

            if( !in_array($response_code, array(0,500,502,503,504)) ) {
                break;
            }

            sleep($this->request_retry_delay);
        }


        /*  close connection  */

        curl_close($ch);


        /*  process $json_response  */

        $response = $this->process_response( $json_response, $response_code, $curl_error );


        /*  set response code  */

        $this->set_response_code( $response_code );


        /*  set error message  */

        if( !$this->is_ok() ) {
            if( $this->return_diagnostics ) {
                $error_message = $response['response']['message'];
            } else {
                $error_message = $response['message'];
            }
            $this->set_error_message( $error_message );
        }

        /*  write to error log if appropriate  */

        if( !$this->is_ok() && $this->log_errors === TRUE ) {
            $request_uri = $uri . '?' . http_build_query($parameters);
            $this->write_to_error_log( $request_uri );
        }


        /*  return response  */

        return $response;
    }


    /**
     *
     *  Return the current status
     *
     *  @return boolean  TRUE if current status is not in error, FALSE otherwise.
     */
    public function is_ok() {

        return $this->is_ok;
    }


    /**
     *
     *  Return the HTTP Status Code for the most recent request
     *
     *  @return integer  the HTTP Status Code for the most recent request (NULL if no previous request)
     */
    public function get_response_code() {

        return $this->response_code;
    }


    /**
     *
     *  Return the error message for the most recent request
     *
     *  @return string  error message for most recent request (NULL if no error)
     */
    public function get_error_message() {

        return $this->error_message;
    }


    /**
     *
     *  Return the error message for the most recent request
     *
     *  @return string  path to error log directory
     */
    public function get_error_log_directory() {

        return $this->root_directory . $this->error_log_directory;
    }


    /**
     *
     *  Set the error message for the most recent request
     *
     *  @param  bookean  $is_ok  REQUIRED  If the current status is not in error: TRUE. Otherwise FALSE.
     *
     *  @return NULL
     */
    private function set_is_ok( $is_ok ) {

        $this->is_ok = $is_ok;
    }


    /**
     *
     *  Set the HTTP Status Code for the most recent request
     *
     *  @param  integer  $response_code  REQUIRED  The HTTP Status Code for most recent request
     *
     *  @return NULL
     */
    private function set_response_code( $response_code ) {

        $this->response_code = $response_code;
        $this->set_is_ok( $response_code == 200 );
    }


    /**
     *
     *  Set the error message for the most recent request
     *
     *  @param  string  $error_message  REQUIRED  The error message for most recent request (NULL if no error)
     *
     *  @return NULL
     */
    private function set_error_message( $error_message ) {

        $this->error_message = $error_message;
    }


    /**
     *
     *  Set the HTTP Status Code for the most recent request
     *
     *  @param  integer  $request_uri  REQUIRED  The API request URI
     *
     *  @return NULL
     */
    private function write_to_error_log( $request_uri ) {

        if( !$this->is_ok() ) {

            // modify $request_uri to more readable format
            $request_uri = urldecode($request_uri);


            // compose our error message
            $timestamp = date('c');
            $error_message = "[{$timestamp}] [Error {$this->response_code} | {$this->error_message}] [{$request_uri}]\r\n";

            // assemble our path parts
            $error_log_directory = $this->error_log_directory;
            $error_log_directory = substr($error_log_directory, -1) == '/' ? $error_log_directory : $error_log_directory . '/';
            if( substr($error_log_directory, 0, 1) != '/' ) {
                // this is a relative path
                $error_log_directory = $this->root_directory . $error_log_directory;
            }

            // make sure the error log directory exists
            if( !is_dir($error_log_directory) ) {
                mkdir($error_log_directory);
            }

            // assemble our error log filename
            $error_log_filename = $error_log_directory . 'wsapi_errors_' . date('Ymd') . '.log';

            // write to the error log
            $file_pointer = fopen($error_log_filename, 'a+');
            fwrite($file_pointer, $error_message);
            fclose($file_pointer);
        }
    }


    /**
     *
     *  Get the HTTP Response Message for a givin HTTP Response Code
     *
     *  @param  integer  $response_code  REQUIRED  The HTTP Response Code for most recent request
     *  @param  string   $curl_error     OPTIONAL  The text of the cURL error when $response_code == 0
     *
     *  @return string   HTTP Response Message
     */
    private function http_response_message( $response_code, $curl_error = '' ) {

        if( !is_null($response_code) ) {
            switch ($response_code) {
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
     *
     *  Process a JSON formatted response into a PHP array updated with absent error messages
     *
     *  @param  integer  $json_response  REQUIRED  The JSON formatted response
     *  @param  integer  $response_code  REQUIRED  The HTTP Response Code for most recent request
     *  @param  string   $curl_error     OPTIONAL  The text of the cURL error when $response_code == 0
     *
     *  @return array    response updated with absent error messages
     */
    private function process_response( $json_response, $response_code, $curl_error = '' ) {

        $response = json_decode($json_response, TRUE);

        $response = is_array($response) ? $response : array();

        // backfill any missing error messages
        if( $response_code != 200 ) {

            if( $this->return_diagnostics ) {
                if( !isset($response['diagnostics']) ) {
                    $response['diagnostics'] = array();
                }
                if( !isset($response['response']) ) {
                    $response['response'] = array();
                }
                if( !isset($response['response']['response_code']) ) {
                    $response['response']['response_code'] = $response_code;
                }
                if( !isset($response['response']['message']) ) {
                    $response['response']['message'] = $this->http_response_message( $response_code, $curl_error );
                }
            } else {
                if( !isset($response['response_code']) ) {
                    $response['response_code'] = $response_code;
                }
                if( !isset($response['message']) ) {
                    $response['message'] = $this->http_response_message( $response_code, $curl_error );
                }
            }
        }

        // convert response if user has specified alternate scales (i.e. metric or celsius)
        $response = $this->scale_response( $response );

        return $response;
    }

    /**
     *
     *  Convert to preferred scales
     *
     *  @param  array  $response  REQUIRED  The response array
     *
     *  @return array  converted response
     *
     */
    private function scale_response( $response ) {

        if( $this->distance_unit == 'metric' || $this->temperature_unit == 'celsius' ) {
            array_walk_recursive( $response, array($this, 'scale_value') );
        }

        return $response;
    }

    /**
     *
     *  Scale individual values (this is a callback function for array_walk_recursive)
     *
     *  @param  array  $response  REQUIRED  The response array
     *  @param  array  $response  REQUIRED  The response array
     *
     *  @return array  converted response
     *
     */
    private function scale_value( &$value, &$key ) {

        if( is_numeric($value) ) {
            if( isset($this->inch_keys[$key]) && $this->distance_unit == 'metric' ) {
                $value = $this->convert_inches_to_centimeters($value);
            } elseif( isset($this->mph_keys[$key]) && $this->distance_unit == 'metric' ) {
                $value = $this->convert_mph_to_kmph($value);
            } elseif( isset($this->fahrenheit_keys[$key]) && $this->temperature_unit == 'celsius' ) {
                $value = $this->convert_fahrenheit_to_celsius($value);
            }
        }
    }

    /**
     *
     *  Convert inches to centimeters
     *
     *  @param  float  $inches  REQUIRED
     *
     *  @return float  centimeter conversion value
     *
     */
    private function convert_inches_to_centimeters( $inches ) {

        return round( $inches * 2.54, 2 );
    }

    /**
     *
     *  Convert mph to km/hour
     *
     *  @param  float  $mph  REQUIRED
     *
     *  @return float  km/hour conversion value
     *
     */
    private function convert_mph_to_kmph( $mph ) {

        return round( $mph * 1.60934, 1 );
    }

    /**
     *
     *  Convert degrees fahrenheit to degrees celsius
     *
     *  @param  float  $fahrenheit  REQUIRED
     *
     *  @return float  celsius conversion value
     *
     */
    private function convert_fahrenheit_to_celsius( $fahrenheit ) {

        return round( (($fahrenheit-32)*5)/9, 1 );
    }

}

?>