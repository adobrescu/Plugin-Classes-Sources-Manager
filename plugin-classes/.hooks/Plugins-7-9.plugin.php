<?php

namespace NS1
{				
	use \Base as RenamedBase3;
	
	class Plugin7 extends RenamedBase3
	{
	}
	
}
namespace NS2
{
					
	use 
	\TEST_NS
	\Plugin6 as NS1_Plugin7
	
	
	;
	
	class Plugin8 extends NS1_Plugin7
	{
	}
}
