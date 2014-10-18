<?php
	if ($_GET['tablename'] == "Acineto_master") header("Location: dataRetriever_acineto.php?tablename=acineto_master&dataset=&privateFlag=1");
	include "db/DBAccess.php";
	$dbaccess = new DBAccess();
	$dbaccess->connectDB();
	set_time_limit(0);
	$tableName="";
	if(isset($_GET['tablename'])) {
		$tableName=$_GET['tablename'];
	}
	$dataset="";
	if(isset($_GET['dataset'])) {
		$dataset=$_GET['dataset'];
	}
	$privateFlag="";
	if(isset($_GET['privateFlag'])) {
		$privateFlag=$_GET['privateFlag'];
	}
	echo "<html>
			<head>";
	echo "<title>XLinkDB | Table View</title>";
	echo "<link rel='stylesheet' type='text/css' href='/xlinkdb/js/jquery/jquery-ui/css/custom-theme/jquery-ui-1.7.2.custom.css' />
			<link href='/xlinkdb/css/bootstrap.css' rel='stylesheet' type='text/css'>
			<link href='/xlinkdb/css/bootstrap-responsive.css' rel='stylesheet' type='text/css'>
			<link href='/xlinkdb/css/bootstrap.min.css' rel='stylesheet' type='text/css'>
			<link href='/xlinkdb/css/bootstrap-responsive.min.css' rel='stylesheet' type='text/css'>
			<script type='text/javascript' src='/xlinkdb/js/jquery/jquery-1.3.2.min.js'></script>
			<script type='text/javascript' src='/xlinkdb/js/jquery/plugins/jquery.tablesorter.min.js'></script>
			<script>
				$(document).ready(function () {
				$('#container').find('.tablesorter').tablesorter()});
			</script>
		</head>";
	echo "<body>";
	include_once("analyticstracking.php");
	include "_header.php";
	echo"<div class='container' id='title'>";
	if($tableName!=""){
		echo"<div align='center'><a href='interactionView.php?tablename=".$tableName."&dataset=".$dataset."&privateFlag=".$privateFlag."' target='_blank'><button id='proteinViewButton' class='btn btn-primary'>Generate interaction view</button></a>
			<a href='downloadTable.php?tablename=".$tableName."&dataset=".$dataset."&privateFlag=".$privateFlag."' target='_blank'>Download the data table</a><br\><br\></div>";
	}
	echo"
		</div>
		<div id ='container' class='container'><table class='tablesorter table-bordered table-striped table-condensed' id='table-center'><thead>
			<tr>
			<th><label>Peptide A<img src='img/icons/sort_neutral_green.ico' width='15' height='15' /></label></th>
			<th><label>Protein A<img src='img/icons/sort_neutral_green.ico' width='15' height='15' /></label></th>
			<th><label>PDB code for Peptide A<img src='img/icons/sort_neutral_green.ico'  width='15' height='15' /></label></th>
			<th><label>Peptide B<img src='img/icons/sort_neutral_green.ico' width='15' height='15' /></label></th>
			<th><label>Protein B<img src='img/icons/sort_neutral_green.ico'  width='15' height='15' /></label></th>
			<th><label>PDB code for peptide B<img src='img/icons/sort_neutral_green.ico' width='15' height='15' /></label></th>
			<th><label>Distance of connection<img src='img/icons/sort_neutral_green.ico'width='15' height='15' /></label></th>
			<th><label>Display structure<img src='img/icons/sort_neutral_green.ico' width='15' height='15' /></label></th>
			</tr></thead><tbody>";
	
	if($privateFlag==1) $result=$dbaccess->selectAll($tableName);
	else {
		$tableName="xlinkdb";
		$result=$dbaccess->selectWhere("xlinkdb", "dataset='".$dataset."'");
	}
	while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
		echo"<tr>";
		echo "<td><a href='displaySite.php?cross_linkID=".$row['cross_linkID']."&tablename=".$tableName."&dataset=".$dataset."' target='_blank'>"
			.substr($row['pepA'], 0, (int)$row['kposA'])."<b style='color:FF0000'>".substr($row['pepA'], (int)$row['kposA'], 1)."</b>".substr($row['pepA'], (int) $row['kposA'] + 1)."</a></td>";
		echo "<td><a href='http://www.uniprot.org/uniprot/".trim($row['proA'], ' ')."' target='_blank'>".$row['gnA']."</a></td>";
		if (substr($row['pdbA'], 0, 1) != '#') echo "<td><a href='pdb/".$row['pdbA'].".pdb' target='_blank'>".$row['pdbA']."</a></td>";
		else echo "<td>N/A</td>";
		echo "<td><a href='displaySite.php?cross_linkID=".$row['cross_linkID']."&tablename=".$tableName."&dataset=".$dataset."' target='_blank'>".substr($row['pepB'], 0, (int)$row['kposB'])."<b style='color:FF0000'>".substr($row['pepB'], (int)$row['kposB'], 1)."</b>".substr($row['pepB'], (int) $row['kposB'] + 1)."</a></td>";
		echo "<td><a href='http://www.uniprot.org/uniprot/".trim($row['proB'], ' ')."' target='_blank'>".$row['gnB']."</a></td>";
		if (substr($row['pdbB'], 0, 1) != '#') echo "<td><a href='pdb/".$row['pdbB'].".pdb' target='_blank'>".$row['pdbB']."</a></td>";
		else echo "<td>N/A</td>";
		echo "<td>".$row['known']."</td>";
		if (substr($row['siteA'], 0, 1) != '#' && substr($row['siteB'], 0, 1) != '#' && $row['pdbA'] == $row['pdbB']){
			echo "<td><a href='displayAll_jsmol.php?tablename=".$tableName."&dataset=".$dataset."&privateFlag=".$privateFlag."&pdb=".$row['pdbA']."&siteA=".$row['siteA']."&siteB=".$row['siteB']."&atomNumA=".$row['atomNumA']."&atomNumB=".$row['atomNumB']."' target='_blank'>
			<button type='submit' name='submit' class='btn btn-primary'>Show structure</button></a></td>";
		} else {
			echo "<td>N/A</td>";
		}
		echo"</tr>";
	}
	echo "</tbody></table></div>";
	include "_footer.php";
	echo "</body></html>";
?>
