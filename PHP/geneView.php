<?php
	function checkSubstring($s1, $s2) {
		$i=0;
		while($i+strlen($s2)<strlen($s1)-1){
			if(substr($s1,$i,strlen($s2))==$s2) return true;
			$i++;
		}
		return false;
	}
	function convertProname($tableName, $dbaccess, $proname) {
		$result = getResult($tableName, $dbaccess, $proname);
		$a = $result[0];
		if ($a['gnA']==$proname || $a['geneA']==$proname || $a['proA']==$proname || checkSubstring($a['AccessionA'], $proname)) return $a['gnA'];
		if ($a['gnB']==$proname || $a['geneB']==$proname || $a['proB']==$proname || checkSubstring($a['AccessionB'], $proname)) return $a['gnB'];
	}
	function generateArr($result) {
		$arr = array();
		$arrPro = array();
		foreach ($result as $row){
			if (empty($row)) continue;
			if ($row['pdbA']!="####" && $row['siteA'] != "####" && $row['atomNumA'] != "####") {
				$arrPro[$row['gnA']] = 3;
			} else {
				if (!array_key_exists($row['gnA'], $arr)) $arrPro[$row['gnA']] = 2;
			}
			if ($row['pdbB']!="####" && $row['siteB'] != "####" && $row['atomNumB'] != "####") {
				$arrPro[$row['gnB']] = 3;
			} else {
				if (!array_key_exists($row['gnB'], $arr)) $arrPro[$row['gnB']] = 2;
			}
			if (array_key_exists($row['gnA'], $arr)) {
				if (!array_key_exists($row['gnB'], $arr[$row['gnA']])) {
					$arr[$row['gnA']][$row['gnB']] = array();
					$arr[$row['gnA']][$row['gnB']]["interact"] = $row['gnA'];
					switch ($row["known"]) {
						case 0: $known=1;break;
						case 1: $known=2;break;
						default: $known=3;
					}
					$arr[$row['gnA']][$row['gnB']]["known"] = $known;
					$arr[$row['gnA']][$row['gnB']]["connection"] = 1;
				} else {
					$arr[$row['gnA']][$row['gnB']]["connection"]++;
				}
			} else {
				$arr[$row['gnA']] = array($row['gnB']=>array());
				$arr[$row['gnA']][$row['gnB']]["interact"] = $row['gnA'];
				switch ($row["known"]) {
					case 0: $known=1;break;
					case 1: $known=2;break;
					default: $known=3; 
				}
				$arr[$row['gnA']][$row['gnB']]["known"] = $known;
				$arr[$row['gnA']][$row['gnB']]["connection"] = 1;
			}
			if (array_key_exists($row['gnB'], $arr)) {
				if (!array_key_exists($row['gnA'], $arr[$row['gnB']])) {
					$arr[$row['gnB']][$row['gnA']] = array();
					$arr[$row['gnB']][$row['gnA']]["interact"] = $row['gnB'];
					switch ($row["known"]) {
						case 0: $known=1;break;
						case 1: $known=2;break;
						default: $known=3; 
					}
					$arr[$row['gnB']][$row['gnA']]["known"] = $known;
					$arr[$row['gnB']][$row['gnA']]["connection"] = 1;
				} else {
					$arr[$row['gnB']][$row['gnA']]["connection"]++;	
				}
			} else {
				$arr[$row['gnB']] = array($row['gnA']=>array());
				$arr[$row['gnB']][$row['gnA']]["interact"] = $row['gnB'];
				switch ($row["known"]) {
					case 0: $known=1;break;
					case 1: $known=2;break;
					default: $known=3; 
				}
				$arr[$row['gnB']][$row['gnA']]["known"] = $known;
				$arr[$row['gnB']][$row['gnA']]["connection"] = 1;
			}
			
			
		}
		$buffer['Result'] = $arr;
		$buffer['Protein'] = $arrPro;
		return $buffer;
	}
	include "db/DBAccess.php";
	$dbaccess = new DBAccess();
	$dbaccess->connectDB();
	$public = "";
	if (isset($_GET['public'])) $public = $_GET['public'];
	if($public == "share") {
		$tableName="xlinkdb";
		$private = 0;
	} else {
		$tableName=$_GET['tablename'];
		$private = 1;
	}
	$arr = array();
	
	set_time_limit(0);
	$pronamelist = "";
	if (isset($_GET['proNamelist'])) $pronamelist = $_GET['proNamelist'];
	$proname_list = explode(";",$pronamelist);
	
	//get all the results
	$result = array();
	foreach ($proname_list as $proname) {
		$proname = trim($proname);
		if ($proname == '') continue;
		$result_sql = $dbaccess->selectWhere($tableName, "`gnA`='".$proname."'"." OR `gnB`='".$proname."' OR `geneA`='".$proname."' OR `geneB`='".$proname
			."' OR `proA`='".$proname."' OR `proB`='".$proname."' OR `AccessionA` like '%".$proname."%' OR `AccessionB`='%".$proname."%'");
		while ($row = $result_sql->fetch_array(MYSQLI_ASSOC)) {
			$result[] = $row;
		}
	}
	if (empty($result)) header("Location: http://brucelab.gs.washington.edu/xlinkdb/search.php");
	$buffer = generateArr($result);
	$arr=array();
	$arrPro=array();
	$arr = $buffer['Result'];
	$arrPro = $buffer['Protein'];
	
	$nodes = "nodes: [";
	$edges = "edges: [";
	foreach ($arr as $key=>$val) {
		$nodes .= "{sizeAttri:".$arrPro[$key].", id:'".trim($key)."',label:'".trim($key)."',Interactors:'";
		foreach ($arr[$key] as $k=>$v) {
			$nodes .= trim($k)."-";
			if ($v["connection"] >= 2) $connection = 3;
			else $connection = 2;
			if(strcmp($k, $v["interact"]) >= 0) $edges .= "{colorAttri:".$v["known"].", widthAttri:".$connection.", id:'".trim($k)."-".trim($v["interact"])."',target:'".trim($v["interact"])."',source:'".trim($k)."', Crosslinking:'".trim($k)."-".trim($v["interact"])."'},";
		}
		$nodes = trim($nodes,"-"). "'},";
	}
	$value = trim($nodes, ",")."],".trim($edges,",")."]";
	error_log($value."\n", 3, "log.txt");
	$string = 
	"<html>
		<head>
				
			<title>XLink-DB | Search View</title>
			
			<link rel='shortcut icon' href='/img/layout/favicon.png' />
			<link rel='stylesheet' type='text/css' href='css/layout.css' />
			<link rel='stylesheet' type='text/css' href='css/content.css' />
			<link rel='stylesheet' type='text/css' href='js/jquery/jquery-ui/css/custom-theme/jquery-ui-1.7.2.custom.css' />
			<link rel='stylesheet' type='text/css' href='css/content/demo.css' />
			<link href = 'css/bootstrap.css' rel = 'stylesheet' type = 'text/css'>
			<link href = 'css/bootstrap-responsive.css' rel = 'stylesheet' type = 'text/css'>
			<link href = 'css/bootstrap.min.css' rel = 'stylesheet' type = 'text/css'>
			<link href = 'css/bootstrap-responsive.min.css' rel = 'stylesheet' type = 'text/css'>
			<!--[if IE]>
			<link rel='stylesheet' type='text/css' href='/css/content/demo.ie.css' />
			<![endif]-->
			<script type='text/javascript'>var tablename='".$tableName."'</script>
			<script type='text/javascript'>var dataset='".""."'</script>
			<script type='text/javascript'>var flag='".$private."'</script>
			<script type='text/javascript'>var proteinList='".$_GET['proNamelist']."'</script>
			<script type='text/javascript' src='js/jquery/jquery-1.3.2.min.js'></script>
			<script type='text/javascript' src='js/jquery/plugins/jquery.qtip-1.0.0-rc3.min.js'></script>
			<script type='text/javascript' src='js/layout/layout.js'></script>
			<script type='text/javascript' src='js/string/levenshtein.js'></script>
			<script type='text/javascript' src='js/jquery/jquery-ui/js/jquery-ui-1.8.12.custom.min.js'></script>
			<script type='text/javascript' src='js/flash/flash_detect_min.js'></script>
			<script type='text/javascript' src='js/jquery/plugins/jquery.layout.min.js'></script>
			<script type='text/javascript' src='js/jquery/plugins/jquery.menu.js'></script>
			<script type='text/javascript' src='js/jquery/plugins/jquery.tablesorter.min.js'></script>
			<script type='text/javascript' src='js/jquery/plugins/jquery.validate.js'></script>
			<script type='text/javascript' src='js/jquery/plugins/jquery.thread.js'></script>
			<script type='text/javascript' src='js/jquery/plugins/jquery.farbtastic.js'></script>
			<script type='text/javascript' src='js/jquery/plugins/jquery.cytoscapeweb.js'></script>
			<script type='text/javascript' src='js/cytoscape_web/json2.min.js'></script>
			<script type='text/javascript' src='js/cytoscape_web/AC_OETags.min.js'></script>
			<script type='text/javascript' src='js/cytoscape_web/cytoscapeweb.min.js'></script>
			<script type='text/javascript' src='js/cytoscape_web/cytoscapeweb-styles-demo.js'></script>
			<script type='text/javascript' src='js/cytoscape_web/cytoscapeweb-file.js'></script>
			<script type='text/javascript' src='js/content/geneView.js'></script>
			<script type='text/javascript'>
				var network_json = {
			dataSchema: {
			nodes: [ { name: 'label', type: 'string' },
					 { name: 'Interactors', type: 'string' },
					 { name: 'sizeAttri', type: 'number'}
					],
			edges: [ { name: 'Crosslinking', type: 'string' },
					 { name: 'colorAttri', type:'number'},
					 { name: 'widthAttri', type:'number'}
					]
			},
			data: {";
	$string.=$value;
	$string.=
	"}
		};
			</script>
			<!--[if IE]>
					<![endif]-->

			
		</head>
		
		<body>
			<?php include_once('analyticstracking.php') ?>
			<div id='page' class='slice'>
				<!-- begin page content -->
				<div id='content' class='half_and_half'>
					<div class='left'>
						<h1>Please enable Javascript</h1>
						<p>Javascript is not enabled in your browser, and it is necessary to have Javascript enabled to view this demo.</p>
						<p>Please enable Javascript, and then reload this page.</p>
					</div>
					<div class='right'>
						<h1>What if my browser does not support Javascript?</h1>
						<p>Please consider <a href='http://www.mozilla.com'>upgrading your browser</a>.</p>
					</div>
				</div>
				<!-- end page content -->
			</div> 
		</body>
	</html>";
	echo $string;
?>
