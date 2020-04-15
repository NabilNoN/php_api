<?php
/**
 * Copyright (c) 2020. FacceApps
 */
if (!isset($sql)) {
    require_once("../Config.php");
    require_once("../utils.php");
    require_once("../init.php");

    if (isset($_POST["nabil"])) {
        if (isset($_POST["method"])) {
            if ($_POST["method"] === "send_market") {
                echo(json_encode(sendMarket($sql)));
            } else if ($_POST["method"] === "send_clint") {
                echo(json_encode(sendClint($sql)));
            } else if ($_POST["method"] === "send_user") {
                echo(json_encode(sendUser($sql)));
            } else if ($_POST["method"] === "save_token") {
                echo(json_encode(saveToken($sql)));
            } else {
                $data["error"] = "valid-method-" . $_POST["method"];
                $return['data'] = $data;
                echo(json_encode($return));
            }
        }
    }
}

function saveToken(Mysql $sql, $account_id = 0,$dest=null, $info = array())
{
    $date = date('Y-m-d H:i:s');
    $return = array();
    $data = array();

    if ($account_id === null || $account_id < 1 )
        $account_id = (isset($_POST['account_id'])) ? $_POST['account_id'] : $account_id;

    if ($dest === null || strlen($dest) < 5 )
        $dest = (isset($_POST['dest'])) ? $_POST['dest'] : $dest;

    if (empty($account_id) || $dest === null) {
        $data["error"] = "token-only-by-port-or-valid-id";
        $return['data'] = $data;
        return $return;
    }

    try {
        $hasError = false;
        if (isset($_POST['token'])) $info['token'] = $_POST['token']; else if (!isset($info['token'])) $hasError = true;
        if (isset($_POST['account_id'])) $info['account_id'] = $_POST['account_id']; else if (!isset($info['account_id'])) $hasError = true;
        if (isset($_POST['dest'])) $info['dest'] = $_POST['dest']; else if (!isset($info['dest']) || empty($dest) || strlen($dest) < 5) $hasError = true; else $info['dest'] = $dest;
        if (isset($_POST['enabled'])) $info['enabled'] = $_POST['enabled']; else if (!isset($info['enabled'])) $info['enabled'] = enumStore::$yes;
        if (isset($_POST['date'])) $info['date'] = $_POST['date']; else if (!isset($info['date'])) $info['date'] = $date;

        if (!empty($info) && !$hasError) {
            if (($info['dest'] !== enumStore::$clint) && ($info['dest'] !== enumStore::$market)) {
                $data["error"] = "valid-dest";
                $return['data'] = $data;
                return $return;
            }
            $sql->where('id', $account_id)->get(table($dest));
            if ($sql->num_rows() > 0) {

                $curTokens = $sql->where('account_id', $info['account_id'])->where('dest', $info['dest'])->get(table('notify'));
                if ($sql->num_rows() > 0) {
                    if ($sql->where('account_id', $info['account_id'])->where('dest', $info['dest'])->update(table('notify'), $info)) {
                        $data = $sql->where('id', $curTokens['id'])->get(table('notify'));
                        if ($sql->num_rows() > 0) {
                            $return['data'] = $data;
                            return $return;
                        } else {
                            $data["error"] = "valid-update";
                            $return['data'] = $data;
                            return $return;
                        }
                    } else {
                        $data["error"] = "execute-update-token";
                        $return['data'] = $data;
                        return $return;
                    }
                    //then insert new token
                } else if ($sql->insert(table('notify'), $info)) {
                    $data = $sql->where('id', $sql->insert_id())->get(table('notify'));
                    if ($sql->num_rows() > 0) {
                        $return['data'] = $data;
                        return $return;
                    } else {
                        $data["error"] = "valid-insert";
                        $return['data'] = $data;
                        return $return;
                    }
                } else {
                    $data["error"] = "execute-insert-token";
                    $return['data'] = $data;
                    return $return;
                }


            } else {
                $data["error"] = "token-only-by-port-or-valid-id";
                $return['data'] = $data;
                return $return;
            }
        } else {
            $data["error"] = "no-data-insert-token";
            $return['data'] = $data;
            return $return;
        }
    } catch (Exception $e) {
        $data["error"] = $sql->last_query() . " *** #ERROR[" . $e->getMessage() . "]";
        $return['data'] = $data;
        return $return;
    }
}

function sendMarket(Mysql $sql, $title = "", $message = "")
{
    $return = array();
    $data = array();

    if ($title === null || strlen($title) < 3)
        $title = isset($_POST["title"]) ? $_POST["title"] : $title;

    if ($message === null || strlen($message) < 3)
        $message = isset($_POST["message"]) ? $_POST["message"] : $message;

    if (strlen($title) < 3 || strlen($message) < 3) {
        $data['error'] = "no-content-to-send";
        $return['data'] = $data;
        return $return;
    }
    $keys = array();
    try {
        $ids = "";
        $tokens = $sql->where('dest', enumStore::$market)->and_where("enabled", enumStore::$yes)->get(table('notify'), array("token"));

        if ($sql->num_rows() > 0) {
            //asArray($sql->num_rows(),$tokens);
            $sql->forceArray($tokens);
            foreach ($tokens as $token) {
                array_push($keys, $token['token']);
            }
        } else {
            $data['error'] = "no-market-tokens";
            $return['data'] = $data;
            return $return;
        }
    } catch (Exception $e) {
        $data['error'] = $e->getMessage();
        $return['data'] = $data;
        return $return;
    }

    $fields = array(
        'registration_ids' => $keys,
        'notification' => array(
            'title' => $title,
            'body' => $message,
            'sound'=>'default'
        ),
        'priority' => 'high',
        'data'=>addFlutterData(strlen($title))
    );

    $data['status'] = Notify(json_encode($fields));
    $data['tokens'] = $keys;
    //$data['ids'] = $ids;
    $data['param'] = "$title-$message";
    return $return["data"] = $data;
}

function sendClint(Mysql $sql, $title = "", $message = "")
{
    $return = array();
    $data = array();

    if ($title === null || strlen($title) < 3)
        $title = isset($_POST["title"]) ? $_POST["title"] : $title;

    if ($message === null || strlen($message) < 3)
        $message = isset($_POST["message"]) ? $_POST["message"] : $message;

    if (strlen($title) < 3 || strlen($message) < 3) {
        $data['error'] = "no-content-to-send";
        $return['data'] = $data;
        return $return;
    }
    $keys = array();
    try {
        $ids = "";
        $tokens = $sql->clean_extra()->where('dest', enumStore::$clint)->and_where("enabled", enumStore::$yes)->get(table('notify'), array("token"));

        if ($sql->num_rows() > 0) {
            $sql->forceArray($tokens);
            foreach ($tokens as $token) {
                array_push($keys, $token['token']);
            }
        } else {
            $data['error'] = "no-clint-tokens";
            $return['data'] = $data;
            return $return;
        }
    } catch (Exception $e) {
        $data['error'] = $e->getMessage();
        $return['data'] = $data;
        return $return;
    }

    $fields = array(
        'registration_ids' => $keys,
        'notification' => array(
            'title' => $title,
            'body' => $message,
            'sound'=>'default'
        ),
        'priority' => 'high',
        'data'=>addFlutterData(strlen($title))
    );

    $data['status'] = Notify(json_encode($fields));
    $data['tokens'] = $keys;
    //$data['ids'] = $ids;
    $data['param'] = "$title-$message";
    return $return["data"] = $data;
}

function sendUser(Mysql $sql, $account_id = 0, $dest=null, $title = "", $message = "")
{
    $return = array();
    $data = array();
    if ($account_id===null || $account_id < 1)
        $account_id = isset($_POST["account_id"]) ? $_POST["account_id"] : $account_id;

    if ($dest===null || strlen($dest) < 5)
        $dest = isset($_POST["dest"]) ? $_POST["dest"] : $dest;

    if (strlen($title) < 3 || strlen($message) < 3) {
        $title = isset($_POST["title"]) ? $_POST["title"] : $title;
        $message = isset($_POST["message"]) ? $_POST["message"] : $message;
    }

    if (strlen($title) < 3 || strlen($message) < 3) {
        $data['error'] = "no-content-to-send";
        $return['data'] = $data;
        return $return;
    }
    if ($account_id===null || $account_id < 1) {
        $data['error'] = "no-account-valid";
        $return['data'] = $data;
        return $return;
    }
    $keys = array();
    try {
        $tokens = $sql->clean_extra()->where('account_id', $account_id)->and_where("dest", $dest)->and_where("enabled", enumStore::$yes)->get(table('notify'), array("token"));
        if ($sql->num_rows() > 0) {
            $sql->forceArray($tokens);
            foreach ($tokens as $token) {
                array_push($keys, $token['token']);
            }
        } else {
            $data['error'] = "no-account-tokens";
            $return['data'] = $data;
            return $return;
        }
    } catch (Exception $e) {
        $data['error'] = $e->getMessage();
        $return['data'] = $data;
        return $return;
    }

    $fields = array(
        'registration_ids' => $keys,
        'notification' => array(
            'title' => $title,
            'body' => $message,
            'sound'=>'default'
        ),
        'priority' => 'high',
        'data'=>addFlutterData(strlen($title))
    );

    $data['status'] = Notify(json_encode($fields));
    //$data['param'] = "$username-$dest-$title-$message";
    return $return["data"] = $data;
}

function sendUsers(Mysql $sql, $account_id=[], $dest=null, $title = "", $message = "")
{
    $return = array();
    $data = array();

    if (strlen($title) < 3 || strlen($message) < 3) {
        $title = isset($_POST["title"]) ? $_POST["title"] : $title;
        $message = isset($_POST["message"]) ? $_POST["message"] : $message;
    }

    if (strlen($title) < 3 || strlen($message) < 3) {
        $data['error'] = "no-content-to-send";
        $return['data'] = $data;
        return $return;
    }
    if ($account_id === null || count($account_id) < 1) {
        $data['error'] = "no-accounts-valid";
        $return['data'] = $data;
        return $return;
    }

    if ($dest===null || strlen($dest) < 5)
        $dest = isset($_POST["dest"]) ? $_POST["dest"] : $dest;

    $keys = array();
    try {
        $tokens = $sql->clean_extra()->where_in('account_id', join(",",$account_id))->and_where("dest", $dest)->and_where("enabled", enumStore::$yes)->get(table('notify'), array("token"));
        if ($sql->num_rows() > 0) {
            $sql->forceArray($tokens);
            foreach ($tokens as $token) {
                array_push($keys, $token['token']);
            }
        } else {
            $data['error'] = "no-account-tokens";
            $return['data'] = $data;
            return $return;
        }
    } catch (Exception $e) {
        $data['error'] = $e->getMessage();
        $return['data'] = $data;
        return $return;
    }

    $fields = array(
        'registration_ids' => $keys,
        'notification' => array(
            'title' => $title,
            'body' => $message,
            'sound'=>'default'
        ),
        'priority' => 'high',
        'data'=>addFlutterData(strlen($title))
    );

    $data['status'] = Notify(json_encode($fields));
    //$data['param'] = "(".join(",",$account_id).")-$title-$message";
    return $return["data"] = $data;
}

function Notify($fields){
    $headers = array(
        "Authorization: key=" . GOOGLE_API_KEY,
        "Content-Type:application/json"
    );
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, GOOGLE_FCM_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function addFlutterData($id=1){
    return [
        "click_action"=>"FLUTTER_NOTIFICATION_CLICK",
        "id"=>"$id",
        "status"=>"done"
    ];
}
