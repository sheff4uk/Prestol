<?
include "config.php";

//$json = file_get_contents('php://input');
//$data = json_decode($json, true);
//
//message_to_telegram("token: ".$_SERVER['Authorization'], '217756119');
//message_to_telegram("body: ".$json, '217756119');
message_to_telegram('test', '217756119');

//http_response_code(200);
header("HTTP/1.1 200 OK");
exit;
?>
