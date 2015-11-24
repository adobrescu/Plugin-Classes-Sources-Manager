<?php

include_once(__DIR__.'/PHPSource.class.php');

abstract class PluginsSourcesManager
{
	protected $sources;
	protected $pluginClasses=array(); //plugins declared classes info
	protected $pluginClassesAliases=array(); //class aliases info (from statements like: 
											// use ClassName as ClassAlias;
	//protected $baseClassNames=array();//names of classes plugins classes extend
	protected $baseClassesLastInheritors=array(); //used when inheritance chain is built to keep each base class last inheritor class, so when the next inheritos is found it extends previous inheritor
	
	public function __construct($pluginsFileNames)
	{
		foreach($pluginsFileNames as $pluginsFileName)
		{
			$this->sources[$pluginsFileName]=new PHPSource($pluginsFileName);
			
			if(!($pluginClasses=$this->sources[$pluginsFileName]->getDeclaredClasses()))
			{
				continue;
			}
			
			//$this->pluginClassesAliases=array_merge($this->pluginClassesAliases, $this->sources[$pluginsFileName]->getClassesAliases());
			
			foreach($pluginClasses as $classFullName=>$pluginClass)
			{
				$pluginClasses[$classFullName]['index']+=count($this->pluginClasses);
				$pluginClasses[$classFullName]['source']=$this->sources[$pluginsFileName];
			}
			$this->pluginClasses=array_merge($this->pluginClasses, $pluginClasses);
		}
				
		//find base classes (classes extended by declarde classes that are plugin classes themselves
		foreach($this->pluginClasses as $pluginFullClassName=>$classInfo)
		{
			
			$this->pluginClasses[$pluginFullClassName]['base_class_name']=$this->getBaseClassName($pluginFullClassName);
			
			//find parent class name
			if($aliasClassName=$this->pluginClasses[$pluginFullClassName]['source']->getAliasClassName($classInfo[PHPSource::EXTENDED_CLASS_FULL_CLASS_NAME]))
			{
				//the class extends an alias (declared in an "use" statement);
				$this->pluginClasses[$pluginFullClassName]['parent_class_name']=$aliasClassName;
			}
			else
			{
				//parent class name is after "extends"
				$this->pluginClasses[$pluginFullClassName]['parent_class_name']=$classInfo[PHPSource::EXTENDED_CLASS_FULL_CLASS_NAME];
			}
			
			
			if(isset($this->pluginClasses[$this->pluginClasses[$pluginFullClassName]['parent_class_name']]))
			{
				$this->pluginClasses[$pluginFullClassName]['ref_parent']=&$this->pluginClasses[$this->pluginClasses[$pluginFullClassName]['parent_class_name']];
			}
			else
			{
				$this->pluginClasses[$pluginFullClassName]['ref_parent']=null;
			}
			if(!$classInfo[PHPSource::EXTENDED_CLASS_FULL_CLASS_NAME])
			{
				//the class doesn't extend other class
				continue;
			}
			
			$this->baseClassNames[$this->pluginClasses[$pluginFullClassName]['base_class_name']]=
					$this->baseClassesLastInheritors[$this->pluginClasses[$pluginFullClassName]['base_class_name']]=			
					$this->formatBaseClassRenamed($this->pluginClasses[$pluginFullClassName]['base_class_name']);
			
		}
		
		//sort classes info so base/parent classes come first
		/*Nota legata de priority:
		 * - ordinea de derivare este inversa decat ordinea de "incarcare" si executie a pluginurilor;
		 * - cu cat o clasa este in lantul de derivare mai spre coada, cu atat ea va fi executata mai repede (este mai aproape de "hook")
		 * - De ex, dupa modificarea surselor se obtine:
		 * 
		 * class Base_Base;
		 * class Plugin1 extends Base_Base;
		 * class Plugin2 extends Plugin1;
		 * class Base extends Plugin2;
		 * 
		 * Metodele lui Plugin2 vor fi executate inainte celor ale lui Plugin1 (este "incarcat" inaintea lui Plugin1)
		 * 
		 * - clasele care mostenesc dintr-o clasa plugin vin imediat dupa ea;
		 * - printre ele pot aparea clase care mostenesc la randul lor din aceste clase child;
		 * - priority are efect doar printre clasele child ale aceleiasi clase parent;
		 * 
		 */
		//ordinea claselor dupa sortare trebuie sa fie:
		//-clasele mostenitoare ale unei clase plugin vin imediat dupa clasa plugin;
		//-ordinea claselor child poate fi specificata cat de cat prin priority
		/* Exemplu, clasele sint declarate in ordinea:
		 * 
		 //doua pluginuri mostenesc direct de la clasa de baza
		class Plugin1 extends Base;
		class Plugin2 extends Base;
		 
		//Pluginul 1 are 2 descendenti
		//priority: high
		class Plugin1Extension1 extends Plugin1;
				
		class Plugin1Extension2 extends Plugin1; 
		
		//Lantul de derivare generat trebuie sa fie
		 
		class Plugin1 extends Base;
		class Plugin1Extension2 extends Plugin1; //este inserat imediat dupa clasa pe care o mosteneste; 
												//vine mai in fata decat Plugin1Extension1 pentru ca Plugin1Extension2 are priority=high ceea ce cere 
												//sa fie mai aproape de primul apel ale metodelor de baza (executat mai repede in lant)
		
		class Plugin1Extension1 extends Plugin1Extension2;
		class Plugin2 extends Plugin1;
		 */
		uasort($this->pluginClasses, array($this, 'compareClassesByInheritance'));
		
	}
	protected function formatBaseClassRenamed($baseClassName)
	{
		return $baseClassName.$this->classNamesSufix;
	}
	/*
	 * Find and returns a class ancestor class name not declared within source (declared outside plugins dir)
	 */
	protected function getBaseClassName($fullClassName)
	{
		if(!isset($this->pluginClasses[$fullClassName]))
		{
			return '';
		}
		$classInfo=$this->pluginClasses[$fullClassName];
		
		if($aliasClassName=$classInfo['source']->getAliasClassName($classInfo[PHPSource::EXTENDED_CLASS_FULL_CLASS_NAME]))
		{
			if(isset($this->pluginClasses[$aliasClassName]))
			{
				return $this->getBaseClassName($aliasClassName);
			}
			return $aliasClassName;
		}
		
		return ($baseClassName=$this->getBaseClassName($classInfo[PHPSource::EXTENDED_CLASS_FULL_CLASS_NAME]))?$baseClassName:$classInfo[PHPSource::EXTENDED_CLASS_FULL_CLASS_NAME];
	}
	public function compareClassesByInheritance($class1, $class2)
	{
		/*
			Cum se compara 2 clase pentru a stabili ordinea de derivare
			1. Daca o clasa este parentul celeilalte atunci vine inaintea celei child
			2. Daca au acelasi parent atunci se foloseste class priority pentru a stabili ordinea (daca este definita, daca nu este definita atunci se foloseste ordinea de declarare/de incarcare din fisiere);
		    3. Daca nu au acelasi parent si nu au un plugin parent niciuna (deci sint derivate direct din clase de baza diferite) se foloseste denumirea claselor de baza;
			
			4. S-a trecut de 3) deci cel putin o clasa are un plugin parent (poate ambele). Se compara (prin recursie) folosind 1-3 intre clasele plugin parent. Daca una din clase
				nu are asa ceva atunci se compara intre ea si clasa parent plugin a celeilalte.
				
		 */
		//1. 
		if($class1['parent_class_name']==$class2['full_name'])
		{
			return 1;
		}
		//1.
		if($class2['parent_class_name']==$class1['full_name'])
		{
			return -1;
		}
		//2.
		if($class1['parent_class_name']==$class2['parent_class_name'])
		{
			if(isset($class1['settings']['priority']) || isset($class2['settings']['priority']))
			{
				$priority1=isset($class1['settings']['priority'])?$class1['settings']['priority']:-1;
				$priority2=isset($class2['settings']['priority'])?$class2['settings']['priority']:-1;
				
				return $priority1-$priority2;
			}
			
			return $class1['index']-$class2['index'];
		}
		//3.
		elseif(!$class1['ref_parent'] && !$class2['ref_parent'])
		{
			return strcmp($class1['base_class_name'], $class2['base_class_name']);
		}
		
		//4.
		$nextClass1=$class1['ref_parent']?$class1['ref_parent']:$class1;
		$nextClass2=$class2['ref_parent']?$class2['ref_parent']:$class2;
		
		return $this->compareClassesByInheritance($nextClass1, $nextClass2);
	}
	public function rebuildSources($appendCloseTagIfNeeded)
	{
		
		foreach($this->pluginClasses as $pluginClassFullName=>$pluginClassInfo)
		{
			//echo $this->pluginClasses[$pluginClassFullName]['source']->getFileName()."\n";
			if(!$pluginClassInfo[PHPSource::EXTENDED_CLASS_FULL_CLASS_NAME])
			{
				//the class doesn't extend other class or it extends another plugin class 
				continue;
			}
			if($baseClassName=$pluginClassInfo['source']->getAliasClassName($pluginClassInfo[PHPSource::EXTENDED_CLASS_FULL_CLASS_NAME]))
			{
				//the class extends using an alias
				//the base class must be renamed in the "use" statement that creates the alias
				
				$pluginClassInfo['source']->renameUseClass( $pluginClassFullName,//$pluginClassInfo[PHPSource::EXTENDED_CLASS_FULL_CLASS_NAME], 
																$this->baseClassesLastInheritors[$pluginClassInfo['base_class_name']]);
				
				
				$this->baseClassesLastInheritors[$pluginClassInfo['base_class_name']]=$pluginClassFullName;
				
				continue;
				
			}

			
			$this->pluginClasses[$pluginClassFullName]['source']->renameExtendedClass($pluginClassFullName, 
																		$this->baseClassesLastInheritors[$pluginClassInfo['base_class_name']]);
			
			$this->baseClassesLastInheritors[$pluginClassInfo['base_class_name']]=$pluginClassFullName;
		}
		
		$renamedBaseClasses=array();
		
		//print_r($this->baseClassNames);
		foreach($this->baseClassNames as $baseClassName=>$baseNewClassName)
		{
			
			$baseClassSources[$baseClassName]=$this->getBaseClassFileName($baseClassName);
			
			$declaredClasses=$baseClassSources[$baseClassName]->getDeclaredClasses();
			
			$renamedBaseClassName=$this->formatBaseClassRenamed($baseClassName);
			
			if(isset($declaredClasses[$renamedBaseClassName]))
			{//base source already modified 
				$renamedBaseClasses[$baseClassName]=1;
				continue;
			}
			
			$baseClassNameParts=PHPSource::___getClassNameParts($baseClassName);
			$baseNewClassNameParts=PHPSource::___getClassNameParts($baseNewClassName);
			$sources['dummy_base'][$baseClassName]='class '.$baseClassNameParts['class'].' extends '.$baseNewClassNameParts['class'].PHP_EOL.
					'{'.PHP_EOL.
					'}';
		}
		/*
		 * modify base classes names
		 */
		foreach($this->baseClassNames as $baseClassName=>$baseNewClassName)
		{
			$baseClassSource=$this->getBaseClassFileName($baseClassName);
			
			if(!isset($renamedBaseClasses[$baseClassName]))
			{
				$baseClassSource->renameClass($baseClassName, $baseNewClassName);
				$renamedBaseClasses[$baseClassName]=1;
			
				if(!isset($sources['base'][$baseClassName]))
				{
					$sources['base'][$baseClassName]=array(
						'class' => $baseClassName,
						'filename' => $baseClassSource->getFileName(),
						'source' => $baseClassSource->rebuildSource(0, 0, true, $appendCloseTagIfNeeded) );




				}
			}
		}
		/*
		 * rebuild plugin classes sources with the new inheritance chain
		 */
		foreach($this->sources as $pluginFileName=>$source)
		{
			$sources['plugins'][$pluginFileName]=$source->rebuildSource(0, 0, true, $appendCloseTagIfNeeded);
			
		}
		/*
		 * generate new sources, with original classes names at the end of the inheritance chain (empty classes)
		 */
		
		return $sources;
	}
	abstract protected function getBaseClassFileName($baseClassName);

}
