<?php

namespace Models;

class Admin extends \DB
{
    private static $instance = NULL;

    public static function getInstance() {
            if (self::$instance == NULL) {
                    self::$instance = new Admin();
            }
            return self::$instance;
    }
}
