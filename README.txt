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
