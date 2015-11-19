<?php

/*
 * PHPSource 
 * 
 * stie doar cateva chestii de baza legat de clase
 * 
 */
class PHPSource
{
	protected $tokens;
	protected $filename;
	public function __construct($filename)
	{
		$this->filename=$filename;
		$this->tokens=token_get_all(file_get_contents($this->filename));
		foreach($this->tokens as &$token)
		{
			$token[3]=gettype($token[0])=='string'?'':token_name($token[0]);
		}
	}
}

