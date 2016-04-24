<script>
	$(function() {
		// Автокомплит салонов
		var availableTags = [
		<?
			$query = "(SELECT CT.City AS Shop FROM Cities CT JOIN Shops SH ON SH.CT_ID = CT.CT_ID GROUP BY CT.CT_ID)
					  UNION
					  (SELECT CONCAT(CT.City, '/', SH.Shop) AS Shop FROM Cities CT JOIN Shops SH ON SH.CT_ID = CT.CT_ID)";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) )
			{
				echo "\"{$row["Shop"]}\",";
			}
		?>
		""];
		$( ".tags" ).autocomplete({
//			autoFocus: true,
			source: availableTags
		});

		// Автокомплит работников
		var WorkersTags = [
		<?
			$query = "SELECT Name FROM WorkersData";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) )
			{
				echo "\"{$row["Name"]}\",";
			}
		?>
		""];
		$( ".workerstags" ).autocomplete({
//			autoFocus: true,
			source: WorkersTags
		});

		// Автокомплит цветов
		var ColorTags = [
		<?
			$query = "SELECT Color FROM OrdersData GROUP BY Color";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) )
			{
				echo "\"{$row["Color"]}\",";
			}
		?>
		""];
		$( ".colortags" ).autocomplete({
//			autoFocus: true,
			source: ColorTags
		});

		// Автокомплит тканей
		var TextileTags = [
		<?
			$query = "SELECT ODD.Material FROM OrdersDataDetail ODD JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID AND PM.PT_ID = 1 GROUP BY ODD.Material";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) )
			{
				echo "\"{$row["Material"]}\",";
			}
		?>
		""];
		$( ".textiletags" ).autocomplete({
//			autoFocus: true,
			source: TextileTags
		});

		// Автокомплит пластиков
		var PlasticTags = [
		<?
			$query = "SELECT ODD.Material FROM OrdersDataDetail ODD JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID AND PM.PT_ID = 2 GROUP BY ODD.Material";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) )
			{
				echo "\"{$row["Material"]}\",";
			}
		?>
		""];
		$( ".plastictags" ).autocomplete({
//			autoFocus: true,
			source: PlasticTags
		});

		// Автокомплит тканей и пластиков
		var TextilePlasticTags = [
		<?
			$query = "SELECT ODD.Material FROM OrdersDataDetail ODD JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID GROUP BY ODD.Material";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) )
			{
				echo "\"{$row["Material"]}\",";
			}
		?>
		""];
		$( ".textileplastictags" ).autocomplete({
//			autoFocus: true,
			source: TextilePlasticTags
		});
	});
</script>
