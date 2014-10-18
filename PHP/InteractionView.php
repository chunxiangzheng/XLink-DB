<?php
	include "/db/DBAccess.php";
if ($_GET['tablename'] == "yeastspecdb") {
		header("Location: http://brucelab.gs.washington.edu/xlinkdb/yeastspecdb.php");
} else {
	$dbaccess = new DBAccess();
	$dbaccess->connectDB();
	$privateFlag = 0;
	if ($_GET['tablename']!=""){
		$tableName=$_GET['tablename'];
		$privateFlag=1;
	} else {
		$dataset_name = "";
		if (isset($_GET['dataset'])) $dataset_name=$_GET['dataset'];
		$tableName="xlinkdb";
	}
	//echo $tableName;
	if ($privateFlag == 1) {
		$result = $dbaccess->selectAll($tableName);
	} else {
		$result = $dbaccess->selectWhere($tableName, "dataset='".$dataset_name."'");
	}
	$arr = array();
	$arrPro = array();
	set_time_limit(0);
	while ($row = $result->fetch_array(MYSQLI_ASSOC)){
		if ($row['gnA']=="null"){
			$a = $row['proA'];
		} else { 
			$a = $row['gnA'];
		}
		if ($row['gnB']=="null"){
			$b = $row['proB'];
		} else {
			$b = $row['gnB'];
		}
		if ($row['pdbA']!="####") {
			$arrPro[$a] = 3;
		} else {
			if (!array_key_exists($a, $arr)) $arrPro[$a] = 2;
		}
		if ($row['pdbB']!="####") {
			$arrPro[$b] = 3;
		} else {
			if (!array_key_exists($b, $arr)) $arrPro[$b] = 2;
		}
		if (array_key_exists($a, $arr)) {
			if (!array_key_exists($b, $arr[$a])) {
				$arr[$a][$b] = array();
				$arr[$a][$b]["interact"] = $a;
				switch (trim($row["known"])) {
					case "0": $known=1;break;
					case "1": $known=2;break;
					case "intra": $known = 1; break;
					case "N/A": $known = 3; break;
					default: $known=3;
				}
				$arr[$a][$b]["known"] = $known;
				$arr[$a][$b]["connection"] = 1;
			} else {
				$arr[$a][$b]["connection"]++;
			}
		} else {
			$arr[$a] = array($b=>array());
			$arr[$a][$b]["interact"] = $a;
			switch (trim($row["known"])) {
				case "0": $known=1;break;
				case "1": $known=2;break;
				case "intra": $known = 1; break;
				case "N/A": $known = 3; break;
				default: $known=3;
			}
			$arr[$a][$b]["known"] = $known;
			$arr[$a][$b]["connection"] = 1;
		}
		if (array_key_exists($b, $arr)) {
			if (!array_key_exists($a, $arr[$b])) {
				$arr[$b][$a] = array();
				$arr[$b][$a]["interact"] = $b;
				switch (trim($row["known"])) {
					case "0": $known=1;break;
					case "1": $known=2;break;
					case "intra": $known = 1; break;
					case "N/A": $known = 3; break;
					default: $known=3; 
				}
				$arr[$b][$a]["known"] = $known;
				$arr[$b][$a]["connection"] = 1;
			} else {
				$arr[$b][$a]["connection"]++;				
			}
		} else {
			$arr[$b] = array($a=>array());
			$arr[$b][$a]["interact"] = $b;
			switch (trim($row["known"])) {
				case "0": $known=1;break;
				case "1": $known=2;break;
				case "intra": $known = 1; break;
				case "N/A": $known = 3; break;
				default: $known=3;
			}
			$arr[$b][$a]["known"] = $known;
			$arr[$b][$a]["connection"] = 1;
		}
	}
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
	//echo $value;
	$string = <<<EOF
	<html>
		<head>
				
			<title>XLink-DB | Network View</title>
			<link href = 'css/bootstrap.css' rel = 'stylesheet' type = 'text/css'>
			<link href = 'css/bootstrap-responsive.css' rel = 'stylesheet' type = 'text/css'>
			<link href = 'css/bootstrap.min.css' rel = 'stylesheet' type = 'text/css'>
			<link href = 'css/bootstrap-responsive.min.css' rel = 'stylesheet' type = 'text/css'>		
			<link rel='shortcut icon' href='/img/layout/favicon.png' />
			<link rel='stylesheet' type='text/css' href='css/layout.css' />
			<link rel='stylesheet' type='text/css' href='css/content.css' />
			<link rel='stylesheet' type='text/css' href='js/jquery/jquery-ui/css/custom-theme/jquery-ui-1.7.2.custom.css' />
			<link rel='stylesheet' type='text/css' href='css/content/demo.css' />
			
			<!--[if IE]>
			<link rel='stylesheet' type='text/css' href='/css/content/demo.ie.css' />
			<![endif]-->
EOF;
	$string.="<script type='text/javascript'>var tablename='".$tableName."'</script>";
	$string.="<script type='text/javascript'>var flag=".$privateFlag."</script>";
	$string.="<script type='text/javascript'>var dataset='".$dataset_name."'</script>";
	$string.= <<<EOF
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
			<script type='text/javascript' src='js/content/demo.js'></script>
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
			data: {
EOF;
	$string.=$value;
	$string.= <<<EOF
	}
		};
			</script>
EOF;
	$string.=<<<EOF
		</head>
		
		<body>
			<?php include_once("analyticstracking.php") ?>
			<div id='page' class='slice'>
				<!-- begin page content -->
				<div id='content' class='half_and_half'>
					<div class='left'>
						<h1>Please enable Javascript</h1>
						<p>Please enable Javascript, and then reload this page.</p>
					</div>
					<div class='right'>
						<h1>What if my browser does not support Javascript?</h1>
						<p>Please consider <a href='http://www.mozilla.com'>upgrading your browser</a>.</p>
					</div>
				</div>
				<!-- end page content -->
			</div>
		<hr>
		<footer>
			<p style="text-align:center">&copy; Bruce lab 2012</p>
		</footer>
		</body>
	</html>
EOF;
	echo $string;
}
?>
