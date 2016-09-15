<?
	include "config.php";

	$title = 'Товарная накладная';
	include "header.php";

	// Записываем в сессию и в базу порядковый номер накладной
	if( empty($_SESSION["torg_year"]) or empty($_SESSION["torg_count"]) ) {
		$Year = date('Y');
		$query = "SELECT COUNT(1)+1 Cnt FROM NakladnayaCount WHERE Year = {$Year}";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$Count = mysqli_result($res,0,'Cnt');

		$query = "INSERT INTO NakladnayaCount SET Year = {$Year}, Count = {$Count}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		$_SESSION["torg_year"] = $Year;
		$_SESSION["torg_count"] = $Count;
	}
	$Number = str_pad($_SESSION["torg_count"], 8, '0', STR_PAD_LEFT); // Дописываем нули к номеру накладной

	// Формируем список строк для печати
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
</style>

<div style="width:730px; max-width:730px; margin: auto; margin-bottom: 50px;">
	<h1>Товарная накладная  форма № ТОРГ-12</h1>
        <form action="" method="post" id="formdiv">
        <table width="100%" border="0" cellspacing="4" class="forms">
          <tbody><tr class="forms">
            <td width="250" align="left"> Товарно-транспортная накладная:</td>
            <td valign="top">№
              <input required value="<?=$Number?>" type="text" autocomplete="off" name="nomer" id="nomer" class="forminput_seriya" placeholder="" style="width: 35%;">
              от
              <input required type="text" autocomplete="off" name="date" id="date" value="<?= date("d.m.Y") ?>" class="date forminput_N" style="width: 35%;" readonly></td>
          </tr>
          <tr class="forms" style="display: none;">
            <td align="left" valign="top">Серия:</td>
            <td valign="top"><input type="text" autocomplete="off" name="seriya_ttn" id="seriya_ttn" class="forminput" placeholder="" style="width: 35%;"></td>
          </tr>
        </tbody></table>
        <br>
        <table width="100%" border="0" cellspacing="4" class="forms">
          <tbody><tr align="left">
            <td colspan="2"><strong>Информация о грузоотправителе:</strong></td>
            </tr>
          <tr>
            <td width="250" align="left" valign="top">Название ООО или фамилия ИП:</td>
            <td align="left" valign="top"><input required type="text" autocomplete="off" value="<?=htmlspecialchars($Name)?>" name="gruzootpravitel_name" id="gruzootpravitel_name" class="forminput" placeholder=""></td>
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
            <td colspan="2" align="left" valign="top"><strong>Банковские реквизиты грузоотправителя</strong></td>
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
            	<input required type="text" autocomplete="off" name="platelshik_name" id="platelshik_name" class="forminput" placeholder="Введите минимум 2 символа для поиска контрагента">
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
            <td colspan="2" align="left" valign="top"><strong>Банковские реквизиты плательщика&nbsp;</strong></td>
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
                <td><input type="text" autocomplete="off" name="gruzopoluchatel_name" id="gruzopoluchatel_name" class="forminput" placeholder=""></td>
              </tr>
              <tr>
                <td width="245">ИНН:</td>
                <td><input type="text" autocomplete="off" name="gruzopoluchatel_inn" id="gruzopoluchatel_inn" class="forminput" placeholder=""></td>
              </tr>
              <tr>
                <td width="245">КПП:</td>
                <td><input type="text" autocomplete="off" name="gruzopoluchatel_kpp" id="gruzopoluchatel_kpp" class="forminput" placeholder=""></td>
              </tr>
              <tr>
                <td>ОКПО:</td>
                <td><input type="text" autocomplete="off" name="gruzopoluchatel_okpo" id="gruzopoluchatel_okpo" class="forminput" placeholder=""></td>
              </tr>
              <tr>
                <td width="245">Адрес:</td>
                <td><input type="text" autocomplete="off" name="gruzopoluchatel_adres" id="gruzopoluchatel_adres" class="forminput" placeholder=""></td>
              </tr>
              <tr>
                <td width="245">Телефоны:</td>
                <td><input type="text" autocomplete="off" name="gruzopoluchatel_tel" id="gruzopoluchatel_tel" class="forminput" placeholder=""></td>
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
                <td><input type="text" autocomplete="off" name="postavshik_name" id="postavshik_name" class="forminput" placeholder=""></td>
              </tr>
              <tr>
                <td width="245">ИНН:</td>
                <td><input type="text" autocomplete="off" name="postavshik_inn" id="postavshik_inn" class="forminput" placeholder=""></td>
              </tr>
              <tr>
                <td width="245">КПП:</td>
                <td><input type="text" autocomplete="off" name="postavshik_kpp" id="postavshik_kpp" class="forminput" placeholder=""></td>
              </tr>
              <tr>
                <td width="245">Адрес:</td>
                <td><input type="text" autocomplete="off" name="postavshik_adres" id="postavshik_adres" class="forminput" placeholder=""></td>
              </tr>
              <tr>
                <td width="245">Телефоны:</td>
                <td><input type="text" autocomplete="off" name="postavshik_tel" id="postavshik_tel" class="forminput" placeholder=""></td>
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
        <table width="100%" border="0" cellspacing="4" class="forms">
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
          <th colspan="7" align="left"><strong>Информация о перевозимом грузе:</strong></th>
          </tr>
        <tr>
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
          <td><input required type="number" autocomplete="off" min="1" name="tovar_kolvo[]" id="tovar_kolvo" class="f5"></td>
          <td><input required type="number" autocomplete="off" min="0" name="tovar_tcena[]" id="tovar_tcena" class="f6"></td>
          <td><i class="fa fa-minus-square fa-2x" style="color: red;" onclick="deleteRow(this);"></i></td>
        </tr>
-->

	</tbody>

	<tbody></tbody>

	<tbody>
		<tr>
			<td colspan="7">
				<i class="fa fa-plus-square fa-2x" style="color: green;" onclick="addRow();"></i>
				<span onclick="addRow();"><font><font> Добавить строку</font></font></span>
			</td>
		</tr>
	</tbody>
</table>





<script type="text/javascript">
var d = document;

function addRow(name, ed, amount, price, item, pt)
{

    // Находим нужную таблицу
    var tbody = d.getElementById('tab1').getElementsByTagName('TBODY')[0];

    // Создаем строку таблицы и добавляем ее
    var row = d.createElement("TR");
    tbody.appendChild(row);

    // Создаем ячейки в вышесозданной строке
    // и добавляем тх
    var td1 = d.createElement("TD");
    var td2 = d.createElement("TD");
    var td3 = d.createElement("TD");
    var td4 = d.createElement("TD");
    var td5 = d.createElement("TD");
    var td6 = d.createElement("TD");
    var td7 = d.createElement("TD");

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
    td1.innerHTML = '<input required type="text" autocomplete="off" value="'+name+'" name="tovar_name[]" id="tovar_name" class="f2" />';
    td2.innerHTML = '<input required type="text" autocomplete="off" value="'+ed+'" name="tovar_ed[]" id="tovar_ed" class="f3" />';
    td3.innerHTML = '<input type="text" autocomplete="off" name="tovar_okei[]" id="tovar_okei" class="f1" />';
    td4.innerHTML = '<input type="text" autocomplete="off" name="tovar_massa[]" id="tovar_massa" class="f4" />';
    td5.innerHTML = '<input required type="number" autocomplete="off" min="1" value="'+amount+'" name="tovar_kolvo[]" id="tovar_kolvo" class="f5" />';
	td6.innerHTML = '<input required type="number" autocomplete="off" min="0" value="'+price+'" name="tovar_tcena[]" id="tovar_tcena" class="f6" /><input type="hidden" name="item[]" value="'+item+'"><input type="hidden" name="pt[]" value="'+pt+'">';
    td7.innerHTML = '<i class="fa fa-minus-square fa-2x" style="color: red;" onclick="deleteRow(this);"></i>';

}
function deleteRow(r)
{
	var i=r.parentNode.parentNode.rowIndex;
	document.getElementById('tab1').deleteRow(i);
}
</script>
<input name="n" type="hidden" value="1">
<br><div align="center">
        <input type="submit" value="Печать" class="button" onclick="set_target('pdf', 'report');">
        </div>
</form>

<script language="javascript">

function set_target(action, target) {
	//if target is not empty form is submitted into a new window


	var frm = document.getElementById('formdiv');
	frm.action = 'blanc.php?do=torg12';
	frm.target = target;
}

$(document).ready(function() {
<?
	$query = "SELECT ODD_ODB.ItemID
					,ODD_ODB.PT_ID
					,ODD_ODB.Amount
					,ODD_ODB.Price
					,ODD_ODB.Zakaz
			  FROM (SELECT ODD.OD_ID
						  ,ODD.ODD_ID ItemID
						  ,IFNULL(PM.PT_ID, 2) PT_ID
						  ,ODD.Amount
						  ,ODD.Price
						  ,CONCAT(IFNULL(PM.Model, 'Столешница'), ' ', IFNULL(CONCAT(ODD.Length, IF(ODD.Width > 0, CONCAT('х', ODD.Width), ''), IFNULL(CONCAT('/', IFNULL(ODD.PieceAmount, 1), 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, '')) Zakaz
					FROM OrdersDataDetail ODD
					LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
					LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
					LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
					UNION
					SELECT ODB.OD_ID
						  ,ODB.ODB_ID ItemID
						  ,0 PT_ID
						  ,ODB.Amount
						  ,ODB.Price
						  ,CONCAT(IFNULL(BL.Name, ODB.Other)) Zakaz
					FROM OrdersDataBlank ODB
					LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
					) ODD_ODB
			  WHERE ODD_ODB.OD_ID IN ({$id_list})
			  AND ODD_ODB.PT_ID IN({$product_types})
			  GROUP BY ODD_ODB.itemID
			  ORDER BY ODD_ODB.OD_ID, ODD_ODB.PT_ID DESC, ODD_ODB.itemID";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		$Zakaz = htmlspecialchars($row["Zakaz"]);
		echo "addRow('{$Zakaz}', 'шт', '{$row["Amount"]}', '{$row["Price"]}', '{$row["ItemID"]}', '{$row["PT_ID"]}');";
	}
?>
	$( "#platelshik_name" ).autocomplete({
		source: "kontragenty.php",
		minLength: 2,
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

//	//функция выполняется при загрузке документа
//	$("#person_sum").blur(on_price_change);
//	$("#person_sumuslugi").blur(on_price_change);
//	$("#person_sumitog").blur(on_total_price_change);
 });

//function on_price_change() {
//	check_float($("#person_sum"), 2);
//	check_float($("#person_sumuslugi"), 2);
//	//получаем сумму оплаты и услуг и помещаем ее в итого
//	var value = get_float_value($("#price"))+get_float_value($("#service_price"));
//
//	if (value!=0) {
//		$("#person_sumitog").val(value.toFixed(2));
//	} else {
//		$("#person_sumitog").val("");
//	}
//}

//function on_total_price_change() {
//	check_float($("#person_sumitog"), 2);
//}
</script>

</div>

<!--<iframe frameborder="0" height="1550px" marginheight="0" marginwidth="0" scrolling="no" src="http://service-online.su/forms/buh/tovarnaya-nakladnaya/form.php" width="730px"></iframe>-->

</body></html>
