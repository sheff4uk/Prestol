<?
	include "../config.php";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<script src="../js/jquery-1.11.3.min.js"></script>
	<title>Ценники</title>

	<style>
		body {
			margin: 0;
			font-family: Liberation serif, Arial;
			width: 800px;
			margin: auto;
			color: #653333;
		}
		.label-wr {
			display: inline-block;
			width: 800px;
			height: 560px;
			border-width: 5px;
			border-color: #edc248;
			border-style: outset;
			box-sizing: border-box;
		}
		.code {
			background: #653333;
			color: #fff;
			border-radius: 10px;
			border: 3px solid #653333;
			font-size: 1.5em;
			font-weight: bold;
			display: inline-block;
			position: absolute;
			top: 10px;
			right: 10px;
		}
		.prod {
			display: inline-block;
			white-space: nowrap;
			margin: 10px 0;
			height: 80px;
			line-height: 80px;
			font-weight: bold;
		}
		.price_wr {
			background: #edc248;
			height: 140px;
			text-align: center;
		}
		.price {
			color: #C00000;
			font-size: 100px;
			font-weight: bold;
			display: block;
			text-shadow: 3px 3px 5px #640;
		}
	</style>
	<script>
		function fontSize(elem, maxFontSize) {
			var fontSize = $(elem).attr('fontSize');
			var width = $(elem).width();
			var bodyWidth = $(elem).parent().width();
			var multiplier = bodyWidth / width;
			fontSize = Math.floor(fontSize * multiplier);
			if( fontSize > maxFontSize ) fontSize = maxFontSize;
			$(elem).css({fontSize: fontSize+'px'});
			$(elem).attr('fontSize', fontSize);
		}
	</script>
</head>
<body>
<?
	// Собираем идентификаторы изделий и прочего
	$ODD_IDs = 0;
	$ODB_IDs = 0;

	foreach ($_GET["odd"] as $k => $v) {
		$ODD_IDs .= ",{$v}";
	}
	foreach ($_GET["odb"] as $k => $v) {
		$ODB_IDs .= ",{$v}";
	}
?>
	<?
	$query = "
		SELECT CONCAT('odd', ODD.ODD_ID) id
				,OD.Code
				,IFNULL(PM.Model, 'Столешница') product
				,IFNULL(CONCAT(' ', PME.full_mech, IF(ODD.box = 1, '+ящик', '')), '') mechanism
				,IFNULL(CONCAT(IF(ODD.Width > 0, '', 'Ø'), ODD.Length, IFNULL(CONCAT('(+', IFNULL(CONCAT(ODD.PieceAmount, 'x'), ''), ODD.PieceSize, ')'), ''), IFNULL(CONCAT('х', ODD.Width), ''), ' мм'), '') size
				,IFNULL(PM.materials, '') materials
				,ODD.PieceAmount
				,ODD.Price
		FROM OrdersDataDetail ODD
		JOIN OrdersData OD ON OD.OD_ID = ODD.OD_ID
		LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
		LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
		WHERE ODD.ODD_ID IN ($ODD_IDs)

		UNION ALL

		SELECT CONCAT('odb', ODB.ODB_ID) id
				,OD.Code
				,IFNULL(BL.Name, ODB.Other) product
				,''
				,''
				,''
				,''
				,ODB.Price
		FROM OrdersDataBlank ODB
		JOIN OrdersData OD ON OD.OD_ID = ODB.OD_ID
		LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
		WHERE ODB.ODB_ID IN ($ODB_IDs)
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		$price = number_format($row["Price"], 0, '', ' ');
	?>
		<div id="<?=$row["id"]?>" class="label-wr">
			<div style="position: relative;">
				<img src="../img/logo.png" style="height: 171px; margin: auto; display: block;">
				<div class="code"><?=$row["Code"]?></div>
			</div>
			<div style="text-align: center; text-shadow: 3px 3px 5px #666;">
				<div class="prod" style="font-size: 24px;" fontSize="24"><?=$row["product"]?></div>
			</div>
			<div style="display: flex; height: 139px; font-size:26px; white-space: nowrap;">
				<div style="width: 25%; text-align: center; color: #C00000; text-shadow: 3px 3px 5px #666;">
					<?=($row["PieceAmount"] == 2 ? "<br>ДВЕ<br>ВСТАВКИ" : "")?>
					<?=($row["PieceAmount"] == 3 ? "<br>ТРИ<br>ВСТАВКИ" : "")?>
				</div>
				<div style="width: 25%; text-align: right; padding: 5px;">
					<?=($row["mechanism"] ? "<i>Механизм</i><br>" : "")?>
					<?=($row["size"] ? "<i>Размер</i><br>" : "")?>
					<?=($row["materials"] ? "<i>Материалы</i><br>" : "")?>
					<?=($row["size"] ? "<i>Поверхность</i><br>" : "")?>
				</div>
				<div style="width: 50%; text-align: left; border: 2px dotted #edc248; background: #f9da90; padding: 5px; overflow: hidden; text-overflow: ellipsis;">
					<?=($row["mechanism"] ? "<i>{$row["mechanism"]}</i><br>" : "")?>
					<?=($row["size"] ? "<i>{$row["size"]}</i><br>" : "")?>
					<?=($row["materials"] ? "<i>{$row["materials"]}</i><br>" : "")?>
					<?=($row["size"] ? "<i style='color: #C00000;'>ПЛАСТИК (ТЕРМОСТОЙКИЙ)</i><br>" : "")?>
				</div>
			</div>
			<div class="price_wr">
				<span class="price"><?=$price?> Р</span>
				<b>РОССИЯ</b><b style="color: #C00000;"> / </b><b>КИРОВ</b>
			</div>
		</div>
		<script>
			fontSize('#<?=$row["id"]?> .prod', 80);
		</script>
	<?
	}
?>
</body>
</html>
