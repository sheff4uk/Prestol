<?
//phpinfo();

ini_set('display_errors', 1);
error_reporting(E_ALL);

$from = "admin@fabrikaprestol.ru";
$subject = "[КИС Престол] Регистрация нового пользователя";//тема сообщения
$message = "В Корпоративной Информационной Системе ПРЕСТОЛ зарегистрирован новый пользователь";//содержание сообщения

// Отправляем письмо администратору для активации пользователя через PHPMailer
require "PHPMailer/PHPMailerAutoload.php";
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->SMTPAuth = true;
$mail->SMTPSecure = "ssl";
$mail->Host = "smtp.yandex.ru";
$mail->Port = "465";
$mail->Username = "admin@fabrikaprestol.ru";
$mail->Password = "gybyrcAMRwH6YiF";
$mail->CharSet = "UTF-8";
$mail->ContentType = 'text/plain';
$mail->addAddress($from);
$mail->addReplyTo($from);
$mail->setFrom($from);
$mail->Subject = $subject;
$mail->Body = $message;

$mail->send(); //отправляем сообщение
?>
