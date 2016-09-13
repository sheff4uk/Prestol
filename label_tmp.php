<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<script src="js/jquery-1.11.3.min.js"></script>
<style>
	body {
		margin: 0;
		font-family: Arial;
		width: 1200px;
		margin: auto;
		font-size: 0;
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
		width: 150px;
		margin: 1px;
	}
	.head-wr {
		border: 1px solid #999;
		text-align: center;
		overflow: hidden;
		height: 95px;
	}
	.mfr {
		display: inline-block;
		margin: 5px 0;
		white-space: nowrap;
		height: 30px;
		line-height: 30px;
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
	$(document).ready(function(){
		fontSize('.mfr', 18);
	});
</script>
</head>
<body>
<?
	foreach ($_POST["prod"] as $key => $value) {
		?>
		<div class="label-wr prod<?=$key?>">
			<div class="head-wr">
				<img src="/img/logo.jpg" class="logo">
				<div class="mfr" style="font-size: 16px;" fontSize="16" onload="fontSize(this, 18);">Изготовитель: ООО «Престол», г.Киров ул.Луганская 59, т.89091317732, сайт: фабрикастульев.рф</div>
			</div>
			<div class="body-wr">
				<div class="prod" style="font-size: 24px;" fontSize="24"><?=$value?></div>
				<div>
					<p><?=$_POST["mat_label"][$key]?>:</p>
					<div class="material" style="font-size: 16px;" fontSize="16"><?=$_POST["mat"][$key]?></div>
					<p>Цвет:</p>
					<div class="color" style="font-size: 16px;" fontSize="16"><?=$_POST["color"][$key]?></div>
				</div>
				<div class="half-block">
					<p><?=$_POST["amount_label"][$key]?>:</p>
					<div class="box" style="font-size: 16px;" fontSize="16"><?=$_POST["amount"][$key]?></div>
				</div>
				<div class="half-block">
					<p>Дата изготовления:</p>
					<div class="date" style="font-size: 16px;" fontSize="16"><?=$_POST["date"]?></div>
				</div>
				<div class="half-block">
					<p>Номер упаковки:</p>
					<div class="number" style="font-size: 16px;" fontSize="16"><?=$_POST["code"][$key]?></div>
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
		<?
	}
?>
</body>
</html>
