asdasd

<?php

/*
	Original signature:
	abstract 
	class Plugin1 
	extends Base
	*/
	abstract 
	class Plugin1 
	extends Base_Base{
	}

//class Plugin3 extends Plugin2

/*
Original signature:
class Plugin3 
		extends 
		Plugin2
*/
class Plugin3 
		extends 
		Plugin2
	{
	}


/*
Original signature:
class Plugin2 extends Base
*/
class Plugin2 extends Plugin1
{
}

abstract
final 
	class	Util


implements I


{
}


?>
<?php

namespace TEST_NS;


/*
Original signature:
class Plugin4 extends Plugin6
*/
class Plugin4 extends Plugin6
{
	/*some other comments*/
}


/*
Original signature:
class Plugin5 extends Base2
*/
class Plugin5 extends Base2_Base
{
}

/*
 * priority : high
 * package : Some extension
 * setting: value
 * description : one line description
 */

/*
Original signature:
class Plugin6 extends \Base
*/
class Plugin6 extends \Plugin3
{
}


?>
<?php

namespace NS1
{
/*
	Original statement:
	use \Base as RenamedBase3;
	*/
	use \NS2\Plugin8 as RenamedBase3;
	
	class Plugin7 extends RenamedBase3
	{
	}
	
}
namespace NS2
{
/*
	Original statement:
	use 
	\TEST_NS
	\Plugin6 as NS1_Plugin7
	
	
	;
	*/
	use 
	\TEST_NS\Plugin4 as NS1_Plugin7
	
	
	;
	
	class Plugin8 extends NS1_Plugin7
	{
	}
}

?>