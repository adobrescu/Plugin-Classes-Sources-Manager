<?php

class PluginsManager 
{
	protected $pluginsDirName, $cacheDirName;
	protected $pluginsFileNames, $pluginsClassNames, $pluginsDepencies;
	protected $tokens;
	
	public function __construct($pluginsDirName, $cacheDirName)
	{
		spl_autoload_register(array($this, 'autoload'));
		
		$this->pluginsDirName=$pluginsDirName;
		$this->cacheDirName=$cacheDirName;
	}
	public function autoload($className)
	{
		include_once($this->pluginsDirName.'/'.str_replace('\\', '/', $className).'.class.php');
		echo $this->pluginsDirName.'/'.$className.'.class.php';
	}
	public function getPluginsFileNames()
	{
		return $this->pluginsFileNames=glob($this->pluginsDirName.'/*.php');
	}
	public function getPluginsClassNames()
	{
		/*
		 * !! metoda intoarce clasele in ordinea corecta de derivare, deoarece
		 * daca o clasa parent nu a fost deja inclusa atunci va fi rulat "autoload"
		 * care o va incarca facand astfel sa apara in lista inaintea clasei child
		 */
		$declaredClasses=get_declared_classes();
		
		$this->loadPluginClasses();
				
		$declaredClasses2=get_declared_classes();
		
		foreach(array_diff($declaredClasses2, $declaredClasses) as $pluginClassName)
		{
			$this->pluginsClassNames[$pluginClassName]=$pluginClassName;
		}
		return $this->pluginsClassNames;
	}
	public function loadPluginClasses()
	{
		$this->getPluginsFileNames();
		foreach($this->pluginsFileNames as $pluginFileName)
		{
			include_once($pluginFileName);
		}
	}
	public function getPluginsDepencies()
	{
		$this->loadPluginClasses();
		
		foreach($this->pluginsClassNames as  $pluginClassName)
		{
			$rc=new ReflectionClass($pluginClassName);
			
			$pluginParentClassName=$rc->getParentClass()->getName();
			$this->pluginsDepencies[$pluginClassName]['extends']=$pluginParentClassName;
			
			//find the base class (first ancestor class that is not a plugin class)
			$i=0;
			$pluginAncestorClassName=$pluginClassName;
			do
			{
				if(!isset($this->pluginsClassNames[$this->pluginsDepencies[$pluginAncestorClassName]['extends']]))
				{
					$pluginBaseClassName=$this->pluginsDepencies[$pluginAncestorClassName]['extends'];
					break;
				}
				if($i++>10)
				{
					break;
				}
				$pluginAncestorClassName=$this->pluginsDepencies[$pluginAncestorClassName]['extends'];
			}
			while(1);
			$this->pluginsDepencies[$pluginClassName]['base']=$pluginBaseClassName;
		}
		
		return $this->pluginsDepencies;
	}
	public function generateFinalClass()
	{
		$renamedBaseClasses=array();
		$lastBaseClassChildClass=array();
		foreach($this->pluginsDepencies as $pluginClassName=>$depency)
		{
			if(!isset($this->pluginsClassNames[$depency['extends']]))
			{
				//the base class
				if(!isset($renamedBaseClasses[$depency['base']]))
				{
					$this->renameBaseClass($depency['base']);
					$renamedBaseClasses[$depency['base']]=1;
					$this->rebuildClassPHPSource($depency['base']);
				}
				//rename it
				
				$renamedBaseClasses[$depency['extends']]=$depency['extends'];
				
				$this->renameClassParentClassName($pluginClassName,isset($lastBaseClassChildClass[$depency['base']])?$lastBaseClassChildClass[$depency['base']]: $this->getBaseClassRenamedClassName($depency['extends']));
			}
			
			$lastBaseClassChildClass[$depency['base']]=$pluginClassName;
			
			$this->rebuildClassPHPSource($pluginClassName);
		}
		foreach($lastBaseClassChildClass as $baseClassName=>$last)
		{
			echo '<?php
				class '.$baseClassName.' extends '.$last.PHP_EOL.
					'{'.PHP_EOL.
					'}
				?>';
		}
	}
	
	protected function loadClassPHPTokens($baseClassName)
	{
		if(isset($this->tokens[$baseClassName]))
		{
			return;
		}
		$rc=new ReflectionClass($baseClassName);
		$source=file_get_contents($rc->getFileName());
		
		$this->tokens[$baseClassName]=token_get_all($source);
		
		return;
		foreach($this->tokens[$baseClassName]=token_get_all($source) as $i=>$token)
		{
			if(gettype($token[0])=='string')
			{
				continue;
			}
			if($token[0]==T_OPEN_TAG)
			{
				$this->tokens[$baseClassName][$i][0]=T_COMMENT;
				$this->tokens[$baseClassName][$i][1]='/****************************/'.PHP_EOL;
				break;
			}
		}
		
	}
	protected function getClassNamePHPTokenIndex($baseClassName)
	{
		$this->loadClassPHPTokens($baseClassName);
		
		$classTokenFound=false;
		
		foreach($this->tokens[$baseClassName] as $tokenIndex=>$token)
		{
			if(gettype($token[0])=='string')
			{
				continue;
			}
			if($token[0]==T_CLASS)
			{
				$classTokenFound=true;
				continue;
			}
			if($token[0]!=T_STRING)
			{
				continue;
			}
			if($classTokenFound && $token[1]==$baseClassName)
			{				
				return $tokenIndex;
			}
			$classTokenFound=false;
		}
	}
	protected function renameBaseClass($baseClassName)
	{
		$tokenIndex=$this->getClassNamePHPTokenIndex($baseClassName);
		
		$this->tokens[$baseClassName][$tokenIndex][1]=$this->getBaseClassRenamedClassName($baseClassName);
				
		//$this->rebuildClassPHPSource($baseClassName);
		
	}
	protected function renameClassParentClassName($pluginClassName, $baseClassNewClassName)
	{
		//echo $pluginClassName.': '.$baseClassNewClassName."\n";
		$tokenIndex=$this->getClassNamePHPTokenIndex($pluginClassName);
		$extendsTokenFound=false;
		for($i=$tokenIndex; $i<count($this->tokens[$pluginClassName]); $i++)
		{
			$token=$this->tokens[$pluginClassName][$i];
			if(gettype($token[0])=='string')
			{
				continue;
			}
			if($token[0]==T_EXTENDS)
			{
				$extendsTokenFound=true;
				continue;
			}
			if(!$extendsTokenFound || $token[0]!=T_STRING)
			{
				continue;
			}
			$this->tokens[$pluginClassName][$i][1]=$baseClassNewClassName;
			break;
		}
		//$this->rebuildClassPHPSource($pluginClassName);
	}
	protected function getBaseClassRenamedClassName($baseClassName)
	{
		return $baseClassName.'_BaseAXXA';
	}
	protected function rebuildClassPHPSource($baseClassName)
	{
		$this->loadClassPHPTokens($baseClassName);
		$source='';
		//print_r($this->tokens[$baseClassName]);
		foreach($this->tokens[$baseClassName] as $token)
		{
			$source.=(isset($token[1])?$token[1]:$token[0]);
		}
		echo $source;
	}
}


