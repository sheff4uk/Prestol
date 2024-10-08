<?php
	ini_set('display_errors', 1);
	error_reporting(E_ALL);
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Этикетки на упаковку</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<script src="js/jquery-1.11.3.min.js"></script>
<style>
	body {
		margin: 0;
		font-family: Arial;
		width: 1200px;
		margin: auto;
		/*font-size: 0;*/
	}
	.label-wr {
		display: inline-block;
		width: 600px;
		height: 400px;
		border: 1px dashed #999;
		padding: 10px;
		box-sizing: border-box;
	}
	img.logo {
		width: 100px;
		margin: 5px;
	}
	.head-wr {
		border: 1px solid #999;
		text-align: center;
		overflow: hidden;
		height: 95px;
		display: flex;
	}
	.mfr {
		font-size: 16px;
		margin: 5px;
		line-height: 22px;
	}
	.body-wr {
		border: 1px solid #999;
		text-align: center;
		overflow: hidden;
		height: calc(100% - 100px);
		font-size: 0px;
	}
	.prod {
		display: inline-block;
		white-space: nowrap;
		margin: 10px 0;
		height: 40px;
		line-height: 40px;
		font-weight: bold;
	}
	.body-wr p {
		font-size: 12px;
		margin: 0;
		margin-top: 20px;
	}
	.material, .color, .box, .date, .number {
		display: inline-block;
		margin: 5px 0;
		white-space: nowrap;
		height: 20px;
		line-height: 20px;
	}
	.half-block {
		display: inline-block;
		width: 33%;
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
	include "config.php";

	foreach ($_POST["prod"] as $key => $value) {
		?>
		<div class="label-wr prod<?=$key?>">
			<div class="head-wr">
				<img src="/img/logo_black.png" class="logo">
				<div class="mfr">
					Изготовитель: ООО «Престол», г.Киров ул.Луганская 59<br>
					8(909)131-77-32<br>
					fabrikaprestol@gmail.com<br>
					fabrikaprestol.ru
				</div>
			</div>
			<div class="body-wr">
				<div class="half-block">
					<p>Код:</p>
					<div class="number" style="font-size: 40px; font-weight: bold;"><?=$_POST["code"][$key]?></div>
				</div>
				<div class="half-block">
					<p><?=$_POST["amount_label"][$key]?>:</p>
					<div class="box" style="font-size: 32px;"><?=$_POST["amount"][$key]?></div>
				</div>
				<div class="half-block">
					<p>Дата изготовления:</p>
					<div class="date" style="font-size: 32px;"><?=$_POST["date"]?></div>
				</div>
				<div style="margin-bottom: -15px;">
					<p>Изделие:</p>
					<div class="prod" style="font-size: 24px;" fontSize="24"><?=$value?></div>
				</div>
				<div>
					<p><?=$_POST["mat_label"][$key]?>:</p>
					<div class="material" style="font-size: 16px;" fontSize="16"><?=$_POST["mat"][$key]?></div>
					<p>Цвет:</p>
					<div class="color" style="font-size: 16px;" fontSize="16"><?=$_POST["color"][$key]?></div>
				</div>
			</div>
		</div>
		<script>
				fontSize('.prod<?=$key?> .prod', 40);
				fontSize('.prod<?=$key?> .material', 24);
				fontSize('.prod<?=$key?> .color', 24);
				fontSize('.prod<?=$key?> .box', 24);
				fontSize('.prod<?=$key?> .date', 24);
				fontSize('.prod<?=$key?> .number', 24);
		</script>
		<?php
	}
?>
<script>
	$(function(){
		//fontSize('.mfr', 18);
		$('body').css('font-size', '0');
	});
</script>
</body>
</html>
