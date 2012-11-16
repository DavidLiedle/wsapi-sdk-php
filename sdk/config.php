<?php

/**
 * @author Jeffrey D. King
 * @copyright 2012- Weather Source, LLC
 * @since Version 1
 *
 */



// Should all errors be written to a log file?
// DEFAULT: FALSE
define("WSAPI_LOG_ERRORS", FALSE );

// Log file directory location.
// DEFAULT: "/error_logs/"
define("WSAPI_ERROR_LOG_DIRECTORY", "/error_logs/"  );

// The number of types to retry a request that returns a non-user caused error.
// DEFAULT: 5
define("WSAPI_REQUEST_RETRY_ON_ERROR_COUNT", 5 );


?>