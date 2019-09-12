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
		}
		.label-wr {
			display: inline-block;
			width: 800px;
			height: 290px;
			box-sizing: border-box;
			border-top: 1px dashed;
			border-bottom: 1px dashed;
		}
		.code {
			border-radius: 10px;
			border: 2px solid;
			font-weight: bold;
			display: inline-block;
			position: absolute;
			top: 5px;
			left: 5px;
			padding: 2px;
			opacity: .5;
		}
		.prod {
			display: inline-block;
			white-space: nowrap;
			margin: 10px 0;
			height: 50px;
			line-height: 70px;
			font-weight: bold;
		}
		.price_wr {
			height: 100px;
			text-align: center;
			position: relative;
			white-space: nowrap;
		}
		.price {
			font-size: 90px;
			font-weight: bold;
			display: block;
			text-shadow: 3px 3px 5px #666;
			line-height: 100px;
		}
		.discount {
			padding: 5px;
			border-radius: 10px;
			font-weight: bold;
			position: absolute;
			top: 5px;
			right: 5px;
			box-shadow: 3px 3px 5px #666;
			border: 1px solid #666;
		}
		.old_price {
			display: inline;
			margin-right: 50px;
			position: relative;
			text-decoration: line-through;
			color: #ccc;
			text-shadow: 3px 3px 5px #000, -3px 3px 5px #000, -3px -3px 5px #000, 3px -3px 5px #000;
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

	foreach ($_GET["odd"] as $k => $v) {
		$ODD_IDs .= ",{$v}";
	}

	// Формируем ценники на товары
	$query = "
		SELECT CONCAT('odd', ODD.ODD_ID) id
				,OD.Code
				,CL.color
				,IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.Model, 'Столешница'), IFNULL(BL.Name, ODD.Other)) product
				,CONCAT(IF(SH.Shipper LIKE '%=%', '', CONCAT(SH.Shipper, ' ')), MT.Material) material
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
			$discount = "<div class='discount'>Выгода {$discount} руб!</div>";
		}
		else {
			$old_price = "";
			$discount = "";
		}
	?>
		<div id="<?=$row["id"]?>1" class="label-wr">
			<div style="width: 70%;">
				<div style="position: relative; text-align: center;">
					<div class="code"><?=$row["Code"]?></div>
					<?=$discount?>
				</div>
				<div style="text-align: center; text-shadow: 3px 3px 5px #666;">
					<div class="prod" style="font-size: 24px;" fontSize="24"><?=$row["product"]?></div>
				</div>
				<div class="price_wr">
					<span class="price"><?=$old_price?><?=$price?></span>
				</div>
				<div style="display: flex; height: 120px; font-size:22px; white-space: nowrap;">
					<div style="width: 40%; text-align: right; padding: 5px;">
						<?=($row["size"] ? "<i>Размер столешницы</i><br>" : "")?>
						<?=($row["mechanism"] ? "<i>Механизм</i><br>" : "")?>
						<?=($row["materials"] ? "<i>Материалы</i><br>" : "")?>
						<?=($row["mtype"] == 1 ? "<i>Ткань</i>" : ($row["mtype"] == 2 ? "<i>Поверхность (пластик)</i>" : ""))?>
					</div>
					<div style="width: 60%; text-align: left; border: 2px dotted; padding: 5px; overflow: hidden; text-overflow: ellipsis;">
						<?=($row["size"] ? "<i>{$row["size"]}</i><br>" : "")?>
						<?=($row["mechanism"] ? "<i>{$row["mechanism"]}</i><br>" : "")?>
						<?=($row["materials"] ? "<i>{$row["materials"]}</i><br>" : "")?>
						<?=($row["mtype"] == 1 ? "<i>{$row["material"]}</i>" : ($row["mtype"] == 2 ? "<i>{$row["material"]}</i>" : ""))?>
					</div>
				</div>
			</div>
		</div>
		<div id="<?=$row["id"]?>2" class="label-wr">
			<div style="width: 70%;">
				<div style="position: relative; text-align: center;">
					<div class="code"><?=$row["Code"]?></div>
					<?=$discount?>
				</div>
				<div style="text-align: center; text-shadow: 3px 3px 5px #666;">
					<div class="prod" style="font-size: 24px;" fontSize="24"><?=$row["product"]?></div>
				</div>
				<div class="price_wr">
					<span class="price"><?=$old_price?><?=$price?></span>
				</div>
				<div style="display: flex; height: 120px; font-size:22px; white-space: nowrap;">
					<div style="width: 40%; text-align: right; padding: 5px;">
						<?=($row["size"] ? "<i>Размер столешницы</i><br>" : "")?>
						<?=($row["mechanism"] ? "<i>Механизм</i><br>" : "")?>
						<?=($row["materials"] ? "<i>Материалы</i><br>" : "")?>
						<?=($row["mtype"] == 1 ? "<i>Ткань</i>" : ($row["mtype"] == 2 ? "<i>Поверхность (пластик)</i>" : ""))?>
					</div>
					<div style="width: 60%; text-align: left; border: 2px dotted; padding: 5px; overflow: hidden; text-overflow: ellipsis;">
						<?=($row["size"] ? "<i>{$row["size"]}</i><br>" : "")?>
						<?=($row["mechanism"] ? "<i>{$row["mechanism"]}</i><br>" : "")?>
						<?=($row["materials"] ? "<i>{$row["materials"]}</i><br>" : "")?>
						<?=($row["mtype"] == 1 ? "<i>{$row["material"]}</i>" : ($row["mtype"] == 2 ? "<i>{$row["material"]}</i>" : ""))?>
					</div>
				</div>
			</div>
		</div>
		<script>
			fontSize('#<?=$row["id"]?>1 .prod', 45);
			fontSize('#<?=$row["id"]?>2 .prod', 45);
		</script>
	<?
	}
?>
</body>
</html>
