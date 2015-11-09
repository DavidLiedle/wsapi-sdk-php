<?php
/**
 * @author Jeffrey D. King
 * @copyright 2012-2015 Weather Source, LLC
 * @since Version 3.1
 */

//==============================================================================
//==    IMPORTANT:	after setting the configuration options below,
//==    			rename this file to "config.php"
//==============================================================================


/**
 *	@const  string  [REQUIRED]  Your API key
 */
define('WSAPI_KEY', '' );

/**
 *	@const  string  [OPTIONAL]  The base API URI. If not defined here, WSAPI_BASE_URI defaults to
 *							    'https://api.weathersource.com'.
 */
define('WSAPI_BASE_URI', 'https://api.weathersource.com' );

/**
 *	@const  string  [OPTIONAL]  The API version. Make sure the version number is prepended with a
 *							    'v'. If not defined here, WSAPI_VERSION defaults to 'v1'.
 */
define('WSAPI_VERSION', 'v1'  );

/**
 *	@const  boolean  [OPTIONAL]  Return diagnostic information with response? If not defined here,
 *							     WSAPI_RETURN_DIAGNOSTICS defaults to FALSE.
 */
define('WSAPI_RETURN_DIAGNOSTICS', FALSE );

/**
 *	@const  boolean  [OPTIONAL]  Suppress all HTTP response codes (i.e. force a 200 response)? If
 *							     not defined here, WSAPI_SUPPRESS_RESPONSE_CODES defaults to FALSE.
 */
define('WSAPI_SUPPRESS_RESPONSE_CODES', FALSE );

/**
 *	@const  integer  [OPTIONAL]  Pace requests to comply with a subscription plan's minute rate limit.
 *							     If not defined here, WSSDK_MAX_REQUESTS_PER_MINUTE defaults to 10.
 */
define('WSSDK_MAX_REQUESTS_PER_MINUTE', 10 );

/**
 *	@const  integer  [OPTIONAL]  The maximum thread count. WARNING: To many threads originating from
 *								 the same IP address may result in connection errors! If not defined
 *							     here, WSSDK_MAX_THREADS defaults to 10.
 */
define('WSSDK_MAX_THREADS', 10 );

/**
 *	@const  string  [OPTIONAL]  Defines the unit type to return for relevant observations. Allowed
 *								values are 'imperial' and 'metric'. If set to imperial, relevant
 *								values will be returned as inches or miles per hour. If set to
 *								metric, relevant values will be returned as centimeters or
 *								kilometers per hour. This will only convert responses from the API.
 *								All writes must conform to the API spec (imperial). If not defined
 *							    here, WSSDK_DISTANCE_UNIT defaults to 'imperial'.
 */
define('WSSDK_DISTANCE_UNIT', 'imperial' );

/**
 *	@const  string  [OPTIONAL]  Defines the unit type to return for relevant observations. Allowed
 *								values are 'fahrenheit' and 'celsius'. This will only convert responses
 *								from the API. All writes must conform to the API spec (fahrenheit).
 *							    If not defined here, WSSDK_TEMPERATURE_UNIT defaults to 'fahrenheit'.
 */
define('WSSDK_TEMPERATURE_UNIT', 'fahrenheit' );

/**
 *	@const  boolean  [OPTIONAL]  Should all errors be written to a log file? If WSAPI_SUPPRESS_RESPONSE_CODES
 *								 is set to TRUE, this option will be forced to FALSE. If not defined here,
 *							     WSSDK_LOG_ERRORS defaults to FALSE.
 */
define('WSSDK_LOG_ERRORS', FALSE );

/**
 *	@const  string  [OPTIONAL]  Log file directory location. A path beginning beginning with '/' is
 *								considered absolute, otherwise, it is treated as relative to the
 *								api.weathersource.sdk/sdk directory. If not defined here,
 *							    WSSDK_ERROR_LOG_DIRECTORY defaults to 'error_logs/'.
 */
define('WSSDK_ERROR_LOG_DIRECTORY', 'error_logs/' );

/**
 *	@const  integer  [OPTIONAL]  The number of times to retry a request that returns a potentially
 *								 recoverable error. Retries are exponentially delayed. If not defined
 *								 here, WSSDK_REQUEST_RETRY_ON_ERROR_COUNT defaults to 10.
 */
define('WSSDK_REQUEST_RETRY_ON_ERROR_COUNT', 10 );

/**
 *	@const  integer  [OPTIONAL]  The initial allowable requests per minute for warm-up scaling.
 *							     WARNING: increasing this number may result in connection errors
 *							     while the API infrustructure scales to meet your high demand.
 *							     If not defined here, WSSDK_SCALING_INITIAL_REQUESTS_PER_MINUTE
 *							     defaults to 1000.
 */
define('WSSDK_SCALING_INITIAL_REQUESTS_PER_MINUTE', 1000 );

/**
 *	@const  float  [OPTIONAL]  The number of minutes to double warm-up scaling allowable requests
 *							   per minute. WARNING: decreasing this number may result in connection
 *							   errors while the API infrustructure scales to meet your high demand.
 *							   If not defined here, WSSDK_SCALING_DOUBLE_CAPACITY_MINUTES defaults
 *							   to 7.
 */
define('WSSDK_SCALING_DOUBLE_CAPACITY_MINUTES', 7 );
