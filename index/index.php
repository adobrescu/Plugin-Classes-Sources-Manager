<?php


	
	include_once('../lib/PluginsManager.class.php');
	
	
	//$phpSource=new PHPSource(__DIR__.'/plugins/test/nuredenumi/Plugin5.plugin.php');
	
	
	//return;
	//echo '<pre>';
	$pm=new PluginsManager(__DIR__.'/../plugin-classes/.hooks', __DIR__.'/../cache');
	
	$pluginsFileName=__DIR__.'/../plugin-classes/hooks.php';
	
	function getPathsCommonPath($path1, $path2)
	{
		$path1Len=strlen($path1);
		for($i=0; $i<$path1Len; $i++)
		{
			if($path1[$i]!=$path2[$i])
			{
				break;
			}
		}
		return substr($path1, 0, $i);
	}
	function mapFileName2Path($filename, $relativeToPath)
	{
		$pathInfo=pathinfo($filename);
		
		//print_r($pathInfo);
		$path=preg_replace('|[/]+|', '/', preg_replace('|[/]+$|', '', $pathInfo['dirname']));
		$pathMaxIndex=strlen($path)-1;
		
		$relativeToPath=preg_replace('|[/]+|', '/', preg_replace('|[/]+$|', '', $relativeToPath));
		
		for($i=0; $i<strlen($path); $i++)
		{
			if($path[$i]!=$relativeToPath[$i] || $i>$pathMaxIndex)
			{
				break;
			}
		}
		
		
		$path=preg_replace('|^[/]+|', '', substr($path, $i));
		$relativeToPath=preg_replace('|^[/]+|', '', substr($relativeToPath, $i));
		
		return $path.'/'.$pathInfo['basename'];
	}
	
	//echo mapFileName2Path('/httpd/htdocs/lib-utils/sssssss', '/httpd/htdocs/plugins/cache');
	
	echo getPathsCommonPath('/Users/Adi', '/Users');
	
	if($sources=$pm->rebuildSources($skipPHPTags=true))
	{
		foreach($sources['base'] as $baseClassName=>$baseSource)
		{
			$baseClassCacheFileName=mapFileName2Path(realpath($baseSource['filename']), realpath(__DIR__.'/../plugin-classes/original-base-classes'));
				
			$cacheDir=dirname(__DIR__.'/../plugin-classes/original-base-classes/'.$baseClassCacheFileName);
				
			//echo $cacheDir."\n";
			if(!is_dir($cacheDir))
			{
				mkdir($cacheDir, 0777, true);
			}
			if(!is_file(__DIR__.'/../plugin-classes/original-base-classes/'.$baseClassCacheFileName))
			{
				copy($baseSource['filename'], __DIR__.'/../plugin-classes/original-base-classes/'.$baseClassCacheFileName);
			}
			
			//echo 'aici: '.$baseSource['filename']."\n";
			file_put_contents( $baseSource['filename'], $baseSource['source'].
					(isset($sources['dummy_base']) ? 			
					PHP_EOL.'<?php'.
					PHP_EOL.PHP_EOL.'include_once(\'hooks.php\');'.
					PHP_EOL.PHP_EOL.'?>'.
					PHP_EOL.'<?php'.
					PHP_EOL.PHP_EOL.$sources['dummy_base'][$baseClassName]
					:'')
					, 0);
		}
		$pluginSource2='';
		foreach($sources['plugins'] as $pluginClassName=>$pluginSource)
		{
			$pluginSource2.=($pluginSource2?PHP_EOL:'').$pluginSource;//['source'];
		}
		
		echo $pluginSource2;
	}
	