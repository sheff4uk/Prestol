<?
	include "config.php";
	$title = 'Печатные формы';
	include "header.php";
?>
    <div class="printlist">
        <p><b>Столешница:</b></p>
        <?
        $query = "SELECT WD.WD_ID, WD.Name
                    FROM OrdersData OD
                    RIGHT JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
                    LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
                    LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
                    JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID AND ODS.ST_ID = 6 AND ODS.IsReady != 1
                    JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID
                    JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
                    WHERE ODD.is_check = 1
                    GROUP BY WD.WD_ID";
        $res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
        while( $row = mysqli_fetch_array($res) )
        {
            echo "<p><a class='button btnPrint' href='toprint/step6.php?worker={$row["WD_ID"]}'>{$row["Name"]}</a></p>";
        }
        ?>
    </div>
    <div class="printlist">
        <p><b>Каркас+Cборка:</b></p>
        <?
        $query = "SELECT WD.WD_ID, WD.Name
                    FROM OrdersData OD
                    RIGHT JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
                    LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
                    LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
                    LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
                    JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID AND ODS.ST_ID = 7 AND ODS.IsReady != 1
                    JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID
                    JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
                    WHERE ODD.is_check = 1
                    GROUP BY WD.WD_ID";
        $res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
        while( $row = mysqli_fetch_array($res) )
        {
            echo "<p><a class='button btnPrint' href='toprint/step7.php?worker={$row["WD_ID"]}'>{$row["Name"]}</a></p>";
        }
        ?>
    </div>
    <div class="printlist">
		<p><a class='button btnPrint' href='toprint/other.php'>Лакировка+Обивка+Упаковка</a></p>
	</div>

<script>
	$(document).ready(function() {
		$(".btnPrint").printPage();
	});
</script>

<?
	include "footer.php";
?>
