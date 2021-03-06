Tyke
===

[![Tyke (Tom and Jerry) on Wikipedia](http://upload.wikimedia.org/wikipedia/en/thumb/c/c7/Tyke_Tom_and_Jerry.JPG/200px-Tyke_Tom_and_Jerry.JPG)](http://en.wikipedia.org/wiki/Tyke_%28Tom_and_Jerry%29)

Is a nano web framework for PHP, like web.py for Python, Sinatra or Camping for Ruby.

It was forked by Harald Kirschner of Nice Dog (originally by Tiago Bastos).

Minimal
---

	require 'Tyke.php';
	
	Tyke::register('/', array('Test', 'index'));
	
	class Test extends TykeC
	{
			public function index() {
					echo 'Hello';
			}
	}
	
	Tyke::run();

But Why?
---

If you want to build a small site or software and do not need a BIG framework with a lot of features, you can use Nice Dog. Nice Dog do not have any ORM or big features like Cake, Rails or Django have, but it is smaller, easier and faster!

Deploy Two Files
---

Just get the `.htaccess` and `Tyke.php` and drop the files into a folder in your Apache server!

Nice Urls
---

	Tyke::register('/', array('Test', 'index'));

Walk-through
---

1. Get the files
2. Put in your htdocs directory, or a subdirectory.
3. Create a `index.php`
4. Just
	
		require('Tyke.php')

5. Make urls like this, define the url, class, method and HTTP method:
	
		Tyke::register('tag/(?P<tag>[-\w]+)', array('Test', 'tag'), array('http_method' => 'GET'));

6. Write a class that extends `TykeC`
	
		class Test extends TykeC {}

7. Add a method `tag`
	
		public function tag($tag){ echo $tag; }

8. And dispatch Tyke magic
	
		Tyke::run()

9. Open in your browser: `http://localhost/tag/dog`

Templates
---

PHP works nice for templating, so lets use it!

	public function tag($tag){
			$this->tag = $tag; 
			$this->render('views/index.php');
	}

### Base layout `views/layout.php`

	<h1>My first example</h1>
	<?=$content?>

To change base layout add to your method: `$this->layout = 'myLayout.php';`

### Template `views/index.php`

	<span><?= htmlspecialchars($tag) ?></span>

Samples
---

To run the samples, copy the `.htaccess` in your favourite sample folder and change the `RewriteBase` according to your absolute web path (e.g. `/tyke/sample/simple/` if you load it via `http://localhost/tyke/sample/simple/`). 

License
---

See [license](master/license) file.
