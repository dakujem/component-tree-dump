
# Component model tree dump for Nette

A Tracy panel and general dumper for current component tree in Nette.

Good for visualising components that get loaded during the page rendering.


## Usage

In config:
```
decorator:
	Nette\Application\Application:
		setup:
			- Dakujem\Nette\ComponentTreeDumper::registerPanel()
```

or in `index.php`:
```php
$container = require __DIR__ . '/../app/bootstrap.php';

$app = $container->getService( 'application' );
Dakujem\Nette\ComponentTreeDumper::registerPanel( $app );
$app->run()
;
```

## Installation

`$` `composer require dakujem/component-tree-dump`
