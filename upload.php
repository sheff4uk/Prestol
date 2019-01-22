<?
include "config.php";
session_start();

// Удаляем старые файлы
$expire_time = 31536000; // Время через которое файл считается устаревшим (в сек.)
$dir = $_SERVER['DOCUMENT_ROOT']."/uploads/";
// проверяем, что $dir - каталог
if (is_dir($dir)) {
	// открываем каталог
	if ($dh = opendir($dir)) {
		// читаем и выводим все элементы
		// от первого до последнего
		while (($file = readdir($dh)) !== false) {
			// текущее время
			$time_sec=time();
			// время изменения файла
			$time_file=filemtime($dir . $file);
			// тепрь узнаем сколько прошло времени (в секундах)
			$time=$time_sec-$time_file;

			$unlink = $dir.$file;

			if (is_file($unlink)){
				if ($time>$expire_time){
					unlink($unlink);
				}
			}
		}
		// закрываем каталог
		closedir($dh);
	}
}

// Каталог, в который мы будем принимать файл:
$filename = date('U').'_'.$_FILES['uploadfile']['name'];
$uploaddir = './uploads/';
$uploadfile = $uploaddir.basename($filename);

// Копируем файл из каталога для временного хранения файлов:
if (copy($_FILES['uploadfile']['tmp_name'], $uploadfile))
{
//	// Записываем в БД информацию о файле
//	$comment = convert_str($_POST["comment"]);
//	$comment = mysqli_real_escape_string($mysqli, $comment);
//	$query = "INSERT INTO OrdersAttachments SET OD_ID = {$_POST["odid"]}, filename = '{$filename}', comment = '{$comment}'";
//	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//
//	$_SESSION["success"][] = "Файл ".$_FILES['uploadfile']['name']." успешно загружен на сервер.";
}
else {
	$_SESSION["alert"][] = "Ошибка! Не удалось загрузить файл на сервер!";
}

exit ('<meta http-equiv="refresh" content="0; url=/orderdetail.php?id='.$_POST["odid"].'&tabs=2">');
?>
