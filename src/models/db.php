<?php

// REQUIRES
require_once PROJECT_ROOT_PATH . "controllers/procedures.php";

// CONSTANTS
$CONFIG_FILE = PROJECT_ROOT_PATH . "inc/db_config.json";

error_log(print_r($CONFIG_FILE, true));

// INSTANCES
$procedures = new Procedures();

class DB {
    public function __constructor() {

    if (!file_exists($filepath)) {
        return procedures.firstConnection();
    }
}
}


return procedures.todo();

/*
$json = json_decode(file_get_contents($file), true);
// Efface un fichier sur le disque où l'utilisateur a le droit d'aller
$username = $_SERVER['REMOTE_USER']; // utilisation d'un mécanisme d'identification
$userfile = basename($_POST['user_submitted_filename']);
$homedir  = "/home/$username";

$filepath = "$homedir/$userfile";

if (file_exists($filepath) && unlink($filepath)) {
   $logstring = "$filepath effacé\n";
} else {
   $logstring = "Échec lors de l'effacement de $filepath\n";
}
$fp = fopen("/home/logging/filedelete.log", "a");
fwrite($fp, $logstring);
fclose($fp);

echo htmlentities($logstring, ENT_QUOTES);
*/


?>

