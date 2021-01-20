<?
$end_date = date_create(date('Y-m-d'));
$working_days = 0;
$year = 0;

// Для розницы 30 рабочих дней, но не более 20 заказов на дату сдачи
if( $_GET["retail"] == "1" ) {
	include_once "config.php";
	$wd = 30;
	$day_limit = 20;
	while( $working_days < $wd or $cnt >= $day_limit or !$work_day ) {
		date_modify($end_date, '+1 day');
		// Если при подсчете рабочих дней изменился год, то получаем новый календарь
		if( $year != date('Y', strtotime(date_format($end_date, 'd.m.Y'))) ) {
			$year = date('Y', strtotime(date_format($end_date, 'd.m.Y')));
			$xml = simplexml_load_file("http://xmlcalendar.ru/data/ru/".$year."/calendar.xml");
			$json = json_encode($xml);
			$data = json_decode($json,TRUE);
		}
		$day_of_week = date('N', strtotime(date_format($end_date, 'd.m.Y')));
		$month = date('m', strtotime(date_format($end_date, 'd.m.Y')));
		$day = date('d', strtotime(date_format($end_date, 'd.m.Y')));
		// Перебираем массив и если находим дату то проверяем ее тип (тип дня: 1 - выходной день, 2 - рабочий и сокращенный (может быть использован для любого дня недели), 3 - рабочий день (суббота/воскресенье))
		$t = 0;
		foreach( $data["days"]["day"] as $key=>$value ) {
			if( $value["@attributes"]["d"] == $month.".".$day) {
				$t = $value["@attributes"]["t"];
			}
		}
		// Если очередной день - рабочий, то увеличиваем счетчик
		if ( !(($day_of_week >= 6 and $t != "3" and $t != "2") or ($t == "1")) ) {
			++$working_days;
			$work_day = 1;
		}
		else {
			$work_day = 0;
		}

		// Если достигнуто требуемое число раб. дней, узнаем кол-во заказов на этот день
		if( $working_days >= $wd and $work_day ) {
			$query = "
				#Считаем кол-во уникальных кодов внутри года
				SELECT COUNT(DISTINCT Code, YEAR(AddDate)) cnt
				FROM OrdersData OD
				WHERE OD.EndDate = '".date_format($end_date, 'Y-m-d')."'
					AND OD.DelDate IS NULL
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$row = mysqli_fetch_array($res);
			$cnt = $row["cnt"];
		}
	}
	echo "$('#day_limit').html('Автоматически <b>+{$working_days}</b> рабочих дней.<br>На эту дату <b>{$cnt}</b> заказов из <b>{$day_limit}</b>.');";
}
// Для оптовиков 40 рабочих дней
else {
	$wd = 40;
	while( $working_days < $wd ) {
		date_modify($end_date, '+1 day');
		// Если при подсчете рабочих дней изменился год, то получаем новый календарь
		if( $year != date('Y', strtotime(date_format($end_date, 'd.m.Y'))) ) {
			$year = date('Y', strtotime(date_format($end_date, 'd.m.Y')));
			$xml = simplexml_load_file("http://xmlcalendar.ru/data/ru/".$year."/calendar.xml");
			$json = json_encode($xml);
			$data = json_decode($json,TRUE);
		}
		$day_of_week = date('N', strtotime(date_format($end_date, 'd.m.Y')));
		$month = date('m', strtotime(date_format($end_date, 'd.m.Y')));
		$day = date('d', strtotime(date_format($end_date, 'd.m.Y')));
		// Перебираем массив и если находим дату то проверяем ее тип (тип дня: 1 - выходной день, 2 - рабочий и сокращенный (может быть использован для любого дня недели), 3 - рабочий день (суббота/воскресенье))
		$t = 0;
		foreach( $data["days"]["day"] as $key=>$value ) {
			if( $value["@attributes"]["d"] == $month.".".$day) {
				$t = $value["@attributes"]["t"];
			}
		}
		// Если очередной день - рабочий, то увеличиваем счетчик
		if ( !(($day_of_week >= 6 and $t != "3" and $t != "2") or ($t == "1")) ) {
			++$working_days;
		}
	}
	echo "$('#day_limit').html('Автоматически <b>+{$wd}</b> рабочих дней.');";
}

if( $_GET["script"] ) {
	echo "$('#order_form fieldset input[name=\"EndDate\"]').val('".date_format($end_date, 'd.m.Y')."');";
}
?>
