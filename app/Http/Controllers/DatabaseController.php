<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class DatabaseController extends Controller
{
    public function test() {
    try{
        $dbconnect = DB::connection()->getPDO();
        $dbname = DB::connection()->getDatabaseName();
        echo "Connected. Database name is :".$dbname;
    }
    catch(Exception $e){
        echo "Error". $e->getMessage();;
    }
}
}

