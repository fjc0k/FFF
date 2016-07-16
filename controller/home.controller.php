<?php namespace Controller;

class Home{

    private $config = [];

    function __construct(){
        $this->config = include('./config.php');
    }

    function index(){
        return view('home.index');
    }

}