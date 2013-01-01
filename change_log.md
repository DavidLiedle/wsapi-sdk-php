Change Log
==========



Version 3.0
-----------

Fixed bug that prevented request retries from operating correctly.
Added capability to enable verbose output and/or progress output.
Added debug output capability.


Version 2.4
-----------

Removed WSSDK_REQUEST_RETRY_ON_ERROR_DELAY from config_sample.php and set the retry delay
    to double the expected delay per retry attempt.


Version 2.3
-----------

Fixed bug where sleep was for seconds rather than microseconds.


Version 2.2
-----------

Added cURL option to prevent HTTP 100 responses
Improved memory efficiency
Added warm-up scaling logic to retry requests


Version 2.1
-----------

Added warm-up scaling feature to reduce connection errors when making high volumes of
	requests. By default, an initial 1000 requests per minute are allowed, doubling
	every 7 minutes until the Max Requests Per Minute is reached.


Version 2.0
-----------

Rearchitected for multi-threaded requests.
Changes to config_sample.php are not backwards compatable. Update your config.php file.
Code written for Version 1.X is not compatable with 2.0, and must be ported.