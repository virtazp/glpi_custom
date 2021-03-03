<?php
// Désactive le CSRF
define('GLPI_USE_CSRF_CHECK', 0);

include('../inc/includes.php');

Session::checkLoginUser();

Html::header(__('New ticket'), '', "helpdesk", "ticket");

global $CFG_GLPI;

$urgence = 2;
$important = 2;
$mail;
$titre;
$description;
$deadline;
$elements;
$tokenSession;
$tokenUser;
global $url;
$AppToken;
$error = false;
$descriptionMiseEnForme;
$titreMiseEnForme;
$errorAddTicket = false;

if (isset($_POST['AppToken'])) {
    $AppToken = $_POST['AppToken'];
}

if (isset($_POST['urlTarget'])) {
    $url = $_POST['urlTarget'];
}
if (isset($_POST['urgence'])) {
    $mail = new CustomMailTicket();
    $mail->sendMailTest($_POST['nameUser'], $_POST['firstnameUser']);

    $urgence = 4;
}
if (isset($_POST['important'])) {
    $important = 4;
}
if (isset($_POST['titre'])) {
    // Formatage du texte car sinon erreur avec les \n etc.

    $titre = $_POST['titre'];
    // var_dump('titre brut : '.$titre);
    $titreMiseEnForme = stripslashes($titre);
    $titreMiseEnForme = str_replace('"', "'", $titreMiseEnForme);
    $titreMiseEnForme = str_replace('	', " ", $titreMiseEnForme);
    $titreMiseEnForme = str_replace('\\', "/", $titreMiseEnForme);
    // var_dump('titre traité : '.$titreMiseEnForme);
}
if (isset($_POST['description'])) {

    $descriptionMiseEnForme;

    $description = $_POST['description'];
    // var_dump('Description brut : '.$description);
    $data = preg_split('#\\\\r\\\\n#', $description);
    // echo "Description array suite split : ";
    // var_dump($data);

    if (count($data) > 1) {
        for ($i = 0; $i < count($data); $i++) {
            if (strlen($data[$i]) > 0) {
                $data[$i] = stripslashes($data[$i]);
                $descriptionMiseEnForme .= str_replace('\\', "/", $data[$i]) . '\\r\\n';
            }
        }
    } else {
        $data[0] = stripslashes($data[0]);
        $descriptionMiseEnForme = str_replace('\\', "/", $data[0]);
    }
    $descriptionMiseEnForme = str_replace('"', "'", $descriptionMiseEnForme);
    $descriptionMiseEnForme = str_replace('	', " ", $descriptionMiseEnForme);
    // var_dump('Description traitée : '.$descriptionMiseEnForme);
}
if (isset($_POST['deadline'])) {
    $deadline = $_POST['deadline'];
}
if (isset($_POST['elements'])) {
    $elements = $_POST['elements'];
}
if (isset($_POST['tokenSession'])) {
    $tokenSession = $_POST['tokenSession'];
}

$headers = array(
    'Content-Type: application/json',
    "Session-Token:$tokenSession",
    'App-token:' . $AppToken
);

/* Création du ticket */
$ch = curl_init();

$postDeadLine = "";
if (isset($_POST['deadline']) && strlen($_POST['deadline']) > 0) {

    $postDeadLine = ',"plugin_fields_containers_id": 1, "deadlinefield" : "' . $_POST['deadline'] . '"';

}

$postForm = '{"input": {"name" : "' . $titreMiseEnForme . '", "content" : "' . $descriptionMiseEnForme . '", "urgency": ' . $urgence . ', "impact": ' . $important . $postDeadLine . '}}';

echo $postForm;

curl_setopt($ch, CURLOPT_URL, $url . "/apirest.php/Ticket/");
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postForm);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$server_output = curl_exec($ch);
$resultAddTicket = json_decode($server_output, true);
//var_dump($resultAddTicket);
if ($resultAddTicket[0] === 'ERROR_JSON_PAYLOAD_INVALID' || $resultAddTicket[0] === 'ERROR_GLPI_ADD' || $resultAddTicket[0] === 'ERROR_BAD_ARRAY') {
    echo "<h2 style='text-align:center;'>Erreur lors de l'ajout de votre ticket. Merci d'envoyer la capture de l'écran et le message associé à <a href='mailto:' style='color:red;font-size: 1em;font-weight: bold;'></a></h2>";
    echo "<h2 style='width:100%; text-align:center;'>Saisie[" . $resultAddTicket[0] . "] : " . $postForm . "</h2>";
    $errorAddTicket = true;
    die();
} else {
    $errorAddTicket = false;
}
curl_close($ch);

$idTicket = $resultAddTicket['id'];

// Si l'utilisateur renseigne une deadline
// if (isset($_POST['deadline']) && strlen($_POST['deadline']) > 0) {
//     echo '<h2>' . $_POST['deadline'] . '</h2>';
//     $ch = curl_init();

//     $postDeadLine = '{"input": {"itemtype": "Ticket", "items_id": "' . $idTicket . '", "plugin_fields_containers_id": 1, "deadlinefield" : "' . $_POST['deadline'] . '"}}';

//     curl_setopt($ch, CURLOPT_URL, $url . '/apirest.php/Ticket/' . $idTicket . '/PluginFieldsTicketdsiinterne');
//     curl_setopt($ch, CURLOPT_POST, 1);
//     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//     curl_setopt($ch, CURLOPT_POSTFIELDS, $postDeadLine);
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//     $server_output = curl_exec($ch);
//     $server_output = json_decode($server_output, true);
//     // var_dump($server_output);
//     if ($server_output[0] === 'ERROR_JSON_PAYLOAD_INVALID' || $server_output[0] === 'ERROR_GLPI_ADD') {
//         echo "<h2>Erreur ajout DEADLINE : </h2>" . $server_output[0];
//         die();
//     } else {
//         echo "<h2>DeadLine ajouté</h2>";
//     }
//     curl_close($ch);
// }

// Si l'utilisateur veut suivre l'avancement du ticket par mail
//var_dump(isset($_POST['mail']));
if (isset($_POST['mail'])) {
    $mail = new CustomMailTicket();
    $mail->Notification($headers, $idTicket, "active", $url);
} else {
    $mail = new CustomMailTicket();
    $mail->Notification($headers, $idTicket, "noactive", $url);
}
// Si l'utilisateur renseigne un éléments
if (isset($_POST['elements']) && $_POST['elements'] !== 'none') {
    $ch = curl_init();

    $postItemComputer = '{"input": {"itemtype": "Computer", "items_id": "' . $_POST['elements'] . '", "tickets_id": "' . $idTicket . '"}}';

    curl_setopt($ch, CURLOPT_URL, $url . '/apirest.php/Ticket/' . $idTicket . '/Item_Ticket');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postItemComputer);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $server_output = curl_exec($ch);
    $result = json_decode($server_output, true);
    // var_dump($result);
    if ($result[0] === 'ERROR_JSON_PAYLOAD_INVALID' || $result[0] === 'ERROR_GLPI_ADD') {
        echo "<h2>Erreur ajout ELEMENT : </h2>" . $result[0];
        die();
    } else {
        echo "<h2>ELEMENT ajouté</h2>";
    }
    curl_close($ch);
}

/* Ajout d'un document si l'utilisateur a fournit une pièce jointe */
if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {

    $postDataDoc = [
        'uploadManifest' => '{"input": {"name": "Uploaded document", "_filename" : ["wordpress.png"]}}',
        'filename[0]' => new CurlFile($_FILES['file']['tmp_name'], $_FILES['file']['type'], $_FILES['file']['name'])
    ];
    $ch = curl_init();

    $headersFile = array(
        'Content-Type: multipart/form-data',
        "Session-Token: $tokenSession",
        "App-token:$AppToken"
    );

    curl_setopt($ch, CURLOPT_URL, $url . '/apirest.php/Document/');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postDataDoc);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headersFile);
    $server_output = curl_exec($ch);
    $result = json_decode($server_output, true);
    // var_dump($result);
    if ($result[0] === 'ERROR_JSON_PAYLOAD_INVALID' || $result[0] === 'ERROR_GLPI_ADD') {
        echo "<h2>Erreur ajout Pièce jointe : </h2>" . $result[0];
        die();
    } else {
        echo "<h2>Pièce jointe ajouté</h2>";
    }
    curl_close($ch);
    $idDocument = $result['id'];

    // PUT :Mise à jour du document
    $ch = curl_init();

    $postDocument = '{"input": {"is_recursive":1, "tickets_id": ' . $idTicket . ' }}';

    curl_setopt($ch, CURLOPT_URL, $url . '/apirest.php/Document/' . $idDocument);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postDocument);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $server_output = curl_exec($ch);
    $server_output = json_decode($server_output, true);
    // var_dump($server_output);
    if ($server_output[0] === 'ERROR_JSON_PAYLOAD_INVALID' || $server_output[0] === 'ERROR_GLPI_ADD') {
        echo "<h2>Erreur ajout Mise à jour du document : </h2>" . $server_output[0];
        die();
    } else {
        echo "<h2>Mise à jour du document ajouté</h2>";
    }
    curl_close($ch);

    // Ajout dans la table de liaison Document_item pour faire le lien entre le document et le ticket
    $ch = curl_init();

    $postDataTableLiaison = '{"input": {"items_id":' . $idTicket . ', "itemtype":"Ticket", "is_recursive":1, "documents_id": ' . $idDocument . ' }}';

    curl_setopt($ch, CURLOPT_URL, $url . '/apirest.php/Ticket/' . $idTicket . '/Document_Item/');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postDataTableLiaison);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $server_output = curl_exec($ch);
    $server_output = json_decode($server_output, true);
    // var_dump($server_output);
    if ($server_output[0] === 'ERROR_JSON_PAYLOAD_INVALID' || $server_output[0] === 'ERROR_GLPI_ADD') {
        echo "<h2>Erreur ajout table de liaison : </h2>" . $server_output[0];
        die();
    } else {
        echo "<h2>Table de liaison ajouté</h2>";
    }
    curl_close($ch);
}

if ($errorAddTicket) {
    echo '<button style="display: block;margin: auto;"><a href="' . $url . '">Retour à l\'accueil</a></button>';
} else {
    Html::redirect($CFG_GLPI['root_doc'] . "/front/central.php");
}

Html::footer();
