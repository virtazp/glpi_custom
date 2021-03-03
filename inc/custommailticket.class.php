<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class CustomMailTicket extends CommonDBTM
{
    /**
     * Envoi d'un mail si la case urgence est cochée
     */
    public function SendMailTest($name, $firstname)
    {
        $mail = new PHPMailer(true);

        try {
            //Server settings
            //$mail->SMTPDebug = 2;  // Enable verbose debug output
            $mail->isSMTP();         // Set mailer to use SMTP
            $mail->CharSet = 'UTF-8';
            $mail->Host       = '';  // Specify main and backup SMTP servers
            $mail->SMTPAuth   = true;// Enable SMTP authentication
            $mail->Username   = '';  // SMTP username
            $mail->Password   = '';  // SMTP password
            $mail->SMTPSecure = 'tls';// Enable TLS encryption, `ssl` also accepted
            $mail->Port       = 587; // TCP port to connect to
            //Recipients
            $mail->setFrom('', '');
            $mail->addAddress('', '');     // Add a recipient

            // Content
            $mail->isHTML(true);                                  // Set email format to HTML
            $mail->Subject = 'Ticket urgent ajouté !';
            $mail->Body    = 'Un ticket urgent a été ouvert par ' . $name;
            $mail->AltBody = 'Un ticket urgent a été ouvert par ' . $name;

            $mail->send();
        } catch (Exception $e) {
            return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }

    /**
     * Cela va modifier dans la BDD, une valeur "use_notification" dans la table
     * glpi_tickets_users, ce qui permet le suivit ou non par mail
     */
    public function Notification($headers, $idTicket, $action, $url)
    {
        global $CFG_GLPI, $DB;

        // Récupération des éléments de la table de liaison glpi_tickets_users en fonction du ticket
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url . '/apirest.php/Ticket/' . $idTicket . '/Ticket_User');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $server_output = curl_exec($ch);
        $dataIdTicket = json_decode($server_output, true);
        // var_dump($dataIdTicket);
        if($dataIdTicket[0] === 'ERROR_JSON_PAYLOAD_INVALID' || $dataIdTicket[0] === 'ERROR_GLPI_ADD'){
            echo "<h2>Erreur récupération Notification : </h2>".$dataIdTicket[0];
            die();
        }else{
            
            if ($action === 'noactive') {
                echo "<h2>Notification pas activé</h2>";
            } else {
                echo "<h2>Notification activé</h2>";
            }
            //var_dump($dataIdTicket);
        }
        curl_close($ch);

        // Récupération de l'id de la première ligne du tableau reçu, car c'est le demandeur
        $idTicketUser = $dataIdTicket[0]["id"];

        // Mise à jour de la table glpi_tickets_users pour les notifications
        $post = '';
        if ($action === 'noactive') {
            $post = '{"input": {"use_notification" : 0}}';
            $intPost= 0;
        } else {
            $post = '{"input": {"use_notification" : 1}}';
            $intPost= 1;
        }

        $DB->query('UPDATE `glpi_tickets_users` SET `use_notification`='.$intPost.' WHERE `id`='.$idTicketUser.'');


        // $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, $url . '/apirest.php/Ticket/' . $idTicket . '/Ticket_User/' . $idTicketUser);
        // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // $server_output = curl_exec($ch);
        // $server_output = json_decode($server_output, true);
        // // var_dump($server_output);
        // if($server_output[0] === 'ERROR_JSON_PAYLOAD_INVALID' || $server_output[0] === 'ERROR_GLPI_ADD'){
        //     echo "<h2>Erreur ajout Notification : </h2>".$server_output[0];
        //     die();
        // }else{
        //     echo "<h2>Notification ajouté</h2>";
        //     var_dump($server_output);
        // }
        // curl_close($ch);
    }
}
