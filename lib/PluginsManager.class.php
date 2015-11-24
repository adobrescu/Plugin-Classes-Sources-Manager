<?php

include_once(__DIR__.'/PluginsSourcesManager.class.php');

class PluginsManager extends PluginsSourcesManager
{
	protected $classNamesSufix='_Base';
	protected $baseClassesFileNames, $baseSourceFileNames, $baseSources;
	
	public function __construct($pluginsDirName)
	{
		$pluginFiles=$this->getPluginFiles($pluginsDirName);
		
		parent::__construct($pluginFiles);
	}
	protected function getPluginFiles($dirName)
	{
		$pluginFiles=array();
		foreach($files=glob($dirName.'/*') as $fileName)
		{
			if(is_dir($fileName))
			{
				$pluginFiles=array_merge($pluginFiles, $this->getPluginFiles($fileName));
				continue;
			}
			
			
			$pluginFiles[]=$fileName;
		}
		return $pluginFiles;
	}
	
	protected function getBaseClassFileName($baseClassName)
	{
		if(!isset($this->baseClassesFileNames[$baseClassName]))
		{
			$this->baseSourceFileNames=$this->getSourceFileNames();
		}
		
		if(!$this->baseSources)
		{
			foreach($this->baseSourceFileNames as $baseSourceFileName)
			{
				$this->baseSources[$baseSourceFileName]=new PHPSource($baseSourceFileName);
			}
		}
		foreach($this->baseSources as $baseSourceFileName=>$source)
		{
			$declaredClasses=$source->getDeclaredClasses();
			
			if(isset($declaredClasses[$baseClassName]))
			{
				return $source;
			}
		}
		return $this->baseClassesFileNames[$baseClassName];
	}
	protected function getSourceFileNames($dirName='')
	{
		if(!$dirName)
		{
			$dirName=__DIR__.'/../base-classes';
			//it takes ~6.1 seconds to parse all WP files:
			//$dirName=__DIR__.'/../../adrian/wp-includes';
			//
		}
		$sourceFiles=array();
		foreach($files=glob($dirName.'/*') as $file)
		{
			if(is_dir($file))
			{
				$sourceFiles=array_merge($sourceFiles, $this->getSourceFileNames($file));
				continue;
			}
			$sourceFiles[]=$file;
		}
		
		return $sourceFiles;
	}
}