<?
	// Список названий контрагентов для автокомплита
	include "config.php";
//	include "checkrights.php";

if( isset($_GET["KA_ID"]) ) { // Если указан контрагент - показываем его всегда
	$query = "
		SELECT KA_ID
			,Naimenovanie
			,IFNULL(Jur_adres, '') Jur_adres
			,IFNULL(Fakt_adres, '') Fakt_adres
			,IFNULL(Telefony, '') Telefony
			,IFNULL(INN, '') INN
			,IFNULL(OKPO, '') OKPO, IFNULL(KPP, '') KPP
			,IFNULL(Pasport, '') Pasport
			,IFNULL(Email, '') Email
			,IFNULL(Schet, '') Schet
			,IFNULL(Bank, '') Bank
			,IFNULL(BIK, '') BIK
			,IFNULL(KS, '') KS
			,IFNULL(Bank_adres, '') Bank_adres
			,99 score
		FROM Kontragenty
		WHERE KA_ID = {$_GET["KA_ID"]}
		UNION
	";
}

$query .= "
	SELECT KA_ID
		,Naimenovanie
		,IFNULL(Jur_adres, '') Jur_adres
		,IFNULL(Fakt_adres, '') Fakt_adres
		,IFNULL(Telefony, '') Telefony
		,IFNULL(INN, '') INN
		,IFNULL(OKPO, '') OKPO
		,IFNULL(KPP, '') KPP
		,IFNULL(Pasport, '') Pasport
		,IFNULL(Email, '') Email
		,IFNULL(Schet, '') Schet
		,IFNULL(Bank, '') Bank
		,IFNULL(BIK, '') BIK
		,IFNULL(KS, '') KS
		,IFNULL(Bank_adres, '') Bank_adres
		,MATCH(Naimenovanie) AGAINST ('{$_GET["term"]}') score
	FROM Kontragenty
	HAVING score > 0
	ORDER BY score DESC
";

$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$Kontragenty[] = array( "id"=>$row["KA_ID"], "value"=>$row["Naimenovanie"], "Jur_adres"=>$row["Jur_adres"], "Fakt_adres"=>$row["Fakt_adres"], "Telefony"=>$row["Telefony"], "INN"=>$row["INN"], "OKPO"=>$row["OKPO"], "KPP"=>$row["KPP"], "Pasport"=>$row["Pasport"], "Email"=>$row["Email"], "Schet"=>$row["Schet"], "Bank"=>$row["Bank"], "BIK"=>$row["BIK"], "KS"=>$row["KS"], "Bank_adres"=>$row["Bank_adres"] );
}
echo json_encode($Kontragenty);
?>
