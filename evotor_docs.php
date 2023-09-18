<?
include "config.php";

$Authorization = '8865b81a-9279-46ab-b2d9-878a1fa1a615'; // ИП
//$Authorization = '397a775b-c2e5-44ab-adf1-fd45b6f71caf'; // ООО

// Даты периода выборки в нужном формате
$gtCloseDate_out = "2022-05-05T06:24:54.000+0000";
$gtCloseDate = "2023-06-18T13:03:00.000+0000";
$ltCloseDate = "2023-06-18T13:18:00.000+0000";

//$deviceUuid = '20190528-8698-409A-8002-A0C67CCF7B3F';
$deviceUuid = '20190528-D09D-405F-8011-874610944D19'; // Гулливер Б
//$deviceUuid = '20201117-E2FD-402E-80E1-2AEC80643D5B'; // Престол Мегадом

$storeUuid = '20190528-BFDD-4056-802A-2BFD4C905D57'; // ИП
//$storeUuid = '20190528-92E7-4012-80D2-C5CDAF90F275'; // ООО

if( $curl = curl_init() ) {
	curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
	//curl_setopt($curl, CURLOPT_URL, 'https://api.evotor.ru/api/v1/inventories/stores/'.$storeUuid.'/documents?deviceUuid='.$deviceUuid.'&gtCloseDate='.$gtCloseDate_out);
	curl_setopt($curl, CURLOPT_URL, 'https://api.evotor.ru/api/v1/inventories/stores/'.$storeUuid.'/documents?deviceUuid='.$deviceUuid.'&gtCloseDate='.$gtCloseDate.'&ltCloseDate='.$ltCloseDate);
	//curl_setopt($curl, CURLOPT_URL, 'https://api.evotor.ru/api/v1/inventories/stores/'.$storeUuid.'/documents?deviceUuid='.$deviceUuid.'&gtCloseDate='.$gtCloseDate.'&ltCloseDate='.$ltCloseDate.'&types=CASH_OUTCOME');
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
		'X-Authorization: '.$Authorization
	));
	$out = curl_exec($curl);
	curl_close($curl);
}
//$documents = json_decode($out, true);
echo $out;
//foreach($documents as $value) {
//	foreach($value["transactions"] as $transactions) {
//		if( $transactions["type"] == "CASH_OUTCOME" ) {
//			// Преобразуем время в нужный формат при помощ mySQL
//			$query = "SELECT CONVERT_TZ(STR_TO_DATE(SUBSTRING_INDEX('{$transactions["creationDate"]}', '.', 1), '%Y-%m-%dT%T'), '+00:00', CONCAT(IF({$transactions["timezone"]} > 0, '+', ''), TIME_FORMAT(SEC_TO_TIME({$transactions["timezone"]} DIV 1000), '%H:%i'))) payment_date";
//			$result = mysqli_query( $mysqli, $query );
//			$row = mysqli_fetch_array($result);
//			$payment_date = $row["payment_date"];
//			$sum = $transactions["sum"];
//
//			echo "{$payment_date}: {$sum}<br>";
//		}
//	}
//}

//			$curl = curl_init();
//			curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
//			curl_setopt($curl, CURLOPT_URL, 'https://api.evotor.ru/api/v1/inventories/stores/'.$storeUuid.'/documents?deviceUuid='.$deviceUuid.'&gtCloseDate='.$gtCloseDate_out.'&types=CASH_OUTCOME');
//			curl_setopt($curl, CURLOPT_HTTPHEADER, array(
//				'X-Authorization: '.$Authorization
//			));
//			$out = curl_exec($curl);
//			$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE); // Получаем HTTP-код
//			curl_close($curl);

die();
?>




[
	{
		"uuid":"fca7fc9a-ce69-4a60-adc2-9f1dc26276c8",
		"type":"CASH_OUTCOME",
		"deviceId":"352398089066726",
		"deviceUuid":"20201117-E2FD-402E-80E1-2AEC80643D5B",
		"transactions":[
			{
				"type":"DOCUMENT_OPEN",
				"uuid":null,
				"id":"1",
				"userCode":null,
				"userUuid":"20210312-C3E1-407B-80F9-1E65055B8756",
				"creationDate":"2022-02-17T13:30:50.000+0000",
				"timezone":10800000,
				"baseDocumentNumber":null,
				"baseDocumentUUID":null,
				"clientName":null,
				"clientPhone":null,
				"couponNumber":null
			},
			{
				"type":"REGISTER_BILLS",
				"uuid":null,
				"id":"2",
				"userCode":null,
				"userUuid":"20210312-C3E1-407B-80F9-1E65055B8756",
				"creationDate":"2022-02-17T13:30:50.000+0000",
				"timezone":10800000,
				"denomination":25700,
				"quantity":1,
				"sum":25700
			},
			{
				"type":"DOCUMENT_CLOSE_FPRINT",
				"uuid":null,
				"id":"3",
				"userCode":null,
				"userUuid":"20210312-C3E1-407B-80F9-1E65055B8756",
				"creationDate":"2022-02-17T13:30:50.000+0000",
				"timezone":10800000,
				"documentNumber":"1220",
				"receiptNumber":"646",
				"sessionNumber":"286",
				"total":25700
			},
			{
				"type":"CASH_OUTCOME",
				"uuid":null,
				"id":"4",
				"userCode":null,
				"userUuid":"20210312-C3E1-407B-80F9-1E65055B8756",
				"creationDate":"2022-02-17T13:30:50.000+0000",
				"timezone":10800000,
				"paymentCategoryId":1,
				"sum":25700
			}
		],
		"closeDate":"2022-02-17T13:30:50.000+0000",
		"openDate":"2022-02-17T13:30:50.000+0000",
		"openUserCode":null,
		"openUserUuid":"20210312-C3E1-407B-80F9-1E65055B8756",
		"closeUserCode":null,
		"closeUserUuid":"20210312-C3E1-407B-80F9-1E65055B8756",
		"sessionUUID":"8fd4594b-318c-4798-807b-6804e7ef55b4",
		"sessionNumber":"287",
		"number":1791,
		"closeResultSum":"25700.00",
		"closeSum":"25700.00",
		"storeUuid":"20190528-92E7-4012-80D2-C5CDAF90F275",
		"completeInventory":true,
		"extras":{},
		"version":"V1"
	}
]


[
	{
		"uuid":"f2fdd341-a5a2-4163-9501-1253adf68eb2",
		"type":"SELL",
		"deviceId":"352398085589986",
		"deviceUuid":"20190528-8C8F-4049-808C-D1061E662D8A",
		"transactions":[
			{
				"type":"DOCUMENT_OPEN",
				"uuid":null,
				"id":"1",
				"userCode":null,
				"userUuid":"20210203-0B0C-4061-8092-8F9C6C051104",
				"creationDate":"2022-03-12T13:32:30.000+0000",
				"timezone":10800000,
				"baseDocumentNumber":null,
				"baseDocumentUUID":null,
				"clientName":null,
				"clientPhone":null,
				"couponNumber":null
			},
			{
				"type":"REGISTER_POSITION",
				"uuid":"46dd89f0-3a54-470a-a166-ad01fa34b86a",
				"id":"2",
				"userCode":null,
				"userUuid":"20210203-0B0C-4061-8092-8F9C6C051104",
				"creationDate":"2022-03-12T13:32:30.000+0000",
				"timezone":10800000,
				"alcoholByVolume":0,
				"alcoholProductKindCode":0,
				"balanceQuantity":0,
				"barcode":null,
				"commodityCode":"1",
				"commodityUuid":null,
				"commodityName":"Предоплата за изготовление мебели по индивидуальному заказу (кухонный гарнитур), 1 шт",
				"commodityType":"NORMAL",
				"costPrice":0,
				"fprintSection":"0",
				"mark":null,
				"measureName":"шт",
				"tareVolume":0,
				"price":2500,
				"quantity":1,
				"resultPrice":2500,
				"resultSum":2500,
				"sum":2500,
				"positionId":null,
				"extraKeys":[]
			},
			{
				"type":"POSITION_TAX",
				"uuid":null,
				"id":"3",
				"userCode":null,
				"userUuid":"20210203-0B0C-4061-8092-8F9C6C051104",
				"creationDate":"2022-03-12T13:32:30.000+0000",
				"timezone":10800000,"barcode":null,
				"commodityCode":"1",
				"commodityUuid":null,
				"resultPrice":2500,
				"resultSum":2500,
				"resultTaxSum":0,
				"tax":"NO_VAT",
				"taxPercent":0,
				"taxRateCode":null,
				"taxSum":0
			},
			{
				"type":"PAYMENT",
				"uuid":"57d9a9d2-900f-4b10-a4af-07cd4c575e78",
				"id":"4",
				"userCode":null,
				"userUuid":"20210203-0B0C-4061-8092-8F9C6C051104",
				"creationDate":"2022-03-12T13:32:30.000+0000",
				"timezone":10800000,
				"paymentType":"CASH",
				"sum":2500
			},
			{
				"type":"DOCUMENT_CLOSE_FPRINT",
				"uuid":"46dd89f0-3a54-470a-a166-ad01fa34b86a",
				"id":"5",
				"userCode":null,
				"userUuid":"20210203-0B0C-4061-8092-8F9C6C051104",
				"creationDate":"2022-03-12T13:32:30.000+0000",
				"timezone":10800000,
				"documentNumber":"3337",
				"receiptNumber":"1066",
				"sessionNumber":"662",
				"total":2500
			},
			{
				"type":"DOCUMENT_CLOSE",
				"uuid":null,
				"id":"6",
				"userCode":null,
				"userUuid":"20210203-0B0C-4061-8092-8F9C6C051104",
				"creationDate":"2022-03-12T13:32:30.000+0000",
				"timezone":10800000,
				"clientName":null,
				"clientPhone":null,
				"couponNumber":null,
				"quantity":1,
				"sum":2500,
				"baseDocumentNumber":null,
				"baseDocumentUUID":null
			}
		],
		"closeDate":"2022-03-12T13:32:30.000+0000",
		"openDate":"2022-03-12T13:32:30.000+0000",
		"openUserCode":null,
		"openUserUuid":"20210203-0B0C-4061-8092-8F9C6C051104",
		"closeUserCode":null,
		"closeUserUuid":"20210203-0B0C-4061-8092-8F9C6C051104",
		"sessionUUID":"2c1d4c67-e306-4ea6-819f-90009ad88250",
		"sessionNumber":"663",
		"number":4386,
		"closeResultSum":"2500.00",
		"closeSum":"2500.00",
		"storeUuid":"20190528-92E7-4012-80D2-C5CDAF90F275",
		"completeInventory":true,
		"extras":{},
		"version":"V1"
	}
]