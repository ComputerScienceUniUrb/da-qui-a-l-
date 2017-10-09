<?php
/*
 * Telegram Bot Sample
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Basic message processing functionality,
 * used by both pull and push scripts.
 */

require_once('data.php');
require_once('lib_translation.php');

// This file assumes to be included by pull.php or
// hook.php right after receiving a new Telegram update.
// It also assumes that the update data is stored
// inside a $update variable.

function clamp($min, $max, $value) {
    if($value < $min)
        return $min;
    else if($value > $max)
        return $max;
    else
        return $value;
}

function search_r($array, $key)
{
	if (!is_array($array)) {
		return false;
	}

	if (isset($array[$key])) {
		return $array[$key];
	}

	foreach ($array as $subarray) {
		$sub_res = search_r($subarray, $key);
		if($sub_res !== false){
			return $sub_res;
		}
	}
}

function perform_command_begin($chat_id, $user_lang) {
    Logger::debug("Begin command");

    // Delete open journeys
    $num_delete = db_perform_action("DELETE FROM `journeys` WHERE `telegram_id` = {$chat_id} AND `lat2` IS NULL AND `lng2` IS NULL");
    Logger::debug("{$num_delete} open journeys deleted");

    request_start($chat_id, $user_lang);
}

function request_disability($chat_id, $user_lang) {
    telegram_send_message($chat_id, __t("Seleziona il pulsante che meglio rappresenta la condizione in cui affronti il percorso.", $user_lang),
        array("reply_markup" => array(
            "inline_keyboard" => array(
                array(
                    array("text" => __t("A piedi", $user_lang), "callback_data" => "dis a0"),
                    array("text" => __t("Bimbo per mano", $user_lang), "callback_data" => "dis a11")
                ),
                array(
                    array("text" => __t("Bimbo in passeggino", $user_lang), "callback_data" => "dis a8"),
                    array("text" => __t("Neonato in carrozzina", $user_lang), "callback_data" => "dis a9")
                ),
                array(
                    array("text" => __t("Gravidanza", $user_lang), "callback_data" => "dis a10"),
                    array("text" => __t("Deambulatore", $user_lang), "callback_data" => "dis a4")
                ),
                array(
                    array("text" => __t("Bastone", $user_lang), "callback_data" => "dis a1"),
                    array("text" => __t("Bastone tattile", $user_lang), "callback_data" => "dis a7")
                ),
                array(
                    array("text" => __t("Stampelle", $user_lang), "callback_data" => "dis a2"),
                    array("text" => __t("Tripode", $user_lang), "callback_data" => "dis a3")
                ),
                array(
                    array("text" => __t("Carrozzella manuale", $user_lang), "callback_data" => "dis a5"),
                    array("text" => __t("Carrozzella elettrica", $user_lang), "callback_data" => "dis a6")
                )
            )
        ))
    );
}

function request_start($chat_id, $user_lang) {
    telegram_send_message($chat_id, __t("Per iniziare a registrare, clicca sul pulsante qui sotto.", $user_lang),
        array("reply_markup" => array(
            "keyboard" => array(
                array(
                    array("text" => __t("Inizia il percorso!",$user_lang), "request_location" => true)
                )
            ),
            "resize_keyboard" => true,
            "one_time_keyboard" => true
        ))
    );
}

function request_end($chat_id, $user_lang) {
    telegram_send_message($chat_id, __t("Clicca sul pulsante quando hai raggiunto la destinazione.", $user_lang),
        array("reply_markup" => array(
            "keyboard" => array(
                array(
                    array("text" => __t("Sono a destinazione!", $user_lang), "request_location" => true)
                )
            ),
            "resize_keyboard" => true,
            "one_time_keyboard" => true
        ))
    );
}

function request_restart($chat_id, $text, $user_lang) {
    telegram_send_message($chat_id, $text,
        array("reply_markup" => array(
            "inline_keyboard" => array(
                array(
                    array("text" => __t("Aggiorna condizione", $user_lang), "callback_data" => "setup")
                ),
                array(
                    array("text" => __t("Nuovo percorso",$user_lang), "callback_data" => "begin")
                )
            )
        ))
    );
}

// Input: $update
$user_lang = search_r($update, 'language_code');
var_dump($user_lang);

if(isset($update['message'])) {
    // Standard message
    $message = $update['message'];
    $message_id = $message['message_id'];
    $chat_id = $message['chat']['id'];
    $from_id = $message['from']['id'];


    if (isset($message['text'])) {
        // We got an incoming text message
        $text = $message['text'];

        if (strpos($text, "/start") === 0) {
            Logger::debug("Start command");

            telegram_send_message($chat_id, __t("Ciao, sono il bot Da-qui-a-lì! 🤖\n\nRaccolgo informazioni sull’accessibilità dei centri abitati per i pedoni.", $user_lang));
            
            telegram_send_message($chat_id, __t("Le posizioni che mi invierai all'inizio e alla fine del tuo percorso ci aiuteranno a creare una mappa con i percorsi migliori all’interno della città!", $user_lang));
            
            request_disability($chat_id, $user_lang);
            return;
        }
        else if(strpos($text, "/begin") === 0) {
            perform_command_begin($chat_id, $user_lang);
        }
        else if(strpos($text, "/setup") === 0) {
            Logger::debug("Setup command");

            request_disability($chat_id, $user_lang);
        }
        else {
            telegram_send_message($chat_id, __t("Non ho capito.", $user_lang));
        }
    }
    else if (isset($message['location'])) {
        // We got an incoming location message
        $location = $message['location'];
        $latitude = $location['latitude'];
        $longitude = $location['longitude'];

        $running_journey = db_scalar_query("SELECT `id` FROM `journeys` WHERE `telegram_id` = {$chat_id
        } AND `lat2` IS NULL AND `lng2` IS NULL ORDER BY `id` DESC LIMIT 1");
        Logger::debug("Running journey #{$running_journey}");

        if($running_journey) {
            // Close journey
            db_perform_action("UPDATE `journeys` SET `lat2` = {$latitude}, `lng2` = {$longitude}, `time2` = NOW() WHERE `id` = {$running_journey} LIMIT 1");

            $stats = db_row_query("SELECT SQRT(POW(`lat2` - `lat1`, 2) + POW(`lng2` - `lng1`, 2)) AS distance, (3959 * acos(cos(radians(`lat1`)) * cos(radians(`lat2`)) * cos(radians(`lng2`) - radians(`lng1`)) + sin(radians(`lat1`)) * sin(radians(`lat2`)))) AS distance_hav, TIMESTAMPDIFF(MINUTE, `time1`, `time2`) AS elapsed_minutes, `disability` FROM `journeys` WHERE `id` = {$running_journey}");

            print_r($stats);

            $stats_kms = round((float)$stats[1], 1, PHP_ROUND_HALF_UP);
            $stats_mins = intval($stats[2]);
            $stats_dis = $disabilities_to_name_map[$stats[3]];

            var_dump($stats_dis);

            telegram_send_message($chat_id, __t("Hai percorso ", $user_lang).$stats_kms.__t(" km in ", $user_lang).$stats_mins.__t(" minuti, in questa condizione: ",$user_lang).__t($stats_dis,$user_lang).". Ok?", array(
                "reply_markup" => array(
                    "inline_keyboard" => array(
                        array(
                            array("text" => "Ok! 👍", "callback_data" => "confirm {$running_journey}"),
                            array("text" => "No.", "callback_data" => "cancel")
                        )
                    )
                )
            ));
        }
        else {
            $user_disability = get_user_disability($chat_id);

            // New journey
            db_perform_action("INSERT INTO `journeys` (`id`, `telegram_id`, `disability`, `lat1`, `lng1`, `time1`) VALUES(DEFAULT, {$chat_id}, '".db_escape($user_disability)."', {$latitude}, {$longitude}, NOW())");

            request_end($chat_id, $user_lang);
        }
    }
    else {
        telegram_send_message($chat_id, __t("Uhm… non capisco questo tipo di messaggi! 😑\nPer riprovare invia /start.", $user_lang));
    }
}

else if(isset($update['callback_query'])) {
    // Callback query
    $callback_data = $update['callback_query']['data'];
    $chat_id = $update['callback_query']['message']['chat']['id'];

    if(strpos($callback_data, 'dis ') === 0) {
        // Set disability
        $dis_code = substr($callback_data, 4);
        if(isset($disabilities_to_name_map[$dis_code])) {
            db_perform_action("REPLACE INTO `status` (`telegram_id`, `disability`) VALUES($chat_id, '".db_escape($dis_code)."')");

            telegram_send_message($chat_id, __t("Ok, la tua condizione è memorizzata come: ",$user_lang)."".__t($disabilities_to_name_map[$dis_code],$user_lang));

            request_start($chat_id, $user_lang);
        }
        else {
            Logger::error("Invalid callback data: {$callback_data}");
            telegram_send_message($chat_id, __t("Codice non valido. 😑", $user_lang));
        }
    }
    else if(strpos($callback_data, "confirm ") === 0) {
        $track_id = intval(substr($callback_data, 8));
        Logger::debug("Confirming track #{$track_id}");
        
        db_perform_action("UPDATE `journeys` SET `confirm` = 1 WHERE `id` = {$track_id}");

        telegram_send_message($chat_id, __t("Registrato, grazie! Come valuteresti l’accessibilità del percorso, da 1 a 5?", $user_lang), array(
            "reply_markup" => array(
                "inline_keyboard" => array(
                    array(
                        array("text" => "1 👎", "callback_data" => "rate {$track_id} 1"),
                        array("text" => "2", "callback_data" => "rate {$track_id} 2"),
                        array("text" => "3", "callback_data" => "rate {$track_id} 3"),
                        array("text" => "4", "callback_data" => "rate {$track_id} 4"),
                        array("text" => "5 👍", "callback_data" => "rate {$track_id} 5")
                    )
                )
            )
        ));
    }
    else if($callback_data == "cancel") {
        request_restart($chat_id, __t("Allora niente. ☺ Vuoi tracciare un nuovo percorso?",$user_lang), $user_lang);
    }
    else if(strpos($callback_data, "rate ") === 0) {
        $data = explode(" ", substr($callback_data, 5));
        $track_id = intval($data[0]);
        $rating = clamp(1, 5, intval($data[1]));
        Logger::debug("Rating track #{$track_id} with {$rating}");

        db_perform_action("UPDATE `journeys` SET `rating` = ${rating} WHERE `id` = {$track_id}");

        telegram_send_message($chat_id, __t("Bene. Hai avuto bisogno di aiuto durante il tragitto?",$user_lang), array(
            "reply_markup" => array(
                "inline_keyboard" => array(
                    array(
                        array("text" => __t("Sì.", $user_lang), "callback_data" => "help {$track_id} 1"),
                        array("text" => __t("No.", $user_lang), "callback_data" => "help {$track_id} 0")
                    )
                )
            )
        ));
    }
    else if(strpos($callback_data, "help ") === 0) {
        $data = explode(" ", substr($callback_data, 5));
        $track_id = intval($data[0]);
        $help_needed = clamp(0, 1, intval($data[1]));
        Logger::debug("Help needed on track #{$track_id}: {$help_needed}");

        db_perform_action("UPDATE `journeys` SET `help_needed` = ${help_needed} WHERE `id` = {$track_id}");

        request_restart($chat_id, __t("Grazie. 😉 Dimmi quando vuoi tracciare un nuovo percorso.",$user_lang), $user_lang);
    }
    else if($callback_data == "setup") {
        request_disability($chat_id, $user_lang);
    }
    else if($callback_data == "begin") {
        perform_command_begin($chat_id, $user_lang);
    }
    else {
        // Huh?
        Logger::error("Unknown callback, data: {$callback_data}");
    }
}
