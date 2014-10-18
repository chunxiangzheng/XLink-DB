<?php
	$specString=$_POST['spectrum'];
	include "db/DBAccess.php";
	$dbaccess = new DBAccess();
	$dbaccess->connectDB();
	$tableName = "SpecDB";
	$arr = explode(" ", $specString);
	if (sizeOf($arr) == 4) {
		$fileName = $arr[0];
		$scanNum = $arr[3];
		$result = $dbaccess->selectWhere($tableName, "`FileName`='".$fileName."' and `MS2ScanNum`='".$scanNum."'");
		$row = $result->fetch_array(MYSQLI_ASSOC);
		$data = array();
		$data['MS2'] = true;
		$data['PepA'] = $row['PepA'];
		$data['PepB'] = $row['PepB'];
		$data['MS2ScanNum'] = $row['MS2ScanNum'];
		$data['FileName'] = $row['FileName'];
		$data['PrecMZ'] = $row['MS2PrecMz'];
		$data['PrecCharge'] = $row['MS2PrecCharge'];
		$data['MS2Spec'] = $row['MS2Spec'];
		$data['ReporterPeak'] = $row['ReporterPeak'];
		$data['PepAPeak'] = $row['PepAPeak'];
		$data['PepBPeak'] = $row['PepBPeak'];
		$data['PepAAnnotation'] = $row['PepAAnnotation'];
		$data['PepBAnnotation'] = $row['PepBAnnotation'];
		$data['MS1ScanNum'] = (int)$data['MS2ScanNum'] - 1;
		$data['MS1Spec'] = $row['MS1Spec'];
		$data['MS2PrecPeak'] = $row['MS2PrecPeak'];
		$data['MS2PrecAnnotation'] = $row['MS2PrecAnnotation'];
	} else {
		$fileName = $arr[0];
		$pepAScanNum = $arr[3];
		$pepBScanNum = $arr[5];
		$result = $dbaccess->selectWhere($tableName, "`FileName`='".$fileName."' and `MS3ScanNumA`='".$pepAScanNum."' and `MS3ScanNumB`='".$pepBScanNum."'");
		$row = $result->fetch_array(MYSQLI_ASSOC);
		$data = array();
		$data['MS2'] = false;
		$data['PepA'] = $row['PepA'];
		$data['PepB'] = $row['PepB'];
		$data['ScanNumA'] = $row['MS3ScanNumA'];
		$data['ScanNumB'] = $row['MS3ScanNumB'];
		$data['ModA'] = $row['ModA'];
		$data['ModB'] = $row['ModB'];
		$data['FileName'] = $row['FileName'];
		$data['PrecMZA'] = $row['MS3PrecMzA'];
		$data['PrecMZB'] = $row['MS3PrecMzB'];
		$data['PrecChargeA'] = $row['MS3PrecChargeA'];
		$data['PrecChargeB'] = $row['MS3PrecChargeB'];
		$data['SpecA'] = $row['MS3SpecA'];
		$data['SpecB'] = $row['MS3SpecB'];
	}
	echo json_encode($data);
?>
