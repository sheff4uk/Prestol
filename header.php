<?
	ini_set("session.gc_maxlifetime",10) ;
	session_start();
	// Проверяем, пусты ли переменные логина и id пользователя
	if (empty($_SESSION['login']) or empty($_SESSION['id'])) {
		if( !strpos($_SERVER["REQUEST_URI"], 'login.php') and !strpos($_SERVER["REQUEST_URI"], 'reg.php') and !strpos($_SERVER["REQUEST_URI"], 'save_user.php') and !strpos($_SERVER["REQUEST_URI"], 'mailconfirm.php') and !strpos($_SERVER["REQUEST_URI"], 'activation.php') ) {
			header('Location: login.php');
		}
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title><?=$title?></title>
	<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
	<link rel="icon" href="/favicon.ico" type="image/x-icon">
	<link rel="stylesheet" type='text/css' href="js/ui/jquery-ui.css">
	<link rel='stylesheet' type='text/css' href='css/style.css'>
	<link rel='stylesheet' type='text/css' href='css/font-awesome.min.css'>
	<link rel='stylesheet' type='text/css' href='css/buttons.css'>
	<link rel='stylesheet' type='text/css' href='css/animate.css'>
	<script src="js/jquery-1.11.3.min.js"></script>
	<script src="js/ui/jquery-ui.js"></script>
	<script src="js/jquery.ui.datepicker-ru.js"></script>
	<script src="js/modal.js"></script>
	<script src="js/script.js" type="text/javascript"></script>
	<script src="js/jquery.printPage.js" type="text/javascript"></script>
	<script src="js/jquery.columnhover.js" type="text/javascript"></script>
	<script type="text/javascript" src="js/noty/packaged/jquery.noty.packaged.min.js"></script>

	<script>
		$(document).ready(function(){
			$( 'input[type=submit], .button, button' ).button();

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
//			var loc = window.location.hash.replace("#","");
//			if (loc == "") {loc = "main"}
//
//			var destination = $("#"+loc).offset().top - 200;
//			$("body:not(:animated)").animate({ scrollTop: destination }, 500);
//			$("html").animate({ scrollTop: destination }, 500);
		});

		// Диалог подтверждения действия
		function confirm(text, href) {
			var n = noty({
				text        : text,
				//dismissQueue: false,
				modal		: true,
				animation: {
					open: 'animated bounce',
					//close: 'animated flipOutX',
				},
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
		echo "<script>alert('{$_SESSION["alert"]}');</script>";
		$_SESSION["alert"] = '';
	}
	$archive = ($_GET["archive"] == 1) ? 1 : 0;
?>

</head>
<body style='background: <?= ( $archive == 1 ) ? "#bf8" : "#fff" ?>'>
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
		$menu = array ("Ткань/пластик" => "materials.php?isex=0&prod=1"
//					  ,"Производство" => "workers.php?worker=0&type=1&isready=0"
					  ,"Свободные{$ischeckcount}" => "/orderdetail.php"
					  ,"Заготовки" => "blankstock.php"
					  ,"Табель" => "timesheet.php"
					  ,"Платежи" => "paylog.php"
//					  ,"Печатные формы" => "toprint.php"
					  ,"Выход ({$_SESSION['name']})" => "exit.php");
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
	include "autocomplete.php"; //JavaScript
?>
