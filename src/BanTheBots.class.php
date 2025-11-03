<?php

class BanTheBots
{

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function checkSession()
    {
        if (!isset($_SESSION['visited'])) {
            $_SESSION['visited'] = true;
            return false;
        }
        return true;
    }
}

