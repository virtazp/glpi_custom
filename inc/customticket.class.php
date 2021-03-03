<?php

//define('GLPI_USE_CSRF_CHECK', 0);

class CustomTicket
{
    protected $dev = false;
    protected $urlTarget = '';
    protected $AppToken = '';

    function __construct()
    {
        if ($this->dev) {
            $this->urlTarget = '';
            $this->AppToken = '';
        } else {
            $this->urlTarget = '';
            $this->AppToken = '';
        }
    }

    protected $token = '';
    protected $tokenSession = '';
    protected $nameUser = '';
    protected $firstnameUser = '';
    protected $mailUser = '';
    protected $IdUser = '';

    /** Récupération du token utilisateur pour les futurs requêtes 
     * Penser à générer les jetons utilisateurs dans glpi avant 
     */
    public function RecuperationToken()
    {
        $IdUser =  Session::getLoginUserID();
        $this->IdUser = $IdUser;

        $user = new User();
        $userfind = $user->find(['id' => $IdUser]);

        $this->token = $userfind[$IdUser]['api_token'];
        $this->nameUser = $userfind[$IdUser]['name'];
        $this->firstnameUser = $userfind[$IdUser]['firstname'];

        $this->Connexion();
    }

    /**
     * Demandez un jeton de session pour utiliser d'autres points de terminaison d'API
     */
    private function Connexion()
    {
        // Init de la requête
        $ch = curl_init();
        // token récupéré précédemment 
        $headers = array(
            'Content-Type: application/json',
            "Authorization: user_token " . $this->token,
            'App-token:' . $this->AppToken
        );
        // Envoit de la requête
        curl_setopt($ch, CURLOPT_URL, $this->urlTarget . '/apirest.php/initSession');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $server_output = curl_exec($ch);

        curl_close($ch);
        // Split de la réponse en tableau, prendre 3eme élément (tokenSession)
        $data = preg_split('#["]#', $server_output);

        $this->tokenSession = $data[3];
        $this->AffichageFormulaire();
    }

    private function AffichageFormulaire()
    {
        // Code repris à partir de ticket.php pour la gestion du header et du footer
        include('../inc/includes.php');
        // vérifie si l'utilisateur est bien connecté
        Session::checkLoginUser();
        // Condition qui vérifie si affichage simplifié (Helpdesk) ou normal et qui affiche le header en fonction
        if (Session::getCurrentInterface() == "helpdesk") {
            Html::helpHeader(Ticket::getTypeName(Session::getPluralNumber()), '', $_SESSION["glpiname"]);
        } else {
            Html::header(Ticket::getTypeName(Session::getPluralNumber()), '', "helpdesk", "ticket");
        }
        echo "<h2 style='text-align:center'>Avez-vous pensé à regarder la <a target='_blank' href='' style='color:red;font-size: 1em;font-weight: bold;'>base de connaissance</a> ?</h2>";
        if ($this->dev) {
            echo '<form action="/glpi/front/customTicket.php" method="post" id="ticketDemande" name="helpdeskform" enctype="multipart/form-data">';
        } else {
            echo '<form action="/front/customTicket.php" method="post" id="ticketDemande" name="helpdeskform" enctype="multipart/form-data">';
        }

        // Début du formulaire

        echo '<table class="tab_cadre_fixe" id="mainformtable">';
        echo '<tbody>';
        echo '<tr class="headerRow responsive_hidden">';
        echo '<th colspan=5 >Formulaire de demande</th>';
        echo '</tr>';
        echo '<tr class="urgence formAPI tab_bg_1">';
        echo '<th><label for="urgence"><span><img width=20px; id="img-urgent" src="/plugins/azure/images/urgent.png" alt="urgent"></span> Urgence</label></th>';
        echo '<td><input type="checkbox" name="urgence" id="urgence"></td>';
        echo '<th><label for="mail"> Suivi du ticket par mail</label></th>';
        echo '<td><input type="checkbox" name="mail" id="mail"></td>';
        echo '</tr>';
        echo '<tr class="mail formAPI tab_bg_1">';
        echo '<th><label for="important"><span><img width=20px; id="img-important" src="/plugins/azure/images/important.png" alt="important"></span> Important</label></th>';
        echo '<td><input type="checkbox" name="important" id="important"></td>';
        echo '</tr>';
        echo '<tr class="titre formAPI tab_bg_1">';
        echo '<td colspan=4 ><input type="text" name="titre" id="titre" placeholder="Titre" required></td>';
        echo '</tr>';
        echo '<tr class="description formAPI tab_bg_1">';
        echo '<td colspan=4 ><textarea type="textarea" name="description" id="description" placeholder="Merci de saisir la description de votre demande avec le maximum de détails possibles, ex: date et heure du problème, fréquence..." required></textarea></td>';
        echo '</tr>';
        echo '<tr class="deadline formAPI tab_bg_1">';
        echo '<th><label for="deadline">Deadline : </label></th>';
        echo '<td><input type="date" name="deadline" id="deadline"></td>';
        echo '</tr>';
        // Suivi du token session dans la page de traitement
        echo '<input type="hidden" name="nameUser" id="nameUser" value="' . $this->nameUser . '">';
        echo '<input type="hidden" name="firstnameUser" id="firstnameUser" value="' . $this->firstnameUser . '">';
        echo '<input type="hidden" name="tokenSession" id="tokenSession" value="' . $this->tokenSession . '">';
        echo '<input type="hidden" name="urlTarget" id="urlTarget" value="' . $this->urlTarget . '">';
        echo '<input type="hidden" name="AppToken" id="AppToken" value="' . $this->AppToken . '">';

        // Pour faire le lien entre l'utilisateur et ses devices
        $computers = $this->GetComputersUser($this->IdUser);

        // echo '<tr class="elements formAPI tab_bg_1" style="display:none">';
        // echo '<th><label for="elements">Mes élements : </label></th>';
        // echo '<td><select name="elements" id="elements">';
        // echo '<option value="none">-- Ordinateurs --</option>';
        // if (!empty($computers)) {
        //     for ($i = 0; $i < count($computers); $i++) {

        //         echo "<option value=" . $computers[$i]['id'] . ">" . $computers[$i]['name'] . "</option>";
        //     }
        // } else {
        //     echo "<option value='none'>   ---   </option>";
        // }

        // echo ' </select></td>';
        // echo '</tr>';
        echo '<tr class="file formAPI tab_bg_1">';
        echo '<th><label for="file">Pièce jointe : </label></th>';
        echo '<td><input type="file" name="file" id="file"></td>';
        echo '</tr>';
        echo '<tr class="formAPI btnForm tab_bg_1">';
        echo '<td colspan=4 ><input type="submit" value="Valider" class="myButton submit"><td>';
        echo '</tr>';
        echo '</tbody>';
        echo '</table>';
        echo '</form>';
        echo "<h2 style='text-align:center'>Découvrez le processus de gestion des demandes <a target='_blank' href='' style='color:red;font-size: 1em;font-weight: bold;'>ici</a></h2>";

        echo '<div id="text-urgent" class="popup-aide" style="display:none;"><span id="span-text-urgent" class="popup-aide-class">Le problème est bloquant, l\'utilisateur ne peut plus avancer dans son travail </span></div>';
        echo '<div id="text-important" class="popup-aide" style="display:none;"><span id="span-text-important" class="popup-aide-class">La demande a une haute valeur ajoutée dans le travail des utilisateurs </span></div>';

        echo '<script src="/plugins/azure/script.js"></script>';

        if (Session::getCurrentInterface() == "helpdesk") {
            Html::helpFooter();
        } else {
            Html::footer();
        }
    }

    private function GetComputersUser($IdUser)
    {
        $ch = curl_init();

        $headers = array(
            'Content-Type: application/json',
            "Session-Token:$this->tokenSession",
            'App-token:' . $this->AppToken
        );
        curl_setopt($ch, CURLOPT_URL, $this->urlTarget . '/apirest.php/User/' . $IdUser . '/Computer');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $server_output = curl_exec($ch);
        curl_close($ch);

        $Items = json_decode($server_output, true);
        return $Items;
    }
}
