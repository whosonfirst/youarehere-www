<?php

	$root = dirname(dirname(__FILE__));
	ini_set("include_path", "{$root}/www:{$root}/www/include");

	include("include/init.php");
	loadlib("api_config");
	loadlib("cli");

	$spec = array(
		"output" => array("flag" => "o", "required" => 1, "help" => "..."),
		"all" => array("flag" => "a", "required" => 0, "boolean" => 1, "help" => "..."),
		"exclude" => array("flag" => "e", "required" => 0, "help" => "..."),
		# something about abs_root_url here...
	);

	$opts = cli_getopts($spec);

	#

	api_config_init();
	ksort($GLOBALS['cfg']['api']['methods']);

	# this is a dirty hack... (20130406/straup)

	$tmpdir = realpath(dirname(__FILE__)) . "/api_c";

	if (! is_dir($tmpdir)){
		mkdir($tmpdir);	   
	}

	$GLOBALS['smarty']->compile_dir  = $tmpdir;

	#

	$exclude = ($opts['exclude']) ? explode(",", $opts['exclude']) : array();

	#
	
	$fh = fopen($opts['output'], 'w');

	# $GLOBALS['smarty']->assign("page_title", "{$GLOBALS['cfg']['site_name']} API documentation");
	# fwrite($fh, $GLOBALS['smarty']->fetch("inc_head.txt"));

	foreach ($GLOBALS['cfg']['api']['methods'] as $method_name => $method_details){

		$include = 1;

		if (! $method_details['enabled']){
			$include = 0;
		}

		if (! $method_details['documented']){
			$include = 0;
		}

		if ($method_details['requires_blessing']){
			$include = 0;
		}

		if ($opts['all']){
			$include = 1;
		}

		foreach ($exclude as $what){

			if (preg_match("/^{$what}/", $method_name)){
				$include = 0;
				break;
			}
		}

		if (! $include){
			continue;
		}

		echo "adding {$method_name}\n";

		$GLOBALS['smarty']->assign_by_ref("method", $method_name);
		$GLOBALS['smarty']->assign_by_ref("details", $method_details);

		fwrite($fh, $GLOBALS['smarty']->fetch("inc_api_method.txt"));
	}

	# fwrite($fh, $GLOBALS['smarty']->fetch("inc_foot.txt"));

	echo "--\n";
	echo "done\n\n";

	fclose($fh);

	# clean up dirty hack (above)

	foreach (glob($tmpdir . '/*.php') as $file) {
		unlink($file);
	}

	rmdir($tmpdir);

	exit();
?>
