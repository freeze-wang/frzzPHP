<?php

class StartController
{
 	public function __construct()
	{

	}
    public function getIndex()
	{
        return "Start !welcome to frzz11223!".json_encode($_GET).json_encode($_POST)."\n";
	}
    public function getStart()
	{
        return "getStart !welcome to frzz11223!".json_encode($_GET).json_encode($_POST)."\n";
	}    
}