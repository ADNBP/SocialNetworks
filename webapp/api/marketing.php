<?php
use CloudFramework\Service\SocialNetworks\SocialNetworks;
use CloudFramework\Service\SocialNetworks\Marketing;

$api->checkMethod("GET,POST,PUT");  // allowed methods to receive GET,POST etc..

// Check available Networks configured
if(!$api->error) {

    $networks =
        [
            "facebook"=>["available"=>$this->getConf("FacebookOauth") && strlen($this->getConf("FacebookOauth_CLIENT_ID")) && strlen($this->getConf("FacebookOauth_CLIENT_SECRET"))
                ,"active"=>$this->getConf("FacebookOauth")
                ,"client_id"=>(strlen($this->getConf("FacebookOauth_CLIENT_ID")))?$this->getConf("FacebookOauth_CLIENT_ID"):null
                ,"client_secret"=>(strlen($this->getConf("FacebookOauth_CLIENT_SECRET")))?$this->getConf("FacebookOauth_CLIENT_SECRET"):null
                ,"client_scope"=>(is_array($this->getConf("FacebookOauth_SCOPE"))) && (count($this->getConf("FacebookOauth_SCOPE")) > 0)?$this->getConf("FacebookOauth_SCOPE"):null
            ],
        ];
}

// The structure of the API call will be: (socialnetwork|status)/{verb}
// Check parameters and check if the social network is available..
$api->checkMandatoryParam(0,"Missing first parameter");
if(!$api->error && ($api->params[0] != "status" || $api->method!= "GET")) {
    $api->checkMandatoryParam(1, "The API requires a second parameter");
    if(!$api->error) {
        $api->params[0] = strtolower($api->params[0]);
        if(!(array_key_exists($api->params[0],$networks) && $networks[$api->params[0]]["available"])) {
            $api->setError($api->params[0]." is not available");
        }
    }
}

$value =[];

// Get Social network object and credentials from Session.
$credentials = $_SESSION["params_socialnetworks"];
$sc = SocialNetworks::getInstance();
$mkt = Marketing::getInstance();

if(!$api->error) {
    if($api->params[0] != "status") {
        try {
            $sc->setApiKeys($api->params[0], $networks[$api->params[0]]["client_id"],
                $networks[$api->params[0]]["client_secret"],
                $networks[$api->params[0]]["client_scope"]);

            $mkt->setApiKeys($api->params[0], $networks[$api->params[0]]["client_id"],
                $networks[$api->params[0]]["client_secret"]);
        } catch (\Exception $e) {
            $api->setError($e->getMessage());
        }

        if(!$api->error && $api->params[1] != "auth") {
            if(!is_array($credentials[$api->params[0]])) {
                $api->setError("Please, assign credentials to ".$api->params[0]);
            }

            if(!$api->error) {
                try {
                    $sc->setAccessToken($api->params[0], $credentials[$api->params[0]]);
                    $mkt->setAccessToken($api->params[0], $credentials[$api->params[0]]);
                } catch (\Exception $e) {
                    $api->setError($e->getMessage());
                }
            }
        }

    }
}

// END POINTS START HERE
if(!$api->error) {
    switch($api->method) {
        // GET END POINTS
        case "GET":
            switch($api->params[0]) {
                case "status":
                    $value["credentials"] = $credentials;
                    $value["networks"] = $networks;
                    break;
                // The rest of social networks.
                default:
                    switch ($api->params[1]) {
                        // Auth into a SOCIAL NETWORK and show the credentials in the social network
                        case "auth":
                            $redirectUrl = SocialNetworks::generateRequestUrl() . "api/socialnetworks/" .
                                $api->params[0] . "/auth/endcallback";

                            if ($api->params[2] == "endcallback") {
                                $code = $_GET["code"];

                                try {
                                    $value = $sc->confirmAuthorization($api->params[0], $code, $oauthVerifier, $redirectUrl);
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            } else {
                                $authUrl = "";
                                try {
                                    $authUrl = $sc->requestAuthorization($api->params[0], $redirectUrl);
                                    header("Location: " . $authUrl);
                                    exit;
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            }
                            break;
                        // Check SOCIAL NETWORKS Credentials
                        // Just get profile to check if credentials are ok
                        case "check":
                            if ("facebook" === $api->params[0]) {
                                try {
                                    $profile = $sc->checkCredentials($api->params[0], array(
                                        "access_token" => $credentials[$api->params[0]]["access_token"]
                                    ));
                                    $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"] = $profile["user_id"];
                                    $value = $_SESSION["params_socialnetworks"][$api->params[0]];
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            }
                            break;
                        case "user":
                            switch($api->params[2]) {
                                case "adaccount":
                                    switch($api->params[3]) {
                                        case "current":
                                            try {
                                                $value = $mkt->getCurrentUserAdAccount($api->params[0]);
                                            } catch (\Exception $e) {
                                                $api->setError($e->getMessage());
                                            }
                                            break;
                                    }
                                    break;
                            }
                            break;
                        case "campaign":
                            switch($api->params[3]) {
                                case "info":
                                    try {
                                        $value = $mkt->getCampaign($api->params[0], $api->params[2]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                case "delete":
                                    try {
                                        $value = $mkt->deleteCampaign($api->params[0], $api->params[2]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                            }
                            break;
                    }
                    break;
            }
            break;
        // POST END POINTS
        case "POST":
            switch($api->params[1]) {
                // Save SOCIAL NETWORK in session
                case "auth":
                    $_SESSION["params_socialnetworks"][$api->params[0]] = $api->formParams;
                    $value = $_SESSION["params_socialnetworks"][$api->params[0]];
                    break;
                case "adaccount":
                    switch ($api->params[3]) {
                        case "create":
                            switch ($api->params[4]) {
                                case "campaign":
                                    try {
                                        $parameters = array();
                                        $parameters["name"] = $api->formParams["name"];
                                        if (isset($api->formParams["objective"])) {
                                            $parameters["objective"] = $api->formParams["objective"];
                                        }
                                        if (isset($api->formParams["status"])) {
                                            $parameters["status"] = $api->formParams["status"];
                                        }
                                        $value = $mkt->createCampaign(
                                            $api->params[0], $api->params[2], $parameters
                                        );
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                            }
                    }
                    break;
                case "targeting":
                    switch($api->params[2]) {
                        case "geocoding":
                            switch($api->params[3]) {
                                case "search":
                                    try {
                                        $value = $mkt->searchGeolocationCode($api->params[0],
                                            $api->formParams["type"], $api->formParams["text"]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                            }
                            break;
                    }
                    break;
            }
            break;
    }
}

$api->addReturnData($value);