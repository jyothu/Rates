<?php

use Illuminate\Database\Capsule\Manager as Capsule;  
use Symfony\Component\Yaml\Parser;

$yaml = new Parser();
$env = getenv("ENVIRONMENT") ? getenv("ENVIRONMENT") : "production";
$dbConfig = $yaml->parse( file_get_contents('config/database.yml') );

$capsule = new Capsule; 
$capsule->addConnection( $dbConfig[$env] );
$capsule->bootEloquent();
$capsule->setAsGlobal();


class DB extends Capsule
{
}

?>