<?php
/*
 * Telegram Bot Sample
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Basic message processing functionality,
 * used by both pull and push scripts.
 *
 * Put your custom bot intelligence here!
 */

// This file assumes to be included by pull.php or
// hook.php right after receiving a new message.
// It also assumes that the message data is stored
// inside a $message variable.

// Message object structure: {
//     "message_id": 123,
//     "from": {
//       "id": 123456789,
//       "first_name": "First",
//       "last_name": "Last",
//       "username": "FirstLast"
//     },
//     "chat": {
//       "id": 123456789,
//       "first_name": "First",
//       "last_name": "Last",
//       "username": "FirstLast",
//       "type": "private"
//     },
//     "date": 1460036220,
//     "text": "Text"
//   }




function disablity_code($text){
    $disabilities = array(
        "/normodotato" => "a1",
        "/stampelle o bastone" => "a2",
        "/tripode o quadripode" => "a3",
        "/deambulatore" => "a4",
        "/carrozzina manuale" => "a5",
        "/carrozzina elettrica" => "a6",
        "/bastone tattile" => "a7",
        "/passeggini" => "a8",
        "/carrozzine neonati" => "a9",
        "/donna incinta" => "a10",
        "/adulto con un bambino" => "a11",
    );

    if(array_key_exists($text, $disabilities)){
        return $disabilities[$text];
    } else {
        echo "Chiave sbagliata! ($text)".PHP_EOL;
    }

}


$a=0;
$message_id = $message['message_id'];
$chat_id = $message['chat']['id'];
$from_id = $message['from']['id'];

$servername = "localhost";
$username = "root";
$password = "laboratorio";
$db = "test_uniurb_51";


//use Longman\TelegramBot\Entities\InlineKeyboardMarkup;

// Create connection
$conn = new mysqli($servername, $username, $password, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
    telegram_send_message($chat_id, 'fallimento');
} 
echo "Connected successfully".PHP_EOL;



if (isset($message['text'])) {
    // We got an incoming text message
    $text = $message['text'];

    if (strpos($text, "/start") === 0) {
        echo 'Received /start command!' . PHP_EOL; 
        telegram_send_message($chat_id, "Benvenuto! sono il bot Da-qui-a-lì 🤖 \n\nIl mio scopo è collezionare informazioni sull'accessibilità dei percorsi all'interno dell'ambiente urbano."); 
        
        telegram_send_message($chat_id, "Le posizioni che mi invierai all'inizio e alla fine del tuo percorso ci aiuteranno a creare una mappa con i percorsi migliori all'interno della città!"); 
            
        telegram_send_message($chat_id, "Prima di iniziare clicca sul bottone che descrive meglio la tua condizione attuale. \n\nGrazie della collaborazione!", 
            array("reply_markup" => array("keyboard" => array(array(array("text"=>"/normodotato" ),  array("text"=>"/stampelle-bastone"), array("text"=>"/tripode-quadripode"),),array(array("text"=>"/deambulatore"),  array("text"=>"/carrozzina manuale"), array("text"=>"/carrozzina elettrica"),),array(array("text"=>"/bastone tattile"),  array("text"=>"/passeggini"), array("text"=>"/carrozzine neonati"),),array(array("text"=>"/donna incinta"),  array("text"=>"/adulto con un bambino"),)    ),))); 

        return;    
    } else if (strpos($text, "/normodotato") === 0 
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


if (isset($message['location'])) {
    // We got an incoming location message
    $location = $message['location'];
    $latitude = $location ['latitude'];
    $longitude = $location ['longitude'];

    $result = mysqli_query ($conn,"SELECT * FROM TABETEST WHERE user=$chat_id ORDER BY id DESC LIMIT 1; ")
         or die("Connessione non riuscita: " . mysql_error());

    printf("Select returned %d rows.\n", mysqli_num_rows($result));
    if(mysqli_num_rows($result) == 0){
        telegram_send_message($chat_id, "Uhm... sembra che qualcosa sia andato storto! 😑 \nPer riprovare invia /start" );    
        return;
    }

    $result_arr = mysqli_fetch_assoc($result);
    
    
    print_r($result_arr);

    $row_id = $result_arr['id'];
    $lat1 = $result_arr['lat1'];
    $lat2 = $result_arr['lat2'];
    
    /* free result set */
    //mysqli_free_result($result);
    

    if ($lat1 == null) {
        echo 'Received /INIZIO command!' . PHP_EOL;

        $timestamp1 = time();
        $query = "UPDATE TABETEST set lat1=$latitude, long1=$longitude , temp1=NOW() where id=$row_id ; ";
        echo $query.PHP_EOL;
        mysqli_query ($conn, $query)
                or die("Connessione non riuscita: " . mysql_error());
        echo 'Query eseguita correttamente';   

        telegram_send_message($chat_id, "Appena hai raggiunto la tua meta premi il tasto", array("reply_markup" => array("keyboard" => array(array(array("text"=>"/FINE 🏁", "request_location"=> true))))));    

        return;
    } else if($lat2 == NULL) {
        echo 'Received /FINE command!' . PHP_EOL;

        mysqli_query ($conn,"UPDATE TABETEST set lat2=$latitude, long2=$longitude , temp2=NOW() where id=$row_id; ")
                    or die("Connessione non riuscita: " . mysql_error());

        echo 'Query eseguita correttamente'; 

        telegram_send_message($chat_id, "Grazie per il tuo aiuto! 💗 \nSe vuoi fornire dati riguardanti un nuovo percorso riseleziona la disabilità.", 
            array("reply_markup" => array("keyboard" => array(array(array("text"=>"/normodotato" ),  array("text"=>"/stampelle-bastone"), array("text"=>"/tripode-quadrupode"),),array(array("text"=>"/deambulatore"),  array("text"=>"/carrozzina manuale"), array("text"=>"/carrozzina elettrica"),),array(array("text"=>"/bastone tattile"),  array("text"=>"/passeggini"), array("text"=>"/carrozzine neonati"),),array(array("text"=>"/donna incinta"),  array("text"=>"/adulto con un bambino"),)    ),))); 
        return;
    } 
}

telegram_send_message($chat_id, "Uhm... sembra che qualcosa sia andato storto! 😑\n Per riprovare invia /start" );    




?>