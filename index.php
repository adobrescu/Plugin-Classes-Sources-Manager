<?php

	include_once('test-classes/Base.class.php');
	include_once('lib/PluginsManager.class.php');
	
	
	$pm=new PluginsManager(__DIR__.'/plugins', __DIR__.'/cache');
	echo '<pre>';
	print_r($pm->getPluginsFileNames());
	print_r($pm->getPluginsClassNames());
	print_r($pm->getPluginsDepencies());
	print_r($pm->generateFinalClass());