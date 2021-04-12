<?php

// Classe gérant les exceptions
//
class ViessmannApiException extends Exception
{
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

// Classe gérant l'accès au serveur Viessmann
//
class ViessmannApi
{
    const VICARE = true;

    const AUTHORIZE_URL = "https://iam.viessmann.com/idp/v2/authorize";
    const CALLBACK_URI = "http://localhost:4200/";
    
    const TOKEN_URL = "https://iam.viessmann.com/idp/v2/token";

    const IDENTITY_URL = "https://api.viessmann.com/users/v1/users/me?sections=identity";
    
    const GATEWAY_URL = "https://api.viessmann.com/iot/v1/equipment/gateways";

    const FEATURES_URL = "https://api.viessmann.com/iot/v1/equipment";
    
    // Les paramètres d'accès au serveur
    //
    //   Obligatoires
    //
    private $clientId;
    private $codeChallenge;
    private $user;
    private $pwd;
    //
    // Facultatifs
    //
    private $installationId;
    private $serial;
    private $deviceId;
    private $circuitId;
    //
    // Gestion du token
    //
    private $token;
    private $expires_at;
    private $expires_in;
    private $if_new_token;

    //
    // Données récupérées du serveur Viessmann
    //
    private $identity;
    private $gateway;
    private $features;

    // Constructeur
    //
    public function __construct($params)
    {

        // Contrôle des paramètres et mémorisation dans la classe
        //
        if (!array_key_exists('clientId', $params)) {
            throw new ViessmannApiException('Id client obligatoire', 2);
            return;
        }
        if (empty($params['clientId'])) {
            throw new ViessmannApiException('Id client obligatoire', 2);
            return;
        }
        $this->clientId = $params['clientId'];

        if (!array_key_exists('codeChallenge', $params)) {
            throw new ViessmannApiException('Code challenge obligatoire', 2);
            return;
        }
        if (empty($params['codeChallenge'])) {
            throw new ViessmannApiException('Code challenge obligatoire', 2);
            return;
        }
        $this->codeChallenge = $params['codeChallenge'];
            
        if (self::VICARE == true) {
            $this->clientId = '79742319e39245de5f91d15ff4cac2a8';
            $this->codeChallenge = '8ad97aceb92c5892e102b093c7c083fa';
        }
            
        if (!array_key_exists('user', $params)) {
            throw new ViessmannApiException('Nom utilisateur obligatoire', 2);
            return;
        }
        if (empty($params['user'])) {
            throw new ViessmannApiException('Nom utilisateur obligatoire', 2);
            return;
        }
        $this->user = $params['user'];

        if (!array_key_exists('pwd', $params)) {
            throw new ViessmannApiException('Mot de passe obligatoire', 2);
            return;
        }
        if (empty($params['pwd'])) {
            throw new ViessmannApiException('Mot de passe obligatoire', 2);
            return;
        }
        $this->pwd = $params['pwd'];

        if (!array_key_exists('installationId', $params)) {
            $this->installationId = '';
        } else {
            $this->installationId = trim($params['installationId']);
        }

        if (!array_key_exists('serial', $params)) {
            $this->serial = '';
        } else {
            $this->serial = trim($params['serial']);
        }

        if (!array_key_exists('deviceId', $params)) {
            $this->deviceId = 0;
        } else {
            $this->deviceId = trim($params['deviceId']);
        }

        if (!array_key_exists('circuitId', $params)) {
            $this->circuitId = 0;
        } else {
            $this->circuitId = trim($params['circuitId']);
        }

        if (!array_key_exists('token', $params)) {
            $this->token = '';
        } else {
            $this->token = trim($params['token']);
        }

        if (!array_key_exists('expires_at', $params)) {
            $this->expires_at = 0;
        } else {
            $this->expires_at = intval($params['expires_at']);
        }

        $this->identity = array();
        $this->gateway = array();
        $this->features = array();
        
        // Si c'est possible on réutilise l'ancien token
        //
        $this->if_new_token = false;

        if ((time() <= $this->expires_at) && !empty($this->token) && !empty($this->installationId) && !empty($this->serial)) {
            return;
        }

        $code = $this->getCode();
        if ($code == false) {
            throw new ViessmannApiException("Erreur acquisition code sur le serveur Viessmann", 2);
        }
        
        $return = $this->getToken($code);
        if ($return == false) {
            throw new ViessmannApiException("Erreur acquisition token sur le serveur Viessmann", 2);
        }

        $this->if_new_token = true;

        if (empty($this->installationId) || empty($this->serial)) {
            $this->getGateway();
            $this->getIdentity();
            $this->installationId = $this->getInstallationId();
            $this->serial = $this->getSerial();
        }
    }

    // Lire le code d'accès au serveur Viessmann
    //
    private function getCode() : string
    {
        // Paramètres code
        //
        if (self::VICARE == true) {
            $url = self::AUTHORIZE_URL . "?client_id=" . $this->clientId . "&scope=openid&redirect_uri=vicare://oauth-callback/everest" .
            "&response_type=code";
        } else {
            $url = self::AUTHORIZE_URL . "?client_id=" . $this->clientId . "&code_challenge=" . $this->codeChallenge . "&scope=IoT%20User&redirect_uri=" .
            self::CALLBACK_URI . "&response_type=code";
        }
        $header = array("Content-Type: application/x-www-form-urlencoded");

        $curloptions = array(
           CURLOPT_URL => $url,
           CURLOPT_HTTPHEADER => $header,
           CURLOPT_SSL_VERIFYPEER => false,
           CURLOPT_RETURNTRANSFER => true,
           CURLOPT_USERPWD => $this->user.':'.$this->pwd,
           CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
           CURLOPT_POST => true,
        );

        // Appel Curl Code
        //
        $curl = curl_init();
        curl_setopt_array($curl, $curloptions);
        $response = curl_exec($curl);
        curl_close($curl);

        // Extraction Code
        //
        $matches = array();
        $pattern = '/code=(.*)"/';
        if (preg_match_all($pattern, $response, $matches)) {
            return $matches[1][0];
        } else {
            return false;
        }
    }

    // Lire le token d'accès au serveur Viessmann
    //
    private function getToken($code) : string
    {
        // Paramètres Token
        //
        if (self::VICARE == true) {
            $url = self::TOKEN_URL . "?grant_type=authorization_code&client_id=" .
          $this->clientId . "&client_secret=" . $this->codeChallenge .  "&redirect_uri=vicare://oauth-callback/everest&code=" . $code;
        } else {
            $url = self::TOKEN_URL . "?grant_type=authorization_code&code_verifier=" . $this->codeChallenge . "&client_id=" .
          $this->clientId . "&redirect_uri=" . self::CALLBACK_URI . "&code=" . $code;
        }
        $header = array("Content-Type: application/x-www-form-urlencoded");

        $curloptions = array(
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_POST => true,
        );

        // Appel Curl Token
        //
        $curl = curl_init();
        curl_setopt_array($curl, $curloptions);
        $response = curl_exec($curl);
        curl_close($curl);

        // Extraction Token
        //
        $json = json_decode($response, true);
        if (array_key_exists('error', $json)) {
            return false;
        }

        if (!array_key_exists('access_token', $json) || !array_key_exists('expires_in', $json)) {
            return false;
        }
        $this->token = $json['access_token'];
        $this->expires_in = $json['expires_in'];

        return true;
    }

    // Lire les données d'identité
    //
    public function getIdentity()
    {
        // Lire les données utilisateur
        //
        $url = self::IDENTITY_URL;
        $header = array("Authorization: Bearer " . $this->token);

        $curloptions = array(
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        );

        // Appel Curl Données
        //
        $curl = curl_init();
        curl_setopt_array($curl, $curloptions);
        $response = curl_exec($curl);
        curl_close($curl);

        $this->identity = json_decode($response, true);

        $json_file = __DIR__ . '/../../data/identity.json';
        file_put_contents($json_file, $response);

    }

    // Lire les données du gateway
    //
    public function getGateway()
    {

        // Lire les données du gateway
        //
        $url = self::GATEWAY_URL;
        $header = array("Authorization: Bearer " . $this->token);

        $curloptions = array(
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        );

        // Appel Curl Données
        //
        $curl = curl_init();
        curl_setopt_array($curl, $curloptions);
        $response = curl_exec($curl);
        curl_close($curl);

        $this->gateway = json_decode($response, true);
        $json_file = __DIR__ . '/../../data/gateway.json';
        file_put_contents($json_file, $response);

        if (array_key_exists('statusCode', $this->gateway)) {
            throw new ViessmannApiException($this->gateway["message"], 2);
        }
    }

    // Lire les features
    //
    public function getFeatures()
    {
        // Lire les données features
        //
        $url = self::FEATURES_URL . "/installations/" . $this->installationId . "/gateways/" . $this->serial . "/devices/" . $this->deviceId . "/features";
        $header = array("Authorization: Bearer " . $this->token);

        $curloptions = array(
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        );

        // Appel Curl Données
        //
        $curl = curl_init();
        curl_setopt_array($curl, $curloptions);
        $response = curl_exec($curl);
        curl_close($curl);

        $this->features = json_decode($response, true);
        $json_file = __DIR__ . '/../../data/features.json';
        file_put_contents($json_file, $response);

        if (array_key_exists('statusCode', $this->features)) {
            throw new ViessmannApiException($this->features["message"], 2);
        }
    }

    // Ecrire une feature
    //
    public function setFeature($feature, $action, $data)
    {

        // Lire les données du gateway
        //
        $url = self::FEATURES_URL . "/installations/" . $this->installationId . "/gateways/" . $this->serial . "/devices/" . $this->deviceId . "/features/" . $feature . "/commands/" . $action;

        $header = array(
            "Content-Type: application/json",
            "Accept : application/vnd.siren+json",
            "Authorization: Bearer " . $this->token);
 
        $curloptions = array(
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
        );

        // Appel Curl Données
        //
        $curl = curl_init();
        curl_setopt_array($curl, $curloptions);
        $response = curl_exec($curl);
        curl_close($curl);

        $features = json_decode($response, true);

        if (array_key_exists('statusCode', $features)) {
            throw new ViessmannApiException($features["message"], 2);
        }
    }

    // Lire Installation Id
    //
    public function getInstallationId()
    {
        return $this->gateway["data"][0]["installationId"];
    }

    // Lire Login Id
    //
    public function getSerial()
    {
        return $this->gateway["data"][0]["serial"];
    }

    // Si nouveau token
    //
    public function isNewToken()
    {
        return $this->if_new_token;
    }

    // Get Token
    //
    public function getNewToken()
    {
        return $this->token;
    }

    // Expires In
    //
    public function getExpiresIn()
    {
        return $this->expires_in;
    }

    // Get Array Identity
    //
    public function getArrayIdentity()
    {
        return $this->identity;
    }

    // Get Array Gateway
    //
    public function getArrayGateway()
    {
        return $this->gateway;
    }

    // Get Array Features
    //
    public function getArrayFeatures()
    {
        return $this->features;
    }
}
