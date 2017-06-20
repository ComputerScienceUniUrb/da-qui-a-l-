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
                    array("text" => "Inizia percorso!", "request_location" => true)
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

            request_start($chat_id);
        }
        else if(strpos($text, "/setup") === 0) {
            Logger::debug("Setup command");

            request_disability($chat_id);
        }
        else if (strpos($text, "/normodotato") === 0 
            or strpos($text, "/stampelle-bastone") === 0 
            or strpos($text, "/tripode-quadripode") === 0 
            or strpos($text, "/deambulatore") === 0 
            or strpos($text, "/carrozzina manuale") === 0 
            or strpos($text, "/carrozzina elettrica") === 0 
            or strpos($text, "/bastone tattile") === 0 
            or strpos($text, "/passeggini") === 0 
            or strpos($text, "/carrozzine neonati") === 0 
            or strpos($text, "/donna incinta") === 0 
            or strpos($text, "/adulto con un bambino") === 0)  {
            
            $dis = disablity_code($text);

            echo 'Received /dis command!' . PHP_EOL;  
            //clean db
            mysqli_query ($conn,"DELETE FROM TABETEST WHERE user=$chat_id AND lat2 IS NULL;")
                or die("Connessione non riuscita: " . mysql_error());


            telegram_send_message($chat_id, "Iniziamo il percorso?", 
            array("reply_markup" => array("keyboard" => array(array(array("text"=>"/INIZIO 🏃", "request_location"=> true)))))); 
            $query = "INSERT INTO TABETEST (user, dis) VALUES ($chat_id, '$dis' )";
            echo $query.PHP_EOL;
            mysqli_query ($conn,$query)
                or die("Connessione non riuscita: " . mysql_error());

            echo 'Query eseguita correttamente';   
            return;
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

            // TODO: Send stats on journey

            telegram_send_message($chat_id, "Il tuo percorso è stato registrato correttamente, grazie! 👍 Usa il comando /begin per registrarne un altro.");
        }
        else {
            // New journey
            db_perform_action("INSERT INTO `journeys` (`id`, `telegram_id`, `lat1`, `lng1`, `time1`) VALUES(DEFAULT, {$chat_id}, {$latitude}, {$longitude}, NOW())");

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
}
else {

}

?>