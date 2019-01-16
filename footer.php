<?
	// Выводим собранные в сесии сообщения через noty
	include "noty.php";
?>
</div>
<div style="
	height: 40px;
	position: absolute;
	width: 100%;
	left: 0;
"></div>

<div style="
	width: 100%;
	height: 25px;
	position: fixed;
	left: 0;
	bottom: 0;
	#background: #222222;
	background: rgba(0,0,0,0.4);
	z-index: 14;
	box-shadow: 0 -1px 4px rgba(0,0,0,0.3);
	color: #ffffff;
	text-align: center;
	line-height: 25px;
">&copy; ООО "Престол", 2016-<?=( date("Y") )?></div>

<script>
	$(document).ready(function(){
		$('.select2_filter .select2-selection li').attr('title', '');
	});
</script>

</body>
</html>
