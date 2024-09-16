<?php
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
			border-color: #e3d600;
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
			left: 10px;
			-webkit-print-color-adjust: exact;
    		print-color-adjust: exact;
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
			background: #e3d600;
			height: 120px;
			text-align: center;
			position: relative;
		}
		.price {
			color: #C00000;
			font-size: 100px;
			font-weight: bold;
			display: block;
			text-shadow: 3px 3px 5px #640;
			line-height: 90px;
			padding-top: 15px;
		}
		.discount {
			background: red;
			width: 200px;
			padding: 5px;
			border-radius: 10px;
			color: white;
			font-size: 2.5em;
			font-weight: bold;
			transform: rotate(-5deg);
			position: absolute;
			top: 15px;
			right: 40px;
			box-shadow: 3px 3px 5px #640;
		}
		.old_price {
			display: inline;
			color: black;
			margin-right: 50px;
			position: relative;
		}
		.old_price:before {
			border-bottom: 7px solid #C00000;
			position: absolute;
			content: "";
			width: 100%;
			height: 42%;
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
<?php
	// Формируем ценнки на наборы
	foreach ($_GET["od"] as $k => $od_id) {
		$query = "
			SELECT OD.Code
				,SUM((ODD.Price - IFNULL(ODD.discount, 0)) * ODD.Amount) Price
				,SUM(ODD.Price * ODD.Amount) old_Price
				,SUM(IFNULL(ODD.discount, 0) * ODD.Amount) discount
				,ROUND((SUM(IFNULL(ODD.discount, 0) * ODD.Amount) * 100) / SUM(ODD.Price * ODD.Amount)) percent
				,GROUP_CONCAT(ODD.ODD_ID) ODDs
				,SUM(1) count
			FROM OrdersData OD
			JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
			WHERE OD.OD_ID = $od_id
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$Code = mysqli_result($res,0,'Code');
		$price = number_format(mysqli_result($res,0,'Price'), 0, '', ' ');
		$ODD_IDs = mysqli_result($res,0,'ODDs');
		$count = mysqli_result($res,0,'count');

		if( mysqli_result($res,0,'discount') ) {
			$old_price = number_format(mysqli_result($res,0,'old_Price'), 0, '', ' ');
			$discount = number_format(mysqli_result($res,0,'discount'), 0, '', ' ');
			$old_price = "<div class='old_price'>{$old_price}</div>";
			$discount = "<div class='discount'>Выгода<br><span style='white-space: nowrap;'>{$discount} руб!</span></div>";
		}
		else {
			$old_price = "";
			$discount = "";
		}
	?>
		<div class="label-wr">
			<div style="position: relative; text-align: center;">
				<img src="../img/logo.png" style="height: 142px; margin: 5px;">
				<div class="code"><?=$Code?></div>
				<?=$discount?>
			</div>
			<div style="text-align: center; text-shadow: 3px 3px 5px #666;">
				<div class="prod" style="font-size: 50px;">Мебельный гарнитур:</div>
			</div>
			<div style="display: flex;">

			<?php
			// Генерируем содержимое набора
			$query = "
				SELECT CONCAT('g_odd', ODD.ODD_ID) id
						,OD.Code
						,CL.color
						,IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.Model, 'Столешница'), IFNULL(BL.Name, ODD.Other)) product
						,ODD.Amount
						,CONCAT(IF(SH.Shipper LIKE '%=%', '', SH.Shipper), ' ', MT.Material) material
						,SH.mtype
						,IFNULL(CONCAT(' ', PME.full_mech, IF(ODD.box = 1, '+ящик', '')), '') mechanism
						,IFNULL(CONCAT(IF(ODD.Width > 0, '', 'Ø'), ODD.Length, IFNULL(CONCAT('(+', IFNULL(CONCAT(ODD.PieceAmount, 'x'), ''), ODD.PieceSize, ')'), ''), IFNULL(CONCAT('х', ODD.Width), ''), ' мм'), '') size
						,IFNULL(PM.materials, '') materials
						,ODD.PieceAmount
				FROM OrdersDataDetail ODD
				JOIN OrdersData OD ON OD.OD_ID = ODD.OD_ID
				LEFT JOIN Colors CL ON CL.CL_ID = OD.CL_ID
				LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
				LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
				LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
				LEFT JOIN Shippers SH ON SH.SH_ID = MT.SH_ID
				LEFT JOIN BlankList BL ON BL.BL_ID = ODD.BL_ID
				WHERE ODD.ODD_ID IN ($ODD_IDs)
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
			?>
				<div id="<?=$row["id"]?>" style="height: 178px; font-size:<?=round(30/($count == 1 ? 2 : $count), 0)?>px; white-space: nowrap; width: <?=round(100/$count, 4)?>%;">
					<div style="text-align: center; text-shadow: 3px 3px 5px #666;">
						<div class="prod" style="font-size: 24px; height: 55px; line-height: 55px;" fontSize="24">&nbsp;&nbsp;&nbsp;<?=$row["product"]?><?=($row["Amount"] > 1 ? " {$row["Amount"]} шт." : "")?>&nbsp;&nbsp;&nbsp;</div>
					</div>
					<div style="display: flex; height: 103px;">
						<div style="width: 18%; text-align: center; color: #C00000; text-shadow: 3px 3px 5px #666;">
							<?=($row["PieceAmount"] == 2 ? "<br>ДВЕ<br>ВСТАВКИ" : "")?>
							<?=($row["PieceAmount"] == 3 ? "<br>ТРИ<br>ВСТАВКИ" : "")?>
						</div>
						<div style="width: 22%; text-align: right; padding: 5px;">
							<?=($row["size"] ? "<i>Размер</i><br>" : "")?>
							<?=($row["mechanism"] ? "<i>Механизм</i><br>" : "")?>
							<?=($row["materials"] ? "<i>Материалы</i><br>" : "")?>
<!--							<?=($row["color"] ? "<i>Цвет краски</i><br>" : "")?>-->
<!--							<?=($row["mtype"] == 1 ? "<i>Ткань</i><br>" : ($row["mtype"] == 2 ? "<i>Поверхность</i><br>" : ""))?>-->
						</div>
						<div style="width: 60%; text-align: left; border: 2px dotted #5dc140; background: #a3f496; padding: 5px; overflow: hidden; text-overflow: ellipsis;">
							<?=($row["size"] ? "<i>{$row["size"]}</i><br>" : "")?>
							<?=($row["mechanism"] ? "<i>{$row["mechanism"]}</i><br>" : "")?>
							<?=($row["materials"] ? "<i>{$row["materials"]}</i><br>" : "")?>
<!--							<?=($row["color"] ? "<i>{$row["color"]}</i><br>" : "")?>-->
<!--							<?=($row["mtype"] == 1 ? "<i>{$row["material"]}</i><br>" : ($row["mtype"] == 2 ? "<i>{$row["material"]}</i><br><div style='text-align: right;'><i style='color: #C00000; font-size: .6em;'>ТЕРМОСТОЙКИЙ ПЛАСТИК</i></div>" : ""))?>-->
						</div>
					</div>
				</div>
				<script>
					fontSize('#<?=$row["id"]?> .prod', 80);
				</script>
			<?php
			}
			?>
			</div>
			<div class="price_wr">
				<span class="price"><?=$old_price?><?=$price?></span>
				<b>РОССИЯ</b><b style="color: #C00000;"> / </b><b>КИРОВ</b>
			</div>
		</div>
	<?php
	}

	// Собираем идентификаторы изделий и прочего
	$ODD_IDs = 0;

	foreach ($_GET["odd"] as $k => $v) {
		$ODD_IDs .= ",{$v}";
	}

	// Формируем ценники на товары
	$query = "
		SELECT CONCAT('odd', ODD.ODD_ID) id
				,OD.Code
				,CL.color
				,IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.Model, 'Столешница'), IFNULL(BL.Name, ODD.Other)) product
				,CONCAT(IF(SH.Shipper LIKE '%=%', '', SH.Shipper), ' ', MT.Material) material
				,SH.mtype
				,IFNULL(CONCAT(' ', PME.full_mech, IF(ODD.box = 1, '+ящик', '')), '') mechanism
				,IFNULL(CONCAT(IF(ODD.Width > 0, '', 'Ø'), ODD.Length, IFNULL(CONCAT('(+', IFNULL(CONCAT(ODD.PieceAmount, 'x'), ''), ODD.PieceSize, ')'), ''), IFNULL(CONCAT('х', ODD.Width), ''), ' мм'), '') size
				,IFNULL(PM.materials, '') materials
				,ODD.PieceAmount
				,(ODD.Price - IFNULL(ODD.discount, 0)) Price
				,ODD.Price old_Price
				,IFNULL(ODD.discount, 0) discount
				,ROUND((ODD.discount * 100) / ODD.Price) percent
		FROM OrdersDataDetail ODD
		JOIN OrdersData OD ON OD.OD_ID = ODD.OD_ID
		LEFT JOIN Colors CL ON CL.CL_ID = OD.CL_ID
		LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
		LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
		LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
		LEFT JOIN Shippers SH ON SH.SH_ID = MT.SH_ID
		LEFT JOIN BlankList BL ON BL.BL_ID = ODD.BL_ID
		WHERE ODD.ODD_ID IN ($ODD_IDs)
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		$price = number_format($row["Price"], 0, '', ' ');

		if($row["discount"]) {
			$old_price = number_format($row["old_Price"], 0, '', ' ');
			$discount = number_format($row["discount"], 0, '', ' ');
			$old_price = "<div class='old_price'>{$old_price}</div>";
			$discount = "<div class='discount'>Выгода<br><span style='white-space: nowrap;'>{$discount} руб!</span></div>";
		}
		else {
			$old_price = "";
			$discount = "";
		}
	?>
		<div id="<?=$row["id"]?>" class="label-wr">
			<div style="position: relative; text-align: center;">
				<img src="../img/logo.png" style="height: 142px; margin: 5px;">
				<div class="code"><?=$row["Code"]?></div>
				<?=$discount?>
			</div>
			<div style="text-align: center; text-shadow: 3px 3px 5px #666;">
				<div class="prod" style="font-size: 24px;" fontSize="24"><?=$row["product"]?></div>
			</div>
			<div style="display: flex; height: 178px; font-size:30px; white-space: nowrap;">
				<div style="width: 18%; text-align: center; color: #C00000; text-shadow: 3px 3px 5px #666;">
					<?=($row["PieceAmount"] == 2 ? "<br>ДВЕ<br>ВСТАВКИ" : "")?>
					<?=($row["PieceAmount"] == 3 ? "<br>ТРИ<br>ВСТАВКИ" : "")?>
				</div>
				<div style="width: 22%; text-align: right; padding: 5px;">
					<?=($row["size"] ? "<i>Размер</i><br>" : "")?>
					<?=($row["mechanism"] ? "<i>Механизм</i><br>" : "")?>
					<?=($row["materials"] ? "<i>Материалы</i><br>" : "")?>
<!--					<?=($row["color"] ? "<i>Цвет краски</i><br>" : "")?>-->
<!--					<?=($row["mtype"] == 1 ? "<i>Ткань</i><br>" : ($row["mtype"] == 2 ? "<i>Поверхность</i><br>" : ""))?>-->
				</div>
				<div style="width: 60%; text-align: left; border: 2px dotted #5dc140; background: #a3f496; padding: 5px; overflow: hidden; text-overflow: ellipsis;">
					<?=($row["size"] ? "<i>{$row["size"]}</i><br>" : "")?>
					<?=($row["mechanism"] ? "<i>{$row["mechanism"]}</i><br>" : "")?>
					<?=($row["materials"] ? "<i>{$row["materials"]}</i><br>" : "")?>
<!--					<?=($row["color"] ? "<i>{$row["color"]}</i><br>" : "")?>-->
<!--					<?=($row["mtype"] == 1 ? "<i>{$row["material"]}</i><br>" : ($row["mtype"] == 2 ? "<i>{$row["material"]}</i><br><div style='text-align: right;'><i style='color: #C00000; font-size: .6em;'>ТЕРМОСТОЙКИЙ ПЛАСТИК</i></div>" : ""))?>-->
				</div>
			</div>
			<div class="price_wr">
				<span class="price"><?=$old_price?><?=$price?></span>
				<b>РОССИЯ</b><b style="color: #C00000;"> / </b><b>КИРОВ</b>
			</div>

		</div>
		<script>
			fontSize('#<?=$row["id"]?> .prod', 80);
		</script>
	<?php
	}
?>
</body>
</html>
