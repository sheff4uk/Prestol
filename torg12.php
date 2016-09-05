<?
	include "config.php";

	$title = 'Товарная накладная';
	include "header.php";

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
              <input type="text" name="nomer" id="nomer" class="forminput_seriya" placeholder="" style="width: 35%;">
              от
              <input type="text" name="date" id="date" value="<?= date("d.m.Y") ?>" class="date forminput_N" style="width: 35%;" readonly></td>
          </tr>
          <tr class="forms">
            <td align="left" valign="top">Серия:</td>
            <td valign="top"><input type="text" name="seriya_ttn" id="seriya_ttn" class="forminput" placeholder="" style="width: 35%;"></td>
          </tr>
        </tbody></table>
        <br>
        <table width="100%" border="0" cellspacing="4" class="forms">
          <tbody><tr align="left">
            <td colspan="2"><strong>Информация о грузоотправителе:</strong></td>
            </tr>
          <tr>
            <td width="250" align="left" valign="top">Название ООО или фамилия ИП:</td>
            <td align="left" valign="top"><input type="text" value="<?=$Name?>" name="gruzootpravitel_name" id="gruzootpravitel_name" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">ИНН:</td>
            <td align="left" valign="top"><input type="text" value="<?=$INN?>" name="gruzootpravitel_inn" id="gruzootpravitel_inn" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">КПП:</td>
            <td align="left" valign="top"><input type="text" value="<?=$KPP?>" name="gruzootpravitel_kpp" id="gruzootpravitel_kpp" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">ОКПО:</td>
            <td align="left" valign="top"><input type="text" name="gruzootpravitel_okpo" id="gruzootpravitel_okpo" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">Адрес:</td>
            <td align="left" valign="top"><input type="text" value="<?=$Addres?>" name="gruzootpravitel_adres" id="gruzootpravitel_adres" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">Руководитель:</td>
            <td align="left" valign="top"><input type="text" value="<?=$Dir?>" name="gruzootpravitel_director" id="gruzootpravitel_director" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">Главный (старший) бухгалтер:</td>
            <td align="left" valign="top"><input type="text" name="gruzootpravitel_buhgalter" id="gruzootpravitel_buhgalter" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">Телефоны:</td>
            <td align="left" valign="top"><input type="text" value="<?=$Phone?>" name="gruzootpravitel_tel" id="gruzootpravitel_tel" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td colspan="2" align="left" valign="top"><strong>Банковские реквизиты грузоотправителя</strong></td>
            </tr>
          <tr>
            <td width="250" align="left" valign="top">Расчетный счет:</td>
            <td align="left" valign="top"><input type="text" value="<?=$RS?>" name="gruzootpravitel_schet" id="gruzootpravitel_schet" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">В банке (наименование банка):</td>
            <td align="left" valign="top"><input type="text" value="<?=$Bank?>" name="gruzootpravitel_bank" id="gruzootpravitel_bank" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">БИК:</td>
            <td align="left" valign="top"><input type="text" value="<?=$BIK?>" name="gruzootpravitel_bik" id="gruzootpravitel_bik" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">Корреспондентский счет:</td>
            <td align="left" valign="top"><input type="text" value="<?=$KS?>" name="gruzootpravitel_ks" id="gruzootpravitel_ks" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">Местонахождение банка:</td>
            <td align="left" valign="top"><input type="text" name="gruzootpravitel_bank_adres" id="gruzootpravitel_bank_adres" class="forminput" placeholder=""></td>
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
            <td align="left" valign="top"><input type="text" name="platelshik_name" id="platelshik_name" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">ИНН:</td>
            <td align="left" valign="top"><input type="text" name="platelshik_inn" id="platelshik_inn" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">КПП:</td>
            <td align="left" valign="top"><input type="text" name="platelshik_kpp" id="platelshik_kpp" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">ОКПО:</td>
            <td align="left" valign="top"><input type="text" name="platelshik_okpo" id="platelshik_okpo" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">Адрес:</td>
            <td align="left" valign="top"><input type="text" name="platelshik_adres" id="platelshik_adres" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">Телефоны:</td>
            <td align="left" valign="top"><input type="text" name="platelshik_tel" id="platelshik_tel" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td colspan="2" align="left" valign="top"><strong>Банковские реквизиты плательщика&nbsp;</strong></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">Расчетный счет:</td>
            <td align="left" valign="top"><input type="text" name="platelshik_schet" id="platelshik_schet" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">В банке (наименование банка):</td>
            <td align="left" valign="top"><input type="text" name="platelshik_bank" id="platelshik_bank" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">БИК:</td>
            <td align="left" valign="top"><input type="text" name="platelshik_bik" id="platelshik_bik" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">Корреспондентский счет:</td>
            <td align="left" valign="top"><input type="text" name="platelshik_ks" id="platelshik_ks" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td width="250" align="left" valign="top">Местонахождение банка:</td>
            <td align="left" valign="top"><input type="text" name="platelshik_bank_adres" id="platelshik_bank_adres" class="forminput" placeholder=""></td>
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
                <td><input type="text" name="gruzopoluchatel_name" id="gruzopoluchatel_name" class="forminput" placeholder=""></td>
              </tr>
              <tr>
                <td width="245">ИНН:</td>
                <td><input type="text" name="gruzopoluchatel_inn" id="gruzopoluchatel_inn" class="forminput" placeholder=""></td>
              </tr>
              <tr>
                <td width="245">КПП:</td>
                <td><input type="text" name="gruzopoluchatel_kpp" id="gruzopoluchatel_kpp" class="forminput" placeholder=""></td>
              </tr>
              <tr>
                <td>ОКПО:</td>
                <td><input type="text" name="gruzopoluchatel_okpo" id="gruzopoluchatel_okpo" class="forminput" placeholder=""></td>
              </tr>
              <tr>
                <td width="245">Адрес:</td>
                <td><input type="text" name="gruzopoluchatel_adres" id="gruzopoluchatel_adres" class="forminput" placeholder=""></td>
              </tr>
              <tr>
                <td width="245">Телефоны:</td>
                <td><input type="text" name="gruzopoluchatel_tel" id="gruzopoluchatel_tel" class="forminput" placeholder=""></td>
              </tr>
              <tr class="forms">
                <td colspan="2" align="left" valign="top"><strong>Банковские реквизиты грузополучателя:&nbsp;</strong></td>
              </tr>
              <tr class="forms">
                <td width="245" align="left" valign="top">Расчетный счет:</td>
                <td align="left" valign="top"><input type="text" name="gruzopoluchatel_schet" id="gruzopoluchatel_schet" class="forminput" placeholder=""></td>
              </tr>
              <tr class="forms">
                <td width="245" align="left" valign="top">В банке (наименование банка):</td>
                <td align="left" valign="top"><input type="text" name="gruzopoluchatel_bank" id="gruzopoluchatel_bank" class="forminput" placeholder=""></td>
              </tr>
              <tr class="forms">
                <td width="245" align="left" valign="top">БИК:</td>
                <td align="left" valign="top"><input type="text" name="gruzopoluchatel_bik" id="gruzopoluchatel_bik" class="forminput" placeholder=""></td>
              </tr>
              <tr class="forms">
                <td width="245" align="left" valign="top">Корреспондентский счет:</td>
                <td align="left" valign="top"><input type="text" name="gruzopoluchatel_ks" id="gruzopoluchatel_ks" class="forminput" placeholder=""></td>
              </tr>
              <tr class="forms">
                <td width="245" align="left" valign="top">Местонахождение банка:</td>
                <td align="left" valign="top"><input type="text" name="gruzopoluchatel_bank_adres" id="gruzopoluchatel_bank_adres" class="forminput" placeholder=""></td>
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
                <td><input type="text" name="postavshik_name" id="postavshik_name" class="forminput" placeholder=""></td>
              </tr>
              <tr>
                <td width="245">ИНН:</td>
                <td><input type="text" name="postavshik_inn" id="postavshik_inn" class="forminput" placeholder=""></td>
              </tr>
              <tr>
                <td width="245">КПП:</td>
                <td><input type="text" name="postavshik_kpp" id="postavshik_kpp" class="forminput" placeholder=""></td>
              </tr>
              <tr>
                <td width="245">Адрес:</td>
                <td><input type="text" name="postavshik_adres" id="postavshik_adres" class="forminput" placeholder=""></td>
              </tr>
              <tr>
                <td width="245">Телефоны:</td>
                <td><input type="text" name="postavshik_tel" id="postavshik_tel" class="forminput" placeholder=""></td>
              </tr>
              <tr class="forms">
                <td colspan="2" align="left" valign="top"><strong>Банковские реквизиты поставщика:&nbsp;</strong></td>
              </tr>
              <tr class="forms">
                <td width="245" align="left" valign="top">Расчетный счет:</td>
                <td align="left" valign="top"><input type="text" name="postavshik_schet" id="postavshik_schet" class="forminput" placeholder=""></td>
              </tr>
              <tr class="forms">
                <td width="245" align="left" valign="top">В банке (наименование банка):</td>
                <td align="left" valign="top"><input type="text" name="postavshik_bank" id="postavshik_bank" class="forminput" placeholder=""></td>
              </tr>
              <tr class="forms">
                <td width="245" align="left" valign="top">БИК:</td>
                <td align="left" valign="top"><input type="text" name="postavshik_bik" id="postavshik_bik" class="forminput" placeholder=""></td>
              </tr>
              <tr class="forms">
                <td width="245" align="left" valign="top">Корреспондентский счет:</td>
                <td align="left" valign="top"><input type="text" name="postavshik_ks" id="postavshik_ks" class="forminput" placeholder=""></td>
              </tr>
              <tr class="forms">
                <td width="245" align="left" valign="top">Местонахождение банка:</td>
                <td align="left" valign="top"><input type="text" name="postavshik_bank_adres" id="postavshik_bank_adres" class="forminput" placeholder=""></td>
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
          <td><input type="text" name="tovar_name[]" id="tovar_name" class="f2"></td>
          <td><input type="text" name="tovar_ed[]" id="tovar_ed" class="f3"></td>
          <td><input type="text" name="tovar_okei[]" id="tovar_okei" class="f1"></td>
          <td><input type="text" name="tovar_massa[]" id="tovar_massa" class="f4"></td>
          <td><input type="text" name="tovar_kolvo[]" id="tovar_kolvo" class="f5"></td>
          <td><input type="text" name="tovar_tcena[]" id="tovar_tcena" class="f6"></td>
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

function addRow(name, ed, amount, price)
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
    td1.innerHTML = '<input type="text" value="'+name+'" name="tovar_name[]" id="tovar_name" class="f2" />';
    td2.innerHTML = '<input type="text" value="'+ed+'" name="tovar_ed[]" id="tovar_ed" class="f3" />';
    td3.innerHTML = '<input type="text" name="tovar_okei[]" id="tovar_okei" class="f1" />';
    td4.innerHTML = '<input type="text" name="tovar_massa[]" id="tovar_massa" class="f4" />';
    td5.innerHTML = '<input type="text" value="'+amount+'" name="tovar_kolvo[]" id="tovar_kolvo" class="f5" />';
    td6.innerHTML = '<input type="text" value="'+price+'" name="tovar_tcena[]" id="tovar_tcena" class="f6" />';
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
	frm.action = 'blanc.php';
	frm.target = target;
}

$(document).ready(function() {
<?
	$query = "SELECT ODD_ODB.itemID
					,ODD_ODB.PT_ID
					,ODD_ODB.Amount
					,ODD_ODB.Zakaz
			  FROM (SELECT ODD.OD_ID
						  ,ODD.ODD_ID itemID
						  ,IFNULL(PM.PT_ID, 2) PT_ID
						  ,ODD.Amount
						  ,CONCAT(IFNULL(PM.Model, 'Столешница'), ' ', IFNULL(CONCAT(ODD.Length, 'х', ODD.Width, IFNULL(CONCAT('/', ODD.PieceAmount, 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, '')) Zakaz
					FROM OrdersDataDetail ODD
					LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
					LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
					LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
					UNION
					SELECT ODB.OD_ID
						  ,ODB.ODB_ID itemID
						  ,0 PT_ID
						  ,ODB.Amount
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
		echo "addRow('{$row["Zakaz"]}', 'шт', '{$row["Amount"]}');";
	}
?>
//	addRow();

	//функция выполняется при загрузке документа
	$("#person_sum").blur(on_price_change);
	$("#person_sumuslugi").blur(on_price_change);
	$("#person_sumitog").blur(on_total_price_change);
 });

function on_price_change() {
	check_float($("#person_sum"), 2);
	check_float($("#person_sumuslugi"), 2);
	//получаем сумму оплаты и услуг и помещаем ее в итого
	var value = get_float_value($("#price"))+get_float_value($("#service_price"));

	if (value!=0) {
		$("#person_sumitog").val(value.toFixed(2));
	} else {
		$("#person_sumitog").val("");
	}
}

function on_total_price_change() {
	check_float($("#person_sumitog"), 2);
}
</script>

</div>

<!--<iframe frameborder="0" height="1550px" marginheight="0" marginwidth="0" scrolling="no" src="http://service-online.su/forms/buh/tovarnaya-nakladnaya/form.php" width="730px"></iframe>-->

</body></html>
