<?php

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       MIT License (https://opensource.org/licenses/mit-license.php)
 */
/**
 * Use the DS to separate the directories in other defines
 */
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

/**
 * These defines should only be edited if you have cake installed in
 * a directory layout other than the way it is distributed.
 * When using custom settings be sure to use the DS and do not add a trailing DS.
 */
/**
 * The full path to the directory which holds "src", WITHOUT a trailing DS.
 */
define('ROOT', dirname(__DIR__));

/**
 * The actual directory name for the application directory. Normally
 * named 'src'.
 */
define('APP_DIR', 'src');

/**
 * Path to the application's directory.
 */
define('APP', ROOT . DS . APP_DIR . DS);

/**
 * Path to the config directory.
 */
define('CONFIG', ROOT . DS . 'config' . DS);

/**
 * File path to the webroot directory.
 *
 * To derive your webroot from your webserver change this to:
 *
 * `define('WWW_ROOT', rtrim($_SERVER['DOCUMENT_ROOT'], DS) . DS);`
 */
define('WWW_ROOT', ROOT . DS . 'webroot' . DS);

/**
 * Path to the tests directory.
 */
define('TESTS', ROOT . DS . 'tests' . DS);

/**
 * Path to the temporary files directory.
 */
define('TMP', ROOT . DS . 'tmp' . DS);

/**
 * Path to the logs directory.
 */
define('LOGS', ROOT . DS . 'logs' . DS);

/**
 * Path to the cache files directory. It can be shared between hosts in a multi-server setup.
 */
define('CACHE', TMP . 'cache' . DS);

/**
 * The absolute path to the "cake" directory, WITHOUT a trailing DS.
 *
 * CakePHP should always be installed with composer, so look there.
 */
define('CAKE_CORE_INCLUDE_PATH', ROOT . DS . 'vendor' . DS . 'cakephp' . DS . 'cakephp');

/**
 * Path to the cake directory.
 */
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . 'src' . DS);


/**
 * Other useful constants and paths.
 */

define('APP_KEY', 'MENYESHA');
define('APP_ID', 101202302);
define('ENC_KEY', 'My3WVa7l0QvlDWJrDt4JXc1uLrqmDaDx6zUUXwSwfaFXHCdxmmtSRGdAF6sh1et6');
define('PAYMENT_URL_TEST', 'https://api.mvendpay.com/requestpayment/');
define('ACC_INFO_URL_TEST', 'http://192.168.4.5:7378/production/mpay/v1/psp/paymentservice/');
define("SMS_URL", 'http://192.168.4.4:14013');
define("SMS_USER", 'mvend');
define("SMS_PASS", 'mvend');
define("SVC_URL", "https://secure7.transunionafrica.com/crbws/mobile/rw?wsdl");
define("SVC_LOGIN", "RWFq7NE3vzDA");
define("SVC_PASSD", "WxB4sZQXDyUaxLyt");
define("REQ_UNAME", "WS_MWD1");
define("REQ_PASS", "Npsuxa");
define("REQ_CODE", "1574");
define("REQ_INFINITYCode", "3010RW19783");
define("SVC_URL_TEST", "https://secure3.crbafrica.com/crbws/mobile/rw?wsdl");
define("SVC_LOGIN_TEST", "RWFq7NE3vz");
define("SVC_PASSD_TEST", "WxB4sZQXDyUaxL");
define("REQ_UNAME_TEST", "WS_MVD1");
define("REQ_PASS_TEST", "aAcdef");
define("REQ_CODE_TEST", "1552");
define("REQ_INFINITYCode_TEST", "rw123456789");
define("WA_URL", "https://eu34.chat-api.com/instance121838/");
define("WA_TOKEN", "jestcua2a2zd9c0u");
