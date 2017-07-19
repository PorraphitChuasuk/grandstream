<?php

namespace App\Library;

class Pipedrive {

    const THAILAND = 'thailand';
    const SINGAPORE = 'singapore';

    private $tokens;
    private $grandstream;

    public function __construct() {
        $this->tokens = [
            self::THAILAND => '874ed683dc8d5f5d5767a506417ff5cc027fbf58',
            self::SINGAPORE => '9d77171841ed5358557bbdcd1f28f84a6c1134fa'
        ];
        $this->grandstream = new \App\Library\Grandstream;
    }


    public function post_recording_file($country) {
        $token = $this->_get_token($country);
        if ($token == null) return;

        $country_code_id = \App\country_code::get_country_id($country);

        $log_file = create_file(get_post_pipedrive_log_filename()." ".ucwords($country));

        $current_date = date_create();
        date_sub($current_date, date_interval_create_from_date_string('14 days'));
        $date = $current_date->format('Y-m-d');

        $records = \App\recordingfile::where([
            ['is_processed', '=', '0'],
            ['created_at', '>', $date]
        ])->get();

        foreach($records as $record) {
            $filename = $record->filename;
            $file_info = $this->grandstream->extract_info($filename);

            if ($file_info["type"] == \App\Library\Grandstream::INTERNAL) {
                log_to_file($log_file, "Didn't add internal call $filename");
                $record->is_processed = 1;
            } else {
                $customer_nr = $file_info[$file_info["customer"]];
                $data = \App\deal::where([
                    ['phone_nr', '=', $customer_nr],
                    ['country_code_id', '=', $country_code_id]
                ])->first();
                if ($data != null) {
                    $post_result = $this->_post_content($data->deal_id, $file_info,
                                                        $token, $country_code_id,
                                                        $filename, $log_file, $country);
                    if ($post_result) $record->is_processed = 1;
                } else {
                    log_to_file($log_file,
                    "[$country] Phone number is not on pipedrive for $customer_nr with filename $filename");
                }
            }
            $record->save();
        }
    }


    private function _post_content($deal_id, $file_info, $token, $country_code_id, $filename, $log_file, $country) {
        $customer_nr = $file_info[$file_info["customer"]];
        $operator_nr =  $file_info[$file_info["operator"]];
        $operator_detail = \App\extension::where([
            ["extension_nr", "=", $operator_nr],
            ["country_code_id", "=", $country_code_id],
            ["is_enable", "=", "1"]
            ])->first();

        if ($operator_detail == null) {
            log_to_file($log_file, "[$country] Didn't add Unknown Operator($operator_nr) with filename $filename");
            return false;
        }

        $content =  "<ul>";
        $content .= "   <li>Phone number: ".$customer_nr."</li>";
        $content .= "   <li>Call Time: ".$file_info["time"]."</li>";
        $content .= "   <li><a href=\"".$this->grandstream->get_download_url($filename)."\" target=\"_blank\">Download</a></li>";
        $content .= "</ul>";

        $deal_info = $this->_get_deal_info($deal_id, $token);

        $person_id = $deal_info["person_id"];
        $org_id = $deal_info["org_id"];

        $query_array = [
            "subject" => "Call Recording",
            "type" => "call",
            "done" => 1,
            "person_id" => $person_id,
            "org_id" => $org_id,
            "note" => $content,
            "user_id" => $operator_detail->pipedrive_id
        ];

        $log = "[$country] Posted $filename by operator: $operator_nr with person number: $customer_nr and person id: $person_id and org id: $org_id";
        if ($deal_info["open"]) {
            $log .= " and deal id: $deal_id";
            $query_array["deal_id"] = $deal_id;
        }

        $data = http_build_query($query_array);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,'https://api.pipedrive.com/v1/activities?api_token='.$token);
    	curl_setopt($ch, CURLOPT_POST, true);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    	$json = curl_exec($ch);
    	curl_close($ch);

    	$output = json_decode($json, true);

        if($output["success"] == true) {
            log_to_file($log_file, $log);
            return true;
        } else {
            log_to_file($log_file,
            "ERROR adding file $filename to pipedrive with operator $operator_nr , will add later");
            return false;
        }
    }


    private function _get_deal_info($deal_id, $token) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, "https://api.pipedrive.com/v1/deals/$deal_id?api_token=$token");

        $json = curl_exec($ch);
        $output = json_decode($json, true);
        curl_close($ch);

        $result = array();
        $result["person_id"] = $output["data"]["person_id"]["value"];
        $result["org_id"] = $output["data"]["org_id"]["value"];

        if ($output["data"]["status"] == "open") {
            $result["open"] = true;
        } else {
            $result["open"] = false;
        }

        return $result;
    }


    public function update_deal($country) {
        $token = $this->_get_token($country);
        if ($token == null) return;

        /* Note: Make sure that the offset is set first in the database before calling the get_offset */
        $offset = \App\offset_config::get_offset('pipedrive', $country);

        $country_code_id = \App\country_code::get_country_id($country);

        $log_file = create_file('Mapping Pipedrive Phone '.ucwords($country));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);

        $is_more_item = true;
        while($is_more_item) {
            $url = 'https://api.pipedrive.com/v1/deals?start='.$offset.'&limit=500&api_token='.$token;
            curl_setopt($ch, CURLOPT_URL, $url);
            $json = curl_exec($ch);
            $output = json_decode($json, true);

            $is_more_item = $output["additional_data"]["pagination"]["more_items_in_collection"];

            if (isset($output["additional_data"]["pagination"]["next_start"])) {
                $offset = $output["additional_data"]["pagination"]["next_start"];
            }

            /* In case deal gets deleted in pipedrive */
            if ($output["data"] == null) {
                $new_offset = 0;
                if ($offset >= 5000) {
                    $new_offset = $offset - 5000;
                }
                \App\offset_config::set_offset('pipedrive', $country, $new_offset);
                log_to_file(get_error_log(), "[$country] Pipedrive Offset is off $offset setting to $new_offset");
                return;
            }

            foreach($output["data"] as $deal) {
                if ($deal['person_id'] == null) continue;
                foreach($deal["person_id"]["phone"] as $phone) {
                    if ($phone["value"] != "") {
                        $phone_nr = $this->_pretty_phone_nr($phone["value"], $country);
                        \App\deal::updateOrCreate(["phone_nr" => $phone_nr],
                                                    [
                                                        "deal_id" => $deal["id"],
                                                        "country_code_id" => $country_code_id
                                                    ]
                                                );
                        log_to_file($log_file,
                        "[$country] Mapped Phone nr -> $phone_nr to deal id -> ".$deal["id"]);
                    }
                }
            }
            \App\offset_config::set_offset('pipedrive', $country, $offset);
        }
        curl_close($ch);
    }


    private function _pretty_phone_nr($phone_nr, $country) {
        if ($country == self::THAILAND) {
            return $this->_pretty_thailand($phone_nr);
        } elseif ($country == self::SINGAPORE) {
            return $this->_pretty_singapore($phone_nr);
        }
        return null;
    }


    private function _pretty_thailand($phone_nr) {
        $phone_nr = $this->_remove_character($phone_nr);

        /* Remove 66 in case it is there */
        if(substr($phone_nr,0,2) == "66") {
            $phone_nr = substr($phone_nr,2,20);
        }

        /* Prepend 0 to phone_nr incase there isn't any */
        if ($phone_nr != "" and $phone_nr[0] != "0") {
            $phone_nr = "0".$phone_nr;
        }

        $firsttwo = substr($phone_nr,0,2);
        /* Check if mobile nr and if yes reduce to 10 digits */
        if($firsttwo == "06" || $firsttwo == "08" || $firsttwo == "09") {
            $phone_nr = substr($phone_nr,0,10);
        }

        /* Check if fixed line nr and if yes reduce to 9 digits */
        if($firsttwo == "02" || $firsttwo == "03" || $firsttwo == "04" || $firsttwo == "05" || $firsttwo == "07") {
            $phone_nr = substr($phone_nr,0,9);
        }

        $phone_nr = $this->remove_extra_leading_zero($phone_nr);

        return $phone_nr;
    }


    private function _pretty_singapore($phone_nr) {
        return $this->_remove_character($phone_nr);
    }


    private function _remove_character($phone_nr) {
        return preg_replace("/[^0-9]/", "", $phone_nr);
    }


    private function remove_extra_leading_zero($phone_nr) {
        $leading_zero_nums = 0;

        $phone_nr_count = strlen($phone_nr);
        for($i = 0;$i < $phone_nr_count;$i++) {
            if ($phone_nr[$i] != '0') {
                break;
            }
            $leading_zero_nums++;
        }
        if ($leading_zero_nums > 1) {
            $phone_nr = substr($phone_nr, $leading_zero_nums - 1);
        }
        return $phone_nr;
    }


    private function _get_token($country) {
        if (isset($this->tokens[$country])) {
            return $this->tokens[$country];
        }
        return null;
    }
}
