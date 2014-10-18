<?php
	include "db/DBAccess.php";
	$dbaccess = new DBAccess();
	$dbaccess->connectDB();
	$tablename = "";
	if (isset($_GET['tablename'])) $tablename = $_GET['tablename'];
	$cross_linkID = "";
	if (isset($_GET['cross_linkID'])) $cross_linkID = $_GET['cross_linkID'];
	$result = $dbaccess->selectWhere($tablename, "cross_linkID=".$cross_linkID);
	$table= "<table class = 'tablesorter table-bordered table-striped table condensed' id='table-center'>
			<thead>
			<tr>
				<th><label>Peptide A</label></th>
				<th><label>Residue Number A</label></th>
				<th><label>Protein A</label></th>
				<th><label>PDB code for Peptide A</label></th>
				<th><label>Peptide B</label></th>
				<th><label>Residue Number B</label></th>
				<th><label>Protein B</label></th>
				<th><label>PDB code for peptide B</label></th>
				<th><label>Distance of connection</label></th>
			</tr>
			</thead><tbody>";
	$row = $result->fetch_array(MYSQLI_ASSOC);
	$resA = (int)$row['kposA'] + (int)$row['startPosA'] + 1;
	$resB = (int)$row['kposB'] + (int)$row['startPosB'] + 1; 
	$table.="<tr><td>"
		  .substr($row['pepA'], 0, (int)$row['kposA'])
		  ."<b style='color:FF0000'>"
		  .substr($row['pepA'], (int)$row['kposA'], 1)
		  ."</b>".substr($row['pepA'], (int) $row['kposA'] + 1)."</td><td>".$resA."</td><td><a href='http://www.uniprot.org/uniprot/"
		  .trim($row['proA'], ' ')."' target='_blank'>".$row['gnA']."</a></td>";
	if (substr($row['pdbA'], 0, 1) != '#') $table.= "<td><a href='pdb/".$row['pdbA'].".pdb' target='_blank'>".$row['pdbA']."</a></td>";
	else $table.= "<td>N/A</td>";
	$table.="<td>".substr($row['pepB'], 0, (int)$row['kposB'])."<b style='color:FF0000'>".substr($row['pepB'], (int)$row['kposB'], 1)."</b>".substr($row['pepB'], (int) $row['kposB'] + 1)."</td>";
	$table.="<td>".$resB."</td><td><a href='http://www.uniprot.org/uniprot/".trim($row['proB'], ' ')."' target='_blank'>".$row['gnB']."</a></td>";
	if (substr($row['pdbB'], 0, 1) != '#') $table.= "<td><a href='pdb/".$row['pdbB'].".pdb' target='_blank'>".$row['pdbB']."</a></td>";
	else $table.= "<td>N/A</td>";
	$table.="<td>".$row['known']."</td>";
	$table.="</tr>";
	$table.= "</tbody></table>";
	echo "<html><head><title>XLink-DB | Site View</title>";
	echo <<<EOF
	<link href = "css/bootstrap.css" rel = "stylesheet" type = "text/css">
		<link href = "css/bootstrap-responsive.css" rel = "stylesheet" type = "text/css">
		<link href = "css/bootstrap.min.css" rel = "stylesheet" type = "text/css">
		<link href = "css/bootstrap-responsive.min.css" rel = "stylesheet" type = "text/css">
EOF;
	echo "<link rel='stylesheet' type='text/css' href='js/jquery/jquery-ui/css/custom-theme/jquery-ui-1.7.2.custom.css' />";
	echo "<script type='text/javascript' src='js/jquery/jquery-1.3.2.min.js'></script>";
	echo "<script type='text/javascript' src='js/jquery/plugins/jquery.tablesorter.min.js'></script>";
	echo "<script>
			$(document).ready(function () {
			$('#table-container').find('.tablesorter').tablesorter()});
			</script>";
	echo "<link rel='stylesheet' type='text/css' href='css/content/demo.css' />";
	echo <<<EOF
	<script type="text/javascript" language="javascript" src="jsmol/jsme/jsme/jsme.nocache.js"></script>
	<script type="text/javascript" src="jsmol/js/JSmoljQuery.js"></script>
	<script type="text/javascript" src="jsmol/js/JSmolCore.js"></script>
	<script type="text/javascript" src="jsmol/js/JSmolApplet.js"></script>
	<script type="text/javascript" src="jsmol/js/JSmolApi.js"></script>
	<script type="text/javascript" src="jsmol/js/j2sjmol.js"></script>
	<script type="text/javascript" src="jsmol/js/JSmol.js"></script>
	<script type="text/javascript" src="jsmol/js/JSmolJME.js"></script>
EOF;
	echo "</head><body>";
	include_once("analyticstracking.php");
	include "_header.php";
	$graph = "<div id='container' class='container'><table id ='table-center' class='table-condensed'><thead><tr><td style='width:400px'><h3>".$row['DescriptionA']."&nbsp&nbsp&nbsp</h3></td><td style='width:400px'><h3>".$row['DescriptionB']."</h3></td></tr></thead><tr><td>";
	if (substr(trim($row['pdbA']), 0, 1) != '#' && substr(trim($row['siteA']), 0, 1) != '#') $graph .="<script language='JavaScript' type='text/javascript'>
			var jmolApplet0;
			var use = 'HTML5';
			var s = document.location.search;
			Jmol.debugCode = (s.indexOf('debugcode') >= 0);
			jmol_isReady = function(applet) {
				document.title = (applet._id + ' is ready')
				Jmol._getElement(applet, 'appletdiv').style.border='none'
			}	
			var Info = {
				width: 700,
				height: 550,
				debug: false,
				color: '0x000000',
				addSelectionOptions: true,
				use: 'HTML5',
				jarPath: 'jsmol/java',
				jarFile: 'JmolAppletSigned.jar',
				isSigned: true,
				j2sPath: 'jsmol/j2s',
				readyFunction: jmol_isReady,
				script: 'load pdb/".$row['pdbA'].".pdb; set antialiasDisplay;set ambientPercent 40;set diffusePercent 80;set specular 100;set specpower 100; select all; labels off; restrict not water; wireframe off; backbone off; spacefill off; color cpk; ribbons off; cartoons on; hide nucleic; select protein; cartoons; color Chain; select :A; color RED; select :B; color GREEN;select ".$row['siteA']."; spacefill; color magenta; spacefill; color magenta;',
				disableJ2SLoadMonitor: true,
				disableInitialConsole: true,
				allowJavaScript: true
			}
			var JMEInfo = {  
				use: 'HTML5'
			}
			jmolApplet0 = Jmol.getApplet('jmolApplet0', Info);
			jme = Jmol.getJMEApplet(jme , JMEInfo, jmol);
			</script>";
	else {
		$blankcounter = 0;
		$returncounter = 0;
		$startpos = $row['startPosA'];
		$endpos = $row['startPosA'] + strlen($row['pepA']) - 1;
		$sequence = "<p align='justify'><font face='Courier New'>";
		for ($i = 0; $i < strlen(trim($row['seqA'])); $i++) {
			$blankcounter++;
			$returncounter++;
			if ($i == $startpos) $sequence .= "<b style='color:FF0000'>";
			if ($blankcounter != 5) {
				$sequence .= substr($row['seqA'], $i, 1);
			} else {
				$blankcounter = 0;
				$sequence .= substr($row['seqA'], $i, 1)."    ";
			}
			if ($i == $endpos) $sequence .= "</b>";
			if ($returncounter == 30) {
				$sequence .= "<br/>";
				$returncounter = 0;
			}
		}
		$sequence .= "</font></p>";
		$graph .= $sequence;
	}
	$graph.="</td><td>";
	if (substr(trim($row['pdbB']), 0, 1) != '#'&& substr(trim($row['siteB']), 0, 1) != '#') $graph .="<script language='JavaScript' type='text/javascript'>
			var jmolApplet1;
			var use = 'HTML5';
			var s = document.location.search;
			Jmol.debugCode = (s.indexOf('debugcode') >= 0);
			jmol_isReady = function(applet) {
				document.title = (applet._id + ' is ready')
				Jmol._getElement(applet, 'appletdiv').style.border='none'
			}	
			var Info1 = {
				width: 700,
				height: 550,
				debug: false,
				color: '0x000000',
				addSelectionOptions: true,
				use: 'HTML5',
				jarPath: 'jsmol/java',
				jarFile: 'JmolAppletSigned.jar',
				isSigned: true,
				j2sPath: 'jsmol/j2s',
				readyFunction: jmol_isReady,
				script: 'load pdb/".$row['pdbB'].".pdb; set antialiasDisplay;set ambientPercent 40;set diffusePercent 80;set specular 100;set specpower 100; select all; labels off; restrict not water; wireframe off; backbone off; spacefill off; color cpk; ribbons off; cartoons on; hide nucleic; select protein; cartoons; color Chain; select :A; color RED; select :B; color GREEN;select ".$row['siteB']."; spacefill; color magenta; spacefill; color magenta;',
				disableJ2SLoadMonitor: true,
				disableInitialConsole: true,
				allowJavaScript: true
			}
			var JMEInfo = {  
				use: 'HTML5'
			}
			jmolApplet1 = Jmol.getApplet('jmolApplet1', Info1);
			jme = Jmol.getJMEApplet(jme , JMEInfo, jmol);
			</script>";
	else {
		$blankcounter = 0;
		$returncounter = 0;
		$startpos = $row['startPosB'];
		$endpos = $row['startPosB'] + strlen($row['pepB']) - 1;
		$sequence = "<p align='justify'><font face='Courier New'>";
		for ($i = 0; $i < strlen(trim($row['seqB'])); $i++) {
			$blankcounter++;
			$returncounter++;
			if ($i == $startpos) $sequence .= "<b style='color:FF0000'>";
			if ($blankcounter != 5) {
				$sequence .= substr($row['seqB'], $i, 1);
			} else {
				$blankcounter = 0;
				$sequence .= substr($row['seqB'], $i, 1)."    ";
			}
			if ($i == $endpos) $sequence .= "</b>";
			if ($returncounter == 30) {
				$sequence .= "<br/>";
				$returncounter = 0;
			}
		}
		$sequence .= "</font></p>";
		$graph .= $sequence;
	}
	$graph.="</td></tr></table></div>";
	echo $graph."<div id='table-container' class='container'>".$table."</div>";
	include "_footer.php";
	echo "</body></html>";
?>
