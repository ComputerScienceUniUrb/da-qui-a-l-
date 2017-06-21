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

function request_disability($chat_id) {
    telegram_send_message($chat_id, "Seleziona il pulsante che meglio rappresenta la tua condizione.",
        array("reply_markup" => array(
            "inline_keyboard" => array(
                array(
                    array("text" => "🚶", "callback_data" => "dis a1"),
                    array("text" => "Stampelle", "callback_data" => "dis a2"),
                    array("text" => "Tri/quadripode", "callback_data" => "dis a3")
                ),
                array(
                    array("text" => "Deambulatore", "callback_data" => "dis a4"),
                    array("text" => "♿", "callback_data" => "dis a5"),
                    array("text" => "♿ (elettrica)", "callback_data" => "dis a6")
                ),
                array(
                    array("text" => "Bastone tattile", "callback_data" => "dis a7"),
                    array("text" => "Passeggino", "callback_data" => "dis a8"),
                    array("text" => "Carrozzina", "callback_data" => "dis a9")
                ),
                array(
                    array("text" => "🤰", "callback_data" => "dis a10"),
                    array("text" => "👩‍👧", "callback_data" => "dis a11")
                )
            )
        ))
    );
}

function request_start($chat_id) {
    telegram_send_message($chat_id, "Per iniziare a registrare, clicca sul pulsante qui sotto.",
        array("reply_markup" => array(
            "keyboard" => array(
                array(
                    array("text" => "Inizia il percorso!", "request_location" => true)
                )
            ),
            "resize_keyboard" => true,
            "one_time_keyboard" => true
        ))
    );
}

function request_end($chat_id) {
    telegram_send_message($chat_id, "Clicca sul pulsante quando hai raggiunto la destinazione.",
        array("reply_markup" => array(
            "keyboard" => array(
                array(
                    array("text" => "Sono a destinazione!", "request_location" => true)
                )
            ),
            "resize_keyboard" => true,
            "one_time_keyboard" => true
        ))
    );
}

// Input: $update

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

            telegram_send_message($chat_id, "Benvenuto/a, sono il bot Da-qui-a-lì! 🤖\n\nIl mio scopo è collezionare informazioni sull'accessibilità dei percorsi all'interno dell'ambiente urbano."); 
            
            telegram_send_message($chat_id, "Le posizioni che mi invierai all'inizio e alla fine del tuo percorso ci aiuteranno a creare una mappa con i percorsi migliori all'interno della città!"); 
            
            request_disability($chat_id);
            return;
        }
        else if(strpos($text, "/begin") === 0) {
            Logger::debug("Begin command");

            // Delete open journeys
            $num_delete = db_perform_action("DELETE FROM `journeys` WHERE `telegram_id` = {$chat_id} AND `lat2` IS NULL AND `lng2` IS NULL");
            Logger::debug("{$num_delete} open journeys deleted");

            request_start($chat_id);
        }
        else if(strpos($text, "/setup") === 0) {
            Logger::debug("Setup command");

            request_disability($chat_id);
        }
        else {
            telegram_send_message($chat_id, "Non ho capito.");
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

            telegram_send_message($chat_id, "Hai percorso {$stats_kms} km in {$stats_mins} minuti, in questa condizione: {$stats_dis}. Ok?", array(
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

            request_end($chat_id);
        }
    }
    else {
        telegram_send_message($chat_id, "Uhm… non capisco questo tipo di messaggi! 😑\nPer riprovare invia /start.");
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

            telegram_send_message($chat_id, "Ok, la tua condizione è memorizzata come: {$disabilities_to_name_map[$dis_code]}.");

            request_start($chat_id);
        }
        else {
            Logger::error("Invalid callback data: {$callback_data}");
            telegram_send_message($chat_id, "Codice non valido. 😑");
        }
    }
    else if(strpos($callback_data, "confirm ") === 0) {
        $track_id = intval(substr($callback_data, 8));
        Logger::debug("Confirming track #{$track_id}");
        
        db_perform_action("UPDATE `journeys` SET `confirm` = 1 WHERE `id` = {$track_id}");

        telegram_send_message($chat_id, "Registrato, grazie! Come valuteresti l’accessibilità del percorso, da 1 a 5?", array(
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
    else if(strpos($callback_data, "cancel ") === 0) {
        telegram_send_message($chat_id, "Allora niente. Se vuoi tracciare un nuovo percorso, usa il comando /begin.");
    }
    else if(strpos($callback_data, "rate ") === 0) {
        $data = explode(" ", substr($callback_data, 5));
        $track_id = intval($data[0]);
        $rating = clamp(1, 5, intval($data[1]));
        Logger::debug("Rating track #{$track_id} with {$rating}");

        db_perform_action("UPDATE `journeys` SET `rating` = ${rating} WHERE `id` = {$track_id}");

        telegram_send_message($chat_id, "Bene. Hai avuto bisogno di aiuto durante il tragitto?", array(
            "reply_markup" => array(
                "inline_keyboard" => array(
                    array(
                        array("text" => "Sì.", "callback_data" => "help {$track_id} 1"),
                        array("text" => "No.", "callback_data" => "help {$track_id} 0")
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

        telegram_send_message($chat_id, "Grazie. 😉 Se vuoi tracciare un nuovo percorso, usa il comando /begin.");
    }
    else {
        // Huh?
        Logger::error("Unknown callback, data: {$callback_data}");
    }
}
