<?php

/**
 *
 * @author Jeffrey D. King
 * @copyright 2012- Weather Source, LLC
 * @since Version 1.4
 *
 */


//==============================================================================
//==    IMPORTANT:	after setting the configuration options below,
//==    			rename this file to "config.php"
//==============================================================================


// The number of types to retry a request that returns a non-user caused error.
// TYPE: string
// REQUIRED
define('WSAPI_KEY', '' );

// The base API URI
// TYPE: string
// DEFAULT: 'https://api.weathersource.com'
define('WSAPI_BASE_URI', 'https://api.weathersource.com' );

// The API version
// TYPE: string
// DEFAULT: 'v1'
define('WSAPI_VERSION', 'v1'  );

// Return diagnostic information with response?
// TYPE: boolean
// DEFAULT: FALSE
define('WSAPI_RETURN_DIAGNOSTICS', FALSE );

// Suppress all HTTP response codes (i.e. force a 200 response)?
// TYPE: boolean
// DEFAULT: FALSE
define('WSAPI_SUPPRESS_RESPONSE_CODES', FALSE );

// Return imperial or metric distance measures?
// 		imperial | metric
// 		---------+------------
// 		inches   | centimeters
// 		miles/hr | km/hr
// TYPE: string
// ALLOWED: 'imperial', 'metric'
// DEFAULT: 'imperial'
define('WSAPI_DISTANCE_UNIT', 'imperial' );

// Return fahrenheit or celsius distance measures?
// TYPE: string
// ALLOWED: 'fahrenheit', 'celsius'
// DEFAULT: 'fahrenheit'
define('WSAPI_TEMPERATURE_UNIT', 'fahrenheit' );

// Should all errors be written to a log file?
// TYPE: boolean
// DEFAULT: FALSE
define('WSAPI_LOG_ERRORS', FALSE );

// Log file directory location. A path beginning beginning with '/' is considered absolute,
//     otherwise, it is treated as relative to the api.weathersource.sdk/sdk directory.
// TYPE: string
// DEFAULT: 'error_logs/'
define('WSAPI_ERROR_LOG_DIRECTORY', 'error_logs/'  );

// The number of times to retry a request that returns a non-user caused error.
// TYPE: integer
// DEFAULT: 5
define('WSAPI_REQUEST_RETRY_ON_ERROR_COUNT', 5 );

// The delay in seconds before a request that returns a non-user caused error is retries\d.
// TYPE: integer
// DEFAULT: 2
define('WSAPI_REQUEST_RETRY_ON_ERROR_DELAY', 2 );


?>