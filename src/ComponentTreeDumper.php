<?php


namespace Dakujem\Nette;

use Nette\Application\Application,
	Nette\ComponentModel\Component,
	Nette\ComponentModel\IContainer,
	Tracy\Debugger,
	Tracy\IBarPanel;


/**
 * ComponentTreeDumper
 *
 *
  Client:Event     ~ App\Presenters\EventPresenter
  ├─ mainMenu      ~ App\Components\FrontMenuControl
  ├─ clientMenu    ~ App\Components\ClientMenuControl
  ├─ management    ~ Nette\Application\UI\Multiplier
  │  └─ 1          ~ App\Components\EventManagementControl
  └─ detail        ~ App\Components\EventDetailControl
 *
 *
 * @author Andrej Rypák (dakujem) <xrypak@gmail.com>
 */
class ComponentTreeDumper implements IBarPanel
{

	/**
	 * @var array of blacklisted class names
	 */
	private $blacklist;

	/**
	 * @var Application
	 */
	private $app;


	public static function registerPanel(Application $app, array $blacklist = [])
	{
		Debugger::getBar()->addPanel(new static($app, $blacklist), 'component-tree');
	}


	public static function registerShutdownHandler(Application $app, array $blacklist = [])
	{
		$instance = new static($app, $blacklist);
		$app->onShutdown['component-tree-dump'] = function() use($instance) {
			Debugger::barDump($instance->out(), 'Component tree', ['depth' => 0, 'truncate' => 0]);
		};
		return $instance;
	}


	public function __construct(Application $app, array $blacklist = [])
	{
		$this->blacklist = $blacklist;
		$this->app = $app;
	}


	public function out(): string
	{
		$presenter = $this->app->getPresenter();
		$str = $this->formatRow($presenter);
		$tree = $this->formatTree($presenter);
		return "\n$str\n$tree";
	}


	//------------------------------------------------------------------------------
	//--------------------- Internal Methods ---------------------------------------


	protected function formatTree(Component $parent, array $siblings = [])
	{
		$children = $parent instanceof IContainer ? $parent->getComponents() : [];
		$siblings[count($siblings)] = count($children);
		end($siblings);
		$s = &$siblings[key($siblings)];
		$res = '';
		foreach ($children as $child) {
			$s -= 1;
			if (!$this->isBlacklisted($child)) {
				$row = $this->formatRow($child, $siblings);
				$res .= "$row\n";
				$res .= $this->formatTree($child, $siblings);
			}
		}
		return $res;
	}


	protected function formatRow(Component $c, array $siblings = [])
	{
		$str = '';
		if (count($siblings) > 0) {
			$last = array_pop($siblings);
			foreach ($siblings as $cnt) {
				$str .= $cnt > 0 ? '│  ' : '   ';
			}
			$str .= $last > 0 ? '├─ ' : '└─ ';
		}
		return $str . $c->getName() . '    ~ ' . get_class($c);
	}


	protected function isBlacklisted(Component $component): bool
	{
		foreach ($this->blacklist as $className) {
			if ($component instanceof $className) {
				return TRUE;
			}
		}
		return FALSE;
	}


	//------------------------------------------------------------------------------
	//--------------------- Tracy Panel --------------------------------------------


	public function getTab(): string
	{
		$src = include __DIR__ . '/../icons/icon-puzzle.php';
		$icon = '<img style="display:inline;height:20px" src="' . $src . '" />';
		return '<span title="Component Model Tree"><span class="tracy-label">' . $icon . '</span></span>';
	}


	public function getPanel()
	{
		return
				'<h1>' . $this->app->getPresenter()->getName() . ' - component tree</h1>' .
				'<pre class="tracy-inner">' . $this->out() . '</pre>'
		;
	}

}
