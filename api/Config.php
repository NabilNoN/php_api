<?php
header('Content-type: application/json');
header("Cache-Control: max-age=3000, must-revalidate");
date_default_timezone_set('Asia/Riyadh');

define('FORMAT','Y-m-d H:i:s');
define('FORMAT_DATE','Y-m-d');

define("DATA", "data");
define("ARRAY_LIST", "list");
define("ERROR", "error");
define("DONE","done");

define("db_host", "store1.facceapps.com");
define("db_name", "facceapp_store1");
define("db_user", "facceapp_store1");
define("db_pwd", "7Tl1II2zYlKw");

define("APP_ID_MARKET", "com.facceapps.store1");
define("APP_ID_MANAGE", "com.facceapps.store1manage");
define("GOOGLE_API_KEY", "");
define("GOOGLE_FCM_URL", "https://fcm.googleapis.com/fcm/send");