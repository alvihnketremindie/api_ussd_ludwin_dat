<?php

class Utils {

    var $id;

    public function __construct($request) {
        $this->id = (isset($request['id']) ? $request['id'] : 0);
    }

    public function getId() {
        return $this->id;
    }

}

?>
