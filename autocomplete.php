<script>
	$(document).ready(function(){
		$(function() {
			// Автокомплит салонов
			<?
				$query = "(SELECT CT.City AS Shop FROM Cities CT JOIN Shops SH ON SH.CT_ID = CT.CT_ID GROUP BY CT.CT_ID)
						  UNION
						  (SELECT CONCAT(CT.City, '/', SH.Shop) AS Shop FROM Cities CT JOIN Shops SH ON SH.CT_ID = CT.CT_ID)
						  UNION
						  (SELECT 'Свободные' AS Shop)";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) )
				{
					$ShopsTags[] = $row["Shop"];
				}
			?>
			var ShopsTags = <?= json_encode($ShopsTags); ?>;
			$( ".shopstags" ).autocomplete({
	//			autoFocus: true,
				source: ShopsTags
			});

			// Автокомплит цветов
			<?
				$query = "SELECT Color FROM OrdersData GROUP BY Color ORDER BY COUNT(1) DESC";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) )
				{
					$ColorTags[] = $row["Color"];
				}
			?>
			var ColorTags = <?= json_encode($ColorTags); ?>;
			$( ".colortags" ).autocomplete({
	//			autoFocus: true,
				source: ColorTags
			});

			// Автокомплит тканей
			<?
				$query = "SELECT Material FROM Materials WHERE PT_ID = 1 ORDER BY Count DESC";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) )
				{
					$TextileTags[] = $row["Material"];
				}
			?>
			var TextileTags = <?= json_encode($TextileTags); ?>;
			$( ".textiletags" ).autocomplete({
	//			autoFocus: true,
				source: TextileTags
			});

			// Автокомплит пластиков
			<?
				$query = "SELECT Material FROM Materials WHERE PT_ID = 2 ORDER BY Count DESC";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) )
				{
					$PlasticTags[] = $row["Material"];
				}
			?>
			var PlasticTags = <?= json_encode($PlasticTags); ?>;
			$( ".plastictags" ).autocomplete({
	//			autoFocus: true,
				source: PlasticTags
			});

			// Автокомплит тканей, пластиков и прочего
			<?
				$query = "SELECT Material, SUM(Count) Count FROM Materials GROUP BY Material ORDER BY Count DESC";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) )
				{
					$TextilePlasticTags[] = $row["Material"];
				}
			?>
			var TextilePlasticTags = <?= json_encode($TextilePlasticTags); ?>;
			$( ".textileplastictags" ).autocomplete({
	//			autoFocus: true,
				source: TextilePlasticTags
			});

			// Автокомплит заказчиков
			<?
				$query = "SELECT ClientName FROM OrdersData WHERE IFNULL(ClientName, '') != '' GROUP BY ClientName";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) )
				{
					$ClientTags[] = $row["ClientName"];
				}
			?>
			var ClientTags = <?= json_encode($ClientTags); ?>;
			$( ".clienttags" ).autocomplete({
	//			autoFocus: true,
				source: ClientTags
			});
		});
	});
</script>
