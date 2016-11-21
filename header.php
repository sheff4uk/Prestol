<?
	// Снимаем ограничение в 1024 на GROUP_CONCAT
	$query = "SET @@group_concat_max_len = 10000;";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	include "checkrights.php";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title><?=$title?></title>
	<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
	<link rel="icon" href="/favicon.ico" type="image/x-icon">
	<link rel="stylesheet" type='text/css' href="js/ui/jquery-ui.css">
	<link rel='stylesheet' type='text/css' href='css/style.css?v=11'>
	<link rel='stylesheet' type='text/css' href='css/font-awesome.min.css'>
	<link rel='stylesheet' type='text/css' href='css/buttons.css'>
	<link rel='stylesheet' type='text/css' href='css/animate.css'>
	<script src="js/jquery-1.11.3.min.js"></script>
	<script src="js/ui/jquery-ui.js"></script>
	<script src="js/jquery.ui.datepicker-ru.js"></script>
	<script src="js/modal.js?v=4"></script>
	<script src="js/script.js?v=7" type="text/javascript"></script>
	<script src="js/jquery.printPage.js" type="text/javascript"></script>
	<script src="js/jquery.columnhover.js" type="text/javascript"></script>
	<script type="text/javascript" src="js/noty/packaged/jquery.noty.packaged.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/1.4.0/clipboard.min.js"></script>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css" rel="stylesheet" />
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/i18n/ru.js" type="text/javascript"></script>

	<script>
		$(document).ready(function(){

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
			var n = noty({
				text        : text,
				//dismissQueue: false,
				modal		: true,
				buttons     : [
					{addClass: 'btn btn-primary', text: 'Ok', onClick: function ($noty) {
						$noty.close();
						//noty({timeout: 3000, text: 'Вы нажали кнопку "Ok"', type: 'success'});
						window.location.href = href;
					}
					},
					{addClass: 'btn btn-danger', text: 'Отмена', onClick: function ($noty) {
						$noty.close();
						noty({timeout: 3000, text: 'Вы нажали кнопку "Отмена"', type: 'error'});
					}
					}
				],
				closable: false,
				timeout: false
			});
			return false;
		}
	</script>

<?
	if( $_SESSION["alert"] != '' ) {
		echo "<script> $(document).ready(function() {noty({timeout: 10000, text: '{$_SESSION["alert"]}', type: 'alert'});});</script>";
		$_SESSION["alert"] = '';
	}

	//$archive = ($_GET["archive"] >= 1) ? $_GET["archive"] : 0;
	$archive = $_GET["archive"];
	switch ($archive) {
		case 0:
			$BG = "#fff";
			break;
		case 1:
			$BG = "#bf8";
			break;
		case 2:
			$BG = "#ffb";
			break;
	}
?>

</head>
<body style='background: <?=$BG?>'>
	<!-- NAVBAR -->
	<nav class="navbar">
		<div class="navbar-header"  id="main">
			<a class="navbar-brand" href="/" title="На главную">ПРЕСТОЛ</a>
		</div>
<?
	// Узнаем кол-во непроверенных свободных
	$query = "SELECT COUNT(1) CNT FROM `OrdersDataDetail` WHERE OD_ID IS NULL AND is_check = 0";
	$result = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$ischeckcount = " (".mysqli_result($result,0,'CNT').")";

	if (empty($_SESSION['login']) or empty($_SESSION['id'])) {
		$menu = array ("Вход" => "login.php"
					  ,"Регистрация" => "reg.php");
	}
	else {
		if( in_array('selling_all', $Rights) or in_array('selling_city', $Rights) ) {
			$menu["Реализация"] = "selling.php";
		}
		if( in_array('print_forms_view_all', $Rights) or in_array('print_forms_view_autor', $Rights) ) {
			$menu["Печатные формы"] = "print_forms_list.php";
		}
		if( in_array('screen_materials', $Rights) ) {
			$menu["Материалы"] = "materials.php";
		}
		if( in_array('screen_free', $Rights) ) {
			$menu["Свободные".$ischeckcount] = "/orderdetail.php?free=1";
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
		$menu["Выход (".$_SESSION['name'].")"] = "exit.php";
//		$menu = array ("Материалы" => "materials.php"
////					  ,"Производство" => "workers.php?worker=0&type=1&isready=0"
//					  ,"Свободные{$ischeckcount}" => "/orderdetail.php?free=1"
//					  ,"Заготовки" => "blankstock.php"
//					  ,"Табель" => "timesheet.php"
//					  ,"Платежи" => "paylog.php"
////					  ,"Печатные формы" => "toprint.php"
//					  ,"Выход ({$_SESSION['name']})" => "exit.php");
	}
	echo "<ul class='navbar-nav'>";
	foreach ($menu as $title=>$url) {
		$class = strpos($_SERVER["REQUEST_URI"], $url) !== false ? "class='active'" : "";
		echo "<li $class><a href='$url'>$title</a></li>";
	}
	echo "</ul>";
?>
	</nav>
	<!-- END NAVBAR -->
<?
	$MONTHS = array(1=>'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь');
?>
