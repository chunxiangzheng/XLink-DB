<?php
	function getResult($table_arr, $dbaccess, $gnA, $gnB) {
		$result=array();
		foreach($table_arr as $tableName) {
			//error_log($tableName."\r", 3, "log.txt");
			$result_tmp=$dbaccess->selectWhere($tableName, "((gnA LIKE'".$gnA.trim()."%' AND gnB LIKE'". $gnB.trim()."%') OR (gnA LIKE'".$gnB.trim()."%' AND gnB LIKE'".$gnA.trim()."%'))");
			while ($row=$result_tmp->fetch_array(MYSQLI_ASSOC)) {
				if(!empty($row)){
					array_push($result, $row);
				}
			}
		}
		return $result;
	}
	include "db/DBAccess.php";
	$dbaccess = new DBAccess();
	$dbaccess->connectDB();
	$privateFlag = "";
	if (isset($_GET['privateFlag'])) $privateFlag = $_GET['privateFlag'];
	$tableName = "";
	if (isset($_GET['tablename'])) $tableName = $_GET['tablename'];
	$dataset = "";
	if (isset($_GET['dataset'])) $dataset = $_GET['dataset'];
	$gnA = "";
	$gnB = "";
	if (isset($_GET['gnA'])) $gnA = $_GET['gnA'];
	if (isset($_GET['gnB'])) $gnB = $_GET['gnB'];
	$pdb = "";
	if (isset($_GET['pdb'])) $pdb = $_GET['pdb'];
	if($gnA != "") {
		if ($privateFlag==1) {
			$result = $dbaccess->selectWhere($tableName, "((gnA LIKE '".trim($gnA)."%' AND gnB LIKE '". trim($gnB)
			."%') OR (gnA LIKE '".trim($gnB)."%' AND gnB LIKE '".trim($gnA)."%'))");
		} else {
			if ($dataset != "") {
				$result = $dbaccess->selectWhere($tableName, "((gnA LIKE '".trim($gnA)."%' AND gnB LIKE '". trim($gnB)
				."%') OR (gnA LIKE '".trim($gnB)."%' AND gnB LIKE '".trim($gnA)."%')) AND dataset='".$dataset."'");
			} else {
				$result = $dbaccess->selectWhere($tableName, "(gnA LIKE '".trim($gnA)."%' AND gnB LIKE '". trim($gnB)
				."%') OR (gnA LIKE '".trim($gnB)."%' AND gnB LIKE '".trim($gnA)."%')");
			}
		}
	} else {
		if ($privateFlag==1) {
			$result = $dbaccess->selectWhere($tableName, "pdbA='".$pdb."' AND pdbB='".$pdb."'");
		} else {
			$result = $dbaccess->selectWhere($tableName, "pdbA='".$pdb."' AND pdbB='".$pdb."' AND dataset='".$dataset."'");
		}		
	}
	$pdb = "";
	$tmp = ".pdb; select all; cartoons on; backbone off; wireframe off; spacefill off; hide nucleic; restrict not water; select protein; cartoons; color Chain;select :A; color RED; select :B; color GREEN;";
	$is_intra = true;
	$proName = "";
	$pro_arr=array();
	if($_GET['tablename']!=""){
		$table= "<div class='container' id='table-container'><table class = 'tablesorter table-striped table-bordered jmol-container' id='table-center'>
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
				<th><label>Distance</label></th>
				<th><label>Display structure</label></th>
			</tr>
			</thead><tbody>";
		while ($row = $result->fetch_array(MYSQLI_ASSOC))
		{
			if(!array_key_exists($row['gnA'], $pro_arr)) $pro_arr[$row['gnA']]=$row['gnA'];
			if(!array_key_exists($row['gnB'], $pro_arr)) $pro_arr[$row['gnB']]=$row['gnB'];
			$resA = (int)$row['kposA'] + (int)$row['startPosA'] + 1;
			$resB = (int)$row['kposB'] + (int)$row['startPosB'] + 1; 
			if($proName != $row['DescriptionA'] && $proName!="") $is_intra=false;
			else $proName=$row['DescriptionA'];
			if($row['geneA']!=$row['geneB']) $is_intra=false;
			$table.="<tr>";
			$table.= "<td>".substr($row['pepA'], 0, (int)$row['kposA'])."<b style='color:FF0000'>".substr($row['pepA'], (int)$row['kposA'], 1)."</b>".substr($row['pepA'], (int) $row['kposA'] + 1)."</td>";
			$table.="<td>".$resA."</td>";
			$table.="<td><a href='http://www.uniprot.org/uniprot/".trim($row['proA'], ' ')."' target='_blank'>".$row['gnA']."</a></td>";
			if (substr($row['pdbA'], 0, 1) != '#') $table.= "<td><a href='pdb/".$row['pdbA'].".pdb' target='_blank'>".$row['pdbA']."</a></td>";
			else $table.= "<td>N/A</td>";
			$table.="<td>".substr($row['pepB'], 0, (int)$row['kposB'])."<b style='color:FF0000'>".substr($row['pepB'], (int)$row['kposB'], 1)."</b>".substr($row['pepB'], (int) $row['kposB'] + 1)."</td>";
			$table.="<td>".$resB."</td>";
			$table.="<td><a href='http://www.uniprot.org/uniprot/".trim($row['proB'], ' ')."' target='_blank'>".$row['gnB']."</a></td>";
			if (substr($row['pdbB'], 0, 1) != '#') $table.= "<td><a href='pdb/".$row['pdbB'].".pdb' target='_blank'>".$row['pdbB']."</a></td>";
			else $table.= "<td>N/A</td>";
			$table.="<td>".$row['distance']."</td>";
			if (substr($row['siteA'], 0, 1) != '#' && substr($row['siteB'],0,1) != '#' && $row['atomNumA'] != '0' && $row['atomNumB'] != '0' && $row['pdbA']==$row['pdbB'])
			{
				$pdb = $row['pdbA'];
				$table.= "<td><a href=\"JavaScript:Jmol.script(jmolApplet0,'zap; load pdb/".$row['pdbA'].".pdb; select all; cartoons on; backbone off; wireframe off; spacefill off; restrict not water; hide nucleic; select protein; cartoons; color Chain; select :A; color RED; select :B; color GREEN; select "
					.$row['siteA']."; spacefill; color magenta; select "
					.$row['siteB']."; spacefill; color magenta; connect "
					.$row['atomNumA']." ".$row['atomNumB']."; monitor ".$row['atomNumA']." ".$row['atomNumB']."; set monitor 3; color monitor orange; font monitor 24;')\">view single crosslink
					</a></td>";
				$tmp.="select ".$row['siteA']."; spacefill; color magenta; select "
					.$row['siteB']."; spacefill; color magenta; connect "
					.$row['atomNumA']." ".$row['atomNumB']."; monitor ".$row['atomNumA']." ".$row['atomNumB']."; set monitor 3; color monitor orange; font monitor 24;";
			} else {
				$table.= "<td>N/A</td>";
			}
			$table.="</tr>";
		} 
	} else {
		$table= "<div class='container' id='table-container'><table class = 'tablesorter table-striped table-bordered jmol-container' id='table-center'>
			<thead>
			<tr>
				<th><label>Peptide A</label></th>
				<th><label>Protein A</label></th>
				<th><label>PDB code for Peptide A</label></th>
				<th><label>Peptide B</label></th>
				<th><label>Protein B</label></th>
				<th><label>PDB code for peptide B</label></th>
				<th><label>Distance of connection</label></th>
				<th><label>Display structure</label></th>
			</tr>
			</thead><tbody>";
		foreach($result as $row) {
			if(!array_key_exists($row['gnA'], $pro_arr)) $pro_arr[$row['gnA']]=$row['gnA'];
			if(!array_key_exists($row['gnB'], $pro_arr)) $pro_arr[$row['gnB']]=$row['gnB'];
			if($proName != $row['DescriptionA'] && $proName!="") $is_intra=false;
			else $proName=$row['DescriptionA'];
			if($row['geneA']!=$row['geneB']) $is_intra=false;
			$table.="<tr>";
			$table.= "<td>".substr($row['pepA'], 0, (int)$row['kposA'])."<b style='color:FF0000'>".substr($row['pepA'], (int)$row['kposA'], 1)."</b>".substr($row['pepA'], (int) $row['kposA'] + 1)."</td>";
			$table.="<td><a href='http://www.uniprot.org/uniprot/".trim($row['proA'], ' ')."' target='_blank'>".$row['gnA']."</a></td>";
			if (substr($row['pdbA'], 0, 1) != '#') $table.= "<td><a href='pdb/".$row['pdbA'].".pdb' target='_blank'>".$row['pdbA']."</a></td>";
			else $table.= "<td>N/A</td>";
			$table.="<td>".substr($row['pepB'], 0, (int)$row['kposB'])."<b style='color:FF0000'>".substr($row['pepB'], (int)$row['kposB'], 1)."</b>".substr($row['pepB'], (int) $row['kposB'] + 1)."</td>";
			$table.="<td><a href='http://www.uniprot.org/uniprot/".trim($row['proB'], ' ')."' target='_blank'>".$row['gnB']."</a></td>";
			if (substr($row['pdbB'], 0, 1) != '#') $table.= "<td><a href='pdb/".$row['pdbB'].".pdb' target='_blank'>".$row['pdbB']."</a></td>";
			else $table.= "<td>N/A</td>";
			$table.="<td>".$row['known']."</td>";
			if (substr($row['siteA'], 0, 1) != '#' && substr($row['siteB'],0,1) != '#' && $row['atomNumA'] != '0' && $row['atomNumB'] != '0' && $row['pdbA']==$row['pdbB'])
			{
				$pdb = $row['pdbA'];
				$table.= "<td><a href=\"JavaScript:Jmol.script(jmolApplet0,\'zap; load pdb/".$row['pdbA'].".pdb; select all; cartoons on; backbone off; wireframe off; spacefill off; restrict not water; hide nucleic; select protein; cartoons; color Chain; select :A; color RED; select :B; color GREEN; select "
					.$row['siteA']."; spacefill; color magenta; select "
					.$row['siteB']."; spacefill; color magenta; connect "
					.$row['atomNumA']." ".$row['atomNumB']."; monitor ".$row['atomNumA']." ".$row['atomNumB']."; set monitor 3; color monitor orange; font monitor 24;\')\">view single crosslink</a></td>";
				$tmp.="select ".$row['siteA']."; spacefill; color magenta; select "
					.$row['siteB']."; spacefill; color magenta; connect "
					.$row['atomNumA']." ".$row['atomNumB']."; monitor ".$row['atomNumA']." ".$row['atomNumB']."; set monitor 3; color monitor orange; font monitor 24;";
			} else {
				$table.= "<td>N/A</td>";
			}
			$table.="</tr>";
		}		
	}
	$table.= "</tbody></table></div>";
	$tmp="<a href=\"JavaScript:Jmol.script(jmolApplet0,'zap; load pdb/".$pdb.$tmp;
	$tmp.=";')\">view all crosslinks</a>";
	echo "<html><head><title>XLink-DB | Protein View</title><script language = 'JavaScript' type = 'text/javascript' src = 'jsmol/jsmol.min.js'></script>";
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
	echo "
		<script type='text/javascript'>
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
				script: 'load pdb/".$pdb.".pdb; set antialiasDisplay;set ambientPercent 40;set diffusePercent 80;set specular 100;set specpower 100; select all; labels off; restrict not water; wireframe off; backbone off; spacefill off; color cpk; ribbons off; cartoons on; hide nucleic; select protein; cartoons; color Chain; select :A; color RED; select :B; color GREEN;',
				disableJ2SLoadMonitor: true,
				disableInitialConsole: true,
				allowJavaScript: true
			}
			var JMEInfo = {  
				use: 'HTML5'
			}
		</script>
	";
	echo "</head><body>";
	include_once("analyticstracking.php");
	include "_header.php";
	if ($is_intra == true) echo "<div id = 'container' align='center'><h1>".$proName."</h1></div>";
	if ($pdb!="") {
		echo "<div class='container' id='container'><table class='jmol-container'>
				<tr>
					<td>
						<div id='appletdiv'>
							<script>
								jmolApplet0 = Jmol.getApplet('jmolApplet0', Info);
								jme = Jmol.getJMEApplet(jme , JMEInfo, jmol);
							</script>
						</div>
					</td>
					<td>
						<div class='span4'>
			<h3><b>Update the PDB file</b></h3><p></p>
			<form action='updatePDB.php' method='get' class='well form-search'>
				<input type='hidden' value='".$pdb."' name='oldpdb'>
				<input type='hidden' value='".$tableName."' name='tablename'>
				<label class='control-label' for='input01'><b>New PDB:&nbsp&nbsp</b></label>
				<input name='newpdb' type='text' class='input-small search-query' id='input01'><br/><p></p>";
	foreach($pro_arr as $pro) {
		echo "<label class='control-label' for='input".$pro."'><b>Chains for protein&nbsp".$pro.":&nbsp&nbsp</b></label>
				<input name='chains".$pro."' type='text' class='input-small search-query' id='input".$pro."'><br/><p></p>";
	}
	echo <<<EOF
	<br/><p></p>
				<button type='submit' class='btn btn-primary'>Update</button>
			</form>
		</div>
					</td>
				</tr>
			</div>
			<p></p>		
EOF;
	}
		
	echo $table."<p></p>";
	echo "<div class='container' id='container'>";
	if ($pdb != "") {
		echo "<table class='jmol-container'><tr><td><a href=\"JavaScript: Jmol.script(jmolApplet0,'zap; load pdb/".$pdb.".pdb; select all; labels off; set specular on; spacefill off; color cpk; backbone off; ribbons off; cartoons on; wireframe off; select protein; cartoons; color Chain; select :A; color RED; select :B; color GREEN;')\">Reset complex&nbsp;&nbsp;</a>";
		echo "</td><td>";
		echo $tmp."</td></table>";
	}
	echo "</div>";
	include "_footer.php";
	echo"</body></html>";
?>
