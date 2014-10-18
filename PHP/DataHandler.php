<?php
	//database operation class file
	include "db/DBAccess.php";
	//upload file, save in the uploads folder 
	if ($_FILES["tsvfile"]["error"] > 0)echo "Error: ".$_FILES["tsvfile"]["error"]."<br />";
	else {
		if (file_exists("recycled/".$_FILES["tsvfile"]["name"]))	echo $_FILES["tsvfile"]["name"]." already exists. ";
		else move_uploaded_file($_FILES["tsvfile"]["tmp_name"],"recycled/".$_FILES["tsvfile"]["name"]);
	}
	
	//reactoutputanalysis java program
	$filename = escapeshellarg($_FILES["tsvfile"]["name"]);
	$command = "java -jar jar/ReactOutputAnalysis.jar ".$filename;
	system($command);
	error_log($php_errormsg,3,"error.log");

	// Download uniprot file for each protein
	function download_remote_file($file_url, $save_to) {
		if (file_exists($save_to)) return;
		$content = file_get_contents($file_url);
		file_put_contents($save_to, $content);
	}
	$filenameUniprot = "tmp".trim($filename, '"')."uniqueUniprot";
	$filename = trim($filename, '"');
	$file = fopen("recycled/".$filenameUniprot, "r") or exit("Unable to read the file");
	set_time_limit(0);
	while (!feof($file)) {
		$l = fgets($file);
		if($l=="")continue;
		$url = "http://www.uniprot.org/uniprot/".trim($l).".txt";
		download_remote_file($url, "uniprot/".trim($l).".txt");
	}
	fclose($file);
	echo "Uniprot files are downloaded<br/>";	
	error_log($php_errormsg,3,"error.log");
	
	
	//find attribute
	$command = "java -jar jar/FindAttri.jar ".$filename." ".$filenameUniprot." ".$filename;
	system($command);
	error_log($php_errormsg,3,"error.log");
	
	
	//Extract PDB code for each pair
	$filename = "tmp".$filename."attri";
	$command = "java -jar jar/FindPDBCode.jar ".$filename;
	system($command);
	echo "Extract PDB code for every protein<br/>";
	error_log($php_errormsg,3,"error.log");

	//Download PDB files for each protein
	$filenamePDB = $filename."uniquePDB";
	$file = fopen("recycled/".$filenamePDB, "r") or exit("Unable to read the unique PDB file");
	while (!feof($file)) {
		$l = fgets($file);
		if($l=="")continue;
		if (substr($l, 0 , 1) == "#") continue;
		$url = "http://www.rcsb.org/pdb/files/".trim($l).".pdb";
		download_remote_file($url, "pdb/".trim($l).".pdb");
	}
	echo "The PDB files for the cross-linked proteins were downloaded.<br/>";
	fclose($file);

	//FindAtomNumber
	$filename = trim($filename,'"')."pdb";
	$command = "java -jar jar/FindAtomNumber.jar recycled/".$filename;
	system($command);
	echo "The atom numbers were found for each pair<br/>";
	$filename = $filename."final";
	
	//Compute the distance of connection for each interaction
	$organism = $_POST['organism1'];
	$command = "java -jar jar/CalcInteractionDis.jar recycled/".$filename." ".$organism;
	system($command);
	
	$filename = $filename."fin";
	
	//Store the table name in the lab-tablename matching table	
	$dbaccess = new DBAccess();
	$dbaccess->connectDB();
	if($_POST['public']=="share"){
		$dataset_name = sanitizer($_POST['ExpName'])."_".sanitizer($_POST['LabName']);
		$dbaccess->insertStatement(
			'datasets',
			array(
				'dataset_name'=>$dataset_name,
			)
		);
		$tableName = "xlinkdb";
		echo "Store the dataset name in the open list";
	} else {			
		$tableName = sanitizer($_POST['ExpName'])."_".sanitizer($_POST['LabName']);
		$dbaccess->createTable($tableName);
	}
	//Write information into database
	function sanitizer($s) {
		$t="";
		for($i = 0; $i < strlen($s); $i++){
			$c = $s[$i];
			if(($c>='0' && $c<='9') || ($c>='a' && $c<='z') || ($c>='A' && $c<='Z')) $t.=$c;
		}
		return $t;
	}
	function sanitizerR($s) {
		$t="";
		for($i = 0; $i < strlen($s); $i++){
			$c = $s[$i];
			if($c!="'" && $c!="\"") $t.=$c;
		}
		return $t;
	}
	$file = fopen("recycled/".$filename, "r") or exit("The file is not found");

	while(!feof($file)) {
		$line = fgets($file);
		//echo $line."<br/>";
		if($line=="")continue;
		$arr = explode("\t", $line);
		$pepA = sanitizerR($arr[0]);
		$proA = sanitizerR($arr[1]);
		$kposA = sanitizerR($arr[2]);
		$geneA = sanitizerR($arr[3]);
		$AccessionA = sanitizerR($arr[4]);
		$DescriptionA = sanitizerR($arr[5]);
		$gnA = sanitizerR($arr[6]);
		$seqA = sanitizerR($arr[7]);
		$startPosA = sanitizerR($arr[8]);
		$pdbA = sanitizerR($arr[9]);
		if($arr[10]=="null")$siteA= "####";
		else $siteA = sanitizerR($arr[10]);
		if($arr[11]==0)$atomNumA="####";
		else $atomNumA = sanitizerR($arr[11]);
		$pepB = sanitizerR($arr[12]);
		$proB = sanitizerR($arr[13]);
		$kposB = sanitizerR($arr[14]);
		$geneB = sanitizerR($arr[15]);
		$AccessionB = sanitizerR($arr[16]);
		$DescriptionB = sanitizerR($arr[17]);
		$gnB = sanitizerR($arr[18]);
		$seqB = sanitizerR($arr[19]);
		$startPosB = sanitizerR($arr[20]);
		$pdbB = sanitizerR($arr[21]);
		if($arr[22]=="null")$siteB="####";
		else $siteB = sanitizerR($arr[22]);
		if($arr[23]==0)$atomNumB="####";
		else $atomNumB = sanitizerR($arr[23]);
		if($arr[24]==0)$distance="####";
		else $distance = substr(sanitizerR($arr[24]),0,6);
		$homo = false;
		$known = sanitizerR($arr[25]);
		
		if($homo) $known = "Intra";
		$statement_arr = array(
			'Organism' =>$organism,
			'pepA'=>$pepA,
			'proA'=>$proA,
			'kposA'=>$kposA,
			'geneA'=>$geneA,
			'AccessionA'=>$AccessionA,
			'DescriptionA'=>$DescriptionA,
			'gnA'=>$gnA,
			'seqA'=>$seqA,
			'startPosA'=>$startPosA,
			'pdbA'=>$pdbA,
			'siteA'=>$siteA,
			'atomNumA'=>$atomNumA,
			'pepB'=>$pepB,
			'proB'=>$proB,
			'kposB'=>$kposB,
			'geneB'=>$geneB,
			'AccessionB'=>$AccessionB,
			'DescriptionB'=>$DescriptionB,
			'gnB'=>$gnB,
			'seqB'=>$seqB,
			'startPosB'=>$startPosB,
			'pdbB'=>$pdbB,
			'siteB'=>$siteB,
			'atomNumB'=>$atomNumB,
			'distance'=>$distance,
			'known'=>$known,
		);
		if ($_POST['public']=="share") {
			$statement_arr['Organism'] = $organism;
			$statement_arr['dataset'] = $dataset_name;
		}
		$dbaccess->insertStatement($tableName, $statement_arr);
	}
	fclose($file);
	
	echo "<html><body>";
	if ($_POST['public']!="share") echo "<h1>Your table name is ".$tableName."</h1>";
	echo "</h2><a href='http://brucelab.gs.washington.edu/xlinkdb/InteractionView.php?tablename=".$tableName."?datasetName=".$dataset_name.">Go to your data page</a></body></html>";
?>
