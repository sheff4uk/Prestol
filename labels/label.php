<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Этикетки на упаковку</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<style>
	@media print {
		@page {
			size: landscape;
		}
	}
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
		width: 100%;
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
		margin: 20px 0;
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
</head>
<body style="font-size: 0px;">
<?php

	foreach ($_POST["prod"] as $key => $value) {
		?>
		<div class="label-wr">
			<div class="head-wr">
				<img src="../img/logo_black.png" class="logo">
				<div class="mfr">
					Продавец: ИП Шабалина Светлана Сергеевна<br>
					ИНН 434583098031<br>
					610000, г. Киров, пос. Дороничи, ул. 8ая улица, д. 6/1<br>
					fabrikaprestol.ru
				</div>
			</div>
			<div class="body-wr">
				<div class="half-block">
					<p>Код:</p>
					<div class="number" style="font-size: 40px; font-weight: bold;"><?=$_POST["code"][$key]?></div>
				</div>
				<div class="half-block">
					<p></p>
					<div class="box" style="font-size: 32px;"></div>
				</div>
				<div class="half-block">
					<p>Дата изготовления:</p>
					<div class="date" style="font-size: 32px;"><?=date("d.m.Y", strtotime($_POST["date"]))?></div>
				</div>
				<div>
					<p>Изделие:</p>
					<div class="prod" style="font-size: 24px;"><?=$value?></div>
				</div>
				<div  class="half-block">
					<p>Пластик:</p>
					<div class="material" style="font-size: 24px;"><?=$_POST["mat"][$key]?></div>
				</div>
				<div  class="half-block">
					<p>Цвет:</p>
					<div class="color" style="font-size: 24px;"><?=$_POST["color"][$key]?></div>
				</div>
			</div>
		</div>
		<?php
	}
?>
<script>
	window.print();
</script>
</body>
</html>
