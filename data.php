<?php
/* General data */

function get_user_disability($telegram_id) {
    $dis = db_scalar_query("SELECT `disability` FROM `status` WHERE `telegram_id` = {$telegram_id} LIMIT 1");

    if($dis)
        return $dis;
    else
        return $disabilities_code[0];
}

$disabilities_code = array(
    "a0",
    "a1",
    "a2",
    "a3",
    "a4",
    "a5",
    "a6",
    "a7",
    "a8",
    "a9",
    "a10",
    "a11"
);

$disabilities_to_name_map = array(
    "a0" => "A piedi",
    "a1" => "Bastone",
    "a2" => "Stampelle",
    "a3" => "Tripode",
    "a4" => "Deambulatore",
    "a5" => "Carrozzina manuale",
    "a6" => "Carrozzina elettrica",
    "a7" => "Bastone tattile",
    "a8" => "Bimbo in passeggino",
    "a9" => "Neonato in carrozzina",
    "a10" => "Gravidanza",
    "a11" => "Bimbo per mano"
);
