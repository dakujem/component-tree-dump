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
		// credits: https://www.flaticon.com/authors/good-ware
		$icon1 = '<img style="display:inline;height:20px" src="data:image/svg+xml;utf8;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pgo8IS0tIEdlbmVyYXRvcjogQWRvYmUgSWxsdXN0cmF0b3IgMTkuMC4wLCBTVkcgRXhwb3J0IFBsdWctSW4gLiBTVkcgVmVyc2lvbjogNi4wMCBCdWlsZCAwKSAgLS0+CjxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgdmVyc2lvbj0iMS4xIiBpZD0iQ2FwYV8xIiB4PSIwcHgiIHk9IjBweCIgdmlld0JveD0iMCAwIDQ1MC42ODUgNDUwLjY4NSIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgNDUwLjY4NSA0NTAuNjg1OyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSIgd2lkdGg9IjUxMnB4IiBoZWlnaHQ9IjUxMnB4Ij4KPGc+Cgk8Zz4KCQk8cGF0aCBkPSJNMTU0LjkxMiwyMDkuNWMxLjE3LDAuOTM1LDIuMjMyLDEuOTk4LDMuMTY4LDMuMTY4YzIuNzY1LDMuNDQ2LDcuOCwzLjk5OSwxMS4yNDYsMS4yMzQgICAgYzEuOTAyLTEuNTI2LDMuMDA0LTMuODM2LDIuOTk0LTYuMjc0di02MC40YzAtNC40MTgtMy41ODEtOC04LThjLTAuMDI3LDAtMC4wNTQsMC0wLjA4LDBIMTE5LjZjMS44NTItNC40MDcsMi44MDQtOS4xNCwyLjgtMTMuOTIgICAgYy0wLjk0MS0yMC4wMzctMTcuOTQ3LTM1LjUxNy0zNy45ODQtMzQuNTc2Yy0xOC43MTksMC44NzktMzMuNjk3LDE1Ljg1Ni0zNC41NzYsMzQuNTc2Yy0wLjAwNCw0Ljc4LDAuOTQ4LDkuNTEzLDIuOCwxMy45Mkg4ICAgIGMtNC40MTgsMC04LDMuNTgyLTgsOHY2MC40OGMtMC4wMTksNC40MTgsMy41NDgsOC4wMTUsNy45NjYsOC4wMzRjMi40MzgsMC4wMSw0Ljc0OC0xLjA5Miw2LjI3NC0yLjk5NCAgICBjNi45ODEtOC43ODcsMTkuNzYzLTEwLjI1MSwyOC41NS0zLjI3czEwLjI1MSwxOS43NjMsMy4yNywyOC41NXMtMTkuNzYzLDEwLjI1MS0yOC41NSwzLjI3Yy0xLjIxMS0wLjk2Mi0yLjMwOC0yLjA1OS0zLjI3LTMuMjcgICAgYy0yLjc2NS0zLjQ0Ni03LjgtMy45OTktMTEuMjQ2LTEuMjM0QzEuMDkyLDIzOC4zMi0wLjAxLDI0MC42MywwLDI0My4wNjh2NjAuNDhjMCw0LjQxOCwzLjU4Miw4LDgsOGg0My45MiAgICBjLTYuNjkxLDE4LjkxLDMuMjE1LDM5LjY2NCwyMi4xMjUsNDYuMzU1YzE4LjkxLDYuNjkxLDM5LjY2NC0zLjIxNSw0Ni4zNTUtMjIuMTI1YzIuNzczLTcuODM4LDIuNzczLTE2LjM5MSwwLTI0LjIyOWg0My45MiAgICBjNC40MTgsMCw4LTMuNTgyLDgtOHYtNjAuNTZjMC4wMTktNC40MTgtMy41NDgtOC4wMTUtNy45NjYtOC4wMzRjLTIuNDM4LTAuMDEtNC43NDgsMS4wOTItNi4yNzQsMi45OTQgICAgYy02Ljk4MSw4LjczLTE5LjcxNywxMC4xNDktMjguNDQ4LDMuMTY4Yy04LjczLTYuOTgxLTEwLjE0OS0xOS43MTctMy4xNjgtMjguNDQ4QzEzMy40NDUsMjAzLjkzOCwxNDYuMTgyLDIwMi41MiwxNTQuOTEyLDIwOS41eiAgICAgTTEzOC45MTIsMjYxLjYyOGMxLjEzNSwwLjA1MywyLjI3MywwLjA1MywzLjQwOCwwYzQuNzgxLDAuMDExLDkuNTE1LTAuOTQxLDEzLjkyLTIuOHYzNi42NEgxMDYgICAgYy00LjQxOCwwLjAxOC03Ljk4NSwzLjYxNS03Ljk2Niw4LjAzM2MwLjAwOSwyLjEyMiwwLjg2LDQuMTUzLDIuMzY2LDUuNjQ3YzcuOTg0LDcuODg3LDguMDYzLDIwLjc1MiwwLjE3NiwyOC43MzYgICAgYy03Ljg4Nyw3Ljk4NC0yMC43NTIsOC4wNjMtMjguNzM2LDAuMTc2Yy03Ljk4NC03Ljg4Ny04LjA2My0yMC43NTItMC4xNzYtMjguNzM2YzAuMDU4LTAuMDU5LDAuMTE3LTAuMTE4LDAuMTc2LTAuMTc2ICAgIGMzLjEzNy0zLjExMSwzLjE1OC04LjE3NywwLjA0Ny0xMS4zMTRjLTEuNDk0LTEuNTA2LTMuNTI1LTIuMzU3LTUuNjQ2LTIuMzY2SDE2di0zNi42NGM0LjQwNiwxLjg1Nyw5LjEzOSwyLjgwOSwxMy45MiwyLjggICAgYzIwLjAzNy0wLjk0MSwzNS41MTctMTcuOTQ3LDM0LjU3Ni0zNy45ODRjLTAuODc5LTE4LjcxOS0xNS44NTYtMzMuNjk3LTM0LjU3Ni0zNC41NzZjLTQuNzgxLTAuMDA5LTkuNTE0LDAuOTQ0LTEzLjkyLDIuOHYtMzYuNjQgICAgaDUyLjQ4YzQuNDE3LTAuMTEyLDcuOTA2LTMuNzg0LDcuNzk0LTguMmMtMC4wNi0yLjM1Ni0xLjE1NS00LjU2Ni0yLjk5NC02LjA0Yy04Ljc4Ny02Ljk4MS0xMC4yNTEtMTkuNzYzLTMuMjctMjguNTUgICAgczE5Ljc2My0xMC4yNTEsMjguNTUtMy4yN2M4Ljc4Nyw2Ljk4MSwxMC4yNTEsMTkuNzYzLDMuMjcsMjguNTVjLTAuOTYyLDEuMjExLTIuMDU5LDIuMzA4LTMuMjcsMy4yNyAgICBjLTMuNDQ2LDIuNzY1LTMuOTk5LDcuOC0xLjIzNCwxMS4yNDZjMS41MjYsMS45MDIsMy44MzYsMy4wMDQsNi4yNzQsMi45OTRoNTIuNDhsMC4xNiwzNi42NGMtNC40MDUtMS44NTktOS4xMzktMi44MTEtMTMuOTItMi44ICAgIGMtMjAuMDM3LTAuOTQxLTM3LjA0MywxNC41MzktMzcuOTg0LDM0LjU3NkMxMDMuMzk1LDI0My42ODEsMTE4Ljg3NSwyNjAuNjg3LDEzOC45MTIsMjYxLjYyOHoiIGZpbGw9IiMwMDAwMDAiLz4KCTwvZz4KPC9nPgo8Zz4KCTxnPgoJCTxnPgoJCQk8cGF0aCBkPSJNNDI2LjQ2OSwxOTAuMTg4Yy0zLjkwMi0xLjM4LTguMDEtMi4wODQtMTIuMTQ5LTIuMDhjLTQuMTE2LTAuMDAxLTguMjAxLDAuNzAzLTEyLjA4LDIuMDh2LTQzLjkyYzAtNC40MTgtMy41ODItOC04LTggICAgIGgtNjAuNDhjLTQuNDE4LDAuMDM1LTcuOTcxLDMuNjQ2LTcuOTM2LDguMDY0YzAuMDE5LDIuMzYsMS4wNzksNC41OTEsMi44OTYsNi4wOTZjOC44MDQsNi45NTksMTAuMzAxLDE5LjczNywzLjM0MiwyOC41NDIgICAgIGMtNi45NTksOC44MDQtMTkuNzM3LDEwLjMwMS0yOC41NDIsMy4zNDJzLTEwLjMwMS0xOS43MzctMy4zNDItMjguNTQyYzAuOTgtMS4yNCwyLjEwMi0yLjM2MiwzLjM0Mi0zLjM0MiAgICAgYzMuNDQ2LTIuNzY1LDMuOTk5LTcuOCwxLjIzNC0xMS4yNDZjLTEuNTI2LTEuOTAyLTMuODM2LTMuMDA0LTYuMjc0LTIuOTk0SDIzOGMtNC40MTgsMC04LDMuNTgyLTgsOHY0NC42NCAgICAgYy00LjQwNi0xLjg1Ni05LjEzOS0yLjgwOS0xMy45Mi0yLjhjLTIwLjAzNywwLjk0MS0zNS41MTcsMTcuOTQ3LTM0LjU3NiwzNy45ODRjMC44NzksMTguNzE5LDE1Ljg1NiwzMy42OTYsMzQuNTc2LDM0LjU3NiAgICAgYzQuNzgxLDAuMDA2LDkuNTE0LTAuOTQ2LDEzLjkyLTIuOHY0NC42NGMwLDQuNDE4LDMuNTgyLDgsOCw4aDYwLjY0YzQuNDE4LTAuMDAxLDcuOTk5LTMuNTg0LDcuOTk4LTguMDAyICAgICBjLTAuMDAxLTIuODIzLTEuNDktNS40MzctMy45MTgtNi44NzhjLTguNDQ0LTcuMzkyLTkuMjk3LTIwLjIzLTEuOTA1LTI4LjY3NGM3LjM5Mi04LjQ0NCwyMC4yMy05LjI5NywyOC42NzQtMS45MDUgICAgIGM4LjQ0NCw3LjM5Miw5LjI5NywyMC4yMjksMS45MDUsMjguNjc0Yy0wLjgxMiwwLjkyOC0xLjcwNywxLjc4LTIuNjc0LDIuNTQ1Yy0zLjQ0NiwyLjc2NS0zLjk5OSw3LjgtMS4yMzQsMTEuMjQ2ICAgICBjMS41MjYsMS45MDIsMy44MzYsMy4wMDQsNi4yNzQsMi45OTRoNjAuNDhjNC40MTgsMCw4LTMuNTgyLDgtOHYtNDMuNzZjMTguOTEsNi42OTEsMzkuNjY0LTMuMjE1LDQ2LjM1NS0yMi4xMjUgICAgIFM0NDUuMzgsMTk2Ljg3OSw0MjYuNDY5LDE5MC4xODh6IE00MjguNDQ3LDIzOC45ODljLTMuNzcxLDMuNzY3LTguODc3LDUuODk0LTE0LjIwNyw1LjkybDAuMDgtMC4yNCAgICAgYy01LjQxMSwwLjAxMy0xMC42LTIuMTQ5LTE0LjQtNmMtMy4xMTEtMy4xMzctOC4xNzctMy4xNTgtMTEuMzE0LTAuMDQ3Yy0xLjUwNiwxLjQ5NC0yLjM1NywzLjUyNS0yLjM2Niw1LjY0NnY1MC4yNGgtMzYuNzIgICAgIGMxLjkwMS00LjQ1MSwyLjg4LTkuMjQsMi44OC0xNC4wOGMtMC45NDEtMjAuMDM3LTE3Ljk0Ny0zNS41MTctMzcuOTg0LTM0LjU3NmMtMTguNzE5LDAuODc5LTMzLjY5NywxNS44NTYtMzQuNTc2LDM0LjU3NiAgICAgYy0wLjAwNCw0Ljc4LDAuOTQ4LDkuNTEzLDIuOCwxMy45MkgyNDZ2LTUyLjI0YzAuMDE5LTQuNDE4LTMuNTQ4LTguMDE1LTcuOTY2LTguMDM0Yy0yLjQzOC0wLjAxLTQuNzQ4LDEuMDkyLTYuMjc0LDIuOTk0ICAgICBjLTYuOTgxLDguNzg3LTE5Ljc2MywxMC4yNTEtMjguNTUsMy4yN2MtOC43ODctNi45ODEtMTAuMjUxLTE5Ljc2My0zLjI3LTI4LjU1czE5Ljc2My0xMC4yNTEsMjguNTUtMy4yNyAgICAgYzEuMjExLDAuOTYyLDIuMzA4LDIuMDU5LDMuMjcsMy4yN2MyLjc2NSwzLjQ0Niw3LjgsMy45OTksMTEuMjQ2LDEuMjM0YzEuOTAyLTEuNTI2LDMuMDA0LTMuODM2LDIuOTk0LTYuMjc0di01Mi40OGgzNi42NCAgICAgYy0xLjkxMSw0LjQ3Ni0yLjg5MSw5LjI5My0yLjg4LDE0LjE2Yy0wLjk0MSwyMC4wMzcsMTQuNTM5LDM3LjA0MywzNC41NzYsMzcuOTg0czM3LjA0My0xNC41MzksMzcuOTg0LTM0LjU3NiAgICAgYzAuMDUzLTEuMTM1LDAuMDUzLTIuMjczLDAtMy40MDhjMC4wMDQtNC43OC0wLjk0OC05LjUxMy0yLjgtMTMuOTJoMzYuNjR2NTAuMjRjMC4wMTksNC40MTgsMy42MTUsNy45ODUsOC4wMzQsNy45NjYgICAgIGMyLjEyMS0wLjAwOSw0LjE1My0wLjg2LDUuNjQ2LTIuMzY2YzcuOTA5LTcuOSwyMC43MjQtNy44OTIsMjguNjI0LDAuMDE3QzQzNi4zNjMsMjE4LjI3NCw0MzYuMzU2LDIzMS4wODksNDI4LjQ0NywyMzguOTg5eiIgZmlsbD0iIzAwMDAwMCIvPgoJCQk8cG9seWdvbiBwb2ludHM9IjQxNC4zMiwxODguMTA4IDQxNC4zMiwxODguMTA4IDQxNC4zMiwxODguMTA4ICAgICIgZmlsbD0iIzAwMDAwMCIvPgoJCTwvZz4KCTwvZz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8L3N2Zz4K" />';
		// credits: https://www.flaticon.com/authors/kiranshastry
//		$icon2 = '<img style="display:inline;height:17px" src="data:image/svg+xml;utf8;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pgo8IS0tIEdlbmVyYXRvcjogQWRvYmUgSWxsdXN0cmF0b3IgMTkuMC4wLCBTVkcgRXhwb3J0IFBsdWctSW4gLiBTVkcgVmVyc2lvbjogNi4wMCBCdWlsZCAwKSAgLS0+CjxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgdmVyc2lvbj0iMS4xIiBpZD0iQ2FwYV8xIiB4PSIwcHgiIHk9IjBweCIgdmlld0JveD0iMCAwIDUxMiA1MTIiIHN0eWxlPSJlbmFibGUtYmFja2dyb3VuZDpuZXcgMCAwIDUxMiA1MTI7IiB4bWw6c3BhY2U9InByZXNlcnZlIiB3aWR0aD0iMjRweCIgaGVpZ2h0PSIyNHB4Ij4KPGc+Cgk8Zz4KCQk8cGF0aCBkPSJNNDMzLjkwOCwyNTIuMTVINzguNjQyYy05LjExMywwLTE2LjQ5OCw3LjM4Ni0xNi40OTgsMTYuNDk4djg0LjE0MmgzMi45OTd2LTY3LjY0M2gzMjIuMjY5djY3LjY0M2gzMi45OTd2LTg0LjE0MiAgICBDNDUwLjQwNiwyNTkuNTM2LDQ0My4wMiwyNTIuMTUsNDMzLjkwOCwyNTIuMTV6IiBmaWxsPSIjMDAwMDAwIi8+Cgk8L2c+CjwvZz4KPGc+Cgk8Zz4KCQk8cGF0aCBkPSJNMzI5Ljk2OCw2LjMyNEgxODMuMTMyYy05LjExMywwLTE2LjQ5OCw3LjM4Ni0xNi40OTgsMTYuNDk4djE2My44ODRjMCw5LjExMyw3LjM4NiwxNi40OTgsMTYuNDk4LDE2LjQ5OGgxNDYuODM2ICAgIGM5LjExMywwLDE2LjQ5OC03LjM5MSwxNi40OTgtMTYuNDk4VjIyLjgyM0MzNDYuNDY2LDEzLjcxLDMzOS4wOCw2LjMyNCwzMjkuOTY4LDYuMzI0eiBNMzEzLjQ2OSwxNzAuMjA4SDE5OS42M1YzOS4zMjFoMTEzLjgzOSAgICBWMTcwLjIwOHoiIGZpbGw9IiMwMDAwMDAiLz4KCTwvZz4KPC9nPgo8Zz4KCTxnPgoJCTxwYXRoIGQ9Ik00OTUuNTAyLDMzNi4yOTJIMzc3LjI2M2MtOS4xMTMsMC0xNi40OTgsNy4zODYtMTYuNDk4LDE2LjQ5OHYxMzYuMzg3YzAsOS4xMTMsNy4zODYsMTYuNDk4LDE2LjQ5OCwxNi40OThoMTE4LjIzOCAgICBjOS4xMTMsMCwxNi40OTgtNy4zODYsMTYuNDk4LTE2LjQ5OFYzNTIuNzkxQzUxMiwzNDMuNjc4LDUwNC42MTQsMzM2LjI5Miw0OTUuNTAyLDMzNi4yOTJ6IE00NzkuMDAzLDQ3Mi42NzloLTg1LjI0MnYtMTAzLjM5ICAgIGg4NS4yNDJWNDcyLjY3OXoiIGZpbGw9IiMwMDAwMDAiLz4KCTwvZz4KPC9nPgo8Zz4KCTxnPgoJCTxwYXRoIGQ9Ik0xMzQuNzM3LDMzNi4yOTJIMTYuNDk4QzcuMzg2LDMzNi4yOTIsMCwzNDMuNjc4LDAsMzUyLjc5MXYxMzYuMzg3YzAsOS4xMTMsNy4zODYsMTYuNDk4LDE2LjQ5OCwxNi40OThoMTE4LjIzOCAgICBjOS4xMTMsMCwxNi40OTgtNy4zODYsMTYuNDk4LTE2LjQ5OFYzNTIuNzkxQzE1MS4yMzUsMzQzLjY3OCwxNDMuODQ5LDMzNi4yOTIsMTM0LjczNywzMzYuMjkyeiBNMTE4LjIzOCw0NzIuNjc5SDMyLjk5N3YtMTAzLjM5ICAgIGg4NS4yNDJWNDcyLjY3OXoiIGZpbGw9IiMwMDAwMDAiLz4KCTwvZz4KPC9nPgo8Zz4KCTxnPgoJCTxwYXRoIGQ9Ik0zMTUuMTE5LDMzNi4yOTJIMTk2Ljg4MWMtOS4xMTMsMC0xNi40OTgsNy4zODYtMTYuNDk4LDE2LjQ5OHYxMzYuMzg3YzAsOS4xMTMsNy4zODYsMTYuNDk4LDE2LjQ5OCwxNi40OThoMTE4LjIzOCAgICBjOS4xMTMsMCwxNi40OTgtNy4zODYsMTYuNDk4LTE2LjQ5OFYzNTIuNzkxQzMzMS42MTgsMzQzLjY3OCwzMjQuMjMyLDMzNi4yOTIsMzE1LjExOSwzMzYuMjkyeiBNMjk4LjYyMSw0NzIuNjc5aC04NS4yNDJ2LTEwMy4zOSAgICBoODUuMjQyVjQ3Mi42Nzl6IiBmaWxsPSIjMDAwMDAwIi8+Cgk8L2c+CjwvZz4KPGc+Cgk8Zz4KCQk8cmVjdCB4PSIyMzkuNzc3IiB5PSIxODYuNzA3IiB3aWR0aD0iMzIuOTk3IiBoZWlnaHQ9IjE2Ni4wODQiIGZpbGw9IiMwMDAwMDAiLz4KCTwvZz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8L3N2Zz4K" />';
		return '<span title="Component Model Tree"><span class="tracy-label">' . $icon1 . '</span></span>';
	}


	public function getPanel()
	{
		return '<pre><h1>' . $this->app->getPresenter()->getName() . ' - component tree</h1>' . $this->out() . '</pre>';
	}

}
