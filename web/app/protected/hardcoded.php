<?php
/* 
    Define all hardcoded values (assets directory, etc etc.)
    Put values that don't belong scattered along across random pages into here that WON'T change
*/

$init = (object) [
    "assets_root" => "/assets",
    "dynamic_root" => "/sites",
    "ffmpeg_binary" => "ffmpeg", 
    "ffprobe_binary" => "ffprobe", 
    "ffmpeg_threads" => 2, 

    "db_properties" => (object) [
        "db_user" => "root",
        "db_password" => "root",
        "db_host" => "mysql",
        "db_database" => "pebbles",
        "db_connected" => false,
    ], 
];

?>