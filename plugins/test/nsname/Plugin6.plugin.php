<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


namespace N1
{
	class Plugin1 extends \Base
	{
	}
	
	class Plugin2
	{
	}
}

namespace N2
{
	use N1\Plugin1 as N1_Plugin1;
	
	class Plugin1
	{
	}
	class Plugin2 extends N1_Plugin1
	{
	}
	
	use N1\P3 as P3;
}
