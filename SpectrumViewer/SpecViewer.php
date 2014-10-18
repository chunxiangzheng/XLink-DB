<!DOCTYPE html>
	<html lang="en">
		<head>
			<title>XLink-DB | Spectrum Viewer</title>
			<link href = "css/bootstrap.css" rel = "stylesheet" type = "text/css">
			<link href = "css/bootstrap-responsive.css" rel = "stylesheet" type = "text/css">
			<link href = "css/bootstrap.min.css" rel = "stylesheet" type = "text/css">
			<link href = "css/bootstrap-responsive.min.css" rel = "stylesheet" type = "text/css">
			<script src="js/jquery-2.0.3.min.js"></script>
			<!--[if IE]><script language="javascript" type="text/javascript" src="../js/excanvas.min.js"></script><![endif]-->
			<!--<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>-->
			<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.4/jquery-ui.min.js"></script>
			<script type="text/javascript" src="lorikeetTest/lorikeet/js/jquery.flot.js"></script>
			<script type="text/javascript" src="lorikeetTest/lorikeet/js/jquery.flot.selection.js"></script>
			<script type="text/javascript" src="lorikeetTest/lorikeet/js/peptide.js"></script>
			<script type="text/javascript" src="lorikeetTest/lorikeet/js/aminoacid.js"></script>
			<script type="text/javascript" src="lorikeetTest/lorikeet/js/ion.js"></script>
			<link REL="stylesheet" TYPE="text/css" HREF="lorikeetTest/lorikeet/css/lorikeet.css">
			
			<script type="text/javascript">
			$(document).ready(function () {
				$('#specSelector').submit(function(event) {
					event.preventDefault();
					var posting = $.post('getSpec.php', $('#specSelector').serialize());
					posting.done(function( data ) {
						var json = eval('(' + data + ')')
						//console.log(json);
						if (json['MS2']) {
							var pepA = json['PepA'];
							var pepB = json['PepB'];
							var sequence = pepA + '|' + pepB;
							var MS2ScanNum = json['MS2ScanNum'];
							var charge = json['MS2PrecCharge'];
							var mz = json['PrecMZ'];
							var fileName = json['FileName'];
							var MS2Spec = eval(json['MS2Spec']);
							var reporter = eval("[" + json['ReporterPeak'] + "]");
							var pepAPeak = eval(json['PepAPeak']);
							var pepBPeak = eval(json['PepBPeak']);
							var pepAAnnotation = json['PepAAnnotation'].substring(1,json['PepAAnnotation'].length-2).split(",");
							var pepBAnnotation = json['PepBAnnotation'].substring(1,json['PepBAnnotation'].length-2).split(",");
							var MS1ScanNum = json['MS1ScanNum'];
							var MS1Spec = eval(json['MS1Spec']);
							var MS2PrecPeak = eval(json['MS2PrecPeak']);
							var MS2PrecAnnotation = json['MS2PrecAnnotation'].substring(1,json['MS2PrecAnnotation'].length-2).split(",");
							//alert(json['MS2PrecAnnotation'].substring(1));
							function cleanString(s) {
								//alert(s);
								s.replace('+','=');
								s.replace(']','');
								//alert(s);
							}
							pepAAnnotation.forEach(cleanString);
							pepBAnnotation.forEach(cleanString);
							$.getScript("lorikeetTest/lorikeet/js/specview_React.js", function (){}).done(function () {
								document.getElementById('lorikeetContainer').innerHTML = '<div id="lorikeet1" align="center"></div><div id="lorikeet2" align="center"></div>';
								$(document).ready(function () {
									$("#lorikeet2").specview({sequence: sequence,
															scanNum: MS2ScanNum,
															charge: charge,
															precursorMz: mz,
															fileName: fileName,
															peaks: MS2Spec,
															extraPeakSeries: [{data: reporter,color: "#00aa00",labels: ["reporter"]},
																			{data:pepAPeak,color: "#aa0000",labels: pepAAnnotation},
																			{data:pepBPeak, color:"#0000aa", labels: pepBAnnotation}]
															});
									$("#lorikeet1").specview({sequence: "MS1 Scan",
															scanNum: MS1ScanNum,
															fileName: fileName,
															peaks: MS1Spec,
															extraPeakSeries: [{data: MS2PrecPeak,color: "#00aa00",labels: MS2PrecAnnotation}]
															});
								});
							});
						} else {
							var pepA = json['PepA'];
							var pepB = json['PepB'];
							var scanNumA = json['ScanNumA'];
							var scanNumB = json['ScanNumB'];
							var modA = eval(json['ModA']);
							var modB = eval(json['ModB']);
							var fileName = json['FileName'];
							var precMzA = json['PrecMZA'];
							var precMzB = json['PrecMZB'];
							var precChargeA = json['PrecChargeA'];
							var precChargeB = json['PrecChargeB'];
							var specA = eval(json['SpecA']);
							var specB = eval(json['SpecB']);
							$.getScript("lorikeetTest/lorikeet/js/specview.js", function (){}).done(function () {
								document.getElementById('lorikeetContainer').innerHTML = '<div id="lorikeet1" align="center"></div><div id="lorikeet2" align="center"></div>';
								$(document).ready(function () {
									$("#lorikeet1").specview({sequence: pepA, 
															scanNum: scanNumA,
															charge: precChargeA,
															precursorMz: precMzA,
															fileName: fileName,
															peaks: specA,
															variableMods: modA});
									$("#lorikeet2").specview({sequence: pepB,
															scanNum: scanNumB,
															charge: precChargeB,
															precursorMz: precMzB,
															fileName: fileName,
															peaks: specB,
															variableMods: modB});
								});
							});							
						}
					});
				});
			});
		
		
		</script>
		</head>
		<body>
<?php include "_header.php";?>
<?php include_once("analyticstracking.php") ?>
<?php
	include "db/DBAccess.php";
	$dbaccess = new DBAccess();
	$tableName = "SpecDB";
	$pepA = "";
	if(isset($_GET['pepA'])) $pepA = $_GET['pepA'];
	$pepB = "";
	if(isset($_GET['pepB'])) $pepB = $_GET['pepB'];
	$dbaccess->connectDB();
	$string = "";
	$result = $dbaccess->selectWhere($tableName, "(`PepA`='".$pepA."' and `PepB`='".$pepB."') or (`PepB`='".$pepA."' and `PepA`='".$pepB."')");
	while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
		$fileName = $row['FileName'];
		$s = $fileName." MS2 Scan ".$row['MS2ScanNum'];
		$string .= "<option>".$s."</option>";
		$s = $fileName." MS3 Scans ".$row['MS3ScanNumA']." and ".$row['MS3ScanNumB'];
		$string .= "<option>".$s."</option>";
	}
echo <<<EOF
			<div>
				<form id="specSelector">
					<label>&nbsp;&nbsp;Choose a spectrum: </label>
					<select type="tableName" name="spectrum" id="select01">
EOF;
echo $string;
echo <<<EOF
					</select>
					<button type="submit" class="btn btn-primary">Go</button>
				</form>
			</div>
EOF;
?>
<div id='lorikeetContainer'></div>
	
<?php include "_footer.php";?>
