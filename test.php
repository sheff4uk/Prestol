<?
phpinfo();

//// Преобразование удаленных изделий
//include "config.php";
//
//$query = "
//	SELECT ODD.ODD_ID, ODD.OD_ID, OCL.date_time, OCL.author
//	FROM OrdersDataDetail ODD
//	JOIN OrdersData OD ON OD.OD_ID = ODD.OD_ID AND OD.DelDate IS NULL
//	JOIN OrdersChangeLog OCL ON OCL.table_value = ODD.ODD_ID AND OCL.table_key LIKE 'ODD_ID' AND OCL.OFN_ID = 18
//	WHERE ODD.Del = 1
//";
//$res = mysqli_query( $mysqli, $query ) or die("Invalid query0: " .mysqli_error( $mysqli ));
//while( $row = mysqli_fetch_array($res) ) {
//	// Создание копии набора
//	$query = "INSERT INTO OrdersData(Code, SH_ID, ClientName, ul, mtel, address, AddDate, StartDate, EndDate, DelDate, OrderNumber, CL_ID, IsPainting, WD_ID, Comment, IsReady, author, confirmed)
//	SELECT Code, SH_ID, ClientName, ul, mtel, address, AddDate, StartDate, EndDate, '{$row["date_time"]}', OrderNumber, CL_ID, IsPainting, WD_ID, Comment, IsReady, {$row["author"]}, confirmed FROM OrdersData WHERE OD_ID = {$row["OD_ID"]}";
//	mysqli_query( $mysqli, $query ) or die("Invalid query1: " .mysqli_error( $mysqli ));
//	$newOD_ID = mysqli_insert_id($mysqli);
//
//	// Записываем в журнал событие разделения набора удаление дубликата
//	$query = "INSERT INTO OrdersChangeLog SET table_key = 'OD_ID', table_value = {$row["OD_ID"]}, OFN_ID = 1, old_value = '{$newOD_ID}', new_value = '', date_time = '{$row["date_time"]}', author = {$row["author"]}";
//	mysqli_query( $mysqli, $query ) or die("Invalid query2: " .mysqli_error( $mysqli ));
//	$query = "INSERT INTO OrdersChangeLog SET table_key = 'OD_ID', table_value = {$newOD_ID}, OFN_ID = 1, old_value = '{$row["OD_ID"]}', new_value = '', date_time = '{$row["date_time"]}', author = {$row["author"]}";
//	mysqli_query( $mysqli, $query ) or die("Invalid query3: " .mysqli_error( $mysqli ));
//	$query = "INSERT INTO OrdersChangeLog SET table_key = 'OD_ID', table_value = {$newOD_ID}, OFN_ID = 18, old_value = '', new_value = '', date_time = '{$row["date_time"]}', author = {$row["author"]}";
//	mysqli_query( $mysqli, $query ) or die("Invalid query4: " .mysqli_error( $mysqli ));
//
//	// Переносим удаляемое изделие в отделенный контейнер
//	$query = "UPDATE OrdersDataDetail SET OD_ID = {$newOD_ID} WHERE ODD_ID = {$row["ODD_ID"]}";
//	mysqli_query( $mysqli, $query ) or die("Invalid query5: " .mysqli_error( $mysqli ));
//}
?>
