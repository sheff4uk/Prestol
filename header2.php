<?
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

//	include_once "checkrights.php";

	// Функция делает ссылки кликабельными
	function src_url($src) {
		$src = preg_replace('/((?:\w+:\/\/|www\.)[\w.\/%\d&?#+=-]+)/i', '<a href="\1" target="_blank" class="button">\1</a>', $src);
		return $src;
	}

	if( in_array('order_add', $Rights) ) {
		// Генерируем таблицу workflow
		$query = "
			SELECT OM.OM_ID
				,OM.OD_ID
				,OD.Code
				,OM.Message
				,OM.priority
				,1 is_read
				,USR_Icon(OM.author) Name
			FROM OrdersMessage OM
			JOIN OrdersData OD ON OD.OD_ID = OM.OD_ID
			LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
			WHERE OM.destination = ".(in_array('order_add_confirm', $Rights) ? "1" : "0")." AND OM.read_user IS NULL AND IFNULL(SH.CT_ID, 0) IN ({$USR_cities})
			".($USR_Shop ? "AND (SH.SH_ID = {$USR_Shop} OR (OD.StartDate IS NULL AND IF(SH.KA_ID IS NULL, 1, 0)) OR OD.SH_ID IS NULL)" : "")."
			".($USR_KA ? "AND (SH.KA_ID = {$USR_KA} OR (OD.StartDate IS NULL AND SH.stock = 1) OR OD.SH_ID IS NULL)" : "")."

			UNION ALL

			SELECT OM.OM_ID
				,OM.OD_ID
				,OD.Code
				,OM.Message
				,OM.priority
				,0 is_read
				,USR_Icon(OM.author) Name
			FROM OrdersMessage OM
			JOIN OrdersData OD ON OD.OD_ID = OM.OD_ID
			LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
			WHERE OM.destination = ".(in_array('order_add_confirm', $Rights) ? "1" : "0")." AND OM.read_user IS NOT NULL AND IFNULL(SH.CT_ID, 0) IN ({$USR_cities}) AND DATEDIFF(NOW(), OM.read_time) <= 7
			".($USR_Shop ? "AND (SH.SH_ID = {$USR_Shop} OR (OD.StartDate IS NULL AND IF(SH.KA_ID IS NULL, 1, 0)) OR OD.SH_ID IS NULL)" : "")."
			".($USR_KA ? "AND (SH.KA_ID = {$USR_KA} OR (OD.StartDate IS NULL AND SH.stock = 1) OR OD.SH_ID IS NULL)" : "")."
			ORDER BY is_read DESC, OM_ID DESC
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		$workflow_color = "green";
		$workflow_table = "
			<table class='main_table'>
				<thead>
					<tr>
						<th width='60'>Код</th>
						<th>Сообщение</th>
						<th width='100'>Автор</th>
					</tr>
				</thead>
				<tbody>
		";

		while( $row = mysqli_fetch_array($res) )
		{
			$workflow_table .= "
				<tr onclick='document.location = \"./orderdetail.php?id={$row["OD_ID"]}\";' style='".($row["priority"] ? "font-weight: bold;" : "")." ".($row["is_read"] == 0 ? "opacity: .3;" : "")."'>
					<td><a href='./orderdetail.php?id={$row["OD_ID"]}'><b class='code'>{$row["Code"]}</b></a></td>
					<td>{$row["Message"]}</td>
					<td>{$row["Name"]}</td>
				</tr>
			";
			if( $row["is_read"] ) {
				if( $row["priority"] == 0 and $workflow_color != 'red' ) {
					$workflow_color = "yellow";
				}
				else {
					$workflow_color = "red";
				}
			}
		}
		$workflow_table .= "</tbody></table>";

		$query = "
			SELECT OM.OM_ID
				,OM.OD_ID
				,OD.Code
				,OM.Message
				,OM.priority
				,1 is_read
				,IF(OM.read_user IS NULL, '', USR_Icon(OM.read_user)) Name
			FROM OrdersMessage OM
			JOIN OrdersData OD ON OD.OD_ID = OM.OD_ID
			LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
			WHERE OM.author = {$_SESSION["id"]} AND OM.read_user IS NULL AND IFNULL(SH.CT_ID, 0) IN ({$USR_cities})
			".($USR_Shop ? "AND (SH.SH_ID = {$USR_Shop} OR (OD.StartDate IS NULL AND IF(SH.KA_ID IS NULL, 1, 0)) OR OD.SH_ID IS NULL)" : "")."
			".($USR_KA ? "AND (SH.KA_ID = {$USR_KA} OR (OD.StartDate IS NULL AND SH.stock = 1) OR OD.SH_ID IS NULL)" : "")."

			UNION ALL

			SELECT OM.OM_ID
				,OM.OD_ID
				,OD.Code
				,OM.Message
				,OM.priority
				,0 is_read
				,IF(OM.read_user IS NULL, '', USR_Icon(OM.read_user)) Name
			FROM OrdersMessage OM
			JOIN OrdersData OD ON OD.OD_ID = OM.OD_ID
			LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
			WHERE OM.author = {$_SESSION["id"]} AND OM.read_user IS NOT NULL AND IFNULL(SH.CT_ID, 0) IN ({$USR_cities}) AND DATEDIFF(NOW(), OM.read_time) <= 7
			".($USR_Shop ? "AND (SH.SH_ID = {$USR_Shop} OR (OD.StartDate IS NULL AND IF(SH.KA_ID IS NULL, 1, 0)) OR OD.SH_ID IS NULL)" : "")."
			".($USR_KA ? "AND (SH.KA_ID = {$USR_KA} OR (OD.StartDate IS NULL AND SH.stock = 1) OR OD.SH_ID IS NULL)" : "")."
			ORDER BY is_read DESC, OM_ID DESC
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		$workflow_table_outcoming = "
			<table class='main_table'>
				<thead>
					<tr>
						<th width='60'>Код</th>
						<th>Сообщение</th>
						<th width='100'>Прочитано</th>
					</tr>
				</thead>
				<tbody>
		";

		while( $row = mysqli_fetch_array($res) )
		{
			$workflow_table_outcoming .= "
				<tr onclick='document.location = \"./orderdetail.php?id={$row["OD_ID"]}\";' style='".($row["priority"] ? "font-weight: bold;" : "")." ".($row["is_read"] == 0 ? "opacity: .3;" : "")."'>
					<td><a href='./orderdetail.php?id={$row["OD_ID"]}'><b class='code'>{$row["Code"]}</b></a></td>
					<td>{$row["Message"]}</td>
					<td>{$row["Name"]}</td>
				</tr>
			";
		}
		$workflow_table_outcoming .= "</tbody></table>";

		// Проверяем отметку об изменении суммы заказа и выводим сообщение
		$query = "SELECT OD.Code FROM OrdersData OD WHERE OD.author = {$_SESSION['id']} AND OD.change_price = 1";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			$_SESSION['error'][] = "Внимание! Ваши действия вызвали изменение суммы заказа {$row['Code']}.";
		}
		$query = "UPDATE OrdersData OD SET OD.change_price = 0 WHERE OD.author = {$_SESSION['id']} AND OD.change_price = 1";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}
?>
<!DOCTYPE html>
<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title><?=$title?></title>
	<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
	<link rel="icon" href="/favicon.ico" type="image/x-icon">
<!--	<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/ui-lightness/jquery-ui.css">-->
	<link rel="stylesheet" type='text/css' href="js/ui/jquery-ui.css?v=1">
	<link rel='stylesheet' type='text/css' href='css/style.css?v=54'>
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">
<!--	<link rel='stylesheet' type='text/css' href='css/font-awesome.min.css'>-->
	<link rel='stylesheet' type='text/css' href='css/buttons.css'>
	<link rel='stylesheet' type='text/css' href='css/animate.css'>
	<link rel='stylesheet' type='text/css' href='plugins/jReject-master/css/jquery.reject.css'>
	<link rel='stylesheet' type='text/css' href='css/loading.css'>
	<link rel='stylesheet' type='text/css' href='js/timepicker/jquery-ui-timepicker-addon.css'>
<!--	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>-->
<!--	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>-->
	<script src="js/jquery-1.11.3.min.js"></script>
	<script src="js/ui/jquery-ui.js"></script>
	<script src="js/jquery.ui.datepicker-ru.js"></script>
	<script src="js/modal.js?v=8"></script>
	<script src="js/script.js?v=36" type="text/javascript"></script>
	<script src="js/jquery.printPage.js" type="text/javascript"></script>
	<script src="js/jquery.columnhover.js" type="text/javascript"></script>
	<script src="js/noty/packaged/jquery.noty.packaged.min.js" type="text/javascript"></script>
	<script src="js/Chart.min.js" type="text/javascript"></script>
	<script src="plugins/jReject-master/js/jquery.reject.js" type="text/javascript"></script>
	<script src="js/timepicker/jquery-ui-timepicker-addon.js" type="text/javascript"></script>
	<script src="js/timepicker/jquery-ui-timepicker-ru.js" type="text/javascript"></script>

	<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/1.4.0/clipboard.min.js"></script>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css" rel="stylesheet" />
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/i18n/ru.js" type="text/javascript"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.maskedinput/1.4.1/jquery.maskedinput.js"></script>

	<script>
		$(document).ready(function(){
			$('.aside-nav-control').click(function() {
				$('.aside-nav').addClass('opened');
				$('body').css('overflow', 'hidden');
			});

			$('.aside-nav .close_btn').click(function() {
				$('.aside-nav').removeClass('opened');
				$('body').css('overflow', '');
			});

			$('#body_wraper').show();
			$('#loading').hide();

			//Проверка браузера
			$.reject({
				reject: {
					safari: true, // Apple Safari
					//chrome: true, // Google Chrome
					//firefox: true, // Mozilla Firefox
					msie: true, // Microsoft Internet Explorer
					//opera: true, // Opera
					konqueror: true, // Konqueror (Linux)
					unknown: true // Everything else
				},
				close: false,
				display: ['chrome','firefox','opera'],
				header: 'Ваш браузер устарел',
				paragraph1: 'Вы пользуетесь устаревшим браузером, который не поддерживает современные веб-стандарты и представляет угрозу безопасности Ваших данных.',
				paragraph2: 'Пожалуйста, установите современный браузер:',
				closeMessage: ''
			});

			// Принудительное перемещение к якорю после перезагрузки страницы
			var loc = window.location.hash.replace("#","");
			if (loc != "") {
				location.replace(document.URL);
			}

			$( 'input[type=submit], input[type=button], .button, button' ).button();

			// Календарь
			$( "input.date" ).datepicker({
				dateFormat: 'dd.mm.yy',
				onClose: function( selectedDate ) {
					if( $(this).hasClass( "from" ) ) {
						$(this).parents( "form" ).find( ".to" ).datepicker( "option", "minDate", selectedDate );
					}
					if( $(this).hasClass( "to" ) ) {
						$(this).parents( "form" ).find( ".from" ).datepicker( "option", "maxDate", selectedDate );
					}
				}
			});

			// Плавная прокрутка к якорю
			var loc = window.location.hash.replace("#","");
			if (loc == "") {loc = "main"}

			var nav = $("#"+loc);
			if (nav.length) {
				var destination = nav.offset().top - 200;
				$("body:not(:animated)").animate({ scrollTop: destination }, 200);
				$("html").animate({ scrollTop: destination }, 200);
			}
		});

		// Диалог подтверждения действия
		function confirm(text, href) {
			var self = this;
			self.dfd = $.Deferred();
			var n = noty({
				text		: text,
				dismissQueue: false,
				modal		: true,
				buttons		: [
					{addClass: 'btn btn-primary', text: 'Ok', onClick: function ($noty) {
						$noty.close();
						//noty({timeout: 3000, text: 'Вы нажали кнопку "Ok"', type: 'success'});
						if(href !== undefined) {window.location.href = href}
						self.dfd.resolve(true);
					}
					},
					{addClass: 'btn btn-danger', text: 'Отмена', onClick: function ($noty) {
						$noty.close();
						noty({timeout: 3000, text: 'Вы нажали кнопку "Отмена"', type: 'error'});
						self.dfd.resolve(false);
					}
					}
				],
				closable: false,
				timeout: false
			});
			return self.dfd.promise();
		}

		// Функция замены в строке спец символов
		var entityMap = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#39;',
			'/': '&#x2F;',
			'`': '&#x60;',
			'=': '&#x3D;'
		};

		function escapeHtml(string) {
			return String(string).replace(/[&<>"'`=\/]/g, function (s) {
				return entityMap[s];
			});
		}
	</script>

<?
	// Выводим собранные в сесии сообщения через noty
	if( isset($_SESSION["error"]) ) {
		foreach ($_SESSION["error"] as $value) {
			$value = str_replace("\n", "", addslashes(htmlspecialchars($value)));
			echo "<script>$(document).ready(function() {noty({text: '{$value}', type: 'error'});});</script>";
		}
		unset($_SESSION["error"]);
	}

	if( isset($_SESSION["alert"]) ) {
		foreach ($_SESSION["alert"] as $value) {
			$value = str_replace("\n", "", addslashes(htmlspecialchars($value)));
			echo "<script>$(document).ready(function() {noty({timeout: 10000, text: '{$value}', type: 'alert'});});</script>";
		}
		unset($_SESSION["alert"]);
	}

	if( isset($_SESSION["success"]) ) {
		foreach ($_SESSION["success"] as $value) {
			$value = str_replace("\n", "", addslashes(htmlspecialchars($value)));
			echo "<script>$(document).ready(function() {noty({timeout: 3000, text: '{$value}', type: 'success'});});</script>";
		}
		unset($_SESSION["success"]);
	}

	//Получаем статус заказов (В работе, Свободные, Отгруженные, Удаленные)
	$archive = $_GET["archive"] ? $_GET["archive"] : 0;
	switch ($archive) {
		case 0:
			$BG = "#fff";
			break;
		case 1:
			$BG = "#ffb";
			break;
		case 2:
			$BG = "#bf8";
			break;
		case 3:
			$BG = "#CCC";
			break;
	}
?>

</head>
<body style='background: <?=$BG?>;'>

	<div id="loading" class='uil-default-css' style='transform:scale(1); position: absolute; left: calc(50% - 100px); top: calc(50% - 100px);'><div style='top:80px;left:93px;width:14px;height:40px;background:#e78f08;-webkit-transform:rotate(0deg) translate(0,-60px);transform:rotate(0deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#e78f08;-webkit-transform:rotate(30deg) translate(0,-60px);transform:rotate(30deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#e78f08;-webkit-transform:rotate(60deg) translate(0,-60px);transform:rotate(60deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#e78f08;-webkit-transform:rotate(90deg) translate(0,-60px);transform:rotate(90deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#e78f08;-webkit-transform:rotate(120deg) translate(0,-60px);transform:rotate(120deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#e78f08;-webkit-transform:rotate(150deg) translate(0,-60px);transform:rotate(150deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#e78f08;-webkit-transform:rotate(180deg) translate(0,-60px);transform:rotate(180deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#e78f08;-webkit-transform:rotate(210deg) translate(0,-60px);transform:rotate(210deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#e78f08;-webkit-transform:rotate(240deg) translate(0,-60px);transform:rotate(240deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#e78f08;-webkit-transform:rotate(270deg) translate(0,-60px);transform:rotate(270deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#e78f08;-webkit-transform:rotate(300deg) translate(0,-60px);transform:rotate(300deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:93px;width:14px;height:40px;background:#e78f08;-webkit-transform:rotate(330deg) translate(0,-60px);transform:rotate(330deg) translate(0,-60px);border-radius:10px;position:absolute;'></div></div>

	<!-- NAVBAR -->
	<nav class="navbar">
		<div class="navbar-header"  id="main">
			<div class="aside-nav-control navbar-brand">
				<i class="fa fa-bars fa-lg"></i>
			</div>
			<a class="navbar-brand" href="/" title="На главную" style="position: relative;"><?=$company_name?></a>
			<?
			if( in_array('order_add', $Rights) ) {
			?>
			<div id="navbar_workflow" style="background: <?=$workflow_color?>; box-shadow: 0 0 3px 3px <?=$workflow_color?>;">
				<div>
					<div id="tabs_workflow" style="height: 100%; background: #fff;">
						<ul>
							<li><a href="#incoming">Входящие</a></li>
							<li><a href="#outcoming">Отправленные</a></li>
						</ul>
						<div id="incoming" style="height: calc(100% - 35px); overflow: auto;">
							<?=$workflow_table?>
						</div>
						<div id="outcoming" style="height: calc(100% - 35px); overflow: auto;">
							<?=$workflow_table_outcoming?>
						</div>
					</div>
				</div>
			</div>
			<?
			}
			?>
		</div>

		<script>
			$(document).ready(function() {
				$( "#tabs_workflow" ).tabs();
			});
		</script>
<?
	if (empty($_SESSION['login']) or empty($_SESSION['id'])) {
		$menu = array ("Вход" => "login.php"
					  ,"Регистрация" => "reg.php");
	}
	else {
		if( in_array('chart', $Rights) ) {
			$menu["График"] = "chart.php";
		}
		if( in_array('selling_all', $Rights) or in_array('selling_city', $Rights) ) {
			$year = date("Y");
			$month = date("n");
			$menu["Реализация"] = "selling.php?CT_ID={$USR_City}&year={$year}&month={$month}";
		}
		if( in_array('sverki_all', $Rights) or in_array('sverki_city', $Rights) or in_array('sverki_opt', $Rights) ) {
			$menu["Счета"] = "bills.php";
		}
		if( in_array('doverennost', $Rights) ) {
			$menu["Доверенность"] = "doverennost.php";
		}
		if( in_array('sverki_all', $Rights) or in_array('sverki_city', $Rights) or in_array('sverki_opt', $Rights) ) {
			$menu["Сверки"] = "sverki.php";
		}
		if( in_array('screen_materials', $Rights) ) {
			$menu["Материалы"] = "materials.php";
		}
		if( in_array('screen_blanks', $Rights) ) {
			$menu["Заготовки"] = "blankstock.php";
		}
		if( in_array('screen_timesheet', $Rights) ) {
			$menu["Табель"] = "timesheet.php";
		}
		if( in_array('screen_paylog', $Rights) ) {
			$menu["Платежи"] = "paylog.php";
		}
		if( in_array('finance_all', $Rights) or in_array('finance_account', $Rights) ) {
			$menu["Касса"] = "cash.php";
		}
		$menu["Выход {$USR_Icon}"] = "exit.php";
	}

	// Формируем элементы меню
	$nav_buttons = "";
	foreach ($menu as $title=>$url) {
		$pieces = explode("?", $url);
		$class = strpos($_SERVER["REQUEST_URI"], $pieces[0]) !== false ? "active" : "";
		$nav_buttons .= "<li class='{$class}'><a href='{$url}'>{$title}</a></li>";
	}

	echo "<ul class='navbar-nav'>";
	echo $nav_buttons;
	echo "</ul>";
	echo "</nav>";

	echo "<div class='aside-nav'>";
	echo "<div class='close_btn'><i class='fa fa-times fa-2x'></i></div>";
	echo "<ul>";
	echo $nav_buttons;
	echo "</ul>";
	echo "</div>";
	// END NAVBAR

	$MONTHS = array(1=>'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь');
	$MONTHS_DATE = array(1=>'янв.', 'февр.', 'мар.', 'апр.', 'мая', 'июня', 'июля', 'авг.', 'сент.', 'окт.', 'нояб.', 'дек.');
?>
	<div id="body_wraper" style="display: none;">

<script>
	$(function() {
		$("#mtel").mask("+7 (999) 999 99 99");
	});
</script>
