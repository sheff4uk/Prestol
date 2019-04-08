<?
//phpinfo();

include "config.php";

$shop = $_GET["shop"];

// Узнаем настоящую стоимость выставки
$query = "
	SELECT SUM((ODD.Price - IFNULL(ODD.discount, 0))*ODD.Amount) cost
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
	WHERE OD.StartDate IS NULL
		AND OD.ReadyDate IS NOT NULL
		AND OD.DelDate IS NULL
		AND OD.SH_ID = {$shop}
";
$cost = 0;
$result = mysqli_query( $mysqli, $query );
if ($row = mysqli_fetch_array($result)) {
	$cost = $row["cost"];
}

// Узнаем начальную дату
$query = "
	SELECT MIN(OD.ReadyDate) date_from
	FROM OrdersData OD
	WHERE (OD.AddDate < OD.StartDate OR OD.StartDate IS NULL)
		AND OD.DelDate IS NULL
		AND OD.ReadyDate IS NOT NULL
		AND OD.SH_ID = 2
";
$result = mysqli_query( $mysqli, $query );
$row = mysqli_fetch_array($result);
$date_from = $row["date_from"];

$from = new DateTime($date_from);
$to   = new DateTime(date("Y-m-d"));

$period = new DatePeriod($from, new DateInterval('P1D'), $to);

$arrayOfDates = array_map(
	function($item){return $item->format('Y-m-d');},
	iterator_to_array($period)
);

// Переворачиваем массив дат
$arrayOfDates = array_reverse($arrayOfDates);

foreach ($arrayOfDates as $v) {
	// Отгрузки на выставку
	$query = "
		SELECT IFNULL(SUM((ODD.Price - IFNULL(ODD.discount, 0))*ODD.Amount), 0) incoming
		FROM OrdersData OD
		JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
		WHERE (OD.AddDate < OD.StartDate OR OD.StartDate IS NULL)
			AND OD.DelDate IS NULL
			AND OD.ReadyDate = '{$v}'
			AND OD.SH_ID = {$shop}
	";
	$incoming = 0;
	$result = mysqli_query( $mysqli, $query );
	if ($row = mysqli_fetch_array($result)) {
		$incoming = $row["incoming"];
	}

	// Продажи с выставки
	$query = "
		SELECT IFNULL(SUM((ODD.Price - IFNULL(ODD.discount, 0))*ODD.Amount), 0) outcoming
		FROM OrdersData OD
		JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
		WHERE OD.AddDate < OD.StartDate
			AND OD.DelDate IS NULL
			AND OD.StartDate = '{$v}'
			AND OD.SH_ID = {$shop}
	";
	$outcoming = 0;
	$result = mysqli_query( $mysqli, $query );
	if ($row = mysqli_fetch_array($result)) {
		$outcoming = $row["outcoming"];
	}

	// Изменения за сутки
	$diff = $incoming - $outcoming;

	// Стоимость выставки
	$cost = $cost + $diff;

	// Записываем в базу
	$query = "
		INSERT INTO ExhibitionCost
		SET date = '{$v}', SH_ID = {$shop}, cost = {$cost}
		ON DUPLICATE KEY
		UPDATE cost = {$cost}
	";
	mysqli_query( $mysqli, $query );
}
?>
