<?php

/*
 * PHPSource 
 * 
 * stie doar cateva chestii de baza legat de clase
 * 
 */
class PHPSource
{
	static $___scriptStartEmptyTokens=array( //tokens at the top of the source, befora any code
										T_OPEN_TAG=>1,
										T_COMMENT=>1,
										T_WHITESPACE=>1
									);
	
	protected $tokens;
	protected $filename;
	protected $classes, $currentNS='';
	public function __construct($filename)
	{
		$this->filename=$filename;include($this->filename);
		$this->tokens=token_get_all(file_get_contents($this->filename));
		foreach($this->tokens as $i=>$token)
		{
			if(is_array($token))
			{
				$this->tokens[$i][3]=token_name($token[0]);
			}
			else
			{
				$this->tokens[$i]=array( 0=> 0, 1=>$token, 2 => '', 3 => '');
			}
		}
		$this->parse();
	}
	protected function parse()
	{
		echo '<pre>';//.print_r($this->tokens, true);
		for($i=0; $i<count($this->tokens); $i++)
		{
			if(!isset(static::$___scriptStartEmptyTokens[$this->tokens[$i][0]]))
			{
				break;
			}
		}
		if($this->tokens[$i][0]==T_NAMESPACE)
		{
			$nsNameEndToken=$this->findNextTokens($i+1, array(';', '{'));
			$this->currentNS=$this->rebuildSource($i+1, $nsNameEndToken-1, false);
			$i=$nsNameEndToken+1;
		}
		for($i; $i<count($this->tokens); $i++)
		{
			switch($this->tokens[$i][0])
			{
				case T_CLASS:
					$classNameEndToken=$this->findNextTokens($i+1, array(T_EXTENDS, T_IMPLEMENTS, '{'));
					$this->classes[]=array(
							'name'=>$this->rebuildSource($i+1, $classNameEndToken-1, false),
							'ns' => $this->currentNS,
							'extends' => ''
							);
					break;
				case T_EXTENDS:
					$baseClassNameEndToken=$this->findNextTokens($i+1, array(T_IMPLEMENTS, '{'));
					end($this->classes);
					$this->classes[key($this->classes)]['extends']=$this->rebuildSource($i+1, $baseClassNameEndToken-1, false);
					break;
			}
		}
		print_r($this->classes);
	}
	/*
	 * findExpression
	 * 
	 * returns the tokens between $foreTokens and $backTokens
	 */
	protected function findNextTokens($i, $findTokens)
	{
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
		}
	}
	public function rebuildSource($start, $end, $allowSpaces)
	{
		$source='';
		for($i=$start; $i<=$end; $i++)
		{
			if(!$allowSpaces && $this->tokens[$i][0]==T_WHITESPACE)
			{
				continue;
			}
			$source.=$this->tokens[$i][1];
		}
		
		return $source;
	}
}

