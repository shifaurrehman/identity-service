<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    function index(){
        return "This is user controller.";
    }
    function addUser(){
        return "Your user added.";
    }
    function getUsers(){
        return ["arashad","ali","moshin","Arbaz","Irfan"];
    }
    function getUserDetails($name){
        return "These are the details of $name";
    }
}
