<?php
// includes/functions.php
interface PluginInterface {
    public function init();
}

class PluginManager {
    private $plugins = [];

    public function registerPlugin($plugin) {
        if ($plugin instanceof PluginInterface) {
            $this->plugins[] = $plugin;
        }
    }

    public function initPlugins() {
        foreach ($this->plugins as $plugin) {
            $plugin->init();
        }
    }
}
class HookManager {
    private static $hooks = [];

    public static function addHook($hookName, $callback) {
        if (!isset(self::$hooks[$hookName])) {
            self::$hooks[$hookName] = [];
        }
        self::$hooks[$hookName][] = $callback;
    }

    public static function executeHook($hookName, $params = null) {
        if (isset(self::$hooks[$hookName])) {
            foreach (self::$hooks[$hookName] as $callback) {
                call_user_func($callback, $params);
            }
        }
    }
}