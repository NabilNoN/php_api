<?php

function strStart($string, $query)
{
    return substr($string, 0, strlen($query)) === $query;
}

function getError($error_index){
    $errors=[0=>"no_port",1=>"no_method",2=>"unknown_method",3=>"need_data",4=>"new_user_insert_error",5=>"duplicate_data",6=>"login_field",
        7=>"try_again",8=>"logout_field",9=>"session_expired",10=>"verify_error",11=>"need_keywords"];
    if (isset($errors[$error_index])) {
        return $errors[$error_index];
    }else return $error_index;
}

function getVerify($digits=4){
    return str_pad(rand(0, pow(10, $digits)-1), $digits, '0', STR_PAD_LEFT);
}

function sendMail($from,$to,$subject,$htmlTitle,$htmlMsg){
// To send HTML mail, the Content-type header must be set
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

// Create email headers
    $headers .= 'From: '.$from."\r\n".
        'Reply-To: '.$from."\r\n" .
        'X-Mailer: PHP/' . phpversion();

// Compose a simple HTML email message
    $message = '<html><body>';
    $message .= '<div style="color:#000b63;">' .$htmlTitle.'</div>';
    $message .= '<div style="color:#00b7e8;font-size:18px;">' .$htmlMsg.'</div>';
    $message .= '</body></html>';

// Sending email
    return mail($to, $subject, $message, $headers);
}

function asArray($sql_num_rows,&$data){
    $tmp_0=[];
    if($sql_num_rows===1)$tmp_0[]=$data;
    elseif($sql_num_rows>1)$tmp_0=$data;
    $data=$tmp_0;
    return $tmp_0;
}

function isValidJSON($str) {
    json_decode($str);
    return json_last_error() == JSON_ERROR_NONE;
}

///0555555555 => +966555555555
/**
 * @param $phone string 0555555555 => (+966555555555) || 00966555555555 => (+966555555555) || 555555555 => (+966555555555)
 * @param string $countryCode +966 || +967 || +xxx
 * @param string $phoneStart 5 || 7 || 9 || .....
 * @param int $phoneLength including $phoneStart without zero at first if found
 * @param bool $isLeadZero true if has zero at first
 * @return bool|string if valid phone return phone else return false
 */
function optimisePhone(&$phone,$countryCode="+966",$phoneStart="5",$phoneLength=9,$isLeadZero=true)
{
    $phoneStartLeading=$isLeadZero?"0$phoneStart":"$phoneStart";
    $countryCodeZero=str_replace("+","00",$countryCode);
    if (!$phone || strlen($phone) < $phoneLength) return false;
    $matches = array();
    if (strStart($phone, $countryCodeZero) && strlen($phone) === ($phoneLength + strlen($countryCodeZero))) {
        $phone = substr($phone, strlen($countryCodeZero));
    } else if ($isLeadZero && strlen($phone) === ($phoneLength+1) && strStart($phone, $phoneStartLeading)) {
        $phone = substr($phone, 1);
    } else if (preg_match_all("/[^0-9+]/", $phone, $matches)) {
        if (count($matches) > 0) {
            return false;
        }
    }
    if (strlen($phone) === $phoneLength && strStart($phone, $phoneStart)) {
        $phone = $countryCode . $phone;
        return $phone;
    } else if (strStart($phone, $countryCode.$phoneStart) && strlen($phone) === ($phoneLength+strlen($countryCode))) {
        return $phone;
    } else return false;
}

/**
 * @param $phone string as 555555555 format
 * @param string $countryCode +966 || +967 || +xxx
 * @param string $phoneStart 5 || 7 || 9 || .....
 * @param int $phoneLength including $phoneStart without zero at first if found
 * @param bool $isLeadZero true if has zero at first
 * @return bool|string if valid phone return phone else return false
 */
function optimisePhoneNoCode(&$phone,$countryCode="+966",$phoneStart="5",$phoneLength=9,$isLeadZero=true)
{
    if (optimisePhone($phone,$countryCode,$phoneStart,$phoneLength,$isLeadZero)) {
        $phone = substr($phone, strlen($countryCode));
        return $phone;
    } else return false;
}

function newError($error_index,$info=""){
    return newData([ERROR => getError($error_index).($info !== null && strlen($info)>1?"-$info":"")]);
}

function newDoneData(bool $isDone){
    return newData([DONE=>$isDone]);
}

function newDataList($data){
    return newData([ARRAY_LIST=>$data]);
}

function newData($data){
    return [DATA => $data];
}

function table($table){
    $table=strtolower($table);
    $tbl = "table_";
    if(strStart($table, $tbl))return $table;
    else return $tbl .$table;
}

class enumStore{
    static $market="MARKET";
    static $clint="CLINT";
    static $in_market="IN_MARKET";
    static $in_house="IN_HOUSE";
    static $yes="Y";
    static $no="N";
}
