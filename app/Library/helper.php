<?php


use Carbon\Carbon;


function log_to_file($filename, $content) {
    $date = Carbon::now('Asia/Bangkok')->format('Y-m-d H:i:s');
    file_put_contents($filename, '['.$date.'] '.$content."\r\n", FILE_APPEND);
}


function create_file($name = "") {
    $now = Carbon::now('Asia/Bangkok');

    $directory = base_path()."/storage/logs/cron/".$now->format('Y-m-d');
    if (!is_dir($directory)) mkdir($directory);

    $log_file = $directory."/".$now->format('Y-m-d H-i-s')." $name.txt";
    $file = fopen($log_file, "w");
    chmod($log_file, 0666);
    return $log_file;
}


function get_post_pipedrive_log_filename() {
    return "Posting Recordfile Pipedrive";
}
