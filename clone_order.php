<?
include "config.php";
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('order_add', $Rights) and !in_array('order_add_free', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

	if( isset($_GET["id"]) ) {
		// Узнаём какие подразделения доступны пользователю при добавлении набора
		$SH_IDs = "";
		if( in_array('order_add_confirm', $Rights) or in_array('order_add_free', $Rights) ) {
			$SH_IDs .= "0,";
		}
		$query = "
			SELECT GROUP_CONCAT(SH.SH_ID) SH_IDs
				,MIN(IF(SH.retail = 1, SH.SH_ID, NULL)) first_SH_ID_retail
				,MIN(IF(SH.retail = 0, SH.SH_ID, NULL)) first_SH_ID_opt
			FROM Shops SH
			JOIN Cities CT ON CT.CT_ID = SH.CT_ID
			WHERE CT.CT_ID IN ({$USR_cities})
				".($USR_Shop ? "AND SH.SH_ID IN ({$USR_Shop})" : "")."
				".($USR_KA ? "AND SH.KA_ID = {$USR_KA}" : "")."
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$row = mysqli_fetch_array($res);
		$SH_IDs .= $row["SH_IDs"];
		$first_SH_ID .= $row["first_SH_ID_retail"] ? $row["first_SH_ID_retail"] : $row["first_SH_ID_opt"]; // Отдаём преимущество рознице

		// Создаём новый набор
		$AddDate = date("Y-m-d");
		$query = "
			INSERT INTO OrdersData(AddDate, SH_ID, CL_ID, author, confirmed)
			SELECT '{$AddDate}', IF(IFNULL(SH_ID, 0) IN ({$SH_IDs}), SH_ID, {$first_SH_ID}), CL_ID, {$_SESSION['id']}, ".(in_array('order_add_confirm', $Rights) ? 1 : 0)."
			FROM OrdersData
			WHERE OD_ID = {$_GET["id"]}
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$id = mysqli_insert_id( $mysqli );

		// Узнаем дату сдачи для опта
		$_GET["retail"] = 0;
		include "get_end_date.php";
		$EndDate = date_format($end_date, 'Y-m-d');

		// Если присвоилось оптовое подразделение, то ставим дату сдачи
		$query = "
			UPDATE OrdersData OD
			LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
			SET OD.EndDate = IF(SH.retail = 0, '{$EndDate}', OD.EndDate)
			WHERE OD.OD_ID = {$id}
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		// Копируем изделия из первоначального набора и вычисляем стоимость
		$query = "
			SELECT ODD_ID
			FROM OrdersDataDetail
			WHERE OD_ID = {$_GET["id"]}
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while ($row = mysqli_fetch_array($res)) {
			$query = "
				INSERT INTO OrdersDataDetail(OD_ID, PM_ID, BL_ID, Other, PF_ID, PME_ID, box, Length, Width, PieceAmount, PieceSize, piece_stored, PVC_ID, MT_ID, Amount, author, P_ID)
				SELECT {$id}, PM_ID, BL_ID, Other, PF_ID, PME_ID, box, Length, Width, PieceAmount, PieceSize, piece_stored, PVC_ID, MT_ID, Amount, {$_SESSION['id']}, P_ID
				FROM OrdersDataDetail
				WHERE ODD_ID = {$row["ODD_ID"]}
			";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$odd_id = mysqli_insert_id( $mysqli );

			// Вычисляем и записываем стоимость по прайсу
			$query = "CALL Price({$odd_id})";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}

		exit ('<meta http-equiv="refresh" content="0; url=orderdetail.php?id='.$id.'">');
		die;
	}
	else {
		exit ('<meta http-equiv="refresh" content="0; url=/">');
		die;
	}
?>
