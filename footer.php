<?
	// Выводим собранные в сесии сообщения через noty
	include "noty.php";

	// Записывае в БД текущий URL
	if (isset($_SESSION['id'])) {
		$query = "UPDATE Users SET last_url = '{$_SERVER["REQUEST_URI"]}' WHERE USR_ID = {$_SESSION['id']}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}
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
	background: rgba(0,0,0,0.2);
	z-index: 14;
	box-shadow: 0 -1px 4px rgba(0,0,0,0.2);
	text-align: center;
	line-height: 25px;
			">&copy; 2016-<?=( date("Y") )?> ООО &laquo;<a href="https://fabrikaprestol.ru" target="_blank">Престол</a>&raquo;</div>

<script>
	$(document).ready(function(){
		$('.select2_filter .select2-selection li').attr('title', '');
	});
</script>

</body>
</html>
