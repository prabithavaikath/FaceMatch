<?php


class FaceMatchPlugin implements PluginInterface {
    public function init() {
        HookManager::addHook('face_match', [$this, 'doSomething']);
    }

    public function doSomething() {
       // echo "Sample Plugin Hook Executed!<br>";
        include "ocvjs/index.php";
    }
}

