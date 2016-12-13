<?
	include "config.php";

	$title = 'Счет на оплату';
	include "header.php";

//	// Проверка прав на доступ к экрану
//	if( !in_array('print_schet', $Rights) ) {
//		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
//		die('Недостаточно прав для совершения операции');
//	}

	// Записываем в сессию и в базу порядковый номер накладной
	if( empty($_SESSION["schet_year"]) or empty($_SESSION["schet_count"]) ) {
		$Year = date('Y');
		$query = "SELECT COUNT(1)+1 Cnt FROM SchetCount WHERE Year = {$Year}";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$Count = mysqli_result($res,0,'Cnt');

		$query = "INSERT INTO SchetCount SET Year = {$Year}, Count = {$Count}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		$_SESSION["schet_year"] = $Year;
		$_SESSION["schet_count"] = $Count;
	}
	$Number = str_pad($_SESSION["schet_count"], 8, '0', STR_PAD_LEFT); // Дописываем нули к номеру накладной

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

	.formdiv {
		width: 100%;
		margin: 5px 0;
		vertical-align: top;
		//background: #f1f5f6;
		border-radius: 5px;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
		//border: #bad7ff solid 2px;
	}

	.forms {
		//border: 1px solid #999;
		margin: 1%;
		width: 98%;
	}

	.left {
		min-width: 12%;
		max-width: 14%;
	}

	.forms .left {
		width: 25%;
	}

	.forms .date_nomer {
		width: 35%;
		display: inline-block;
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
	<h1>Счет на оплату</h1>
<form action="" method="post" id="formdiv">
          <div class="formdiv">
        <table border="0" cellspacing="4" class="forms">
          <tbody><tr class="forms">
            <td align="left" class="left"> Счет:</td>
            <td valign="top">№ <div class="date_nomer"><input required value="<?=$Number?>" type="text" autocomplete="off" name="nomer" id="nomer" class="forminput_seriya" placeholder=""></div>
              от <div class="date_nomer"><input required type="text" autocomplete="off" name="date" id="date" value="<?= date("d.m.Y") ?>" class="date forminput_N" readonly></div></td>
          </tr>
        </tbody></table>
        <br>
        <table border="0" cellspacing="4" class="forms">
          <tbody><tr align="left">
            <td><strong>Информация о продавце:</strong></td>
            <td>
                        </td>
           </tr>
          </tbody><tbody id="auto_recording_up_forms">

          <tr>
            <td class="left">Название:</td>
            <td><input required type="text" autocomplete="off" value="<?=htmlspecialchars($Name)?>" name="destination_name" id="destination_name" class="forminput" placeholder="ООО «Фортуна»"></td>
          </tr>
          <tr>
            <td>Адрес:</td>
            <td><input type="text" autocomplete="off" value="<?=htmlspecialchars($Addres)?>" name="destination_adres" id="destination_adres" class="forminput" placeholder="г. Москва, ул. Ленина, 2"></td>
          </tr>
          <tr>
            <td>ИНН:</td>
            <td><input type="text" autocomplete="off" value="<?=htmlspecialchars($INN)?>" name="destination_INN" id="destination_INN" class="forminput" placeholder="7701123456"></td>
          </tr>
          <tr>
            <td>КПП:</td>
            <td><input type="text" autocomplete="off" value="<?=htmlspecialchars($KPP)?>" name="destination_KPP" id="destination_KPP" class="forminput" placeholder="123456789"></td>
          </tr>
          <tr>
            <td>Руководитель/уполномоч. лицо:</td>
            <td><input type="text" autocomplete="off" value="<?=htmlspecialchars($Dir)?>" name="dorector" id="dorector" class="forminput" placeholder="Иванов И. И."></td>
          </tr>
          <tr>
            <td>Главный бухгалтер/уполномоч. лицо:</td>
            <td valign="top"><input type="text" autocomplete="off" name="bux" id="bux" class="forminput" placeholder="Попова Л. П."></td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td valign="top">&nbsp;</td>
          </tr>
          <tr>
            <td colspan="2"><strong>Банковские реквизиты продавца:</strong></td>
            </tr>
          <tr>
            <td align="left">Расчетный счет получателя платежа:</td>
            <td valign="top"><input type="text" autocomplete="off" value="<?=htmlspecialchars($RS)?>" name="destination_szhet" id="destination_szhet" class="forminput" placeholder="20 цифр" maxlength="20"></td>
          </tr>
          <tr>
            <td align="left">В банке (наименование банка): </td>
            <td valign="top"><input type="text" autocomplete="off" value="<?=htmlspecialchars($Bank)?>" name="destination_bank" id="destination_bank" class="forminput"></td>
          </tr>
          <tr>
            <td align="left">БИК:</td>
            <td valign="top"><input type="text" autocomplete="off" value="<?=htmlspecialchars($BIK)?>" name="destination_BIK" id="destination_BIK" class="forminput" placeholder="9 цифр" maxlength="9"></td>
          </tr>
          <tr>
            <td align="left">Корреспондентский счет:</td>
            <td valign="top"><input type="text" autocomplete="off" value="<?=htmlspecialchars($KS)?>" name="destination_KS" id="destination_KS" class="forminput" placeholder="20 цифр" maxlength="20"></td>
          </tr>
          </tbody>
                  </table>
        <br>
        <table border="0" cellspacing="4" class="forms">
          <tbody><tr>
            <td colspan="2" valign="top"><strong>Информация  о покупателе:</strong></td>
          </tr>
          <tr>
            <td valign="top" class="left">Название ООО или ФИО:</td>
            <td valign="top">
            	<input type="hidden" name="pokupatel_id" id="pokupatel_id" class="forminput">
            	<input required type="text" autocomplete="off" name="pokupatel" id="pokupatel" class="forminput" placeholder="Введите минимум 2 символа для поиска контрагента">
            </td>
          </tr>
          <tr>
            <td valign="top">Адрес</td>
            <td valign="top"><input type="text" autocomplete="off" name="pokupatel_adres" id="pokupatel_adres" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td valign="top">ИНН:</td>
            <td valign="top"><input type="text" autocomplete="off" name="pokupatel_inn" id="pokupatel_inn" class="forminput" placeholder=""></td>
          </tr>
          <tr>
            <td valign="top">КПП:</td>
            <td valign="top"><input type="text" autocomplete="off" name="pokupatel_kpp" id="pokupatel_kpp" class="forminput" placeholder=""></td>
          </tr>
      </tbody></table>
        <br>
        <table border="0" cellspacing="4" class="forms">
          <tbody><tr class="forms">
            <td colspan="2" align="left" valign="top"><strong>Ставка НДС :</strong></td>
          </tr>
          <tr>
            <td align="left" valign="top" class="left">НДС:</td>
            <td valign="top"><style type="text/css">
                        #nds1{display:none}
                </style>
              <label>
                <input type="radio" name="nds" value="0" id="nds_1" checked="checked" onclick="ndss(this.value)">
                Без НДС</label>
              <label>
                <input type="radio" name="nds" value="1" id="nds_2" onclick="ndss(this.value)">
               с НДС в том числе </label>

              <script type="text/javascript">
                        <!--
                        function ndss(val){
                                if (val > 0){
									$("#nds1").show(600);
                                }
                                else{
                                   $("#nds1").hide(600);
                                }
                        }
                        -->
                </script></td>
          </tr>
          <tr id="nds1">
            <td align="left" valign="top">% ставка НДС </td>
            <td align="left" valign="top"><select name="nds_stavka" class="formselect" id="nds_stavka">
              <option value="0">0 %</option>
              <option value="10">10 % </option>
              <option value="18">18 % </option>
              </select></td>
          </tr>
      </tbody></table>
        <br>
<style type="text/css">
.f1 {
	width:40%;

}
.f2 {
	width:10%;
}
.f3 {
	width:16%;
}
#nds {
	min-width:100%;
}


</style>
<table border="0" cellspacing="4" class="forms" id="tab1">
        <tbody><tr>
          <th colspan="10" align="left"><strong>Наименование товара, работ, услуг, подлежащих оплате:</strong></th>
          </tr>
        <tr>
          <th bgcolor="#D6D6D6" class="f1">Наименование товара, работ, услуг</th>
          <th bgcolor="#D6D6D6" class="f2"><strong>Ед. </strong>изм.<br>
            <em>(шт., кг и т.д.)</em></th>
          <th bgcolor="#D6D6D6" class="f3">Кол-во<br>
            <em>(единиц)</em></th>
          <th bgcolor="#D6D6D6" class="f3"><p>Цена<br>
          за единицу</p></th>
          <th bgcolor="#D6D6D6" class="f3">Сумма</th>
          <th colspan="5" bgcolor="#D6D6D6">&nbsp;</th>
          </tr>
<!--
        <tr>
          <td><input type="text" autocomplete="off" name="tovar_name[]" id="tovar_name"></td>
          <td><input type="text" autocomplete="off" name="tovar_ed[]" id="tovar_ed"></td>
          <td><input type="text" autocomplete="off" name="tovar_kol[]" id="tovar_kol" class="tovar_kol"></td>
          <td><input type="text" autocomplete="off" name="tovar_cena[]" id="tovar_cena" class="tovar_cena"></td>
          <td><input type="text" autocomplete="off" name="tovar_sum[]" id="tovar_sum" class="tovar_sum"></td>
          <td><i class="fa fa-minus-square fa-2x" style="color: red;" onclick="deleteRow(this);"></i></td>
        </tr>
-->


    </tbody><tbody>
    </tbody>
         <tbody><tr>
          <td colspan="2" rowspan="2">
			<i class="fa fa-plus-square fa-2x" style="color: green;" onclick="addRow();"></i>
         	<span onclick="addRow();"><font><font> Добавить строку</font></font></span>     </td>
          <td colspan="2" align="right"><span id="nds_protc">Без налога (НДС)</span></td>
          <td colspan="6"><span id="nds_itog">0.00</span></td>
          </tr>
         <tr>
           <td colspan="2" align="right">Итого:</td>
           <td colspan="6"><span id="itog">0.00</span>
             <input name="itog1" id="itog1" type="hidden" value=""></td>
         </tr>

</tbody></table>

<div align="center">
<strong>Сообщение для клиента.</strong>
<textarea name="text" class="comment">Внимание! Оплата данного счета означает согласие с условиями поставки товара. Уведомление об оплате обязательно, в противном случае не гарантируется наличие товара на складе. Товар отпускается по факту прихода денег на р/с Поставщика, самовывозом, при наличии доверенности и паспорта.
</textarea></div>

<script>
var fn = function(){
	var tr = $(this).parents("tr").get(0);
	var tovar_kol = $("input.tovar_kol", tr).val();
	var tovar_cena = $("input.tovar_cena", tr).val();
	var tovar_sum = $("input.tovar_sum", tr).val();

	var itog = $("#itog1").val();

	tovar_kol = proverka(tovar_kol);
	tovar_cena = proverka(tovar_cena);

	itog = itog-tovar_sum;


	$("input.tovar_kol", tr).val(tovar_kol);
	$("input.tovar_cena", tr).val(tovar_cena);


	if (parseFloat(tovar_kol).toFixed() != "NaN")$("input.tovar_kol", tr).val(tovar_kol);
	if (parseFloat(tovar_cena).toFixed() != "NaN")$("input.tovar_cena", tr).val(parseFloat(tovar_cena).toFixed());


	tovar_sum = parseFloat(tovar_kol*tovar_cena).toFixed();
	if (tovar_sum>0)
	{
		$("input.tovar_sum", tr).val(tovar_sum);
	}

	itog = parseFloat(Number(itog) + Number(tovar_sum)).toFixed();
	$("#itog1").val(itog);
	$("#itog").html(itog);

	return false;
};



var fn2 = function(){
	var tr = $(this).parents("tr").get(0);
	var tovar_kol = $("input.tovar_kol", tr).val();
	var tovar_cena = $("input.tovar_cena", tr).val();
	var tovar_sum = $("input.tovar_sum", tr).val();

	{
		sum = tovar_kol*tovar_cena;
	}

	var itog = $("#itog1").val();
	itog = itog-sum;

	tovar_sum = proverka(tovar_sum);

	$("input.tovar_sum", tr).val(tovar_sum);


	if (parseFloat(tovar_sum).toFixed() != "NaN")$("input.tovar_sum", tr).val(parseFloat(tovar_sum).toFixed());

	if ((tovar_kol == 0 || tovar_kol < 0) && tovar_sum>0)
	{
		tovar_kol =1;
		$("input.tovar_kol", tr).val(tovar_kol);
	}

	if (tovar_sum>0)
	{
		tovar_cena = tovar_sum/tovar_kol;
		tovar_cena = parseFloat(tovar_cena).toFixed();
		$("input.tovar_cena", tr).val(tovar_cena);

	}
	tovar_sum = parseFloat(tovar_kol*tovar_cena).toFixed();
	if (tovar_sum>0)
	{
		$("input.tovar_sum", tr).val(tovar_sum);
	}

	tovar_sum = $("input.tovar_sum", tr).val();
	itog = parseFloat(Number(itog) + Number(tovar_sum)).toFixed();
	$("#itog1").val(itog);
	$("#itog").html(itog);

	return false;
};

var fn3 = function(){
	var tr = $(this).parents("tr").get(0);
	var tovar_sum = $("input.tovar_sum", tr).val();
	var itog = $("#itog1").val();


	itog = parseFloat(Number(itog) - Number(tovar_sum)).toFixed();
	$("#itog1").val(itog);
	$("#itog").html(itog);

	return false;
};

var fn4 = function(){
	var nds = $(":radio[name='nds']:checked").val();

	if (nds == 0)
	{
		$("#nds_protc").html('Без налога (НДС)');
		$("#nds_itog").html('0.00');
	}
	else
	{
		var nds_stavka = $("#nds_stavka").val();
		var itog = $("#itog1").val();

		$("#nds_protc").html('В том числе НДС ('+nds_stavka+'%)');

		if (nds == 1)
		{
			nds_itog = parseFloat(Number(itog)/(Number(nds_stavka)+100)* Number(nds_stavka)).toFixed();
		}
		else
		{
			nds_itog = parseFloat(Number(itog)*(Number(nds_stavka)/100)).toFixed();
			itog = parseFloat(Number(itog)+Number(nds_itog)).toFixed();
		}

		if (nds_itog == 'NaN')
		{
			nds_itog = '0.00'
		}

		$("#nds_itog").html(nds_itog);


	}




	 return false;
   };



$(function(){
	$(document).on('blur',"input.tovar_kol",fn);
	$(document).on('blur',"input.tovar_kol",fn4);
});
$(function(){
	$(document).on('blur',"input.tovar_cena",fn);
	$(document).on('blur',"input.tovar_cena",fn4);
});

$(function(){
	$(document).on('blur',"input.tovar_sum",fn2);
	$(document).on('blur',"input.tovar_sum",fn4);
});


$(function(){
	$(document).on('click',".delete",fn3);
	$(document).on('click',".delete",fn4);
});

$(function(){
	$(document).on('change',":radio[name='nds']",fn4);
	$(document).on('change',"#nds_stavka",fn4);
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

</script>




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

    row.appendChild(td1);
    row.appendChild(td2);
    row.appendChild(td3);
    row.appendChild(td4);
    row.appendChild(td5);
    row.appendChild(td6);
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
    td1.innerHTML = '<input type="text" autocomplete="off" value="'+name+'" name="tovar_name[]" id="tovar_name" />';
    td2.innerHTML = '<input type="text" autocomplete="off" value="'+ed+'" name="tovar_ed[]" id="tovar_ed" />';
    td3.innerHTML = '<input type="number" autocomplete="off" min="1" value="'+amount+'" name="tovar_kol[]" id="tovar_kol" class="tovar_kol" />';
    td4.innerHTML = '<input type="text" autocomplete="off"  value="'+price+'" name="tovar_cena[]" id="tovar_cena" class="tovar_cena" /><input type="hidden" name="item[]" value="'+item+'"><input type="hidden" name="pt[]" value="'+pt+'">';
    td5.innerHTML = '<input type="text" autocomplete="off" name="tovar_sum1[]" id="tovar_sum1" class="tovar_sum1" />';
    td6.innerHTML = '<i class="fa fa-minus-square fa-2x delete" style="color: red;" onclick="deleteRow(this);"></i>';
	$("input.tovar_cena").blur();
}

function deleteRow(r)
{
	var i=r.parentNode.parentNode.rowIndex;
	document.getElementById('tab1').deleteRow(i);
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
					UNION ALL
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
	$( "#pokupatel" ).autocomplete({
		source: "kontragenty.php",
		minLength: 2,
		select: function( event, ui ) {
			$('#pokupatel_id').val(ui.item.id);
			$('#pokupatel_inn').val(ui.item.INN);
			$('#pokupatel_kpp').val(ui.item.KPP);
			$('#pokupatel_adres').val(ui.item.Jur_adres);
		}
	});

	$( "#pokupatel" ).on("keyup", function() {
		if( $( "#pokupatel" ).val().length < 2 ) {
			$('#pokupatel_id').val('');
			$('#pokupatel_inn').val('');
			$('#pokupatel_kpp').val('');
			$('#pokupatel_adres').val('');
		}
	});
 });


</script>
<input name="n" type="hidden" value="1">
<br><div align="center">
<input type="submit" value="Печать" class="button" onclick="set_target('pdf', 'report');">
        </div>
    </div>
</form>
</div>

<script language="javascript">

function set_target(action, target) {
	//if target is not empty form is submitted into a new window


	var frm = document.getElementById('formdiv');
	frm.action = 'blanc.php?do=schet';
	frm.target = target;
}
</script>

</body></html>
