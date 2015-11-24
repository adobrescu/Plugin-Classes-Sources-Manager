
Package content
---------------

3 PHP classes located in "lib" dir. The other PHP files were used for tests.

What the software does
----------------------

They contain functionality that allow:


Extending OOP applications with "plugin" classes
------------------------------------------------

Example:

- The application have some core libraries, one of them may look something like this:
	//Application class
	class App 
	{
		...

		protected function init()
		{
			//called from the constructor
			//app initialisation
		}
		public function sayHello()
		{
			echo 'Hello';
		}
		...
	}

- Besides libraries, the application contain scripts and other classes that use the libraries:
	
	$app=new App();
	...
	$app->sayHello();
	...

- One of the software customers, want his app to say "Hello, Mr. Smith"
- There are 2 ways to go:
1. We modify class App to say "Hello, Mr. Smith". But this force us to create and maintain the customised version of the app in a different branch.
So it's not that good.

2. We extend the class App:
	
	class MrSmithApp extends App
	{
		public function sayHello()
		{
			parent::sayHello();
			echo ', Mr. Smith';
		}
	}

But we have the same problem as (1), this time with other sources:

	...
	$app=new App();
	...
	$app->sayHello();
	...

Should be replaced with:

	...
	$app=new MrSmithApp();
	...
	$app->sayHello();
	...

- We ca do a little trick:

1. Rename App to App_BASE and create a new empty class App that extends App_BASE

	class App_BASE
	{
	}


	class App extends App_BASE
	{
	}

2. Insert Mr. Smith class in the middle and modify the inheritance chain:
	
	class App_BASE
	{
	}

	class MrSmithApp extends App_BASE
	{
	}

	class App extends MrSmithApp
	{
	}

- We still need a Mr. Smith own version of classes, but the changes are minor. The code where App is declared might look:

	class App_BASE
	{
	}

	include_once(PLUGINS_DIRECTORY.'/plugins.php'); //plugins.php contains "plugin" classes

	class App extends MrSmithApp
	{
	}

- If we had a piece of software that does all the replacements above in the original sources, we could give Mr. Smith exactly the same version of software
as the other customers get + his plugin.

- The classes from "lib" dir of this package do exactly that. Given a plugins directory and a directory where the main/core/base classes are located:

1. They detect which classes are declared within plugin sources.
2. Which classes they extend;
3. Deal with more than one "plugin" classes that extend the same base/core class;
4. For now it generates one file containing all plugin classes that inerits one from an other;
5. Modify the original base/core classes sources so it renames the base classes, adds an "include" to the file above, and creates the empty class that close the inheritance chain;


RO
================================================================

Pachetul contine o cateva clase PHP si cateva clase de test.

Clasele implementeaza o functionalitate prin care o clasa oarecare poate fi extinsa intr-un mod putin mai neobisnuit, permitand
altor clase sa o extinda pe cea de baza intr-un mod independent una de alta, ceva gen pluginuri (doar ca idee, nu ca functionare).

Clasele nu sint facute pentru a aplica la runtime cine stie ce mecanisme, ci modifica putin sursele clasei de baza si a extensiilor.
Ar trebui folosita in zona de administrare a unei aplicatii cand se doreste adaugarea/activare sau scoatere/dezactivarea unor functionalitati.

Ca la carte, pentru a adauga functionalitati noi unei clase, fara a o modifica, se foloseste derivarea:

class Base
{
}
class Extension1 extends Base
{
}
class Extension2 extends Extension1
{
}

Problemele care apar:

1. pentru ca functionalitatile implementate de Extension2 sa functioneze in cod care foloseste instante ale Base, ar trebui ca peste tot
in cod sa inlocuiasca "Base" cu "Extension2":

$obj=new Base(...) 

sa devina:

$obj=new Extension2(...)

2. cel care dezvolta Extension2 trebui sa stie de existenta lui Extension1, iar daca se doreste eliminarea functionalitatilor lui Extension1, atunci
Extension2 trebuie derivat direct din Base.

PluginsSourceManager inlatura dezavantajele de mai sus modificand sursele in care clasele sint declarate:

- Se elimina de la inceput, de la proiectarea pluginurilor, necesitatea ca Extension2 sa stie de Extension1 (2)

class Base
{
}
class Extension1 extends Base
{
}
class Extension2 extends Base
{
}

- se modifica sursele astfel incat numele clasei de baza sa apara ultimul in lantul de derivare, inlaturand astfel necesitatea de a modifica sursele
care folosesc instante ale lui Base:

class BaseOriginal //redenumeste Base in BaseOriginal
{
}
class Extension1 extends BaseOriginal
{
}
class Extension2 extends Extension1
{
}
class Base extends Extension2
{
}

Avantaje:
- nu este nevoie la runtime de un sistem de management de tip event/handler;
- control asupra intregului cod;
- orice metoda poate deveni un "hook" pentru clasele plugin;


Dezavantaje:

- Metodele suprascrise trebuie sa apeleze metoda parentului pentru ca toate pluginurile sa isi ruleze codul;
- coliziuni in denumirile metodelor (pot aparea si la un sistem event/handler)
- control asupra intregului cod

Cum functioneaza.

Exista 3 clase: PHPSource, PluginsSourceManager si PluginsManager.

PHPSource - dandu-se un fisier sursa, clasa stie sa lucreze pe codul din fisier (ce clase se declara, sa faca redenumiri etc). Implementeaza
functionalitati foarte simple, pentru chestii complicate se foloseste ReflectionClass. PHPSource nu foloseste "include" de aceea nu este derivata din ReflectionClass.
PluginsSourceManager - dandui-se o lista de fisiere (cu pluginuri si clase de baza) creeaza obiecte PHPSource si stie ce sa le ceara 
pentru a obtine o noua sursa (ce redenumiri sa faca, ce clasa pe cine extinde etc);

PluginsManager - stie structura de directoare, undes-s pluginurile, unde gaseste clasele pe care acestea le extind etc;
