<?
	include "config.php";

	$title = 'Подготовка печатных форм';
	include "header.php";

	// Проверка прав на доступ к экрану
	if( !in_array('print_forms_view_all', $Rights) and !in_array('print_forms_view_author', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}

	// Сохраняем в базу список товаров и перезагружаем экран
	if( isset($_GET["Tables"]) or isset($_GET["Chairs"]) or isset($_GET["Others"]) ) {
		// Создаем запись в таблице PrintForms и получаем ID
		$query = "INSERT INTO PrintForms SET USR_ID = {$_SESSION["id"]}, SHP_ID = ".($_GET["shpid"] ? $_GET["shpid"] : "NULL");
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$id = mysqli_insert_id($mysqli);

		// Формируем список id выбранных товаров из $_GET
		$id_list = '0';
		foreach( $_GET as $k => $v)
		{
			if( strpos($k,"order") === 0 )
			{
				$orderid = (int)str_replace( "order", "", $k );
				$id_list .= ','.$orderid;
			}
		}
		$product_types = "-1";
		if(isset($_GET["Tables"])) $product_types .= ",2";
		if(isset($_GET["Chairs"])) $product_types .= ",1";
		if(isset($_GET["Others"])) $product_types .= ",0";

		// Сохраняем в базу выборку товаров
		$query = "SELECT ODD_ODB.OD_ID
						,ODD_ODB.ItemID
						,ODD_ODB.PT_ID
						,ODD_ODB.Amount
						,ODD_ODB.Price
						,ODD_ODB.Zakaz
				  FROM (SELECT ODD.OD_ID
							  ,ODD.ODD_ID ItemID
							  ,IFNULL(PM.PT_ID, 2) PT_ID
							  ,ODD.Amount
							  ,IFNULL(ODD.opt_price, IFNULL(ODD.Price, 'NULL')) Price
							  ,CONCAT(IFNULL(PM.Model, 'Столешница'), ' ', IFNULL(CONCAT(ODD.Length, IF(ODD.Width > 0, CONCAT('х', ODD.Width), ''), IFNULL(CONCAT('/', IFNULL(ODD.PieceAmount, 1), 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, ''), ' ', IFNULL(CONCAT('+ патина (', ODD.patina, ')'), '')) Zakaz
						FROM OrdersDataDetail ODD
						LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
						LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
						LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
						WHERE ODD.Del = 0
						UNION ALL
						SELECT ODB.OD_ID
							  ,ODB.ODB_ID ItemID
							  ,0 PT_ID
							  ,ODB.Amount
							  ,IFNULL(ODB.opt_price, IFNULL(ODB.Price, 'NULL')) Price
							  ,CONCAT(IFNULL(BL.Name, ODB.Other), ' ', IFNULL(CONCAT('+ патина (', ODB.patina, ')'), '')) Zakaz
						FROM OrdersDataBlank ODB
						LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
						WHERE ODB.Del = 0
						) ODD_ODB
				  WHERE ODD_ODB.OD_ID IN ({$id_list})
				  AND ODD_ODB.PT_ID IN({$product_types})
				  GROUP BY ODD_ODB.itemID
				  ORDER BY ODD_ODB.OD_ID, ODD_ODB.PT_ID DESC, ODD_ODB.itemID";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$Counter = 1;
		while( $row = mysqli_fetch_array($res) ) {
			$query = "INSERT INTO PrintFormsProducts(OD_ID, PF_ID, sort, ItemID, PT_ID, Amount, Price, Zakaz, tovar_ed)
					  VALUES ({$row["OD_ID"]}, {$id}, {$Counter}, {$row["ItemID"]}, {$row["PT_ID"]}, {$row["Amount"]}, {$row["Price"]}, '{$row["Zakaz"]}', 'шт')";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$Counter++;
		}
		exit ('<meta http-equiv="refresh" content="0; url=/print_forms.php?pfid='.$id.'">');
		die;
	}

	$id = $_GET["pfid"];
	$query = "SELECT IFNULL(platelshik_id, 0) platelshik_id
					,IFNULL(gruzopoluchatel, 0) gruzopoluchatel
					,IFNULL(gruzopoluchatel_id, 0) gruzopoluchatel_id
					,IFNULL(postavshik, 1) postavshik
					,IFNULL(postavshik_id, 0) postavshik_id
					,IFNULL(nakladnaya_date, DATE_FORMAT(NOW(),'%d.%m.%Y')) nakladnaya_date
					,IFNULL(schet_date, DATE_FORMAT(NOW(),'%d.%m.%Y')) schet_date
			  FROM PrintForms WHERE PF_ID = {$id}";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$platelshik_id = mysqli_result($res,0,'platelshik_id');
	$gruzopoluchatel = mysqli_result($res,0,'gruzopoluchatel');
	$gruzopoluchatel_id = mysqli_result($res,0,'gruzopoluchatel_id');
	$postavshik = mysqli_result($res,0,'postavshik');
	$postavshik_id = mysqli_result($res,0,'postavshik_id');
	$nakladnaya_date = mysqli_result($res,0,'nakladnaya_date');
	$schet_date = mysqli_result($res,0,'schet_date');

	$query = "SELECT * FROM Rekvizity LIMIT 1";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$Name = mysqli_result($res,0,'Name');
	$INN = mysqli_result($res,0,'INN');
	$KPP = mysqli_result($res,0,'KPP');
	$Addres = mysqli_result($res,0,'Addres');
	$Dir = mysqli_result($res,0,'Dir');
	$Phone = mysqli_result($res,0,'Phone');
	$RS = mysqli_result($res,0,'RS');
	$Bank = mysqli_result($res,0,'Bank');
	$BIK = mysqli_result($res,0,'BIK');
	$KS = mysqli_result($res,0,'KS');
?>

<style>
	.forms input[type="text"] {
		width: 99%;
	}
	.comment {
		width: 95%;
		max-width: 95%;
		min-height: 100px;
		margin: 2%;
		border-radius: 5px;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
	}
</style>

<div style="width:730px; max-width:730px; margin: auto; margin-bottom: 50px;">
	<h1>Подготовка печатных форм</h1>
        <form action="" method="post" id="formdiv">
        <table width="100%" border="0" cellspacing="4" class="forms">
          <tbody><tr align="left">
            <td colspan="2"><strong>Информация о грузоотправителе/продавце:</strong></td>
            </tr>
          <tr>
            <td width="250" align="left" valign="top">Название ООО или фамилия ИП:</td>
            <td align="left" valign="top"><input type="text" autocomplete="off" value="<?=htmlspecialchars($Name)?>" name="gruzootpravitel_name" id="gruzootpravitel_name" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">ИНН:</td>
            <td align="left" valign="top"><input type="text" autocomplete="off" value="<?=htmlspecialchars($INN)?>" name="gruzootpravitel_inn" id="gruzootpravitel_inn" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">КПП:</td>
            <td align="left" valign="top"><input type="text" autocomplete="off" value="<?=htmlspecialchars($KPP)?>" name="gruzootpravitel_kpp" id="gruzootpravitel_kpp" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">ОКПО:</td>
            <td align="left" valign="top"><input type="text" autocomplete="off" name="gruzootpravitel_okpo" id="gruzootpravitel_okpo" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">Адрес:</td>
            <td align="left" valign="top"><input type="text" autocomplete="off" value="<?=htmlspecialchars($Addres)?>" name="gruzootpravitel_adres" id="gruzootpravitel_adres" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">Руководитель:</td>
            <td align="left" valign="top"><input type="text" autocomplete="off" value="<?=htmlspecialchars($Dir)?>" name="gruzootpravitel_director" id="gruzootpravitel_director" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">Главный (старший) бухгалтер:</td>
            <td align="left" valign="top"><input type="text" autocomplete="off" name="gruzootpravitel_buhgalter" id="gruzootpravitel_buhgalter" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">Телефоны:</td>
            <td align="left" valign="top"><input type="text" autocomplete="off" value="<?=htmlspecialchars($Phone)?>" name="gruzootpravitel_tel" id="gruzootpravitel_tel" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td colspan="2" align="left" valign="top"><strong>Банковские реквизиты грузоотправителя/продавца:</strong></td>
            </tr>
          <tr>
            <td width="250" align="left" valign="top">Расчетный счет:</td>
            <td align="left" valign="top"><input type="text" autocomplete="off" value="<?=htmlspecialchars($RS)?>" name="gruzootpravitel_schet" id="gruzootpravitel_schet" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">В банке (наименование банка):</td>
            <td align="left" valign="top"><input type="text" autocomplete="off" value="<?=htmlspecialchars($Bank)?>" name="gruzootpravitel_bank" id="gruzootpravitel_bank" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">БИК:</td>
            <td align="left" valign="top"><input type="text" autocomplete="off" value="<?=htmlspecialchars($BIK)?>" name="gruzootpravitel_bik" id="gruzootpravitel_bik" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">Корреспондентский счет:</td>
            <td align="left" valign="top"><input type="text" autocomplete="off" value="<?=htmlspecialchars($KS)?>" name="gruzootpravitel_ks" id="gruzootpravitel_ks" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">Местонахождение банка:</td>
            <td align="left" valign="top"><input type="text" autocomplete="off" name="gruzootpravitel_bank_adres" id="gruzootpravitel_bank_adres" class="forminput" placeholder=""></td>
          </tr>
        </tbody></table>
        <br>
        <table width="100%" border="0" cellspacing="4" class="forms">
          <tbody><tr align="left">
            <td colspan="2"><strong>Информация о плательщике:</strong>
              </td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">Название ООО или фамилия ИП:</td>
            <td align="left" valign="top">
            	<input type="hidden" name="platelshik_id" id="platelshik_id" class="forminput">
            	<input type="text" autocomplete="off" name="platelshik_name" id="platelshik_name" class="forminput" placeholder="Введите минимум 2 символа для поиска контрагента">
            </td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">ИНН:</td>
            <td align="left" valign="top"><input type="text" autocomplete="off" name="platelshik_inn" id="platelshik_inn" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">КПП:</td>
            <td align="left" valign="top"><input type="text" autocomplete="off" name="platelshik_kpp" id="platelshik_kpp" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">ОКПО:</td>
            <td align="left" valign="top"><input type="text" autocomplete="off" name="platelshik_okpo" id="platelshik_okpo" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">Адрес:</td>
            <td align="left" valign="top"><input type="text" autocomplete="off" name="platelshik_adres" id="platelshik_adres" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">Телефоны:</td>
            <td align="left" valign="top"><input type="text" autocomplete="off" name="platelshik_tel" id="platelshik_tel" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td colspan="2" align="left" valign="top"><strong>Банковские реквизиты плательщика:</strong></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">Расчетный счет:</td>
            <td align="left" valign="top"><input type="text" autocomplete="off" name="platelshik_schet" id="platelshik_schet" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">В банке (наименование банка):</td>
            <td align="left" valign="top"><input type="text" autocomplete="off" name="platelshik_bank" id="platelshik_bank" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">БИК:</td>
            <td align="left" valign="top"><input type="text" autocomplete="off" name="platelshik_bik" id="platelshik_bik" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">Корреспондентский счет:</td>
            <td align="left" valign="top"><input type="text" autocomplete="off" name="platelshik_ks" id="platelshik_ks" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">Местонахождение банка:</td>
            <td align="left" valign="top"><input type="text" autocomplete="off" name="platelshik_bank_adres" id="platelshik_bank_adres" class="forminput" placeholder=""></td>
          </tr>
        </tbody></table>
        <br>
        <table width="100%" border="0" cellspacing="4" class="forms">
          <tbody><tr class="forms">
            <td colspan="2" align="left" valign="top"><strong>Информация о грузополучателе:</strong></td>
          </tr>
          <tr class="forms">
            <td width="250" align="left" valign="top">грузополучатель:</td>
            <td valign="top"><style type="text/css">
                        #gruzopoluchatel1{display:none}
                </style>
              <label>
                <input type="radio" name="gruzopoluchatel" value="0" id="gruzopoluchatel_1" checked="checked" onclick="showhideBlocks3(this.value)">
                Плательщик</label>
              <label><br>
                <input type="radio" name="gruzopoluchatel" value="1" id="gruzopoluchatel_2" onclick="showhideBlocks3(this.value)">
                Сторонняя организация</label>
              <label><br>
                <input type="radio" name="gruzopoluchatel" value="2" id="gruzopoluchatel_3" onclick="showhideBlocks3(this.value)">
              Такой же, как грузоотправитель</label>
              <script type="text/javascript">
                        <!--
                        function showhideBlocks3(val){
                                if (val == 0 || val == 2){
                                    document.getElementById('gruzopoluchatel1').style.display='none';
                                }
                                else{
                                   document.getElementById('gruzopoluchatel'+val).style.display='block';
                                }
                        }
                        -->
                </script></td>
          </tr>
          <tr class="forms">
            <td colspan="2" align="left" valign="top"><table width="100%" border="0" cellspacing="4" id="gruzopoluchatel1">
              <tbody><tr>
                <td width="245">Название ООО или фамилия ИП:</td>
                <td width="500">
                	<input type="hidden" name="gruzopoluchatel_id" id="gruzopoluchatel_id" class="forminput">
                	<input type="text" autocomplete="off" name="gruzopoluchatel_name" id="gruzopoluchatel_name" class="forminput" placeholder="">
                </td>
              </tr>
              <tr>
                <td width="245">ИНН:</td>
                <td width="500"><input type="text" autocomplete="off" name="gruzopoluchatel_inn" id="gruzopoluchatel_inn" class="forminput" placeholder=""></td>
              </tr>
              <tr>
                <td width="245">КПП:</td>
                <td width="500"><input type="text" autocomplete="off" name="gruzopoluchatel_kpp" id="gruzopoluchatel_kpp" class="forminput" placeholder=""></td>
              </tr>
              <tr>
                <td>ОКПО:</td>
                <td><input type="text" autocomplete="off" name="gruzopoluchatel_okpo" id="gruzopoluchatel_okpo" class="forminput" placeholder=""></td>
              </tr>
              <tr>
                <td width="245">Адрес:</td>
                <td width="500"><input type="text" autocomplete="off" name="gruzopoluchatel_adres" id="gruzopoluchatel_adres" class="forminput" placeholder=""></td>
              </tr>
              <tr>
                <td width="245">Телефоны:</td>
                <td width="500"><input type="text" autocomplete="off" name="gruzopoluchatel_tel" id="gruzopoluchatel_tel" class="forminput" placeholder=""></td>
              </tr>
              <tr class="forms">
                <td colspan="2" align="left" valign="top"><strong>Банковские реквизиты грузополучателя:&nbsp;</strong></td>
              </tr>
              <tr class="forms">
                <td width="245" align="left" valign="top">Расчетный счет:</td>
                <td align="left" valign="top"><input type="text" autocomplete="off" name="gruzopoluchatel_schet" id="gruzopoluchatel_schet" class="forminput" placeholder=""></td>
              </tr>
              <tr class="forms">
                <td width="245" align="left" valign="top">В банке (наименование банка):</td>
                <td align="left" valign="top"><input type="text" autocomplete="off" name="gruzopoluchatel_bank" id="gruzopoluchatel_bank" class="forminput" placeholder=""></td>
              </tr>
              <tr class="forms">
                <td width="245" align="left" valign="top">БИК:</td>
                <td align="left" valign="top"><input type="text" autocomplete="off" name="gruzopoluchatel_bik" id="gruzopoluchatel_bik" class="forminput" placeholder=""></td>
              </tr>
              <tr class="forms">
                <td width="245" align="left" valign="top">Корреспондентский счет:</td>
                <td align="left" valign="top"><input type="text" autocomplete="off" name="gruzopoluchatel_ks" id="gruzopoluchatel_ks" class="forminput" placeholder=""></td>
              </tr>
              <tr class="forms">
                <td width="245" align="left" valign="top">Местонахождение банка:</td>
                <td align="left" valign="top"><input type="text" autocomplete="off" name="gruzopoluchatel_bank_adres" id="gruzopoluchatel_bank_adres" class="forminput" placeholder=""></td>
              </tr>
              </tbody></table></td>
          </tr>
        </tbody></table>
        <br>
        <table width="100%" border="0" cellspacing="4" class="forms">
          <tbody><tr class="forms">
            <td colspan="2" align="left" valign="top"><strong>Информация о поставщике:</strong></td>
          </tr>
          <tr class="forms">
            <td width="250" align="left" valign="top">Поставщик:</td>
            <td valign="top"><style type="text/css">
                        #zakazthik2{display:none}
                </style>
              <label>
                <input type="radio" name="postavshik" value="1" id="zakazthik_2" checked="checked" onclick="showhideBlocks4(this.value)">
              Такой же, как грузоотправитель</label><br>
              <label>
                <input type="radio" name="postavshik" value="0" id="zakazthik_1" onclick="showhideBlocks4(this.value)">
              Такой же, как плательщик</label><br>
              <label>
                <input type="radio" name="postavshik" value="2" id="zakazthik_3" onclick="showhideBlocks4(this.value)">
                Сторонняя организация</label>
              <script type="text/javascript">
                        function showhideBlocks4(val){
                                if (val == 0 || val==1){
                                    document.getElementById('zakazthik2').style.display='none';
                                }
                                else{
                                   document.getElementById('zakazthik'+val).style.display='block';
                                }
                        }
                </script></td>
          </tr>
          <tr class="forms">
            <td colspan="2" align="left" valign="top"><table width="100%" border="0" cellspacing="4" id="zakazthik2">
              <tbody><tr>
                <td width="245">Название ООО или фамилия ИП:</td>
                <td width="500">
                	<input type="hidden" name="postavshik_id" id="postavshik_id" class="forminput">
                	<input type="text" autocomplete="off" name="postavshik_name" id="postavshik_name" class="forminput" placeholder="">
                </td>
              </tr>
              <tr>
                <td width="245">ИНН:</td>
                <td width="500"><input type="text" autocomplete="off" name="postavshik_inn" id="postavshik_inn" class="forminput" placeholder=""></td>
              </tr>
              <tr>
                <td width="245">КПП:</td>
                <td width="500"><input type="text" autocomplete="off" name="postavshik_kpp" id="postavshik_kpp" class="forminput" placeholder=""></td>
              </tr>
              <tr>
                <td width="245">Адрес:</td>
                <td width="500"><input type="text" autocomplete="off" name="postavshik_adres" id="postavshik_adres" class="forminput" placeholder=""></td>
              </tr>
              <tr>
                <td width="245">Телефоны:</td>
                <td width="500"><input type="text" autocomplete="off" name="postavshik_tel" id="postavshik_tel" class="forminput" placeholder=""></td>
              </tr>
              <tr class="forms">
                <td colspan="2" align="left" valign="top"><strong>Банковские реквизиты поставщика:&nbsp;</strong></td>
              </tr>
              <tr class="forms">
                <td width="245" align="left" valign="top">Расчетный счет:</td>
                <td align="left" valign="top"><input type="text" autocomplete="off" name="postavshik_schet" id="postavshik_schet" class="forminput" placeholder=""></td>
              </tr>
              <tr class="forms">
                <td width="245" align="left" valign="top">В банке (наименование банка):</td>
                <td align="left" valign="top"><input type="text" autocomplete="off" name="postavshik_bank" id="postavshik_bank" class="forminput" placeholder=""></td>
              </tr>
              <tr class="forms">
                <td width="245" align="left" valign="top">БИК:</td>
                <td align="left" valign="top"><input type="text" autocomplete="off" name="postavshik_bik" id="postavshik_bik" class="forminput" placeholder=""></td>
              </tr>
              <tr class="forms">
                <td width="245" align="left" valign="top">Корреспондентский счет:</td>
                <td align="left" valign="top"><input type="text" autocomplete="off" name="postavshik_ks" id="postavshik_ks" class="forminput" placeholder=""></td>
              </tr>
              <tr class="forms">
                <td width="245" align="left" valign="top">Местонахождение банка:</td>
                <td align="left" valign="top"><input type="text" autocomplete="off" name="postavshik_bank_adres" id="postavshik_bank_adres" class="forminput" placeholder=""></td>
              </tr>
            </tbody></table></td>
          </tr>
        </tbody></table>
        <br>
        <table width="100%" border="0" cellspacing="4" class="forms" style="display: none;">
          <tbody><tr class="forms">
            <td colspan="2" align="left" valign="top"><strong>Ставка НДС :</strong></td>
          </tr>
          <tr class="forms">
            <td width="245" align="left" valign="top">НДС:</td>
            <td width="413" valign="top"><style type="text/css">
                        #nds1{display:none}
                </style>
              <label>
                <input type="radio" name="nds" value="0" id="nds_1" checked="checked" onclick="ndss(this.value)">
                Без НДС</label>
              <label>
                <input type="radio" name="nds" value="1" id="nds_2" onclick="ndss(this.value)">
                В сумме</label>
              <label>
                <input type="radio" name="nds" value="2" id="nds_3" onclick="ndss(this.value)">
                Сверху</label>
              <script type="text/javascript">
                        <!--
                        function ndss(val){
                                if (val > 0){
                                    document.getElementById('nds1').style.display='block';
                                }
                                else{
                                   document.getElementById('nds1').style.display='none';
                                }
                        }
                        -->
                </script></td>
          </tr>
          <tr class="forms">
            <td colspan="2" align="left" valign="top">
            <table width="100%" border="0" cellspacing="4" id="nds1">
              <tbody><tr>
                <td width="245">% ставка НДС::</td>
                <td><select name="nds_stavka" class="formselect">
                  <option value="0">0 %</option>
                  <option value="10">10 % </option>
                  <option value="18">18 % </option>
                </select></td>
              </tr>
            </tbody></table></td>
          </tr>
    </tbody></table>
        <br>
<style type="text/css">
.f1 {
	width:60px;
}
.f2 {
	width:265px;
}
.f3 {
	width:80px;
}
.f4 {
	width:80px;
}
.f5 {
	width:60px;
}
.f6 {
	width:80px;
}
}



</style>
<table width="100%" border="0" cellspacing="4" class="forms" id="tab1">
        <tbody><tr>
          <th colspan="8" align="left"><strong>Информация о перевозимом грузе:</strong></th>
          </tr>
        <tr>
          <th width="59">Код</th>
          <th width="40%">Наименование</th>
          <th>Ед. измерения</th>
          <th>код по ОКЕИ</th>
          <th>масса кг единицы</th>
          <th><strong>Кол-во</strong></th>
          <th><strong>Цена<br>
            за<br>
            единицу</strong></th>
          <th width="20"><p>&nbsp;</p></th>
        </tr>
<!--
        <tr>
          <td><input required type="text" autocomplete="off" name="tovar_name[]" id="tovar_name" class="f2"></td>
          <td><input required type="text" autocomplete="off" name="tovar_ed[]" id="tovar_ed" class="f3"></td>
          <td><input type="text" autocomplete="off" name="tovar_okei[]" id="tovar_okei" class="f1"></td>
          <td><input type="text" autocomplete="off" name="tovar_massa[]" id="tovar_massa" class="f4"></td>
          <td><input required type="text" autocomplete="off" min="1" name="tovar_kolvo[]" id="tovar_kolvo" class="f5 tovar_kol"></td>
          <td><input required type="number" autocomplete="off" min="0" name="tovar_tcena[]" id="tovar_tcena" class="f6"></td>
          <td><i class="fa fa-minus-square fa-2x" style="color: red;" onclick="deleteRow(this);"></i></td>
        </tr>
-->

	</tbody>

	<tbody></tbody>

	<tbody>
		<tr>
			<td colspan="7">
				<i class="fa fa-plus-square fa-2x" style="color: green;" onclick="addRow('шт');"></i>
				<span onclick="addRow('шт');"><font><font> Добавить строку</font></font></span>
			</td>
		</tr>
	</tbody>
</table>

<script type="text/javascript">

var fn = function(){
	var tr = $(this).parents("tr").get(0);
	var tovar_kol = $("input.tovar_kol", tr).val();

	tovar_kol = proverka(tovar_kol);

	$("input.tovar_kol", tr).val(tovar_kol);

	if (parseFloat(tovar_kol).toFixed() != "NaN") $("input.tovar_kol", tr).val(tovar_kol);

	return false;
};

$(function(){
	$(document).on('blur',"input.tovar_kol",fn);
});

function proverka(input) {
	var count = 0;
    ch = input.replace(/[^\d.,]/g, ''); //разрешаем вводить только числа, запятую и точку
	ch= ch.replace(/[,]/g,'.'); //преобразуем запятую в точку
    pos = ch.indexOf('.'); // проверяем, есть ли в строке точка
	while ( pos != -1 ) {
		count++;
		pos = ch.indexOf(".",pos+1);
	}
    if(count>1){ // если точкек много
				if (ch.charAt(ch.length-1 )=='.')
				{
					long = count - (count*2)+1;
					ch = ch.slice(0, long); // удаляем лишнее
				}
				else
				{
					pos = ch.lastIndexOf('.');
					pos = ch.lastIndexOf('.',pos+1);

					ch = ch.slice(0,pos); // удаляем лишнее
				}
    }
    input = ch; // приписываем в инпут новое значение
	return input;
}

var d = document;

function addRow(ed, okei, massa, name, amount, price, item, pt, code, odid)
{

    // Находим нужную таблицу
    var tbody = d.getElementById('tab1').getElementsByTagName('TBODY')[0];

    // Создаем строку таблицы и добавляем ее
    var row = d.createElement("TR");
    tbody.appendChild(row);

    // Создаем ячейки в вышесозданной строке
    // и добавляем тх
    var td0 = d.createElement("TD");
    var td1 = d.createElement("TD");
    var td2 = d.createElement("TD");
    var td3 = d.createElement("TD");
    var td4 = d.createElement("TD");
    var td5 = d.createElement("TD");
    var td6 = d.createElement("TD");
    var td7 = d.createElement("TD");

    row.appendChild(td0);
    row.appendChild(td1);
    row.appendChild(td2);
    row.appendChild(td3);
    row.appendChild(td4);
    row.appendChild(td5);
    row.appendChild(td6);
    row.appendChild(td7);
    // Наполняем ячейки
	if( typeof name === "undefined" ) {
		name = '';
	}
	if( typeof ed === "undefined" ) {
		ed = '';
	}
	if( typeof okei === "undefined" ) {
		okei = '';
	}
	if( typeof massa === "undefined" ) {
		massa = '';
	}
	if( typeof amount === "undefined" ) {
		amount = '';
	}
	if( typeof price === "undefined" ) {
		price = '';
	}
	if( typeof item === "undefined" ) {
		item = '';
	}
	if( typeof pt === "undefined" ) {
		pt = '';
	}
	if( typeof code === "undefined" ) {
		code = '';
	}
	if( typeof odid === "undefined" ) {
		odid = '';
	}
    td0.innerHTML = '<b id="code">'+code+'</b><input type="hidden" name="odid[]" id="odid" value="'+odid+'">';
    td1.innerHTML = '<input required type="text" autocomplete="off" value="'+name+'" name="tovar_name[]" id="tovar_name" class="f2" placeholder="Введите код заказа для поиска товара" />';
    td2.innerHTML = '<input required type="text" autocomplete="off" value="'+ed+'" name="tovar_ed[]" id="tovar_ed" class="f3" />';
    td3.innerHTML = '<input type="text" autocomplete="off" value="'+okei+'" name="tovar_okei[]" id="tovar_okei" class="f1" />';
    td4.innerHTML = '<input type="text" autocomplete="off" value="'+massa+'" name="tovar_massa[]" id="tovar_massa" class="f4" />';
    td5.innerHTML = '<input required type="text" autocomplete="off" min="1" value="'+amount+'" name="tovar_kolvo[]" id="tovar_kolvo" class="f5 tovar_kol" />';
	td6.innerHTML = '<input required type="number" autocomplete="off" min="0" value="'+price+'" name="tovar_tcena[]" id="tovar_tcena" class="f6" /><input type="hidden" name="item[]" id="item" value="'+item+'" class="f7"><input type="hidden" name="pt[]" id="pt" value="'+pt+'" class="f8">';
    td7.innerHTML = '<i class="fa fa-minus-square fa-2x" style="color: red;" onclick="deleteRow(this);"></i>';

	$( ".f2" ).autocomplete({
		source: "search_prod.php",
		minLength: 2,
		select: function( event, ui ) {
			$(this).parents('tr').find('#code').text(ui.item.code);
			$(this).parents('tr').find('#odid').val(ui.item.odid);
			$(this).parents('tr').find('#item').val(ui.item.id);
			$(this).parents('tr').find('#pt').val(ui.item.PT);
			$(this).parents('tr').find('#tovar_tcena').val(ui.item.Price);
			$(this).parents('tr').find('#tovar_kolvo').val(ui.item.Amount);
		}
	});

	$( ".f2" ).on("keyup", function() {
		if( $(this).val().length < 2 ) {
			$(this).parents('tr').find('#code').text('');
			$(this).parents('tr').find('#odid').val('');
			$(this).parents('tr').find('#item').val('');
			$(this).parents('tr').find('#pt').val('');
			$(this).parents('tr').find('#tovar_tcena').val('');
			$(this).parents('tr').find('#tovar_kolvo').val('');
		}
	});
}
function deleteRow(r)
{
	var i=r.parentNode.parentNode.rowIndex;
	document.getElementById('tab1').deleteRow(i);
}
</script>
<input name="n" type="hidden" value="1">
<input name="pfid" type="hidden" value="<?=$id?>">
	<br>
	<div align="center" style="width: 49%; display: inline-block;">
		<input type="submit" value="Печатать накладную" class="button" onclick="set_target('pdf', 'report', 'torg12');">
		от
		<input type="text" name="date_torg12" id="date_torg12" value="<?=$nakladnaya_date?>" class="date" style="width: 100px;" readonly>
	</div>
	<div align="center" style="width: 49%; display: inline-block;">
		<strong>Сообщение для клиента.</strong>
		<textarea name="text" class="comment">Внимание! Оплата данного счета означает согласие с условиями поставки товара. Уведомление об оплате обязательно, в противном случае не гарантируется наличие товара на складе. Товар отпускается по факту прихода денег на р/с Поставщика, самовывозом, при наличии доверенности и паспорта.
		</textarea>
		<input type="submit" value="Печатать счет" class="button" onclick="set_target('pdf', 'report', 'schet');">
		от
		<input type="text" name="date_schet" id="date_schet" value="<?=$schet_date?>" class="date" style="width: 100px;" readonly>
	</div>
	<br>
	<br>
	<div align="center">
		Или<br>
		<input type="submit" value="Сохранить внесенные изменения" class="button" onclick="set_target('', '', '');">
		<br>чтобы вернуться к ним позже.
	</div>
</form>

<script language="javascript">

function set_target(action, target, blanc) {
	//if target is not empty form is submitted into a new window


	var frm = document.getElementById('formdiv');
	frm.action = 'blanc.php?do='+blanc;
	frm.target = target;
}

$(document).ready(function() {
	//$('#gruzopoluchatel_<?=$gruzopoluchatel?>').click();
	//$('#zakazthik_<?=$postavshik?>').click();
	$('input[name="gruzopoluchatel"][value="<?=$gruzopoluchatel?>"]').click();
	$('input[name="postavshik"][value="<?=$postavshik?>"]').click();

	<?
	if( $platelshik_id ) {
		// Заполняем реквизиты плательщика
		$query = "SELECT Naimenovanie, Jur_adres, Telefony, INN, OKPO, KPP, Schet, Bank, BIK, KS, Bank_adres FROM Kontragenty WHERE KA_ID = {$platelshik_id}";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		echo "$('#platelshik_name').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'Naimenovanie'))."');";
		echo "$('#platelshik_id').val('".$platelshik_id."');";
		echo "$('#platelshik_inn').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'INN'))."');";
		echo "$('#platelshik_kpp').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'KPP'))."');";
		echo "$('#platelshik_okpo').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'OKPO'))."');";
		echo "$('#platelshik_adres').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'Jur_adres'))."');";
		echo "$('#platelshik_tel').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'Telefony'))."');";
		echo "$('#platelshik_schet').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'Schet'))."');";
		echo "$('#platelshik_bank').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'Bank'))."');";
		echo "$('#platelshik_bik').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'BIK'))."');";
		echo "$('#platelshik_ks').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'KS'))."');";
		echo "$('#platelshik_bank_adres').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'Bank_adres'))."');";
	}
	if( $gruzopoluchatel_id ) {
		// Заполняем реквизиты грузополучателя
		$query = "SELECT Naimenovanie, Jur_adres, Telefony, INN, OKPO, KPP, Schet, Bank, BIK, KS, Bank_adres FROM Kontragenty WHERE KA_ID = {$gruzopoluchatel_id}";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		echo "$('#gruzopoluchatel_name').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'Naimenovanie'))."');";
		echo "$('#gruzopoluchatel_id').val('".$gruzopoluchatel_id."');";
		echo "$('#gruzopoluchatel_inn').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'INN'))."');";
		echo "$('#gruzopoluchatel_kpp').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'KPP'))."');";
		echo "$('#gruzopoluchatel_okpo').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'OKPO'))."');";
		echo "$('#gruzopoluchatel_adres').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'Jur_adres'))."');";
		echo "$('#gruzopoluchatel_tel').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'Telefony'))."');";
		echo "$('#gruzopoluchatel_schet').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'Schet'))."');";
		echo "$('#gruzopoluchatel_bank').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'Bank'))."');";
		echo "$('#gruzopoluchatel_bik').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'BIK'))."');";
		echo "$('#gruzopoluchatel_ks').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'KS'))."');";
		echo "$('#gruzopoluchatel_bank_adres').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'Bank_adres'))."');";
	}
	if( $postavshik_id ) {
		// Заполняем реквизиты поставщика
		$query = "SELECT Naimenovanie, Jur_adres, Telefony, INN, OKPO, KPP, Schet, Bank, BIK, KS, Bank_adres FROM Kontragenty WHERE KA_ID = {$postavshik_id}";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		echo "$('#postavshik_name').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'Naimenovanie'))."');";
		echo "$('#postavshik_id').val('".$postavshik_id."');";
		echo "$('#postavshik_inn').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'INN'))."');";
		echo "$('#postavshik_kpp').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'KPP'))."');";
		echo "$('#postavshik_okpo').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'OKPO'))."');";
		echo "$('#postavshik_adres').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'Jur_adres'))."');";
		echo "$('#postavshik_tel').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'Telefony'))."');";
		echo "$('#postavshik_schet').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'Schet'))."');";
		echo "$('#postavshik_bank').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'Bank'))."');";
		echo "$('#postavshik_bik').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'BIK'))."');";
		echo "$('#postavshik_ks').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'KS'))."');";
		echo "$('#postavshik_bank_adres').val('".mysqli_real_escape_string($mysqli, mysqli_result($res,0,'Bank_adres'))."');";
	}
	?>
<?
	// Заполняем список товаров на экране
	$query = "SELECT OD.Code, PFP.OD_ID, PFP.ItemID, PFP.PT_ID, PFP.Amount, PFP.Price, PFP.Zakaz, PFP.tovar_ed, PFP.tovar_okei, PFP.tovar_massa
			  FROM PrintFormsProducts PFP
			  LEFT JOIN OrdersData OD ON OD.OD_ID = PFP.OD_ID
			  WHERE PF_ID = {$id}
			  ORDER BY sort";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		$Zakaz = addslashes(htmlspecialchars($row["Zakaz"]));
		$tovar_ed = addslashes(htmlspecialchars($row["tovar_ed"]));
		$tovar_okei = addslashes(htmlspecialchars($row["tovar_okei"]));
		$tovar_massa = addslashes(htmlspecialchars($row["tovar_massa"]));
		echo "addRow('{$tovar_ed}', '{$tovar_okei}', '{$tovar_massa}', '{$Zakaz}', '{$row["Amount"]}', '{$row["Price"]}', '{$row["ItemID"]}', '{$row["PT_ID"]}', '{$row["Code"]}', '{$row["OD_ID"]}');";
	}
?>
	$( "#platelshik_name" ).autocomplete({
		source: "kontragenty.php",
		minLength: 2,
		autoFocus: true,
		select: function( event, ui ) {
			$('#platelshik_id').val(ui.item.id);
			$('#platelshik_inn').val(ui.item.INN);
			$('#platelshik_kpp').val(ui.item.KPP);
			$('#platelshik_okpo').val(ui.item.OKPO);
			$('#platelshik_adres').val(ui.item.Jur_adres);
			$('#platelshik_tel').val(ui.item.Telefony);
			$('#platelshik_schet').val(ui.item.Schet);
			$('#platelshik_bank').val(ui.item.Bank);
			$('#platelshik_bik').val(ui.item.BIK);
			$('#platelshik_ks').val(ui.item.KS);
			$('#platelshik_bank_adres').val(ui.item.Bank_adres);
		}
	});

	$( "#platelshik_name" ).on("keyup", function() {
		if( $( "#platelshik_name" ).val().length < 2 ) {
			$('#platelshik_id').val('');
			$('#platelshik_inn').val('');
			$('#platelshik_kpp').val('');
			$('#platelshik_okpo').val('');
			$('#platelshik_adres').val('');
			$('#platelshik_tel').val('');
			$('#platelshik_schet').val('');
			$('#platelshik_bank').val('');
			$('#platelshik_bik').val('');
			$('#platelshik_ks').val('');
			$('#platelshik_bank_adres').val('');
		}
	});

	$( "#gruzopoluchatel_name" ).autocomplete({
		source: "kontragenty.php",
		minLength: 2,
		autoFocus: true,
		select: function( event, ui ) {
			$('#gruzopoluchatel_id').val(ui.item.id);
			$('#gruzopoluchatel_inn').val(ui.item.INN);
			$('#gruzopoluchatel_kpp').val(ui.item.KPP);
			$('#gruzopoluchatel_okpo').val(ui.item.OKPO);
			$('#gruzopoluchatel_adres').val(ui.item.Jur_adres);
			$('#gruzopoluchatel_tel').val(ui.item.Telefony);
			$('#gruzopoluchatel_schet').val(ui.item.Schet);
			$('#gruzopoluchatel_bank').val(ui.item.Bank);
			$('#gruzopoluchatel_bik').val(ui.item.BIK);
			$('#gruzopoluchatel_ks').val(ui.item.KS);
			$('#gruzopoluchatel_bank_adres').val(ui.item.Bank_adres);
		}
	});

	$( "#gruzopoluchatel_name" ).on("keyup", function() {
		if( $( "#gruzopoluchatel_name" ).val().length < 2 ) {
			$('#gruzopoluchatel_id').val('');
			$('#gruzopoluchatel_inn').val('');
			$('#gruzopoluchatel_kpp').val('');
			$('#gruzopoluchatel_okpo').val('');
			$('#gruzopoluchatel_adres').val('');
			$('#gruzopoluchatel_tel').val('');
			$('#gruzopoluchatel_schet').val('');
			$('#gruzopoluchatel_bank').val('');
			$('#gruzopoluchatel_bik').val('');
			$('#gruzopoluchatel_ks').val('');
			$('#gruzopoluchatel_bank_adres').val('');
		}
	});

	$( "#postavshik_name" ).autocomplete({
		source: "kontragenty.php",
		minLength: 2,
		autoFocus: true,
		select: function( event, ui ) {
			$('#postavshik_id').val(ui.item.id);
			$('#postavshik_inn').val(ui.item.INN);
			$('#postavshik_kpp').val(ui.item.KPP);
			$('#postavshik_okpo').val(ui.item.OKPO);
			$('#postavshik_adres').val(ui.item.Jur_adres);
			$('#postavshik_tel').val(ui.item.Telefony);
			$('#postavshik_schet').val(ui.item.Schet);
			$('#postavshik_bank').val(ui.item.Bank);
			$('#postavshik_bik').val(ui.item.BIK);
			$('#postavshik_ks').val(ui.item.KS);
			$('#postavshik_bank_adres').val(ui.item.Bank_adres);
		}
	});

	$( "#postavshik_name" ).on("keyup", function() {
		if( $( "#postavshik_name" ).val().length < 2 ) {
			$('#postavshik_id').val('');
			$('#postavshik_inn').val('');
			$('#postavshik_kpp').val('');
			$('#postavshik_okpo').val('');
			$('#postavshik_adres').val('');
			$('#postavshik_tel').val('');
			$('#postavshik_schet').val('');
			$('#postavshik_bank').val('');
			$('#postavshik_bik').val('');
			$('#postavshik_ks').val('');
			$('#postavshik_bank_adres').val('');
		}
	});
 });
</script>
</div>

<?
	include "footer.php";
?>
