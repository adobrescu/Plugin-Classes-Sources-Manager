<?php

/*
 * PHPSource 
 * 
 * stie doar cateva chestii de baza legat de clase
 * 
 */
class PHPSource
{
	const EXTENDED_CLASS_FULL_CLASS_NAME='extends_full';
	const EXTENDS_START_TOKEN='extends_start_token';
	const EXTENDS_END_TOKEN='extends_end_token';
	
	const NS='namespace';
	
	static $___scriptStartEmptyTokens=array( //tokens at the top of the source, befora any code
										T_OPEN_TAG=>1,
										T_COMMENT=>1,
										T_WHITESPACE=>1
									);
	static $___settingsValues=array(
		'priority' => array ('high' => 2,
							'normal' => 1,
							'low' => 0 ),
		'package' => ''
		
	);
	protected $tokens;
	protected $filename;
	protected $classes=array(), $classAliases=array(), $currentNS='';
	public function __construct($filename)
	{
		$this->filename=$filename;
		//include($this->filename);
		$this->tokens=token_get_all(file_get_contents($this->filename));
		foreach($this->tokens as $i=>$token)
		{
			if(is_array($token))
			{
				//$this->tokens[$i][3]=token_name($token[0]);
			}
			else
			{
				$this->tokens[$i]=array( 0=> 0, 1=>$token, 2 => '', 3 => '');
			}
		}
		$this->parse();
	}
	protected function getClassSettings($start)
	{
		
		for($i=$start; $i>=0; $i--)
		{
			if($this->tokens[$i][0]!=T_COMMENT && $this->tokens[$i][0]!=T_WHITESPACE)
			{
				return null;
			}
			if($this->tokens[$i][0]==T_COMMENT)
			{
				break;
			}
		}
		if($i<0)
		{
			return '';
		}
		
		
		static $pattern="(([a-z0-9\-\_]+)[\s]*:[\s]*(.*))+";
			
		preg_match_all('/'.$pattern.'/', $this->tokens[$i][1], $matches);
		
		$settings=array();
		
		if($matches)
		{
			for($i=0; $i<count($matches[2]); $i++)
			{
				$settingName=trim($matches[2][$i]);
				$settingValue=trim($matches[3][$i]);
				$settings[$settingName]=isset(static::$___settingsValues[$settingName][$settingValue]) ?static::$___settingsValues[$settingName][$settingValue]:$settingValue;
			}
		}
		
		return $settings;
	}
	protected function parse()
	{
		for($i=0; $i<count($this->tokens); $i++)
		{
			if(!isset(static::$___scriptStartEmptyTokens[$this->tokens[$i][0]]))
			{
				break;
			}
		}
		if(!isset($this->tokens[$i]))//empty file
		{
			return;
		}
		
		$classBrakets=0;//folosit pentru a vedea daca sintem in interiorul unei clase
		
		for($i; $i<count($this->tokens); $i++)
		{
			switch($this->tokens[$i][0])
			{
				case 0:
					if($this->tokens[$i][1]=='{')
					{
						$classBrakets++;
					}
					elseif($this->tokens[$i][1]=='}')
					{
						$classBrakets--;
						if($classBrakets==0)
						{
							end($this->classes);//[$this->currentNS]);
							$lastFullClassName=key($this->classes);
							$this->classes[$lastFullClassName]['end_token']=$i;
						}
					}
						
					break;
				case T_NAMESPACE:
					$nsNameEndToken=$this->findNextTokens($i+1, array(';', '{'));
					$this->currentNS='\\'.$this->rebuildSource($i+1, $nsNameEndToken-1, false);
					
					$i=$nsNameEndToken;
					
					break;
				case T_CLASS:
					$classBrakets=0;
					$classNameEndToken=$this->findNextTokens($i+1, array(T_EXTENDS, T_IMPLEMENTS, '{'));
					$className=$this->rebuildSource($i+1, $classNameEndToken-1, false);
					
					$signatureEndToken=$this->findNextTokens($i+1, array('{'))-1;
					$signatureStartToken=$this->getPreviousAllowedTokensStart($i-1, array(T_CLASS, T_ABSTRACT, T_FINAL));
					
					$this->classes[$this->currentNS.'\\'.$className]=array(
							'index' => count($this->classes),
							'name'=>$className,
							'name_start_token' => $i+2,
							'name_end_token' => $classNameEndToken-2,
							'signature_end_token' => $signatureEndToken,
							'signature_start_token' => $signatureStartToken,
							'signature' => rtrim($this->rebuildSource($signatureStartToken, $signatureEndToken)),
							'settings' => $this->getClassSettings($i-1),
							'full_name' => $this->currentNS.'\\'.$className,
							static::NS=>$this->currentNS,
							'extends' => '',
							static::EXTENDS_START_TOKEN => -1,
							static::EXTENDS_END_TOKEN => -1,
							static::EXTENDED_CLASS_FULL_CLASS_NAME => ''
							);
					
					$i=$classNameEndToken-1;
					
					break;
				case T_EXTENDS:
					$baseClassNameEndToken=$this->findNextTokens($i+1, array(T_IMPLEMENTS, '{'));
					$baseClassName=$this->rebuildSource($i+1, $baseClassNameEndToken-1, false);
					
					
					end($this->classes);//[$this->currentNS]);
					$lastFullClassName=key($this->classes);
					$this->classes[$lastFullClassName]['extends']=$baseClassName;
					
					if($baseClassName[0]!='\\')
					{
						$baseClassName=$this->currentNS.'\\'.$baseClassName;
					}
					
					
					$this->classes[$lastFullClassName][PHPSource::EXTENDED_CLASS_FULL_CLASS_NAME]=$baseClassName;
					$this->classes[$lastFullClassName][static::EXTENDS_START_TOKEN]=$i+2;
					$this->classes[$lastFullClassName][static::EXTENDS_END_TOKEN]=$baseClassNameEndToken-2;//backwards -2 : -1 to skip ";"; -1 to skipt the whitespace after extended class name 
					
					$i=$baseClassNameEndToken-1;
					
					break;
				case T_USE:
					if($classBrakets>0)
					{//within a class brackets, "use" is used with traits
						break;
					}
					$classNameEndToken=$this->findNextTokens($i+1, array(T_AS), ';');
					
					if($classNameEndToken==-1)
					{
						$classNameEndToken=$classNameAliasEndToken=$this->findNextTokens($i+1, array(';'));
						$hasDifferentName=false;
						
					}
					else
					{
						$hasDifferentName=true;
					}
					
					$use=$className=$this->rebuildSource($i+1, $classNameEndToken-1, false);
					
					if($className[0]!='\\')
					{
						$className='\\'.$className;
					}
					
					if($hasDifferentName)
					{
						$classNameAliasEndToken=$this->findNextTokens($classNameEndToken+1, array(';'));
						$classNameAlias=$this->rebuildSource($classNameEndToken+1, $classNameAliasEndToken-1, false);
					}
					else
					{
						$classNameParts=static::___getClassNameParts($className);
						//print_r($classNameParts);
						$classNameAlias=$classNameParts['class'];
						
					}
					$this->classAliases[$this->currentNS.'\\'.$classNameAlias]=array(
						'use' => $use,
						'use_start_token' => $i+2,
						'use_end_token' => $classNameEndToken-1,
						'use_statement' => $this->rebuildSource($i, $classNameAliasEndToken),
						'class_full_name_or_alias' => $className,
						'alias' => $classNameAlias,
						
						static::NS => $this->currentNS
					);
					
					$i=$classNameAliasEndToken;
					
					break;
			}
		}
		
	}
	protected function getPreviousAllowedTokensStart($start, $allowedTokens, $allowWhitespaces=true)
	{
		for($i=$start; $i>0; $i--)
		{
			$isTokenAllowed=false;
			
			foreach($allowedTokens as $allowedToken)
			{
				if( ($allowWhitespaces && $this->tokens[$i][0]==T_WHITESPACE)
					||
					( gettype($allowedToken)=='string' && $this->tokens[$i][1]==$allowedToken) 
					||
					gettype($allowedToken)=='integer' && $this->tokens[$i][0]==$allowedToken)
				{
					$isTokenAllowed=true;
					break;
				}
			}
			if(!$isTokenAllowed)
			{
				//echo token_name($this->tokens[$i][0]).' '.$this->tokens[$i][1];
				//echo "\n";
				break;
				
			}
		}
		
		if($allowWhitespaces && $this->tokens[$i+1][0]==T_WHITESPACE)// && !in_array(T_WHITESPACE, $allowedTokens))
		{
			$offset=2;
		}
		else
		{
			$offset=1;
		}
		
		return $i+$offset;
	}
	/*
	 * findExpression
	 * 
	 * returns the tokens between $foreTokens and $backTokens
	 */
	protected function findNextTokens($i, $findTokens, $stopTokens=array())
	{
		if(!is_array($stopTokens))
		{
			$stopTokens=array($stopTokens);
		}
		for($j=$i; $j<count($this->tokens); $j++)
		{
			$tokenFound=false;
			foreach($findTokens as $findToken)
			{
				if( ( gettype($findToken)=='string' && $this->tokens[$j][1]==$findToken) 
					||
					gettype($findToken)=='integer' && $this->tokens[$j][0]==$findToken)
				{
					$tokenFound=true;
					break;
				}
			}
			if($tokenFound)
			{
				return $j;
			}
			foreach($stopTokens as $stopToken)
			{
				if( ( gettype($stopToken)=='string' && $this->tokens[$j][1]==$stopToken) 
					||
					gettype($stopToken)=='integer' && $this->tokens[$j][0]==$stopToken)
				{
					return -1;
				}
			}
		}
	}
	public function rebuildSource($start=0, $end=0, $allowSpaces=true, $appendCloseTagIfNeeded=false)
	{
		$source='';
		if($end<=0)
		{
			$end=count($this->tokens)-1+$end;
		}
		for($i=$start; $i<=$end; $i++)
		{
			if(!$allowSpaces && $this->tokens[$i][0]==T_WHITESPACE)
			{
				continue;
			}
			$source.=$this->tokens[$i][1];
		}
		
		
		if($appendCloseTagIfNeeded)
		{
			//php close tag is the last token in a source,
			//or the one before followed by an inlint html 
			if($this->tokens[count($this->tokens)-1][0]!=T_CLOSE_TAG
				&& $this->tokens[count($this->tokens)-2][0]!=T_CLOSE_TAG)
			{
				$source .="\n".'?>';
			}
			
		}
		
		return $source;
	}
	public function renameExtendedClass($fullClassName, $newExtendedFullClassName, $commentSignature=true)
	{
		$classNameParts=static::___getClassNameParts($fullClassName);
		$newExtendedClassNameParts=static::___getClassNameParts($newExtendedFullClassName);
		
		if($classNameParts['namespace']==$newExtendedClassNameParts['namespace'])
		{
			$newExtendedFullClassName=$newExtendedClassNameParts['class'];
		}
		
		for($i=$this->classes[$fullClassName][static::EXTENDS_START_TOKEN]; 
				$i<$this->classes[$fullClassName][static::EXTENDS_END_TOKEN]; 
				$i++)
		{
			$this->tokens[$i][0]=T_WHITESPACE;
			$this->tokens[$i][1]='';
		}
		$this->tokens[$i][0]=T_STRING;
		$this->tokens[$i][1]=$newExtendedFullClassName;
		
		if($commentSignature)
		{
			$this->insertComment($this->classes[$fullClassName]['signature_start_token']-1, array('Original signature:', $this->classes[$fullClassName]['signature']));
		}
		
	}
	public function renameUseClass($classFullName, $newUseFullClassName, $commentStatement=true)
	{
		
		//echo 'Rename use '.$classFullName.' to ' .$newUseFullClassName.': '.
		$baseClassName=$this->getAliasClassName($this->classes[$classFullName][static::EXTENDED_CLASS_FULL_CLASS_NAME]);
		$aliasInfo=$this->classAliases[$this->classes[$classFullName][static::EXTENDED_CLASS_FULL_CLASS_NAME]];
		
		for($i=$aliasInfo['use_start_token']; $i<$aliasInfo['use_end_token']-1; $i++)
		{
			$this->tokens[$i][0]=T_WHITESPACE;
			$this->tokens[$i][1]='';
		}
		
		$this->tokens[$i][0]=T_STRING;
		$this->tokens[$i][1]=$newUseFullClassName;
		
		if($commentStatement)
		{
			$this->insertComment($aliasInfo['use_start_token']-3, array('Original statement:', $aliasInfo['use_statement']));
			
		}
	}
	public function renameClass($fullClassName, $newFullClassName, $commentStatement=true)
	{
		$fullClassNameParts=static::___getClassNameParts($fullClassName);
		$newFullClassNameParts=static::___getClassNameParts($newFullClassName);
		//echo $fullClassName.' -> '.$newFullClassName;
		for($i=$this->classes[$fullClassName]['name_start_token']; $i<$this->classes[$fullClassName]['name_end_token']; $i++)
		{
			$this->tokens[$i][0]=T_WHITESPACE;
			$this->tokens[$i][1]='';
		}
		$this->tokens[$i][0]=T_STRING;
		$this->tokens[$i][1]=$newFullClassNameParts['class'];
		
		if($commentStatement)
		{
			$this->insertComment($this->classes[$fullClassName]['signature_start_token']-1, array('Original signature:', $this->classes[$fullClassName]['signature']));
		}
	}
	public function insertCode($token, $code)
	{
		$this->tokens[$token][0]=-1;
		$this->tokens[$token][1].=$code;
	}
	public function insertComment($token, $commentLines)
	{
		if(!is_array($commentLines))
		{
			$commentLines=array($commentLines);
		}
		if($token<0)
		{
			$token=count($this->tokens)-1+$token;
		}
		if(!isset($this->tokens[$token]))
		{
			print_r($commentLines);
			//die(''.$token);
		}
		
		if($this->tokens[$token][0]==T_WHITESPACE)
		{
			//detect identation: 
			//tabs & spaces after the last new line character
			$tabs=substr($this->tokens[$token][1], strrpos($this->tokens[$token][1], "\n")+1);
		}	
		else
		{
			$tabs='';
		}
		
		$comment='';
		
		foreach($commentLines as $commentLine)
		{
			$comment .= ($comment?PHP_EOL.$tabs:'').$commentLine;
		}
		
		$this->tokens[$token][0]=-1;
		$this->tokens[$token][1]=PHP_EOL.'/*'.PHP_EOL.$tabs.$comment.PHP_EOL.$tabs."*/".PHP_EOL.$tabs;
	}
	static public function ___getClassNameParts($className)
	{
		$classNameParts=explode('\\', trim($className));
		$class=$classNameParts[count($classNameParts)-1];
		unset($classNameParts[count($classNameParts)-1]);
		
		return array(
			'class' => $class,
			'namespace' => implode('\\', $classNameParts)
		);
	}
	public function getAliasClassName($alias)
	{
		if(!isset($this->classAliases[$alias]))
		{
			return '';
		}
		
		$classNameOrAlias=$this->classAliases[$alias]['class_full_name_or_alias'];
		
		
		if(isset($this->classAliases[$classNameOrAlias]))
		{
			return $this->getAliasClassName($classNameOrAlias);
		}
		
		return $classNameOrAlias;
	}
	public function getDeclaredClasses()
	{
		return $this->classes;
	}
	public function getClassesAliases()
	{
		return $this->classAliases;
	}
	public function getFileName()
	{
		return $this->filename;
	}
	public function getToken($i)
	{
		return $this->tokens[$i];
	}
}

