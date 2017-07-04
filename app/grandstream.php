<?php

function hello() {
    /*
    $records = \App\recordingfile::where('is_processed', '1')->get();
    foreach($records as $record) {
        $filename = $record->filename;
        $parts= explode('-', $filename);
        $unix_time = $parts[1];
        $caller1 = $parts[2];
        $caller2 = $parts[3];

        if (strlen($caller1) == 4 && strlen($caller2) == 8) {
            continue;
        }

        $date = date('Y-m-d', $unix_time);
        if ($date == '2017-06-26' || $date == '2017-06-27' || $date == '2017-06-28') {
            echo $filename."<br>";
        }
    }
    */
    return "hello";
}
