<?php
require_once('Config.php');
require_once('utils.php');
require_once('init.php');
require_once('notify/fcmManager.php');

/**
 * @param Mysql $sql
 * @return array
 */
function main($sql)
{
    //json content type decode if posted
    $json_params = file_get_contents("php://input");
    if ($json_params!==null && strlen($json_params) > 0 && isValidJSON($json_params)){
        $_POST = json_decode($json_params, true);
    }

    $nabil = isset($_POST['nabil']) ? $_POST['nabil'] : null;
    $method = isset($_POST['method']) ? $_POST['method'] : null;

    if ($nabil !== null) {
        if ($method !== null) {
            switch ($method) {
                case "login":
                {
                    return login($sql);
                }
                case "logout":
                {
                    return logout($sql, $nabil);
                }
                default:
                {
                    return newError(2);
                }
            }
        } else {
            return newError(1);
        }
    } else {
        return newError(0,json_encode($_POST));
    }
}

echo json_encode(main($sql));
//////////////////////////////////////////////////////////////////////////////
///                     START API FUNCTIONS
//////////////////////////////////////////////////////////////////////////////

function login(Mysql $sql){
    $username = isset($_POST['username'])?$_POST['username']:null;
    $password = isset($_POST['password'])?$_POST['password']:null;
    $now = new DateTime();
    try {
    } catch (Exception $e) {
        return newError(6,$e->getMessage());//login_field
    }
}

function logout(Mysql $sql,$nabil){
    try {
    } catch (Exception $e) {
        return newError(8,$e->getMessage());//logout_field
    }
}
