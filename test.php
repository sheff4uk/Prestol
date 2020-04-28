<?
if( $curl = curl_init() ) {
	curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
//	curl_setopt($curl, CURLOPT_URL, 'https://api.evotor.ru/stores/20190528-92E7-4012-80D2-C5CDAF90F275/documents?since=1579219200000&type=SELL');
//	curl_setopt($curl, CURLOPT_URL, 'https://api.evotor.ru/stores/20190528-92E7-4012-80D2-C5CDAF90F275/devices/20190528-8698-409A-8002-A0C67CCF7B3F/documents?since=1579219200000&type=SELL');
//	curl_setopt($curl, CURLOPT_URL, 'https://api.evotor.ru/stores/20190528-92E7-4012-80D2-C5CDAF90F275/devices/20190528-8C8F-4049-808C-D1061E662D8A/documents?since=1582561800000&type=PAYBACK&type=SELL');
	curl_setopt($curl, CURLOPT_URL, 'https://api.evotor.ru/api/v1/inventories/stores/20190528-BFDD-4056-802A-2BFD4C905D57/documents?deviceUuid=20190528-4525-40B7-802F-1DEE82B2E24E&types=PAYBACK,SELL&gtCloseDate=2020-02-22T16:30:00.000+0000');
//	curl_setopt($curl, CURLOPT_URL, 'https://api.evotor.ru/api/v1/inventories/stores/20190528-92E7-4012-80D2-C5CDAF90F275/products');
//	curl_setopt($curl, CURLOPT_POST, 1);
//	curl_setopt($curl, CURLOPT_POSTFIELDS, $tovar);
//	curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($tovar));
//	curl_setopt($curl, CURLOPT_URL, 'https://api.evotor.ru/devices');	// Список касс
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
//		'Accept: application/vnd.evotor.v2+json',
//		'Content-Type: application/vnd.evotor.v2+json',
//		'Content-Type: application/json',
		'X-Authorization: 8865b81a-9279-46ab-b2d9-878a1fa1a615'
	));
	$out = curl_exec($curl);
	$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE); // Получаем HTTP-код
	curl_close($curl);
}
//var_dump(json_decode($out, true));
//echo $out."<br><br>";
echo $http_code."<br><br>";

$documents = json_decode($out, true);
foreach($documents as $value) {
//	echo $value["type"]."___".$value["id"]."___".$value["close_date"]."<br>";
//	foreach($value["body"]["payments"] as $payments) {
//		echo $payments["sum"]."___".$payments["type"]."<br>";
//	}
//	echo $value["uuid"]."___".$value["closeDate"]."<br>";
	echo $value["deviceUuid"]."<br>";
	foreach($value["transactions"] as $transactions) {
		if( $transactions["type"] == "PAYMENT" ) {
			echo $transactions["uuid"]."___".$transactions["creationDate"]."___".$transactions["timezone"]."___".$transactions["sum"]."___".$transactions["paymentType"]."<br>";
		}
	}
}

//phpinfo();

//include "config.php";
//
//$shop = $_GET["shop"];
//
//// Узнаем настоящую стоимость выставки
//$query = "
//	SELECT IFNULL(SUM((ODD.Price - IFNULL(ODD.discount, 0))*ODD.Amount), 0) cost
//	FROM OrdersData OD
//	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
//	WHERE OD.StartDate IS NULL
//		AND OD.ReadyDate IS NOT NULL
//		AND OD.DelDate IS NULL
//		AND OD.SH_ID = {$shop}
//";
//$cost = 0;
//$result = mysqli_query( $mysqli, $query );
//if ($row = mysqli_fetch_array($result)) {
//	$cost = $row["cost"];
//}
//
//// Узнаем начальную дату
//$query = "
//	SELECT MIN(OD.ReadyDate) date_from
//	FROM OrdersData OD
//	WHERE (OD.AddDate < OD.StartDate OR OD.StartDate IS NULL)
//		AND OD.DelDate IS NULL
//		AND OD.ReadyDate IS NOT NULL
//		AND OD.SH_ID = {$shop}
//";
//$result = mysqli_query( $mysqli, $query );
//$row = mysqli_fetch_array($result);
//$date_from = $row["date_from"];
//
//$from = new DateTime($date_from);
//$to   = new DateTime(date("Y-m-d"));
//
//$period = new DatePeriod($from, new DateInterval('P1D'), $to);
//
//$arrayOfDates = array_map(
//	function($item){return $item->format('Y-m-d');},
//	iterator_to_array($period)
//);
//
//// Переворачиваем массив дат
//$arrayOfDates = array_reverse($arrayOfDates);
//
//foreach ($arrayOfDates as $v) {
//	// Отгрузки на выставку
//	$query = "
//		SELECT IFNULL(SUM((ODD.Price - IFNULL(ODD.discount, 0))*ODD.Amount), 0) incoming
//		FROM OrdersData OD
//		JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
//		WHERE (OD.AddDate < OD.StartDate OR OD.StartDate IS NULL)
//			AND OD.DelDate IS NULL
//			AND OD.ReadyDate = '{$v}'
//			AND OD.SH_ID = {$shop}
//	";
//	$incoming = 0;
//	$result = mysqli_query( $mysqli, $query );
//	if ($row = mysqli_fetch_array($result)) {
//		$incoming = $row["incoming"];
//	}
//
//	// Продажи с выставки
//	$query = "
//		SELECT IFNULL(SUM((ODD.Price - IFNULL(ODD.discount, 0))*ODD.Amount), 0) outcoming
//		FROM OrdersData OD
//		JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
//		WHERE OD.AddDate < OD.StartDate
//			AND OD.DelDate IS NULL
//			AND OD.StartDate = '{$v}'
//			AND OD.SH_ID = {$shop}
//	";
//	$outcoming = 0;
//	$result = mysqli_query( $mysqli, $query );
//	if ($row = mysqli_fetch_array($result)) {
//		$outcoming = $row["outcoming"];
//	}
//
//	// Изменения за сутки
//	$diff = $incoming - $outcoming;
//
//	// Стоимость выставки
//	$cost = $cost + $diff;
//
//	// Записываем в базу
//	$query = "
//		INSERT INTO ExhibitionCostLog
//		SET date = '{$v}', SH_ID = {$shop}, cost = {$cost}
//		ON DUPLICATE KEY
//		UPDATE cost = {$cost}
//	";
//	mysqli_query( $mysqli, $query );
//}
?>
<!--
SELECT CONVERT_TZ(STR_TO_DATE('2020-01-23T10:48:57.000+0000', '%Y-%m-%dT%T'), '+00:00', CONCAT(IF(10800000 > 0, '+', ''), TIME_FORMAT(SEC_TO_TIME(10800000 DIV 1000), '%H:%i')));
{
	"type":"SELL",
	"id":"d38ac944-c006-445a-b095-768bcdc6a866",
	"extras":{},
	"number":444,
	"close_date":"2020-01-23T10:48:57.000+0000",
	"time_zone_offset":10800000,
	"session_id":"be5080ca-b6c7-46be-9ad6-c863685c476d",
	"session_number":43,
	"close_user_id":"20190528-6C78-4008-801F-21F84E32D5E3",
	"device_id":"20190528-8698-409A-8002-A0C67CCF7B3F",
	"store_id":"20190528-92E7-4012-80D2-C5CDAF90F275",
	"user_id":"01-000000002039176",
	"body":{
		"positions":[
			{
				"product_id":"450b6aa6-bdee-437b-ade0-c16934912aa7",
				"quantity":1,
				"initial_quantity":-1,
				"product_type":"NORMAL",
				"alcohol_by_volume":0,
				"alcohol_product_kind_code":0,
				"tare_volume":0,
				"code":"00-00006902",
				"product_name":"МЕБЕЛЬНЫЙ ГАРНИТУР",
				"measure_name":"шт.",
				"id":2097,
				"uuid":"650a14c6-1425-48f7-8279-8d9e4ef82028",
				"extra_keys":[],
				"sub_positions":[],
				"measure_precision":3,
				"price":17100,
				"cost_price":0,
				"result_price":17100,
				"sum":17100,
				"tax":{
					"type":"NO_VAT",
					"sum":0,
					"result_sum":0
				},
				"result_sum":17100,
				"print_group_id":"46dd89f0-3a54-470a-a166-ad01fa34b86a",
				"settlement_method":{
					"type":"CHECKOUT_FULL"
				}
			}
		],
		"doc_discounts":[],
		"payments":[
			{
				"id":"ec2e9357-ac3f-4aa0-9b57-1071ebd18922",
				"sum":5100,
				"type":"ADVANCE",
				"parts":[
					{
						"print_group_id":"46dd89f0-3a54-470a-a166-ad01fa34b86a",
						"part_sum":5100,
						"change":0
					}
				],
				"app_info":{
					"name":"По предоплате"
				}
			},
			{
				"id":"277aee95-3ff2-4da4-885b-d07438ede918",
				"sum":12000,
				"type":"CASH",
				"parts":[
					{
						"print_group_id":"46dd89f0-3a54-470a-a166-ad01fa34b86a",
						"part_sum":12000,
						"change":0
					}
				],
				"app_info":{
					"name":"Наличные"
				}
			}
		],
		"print_groups":[
			{
				"id":"46dd89f0-3a54-470a-a166-ad01fa34b86a",
				"type":"CASH_RECEIPT"
			}
		],
		"pos_print_results":[
			{
				"receipt_number":274,
				"document_number":359,
				"session_number":43,
				"receipt_date":"23012020",
				"receipt_time":"1348",
				"fiscal_sign_doc_number":"3566743109",
				"fiscal_document_number":360,
				"fn_serial_number":"9283440300251791",
				"kkt_serial_number":"00308300721491",
				"kkt_reg_number":"0003236243025918 ",
				"print_group_id":"46dd89f0-3a54-470a-a166-ad01fa34b86a",
				"check_sum":17100
			}
		],
		"sum":17100,
		"result_sum":17100
	},
	"created_at":"2020-01-23T10:48:59.213+0000",
	"version":"V2"
}-->
