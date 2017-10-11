<?php
/**
 * Created by PhpStorm.
 * User: Saverio Delpriori
 * Date: 09/10/2017
 * Time: 10:49
 */


const TRANSLATION = array(

	"Seleziona il pulsante che meglio rappresenta la condizione in cui affronti il percorso." => array(
		"it" => "Seleziona il pulsante che meglio rappresenta la condizione in cui affronti il percorso.",
		"en" => "Please, push the button which better represents your condition during the journey."
	),

	"A piedi" => array(
		'it' => "A piedi",
		'en' => "On foot"
	),

	"Bimbo per mano" => array(
		'it' => "Bimbo per mano",
		'en' => "Child taken by the hand"
	),

	"Bimbo in passeggino" => array(
		'it' => "Bimbo in passeggino",
		'en' => "Child on stroller"
	),

	"Neonato in carrozzina" => array(
		'it' => "Neonato in carrozzina",
		'en' => "Newborn on buggy"
	),

	"Gravidanza" => array(
		'it' => "Gravidanza",
		'en' => "Pregnancy"
	),

	"Deambulatore" => array(
		'it' => "Deambulatore",
		'en' => "Medical walker"
	),

	"Bastone" => array(
		'it' => "Bastone",
		'en' => "Walking cane"
	),

	"Bastone tattile" => array(
		'it' => "Bastone tattile",
		'en' => "White cane"
	),

	"Stampelle" => array(
		'it' => "Stampelle",
		'en' => "Crutch"
	),

	"Tripode" => array(
		'it' => "Tripode",
		'en' => "Tripod cane"
	),

	"Carrozzella manuale" => array(
		'it' => "Carrozzella manuale",
		'en' => "Wheelchair"
	),

	"Carrozzella elettrica" => array(
		'it' => "Carrozzella elettrica",
		'en' => "Electric wheelchair"
	),

	"Per iniziare a registrare, clicca sul pulsante qui sotto." => array(
		"it" => "Per iniziare a registrare, clicca sul pulsante qui sotto.",
		"en" => "Click the button below and start your jorney."
	),

	"Inizia il percorso!" => array(
		"it" => "Inizia il percorso!",
		"en" => "Let's start!"
	),

	"Clicca sul pulsante quando hai raggiunto la destinazione." => array(
		"it" => "Clicca sul pulsante quando hai raggiunto la destinazione.",
		"en" => "Please, as soon as you reach your destination click the button below."
	),

	"Sono a destinazione!" => array(
		"it" => "Sono a destinazione!",
		"en" => "Destination reached!"
	),

	"Aggiorna condizione" => array(
		"it" => "Aggiorna condizione",
		"en" => "Change your condition"
	),

	"Nuovo percorso" => array(
		"it" => "Nuovo percorso",
		"en" => "New journey"
	),

	"Ciao, sono il bot Da-qui-a-lì! 🤖\n\nRaccolgo informazioni sull’accessibilità dei centri abitati per i pedoni." => array(
		"it" => "Ciao, sono il bot Da-qui-a-lì! 🤖\n\nRaccolgo informazioni sull’accessibilità dei centri abitati per i pedoni.",
		"en" => "Hello, I'm Da-qui-a-lì (meaning \"from here to there\")! 🤖\n\nI'm here to collect data about pedestrian accessibility."
	),

	"Le posizioni che mi invierai all'inizio e alla fine del tuo percorso ci aiuteranno a creare una mappa con i percorsi migliori all’interno della città!" => array(
		"it" => "Le posizioni che mi invierai all'inizio e alla fine del tuo percorso ci aiuteranno a creare una mappa con i percorsi migliori all’interno della città!",
		"en" => "Whenever you send me your location at the start and at the end of your journey you will help us in building a map of best paths throughout the city!"
	),

	"Non ho capito." => array(
		"it" => "Non ho capito 😕",
		"en" => "I'm sorry! I didn't get what you mean 😕"
	),

	"Uhm… non capisco questo tipo di messaggi! 😑\nPer riprovare invia /start." => array(
		"it" => "Uhm… non capisco questo tipo di messaggi! 😑\nPer riprovare invia /start.",
		"en" => "Uhm… I'm not able to understand this kind of messages! 😑\nPlease retry typing /start."
	),

	"Ok, la tua condizione è memorizzata come: " => array(
		"it" => "Ok, la tua condizione è memorizzata come: ",
		"en" => "Ok, your condition has been saved as: "
	),

	"Codice non valido. 😑" => array(
		"it" => "Codice non valido. 😑",
		"en" => "Invalid code. 😑"
	),

	"Registrato, grazie! Come valuteresti l’accessibilità del percorso, da 1 a 5?" => array(
		"it" => "Registrato, grazie! Come valuteresti l’accessibilità del percorso, da 1 a 5?",
		"en" => "Saved, thanks! How would you rate the accessibility of your path (from 1 up to 5)?"
	),

	"Allora niente. ☺ Vuoi tracciare un nuovo percorso?" => array(
		"it" => "Allora niente. ☺ Vuoi tracciare un nuovo percorso?",
		"en" => "Ok! nevermind... ☺ Do you want to log another journey?"
	),

	"Bene. Hai avuto bisogno di aiuto durante il tragitto?" => array(
		"it" => "Bene. Hai avuto bisogno di aiuto durante il tragitto?",
		"en" => "Good. Did you need any help along your path?"
	),

	"Sì." => array(
		"it" => "Sì.",
		"en" => "Yes."
	),

	"Hai percorso " => array(
		"it" => "Hai percorso ",
		"en" => "You just travelled for "
	),

	" minuti, in questa condizione: " => array(
		"it" => " minuti, in questa condizione: ",
		"en" => " minutes, and your condition was: "
	),

	"Grazie. 😉 Dimmi quando vuoi tracciare un nuovo percorso." => array(
		"it" => "Grazie. 😉 Dimmi quando vuoi tracciare un nuovo percorso.",
		"en" => "Thanks. 😉 Please, tell me whenever you want to log another journey."
	)

);


function __t($string_id, $user_lang){

	if(!array_key_exists($string_id, TRANSLATION)){
		Logger::debug("No traslation found");
		return $string_id;
	}

	if(starts_with($user_lang,'en-')){
		Logger::debug("English translation");
		return TRANSLATION[$string_id]['en'];
	} else {
		Logger::debug("Italian translation");
		return TRANSLATION[$string_id]['it'];
	}

}