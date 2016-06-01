<?php
use CloudFramework\Service\SocialNetworks\Ecommerce;

$api->checkMethod("GET,POST,PUT");  // allowed methods to receive GET,POST etc..

// Check available Networks configured
if(!$api->error) {

    $platforms =
        [
            "shopify"=>["available"=>$this->getConf("ShopifyOauth") && strlen($this->getConf("ShopifyOauth_CLIENT_ID")) && strlen($this->getConf("ShopifyOauth_CLIENT_SECRET"))
                ,"active"=>$this->getConf("ShopifyOauth")
                ,"client_id"=>(strlen($this->getConf("ShopifyOauth_CLIENT_ID")))?$this->getConf("ShopifyOauth_CLIENT_ID"):null
                ,"client_secret"=>(strlen($this->getConf("ShopifyOauth_CLIENT_SECRET")))?$this->getConf("ShopifyOauth_CLIENT_SECRET"):null
                ,"client_scope"=>(is_array($this->getConf("ShopifyOauth_SCOPE"))) && (count($this->getConf("ShopifyOauth_SCOPE")) > 0)?$this->getConf("ShopifyOauth_SCOPE"):null
                ,"client_shop_domain"=>(strlen($this->getConf("ShopifyOauth_SHOP_DOMAIN")))?$this->getConf("ShopifyOauth_SHOP_DOMAIN"):null
            ],
        ];
}

// The structure of the API call will be: (ecommerce|status)/{verb}
// Check parameters and check if the ecommerce platform is available..
$api->checkMandatoryParam(0,"Missing first parameter");
if(!$api->error && ($api->params[0] != "status" || $api->method!= "GET")) {
    $api->checkMandatoryParam(1, "The API requires a second parameter");
    if(!$api->error) {
        $api->params[0] = strtolower($api->params[0]);
        if(!(array_key_exists($api->params[0],$platforms) && $platforms[$api->params[0]]["available"])) {
            $api->setError($api->params[0]." is not available");
        }
    }
}

$value =[];

// Get Ecommerce platform object and credentials from Session.
$credentials = $_SESSION["params_ecommerce_platforms"];
$ecommerce = Ecommerce::getInstance();

if(!$api->error) {
    if($api->params[0] != "status") {
        try {
            $ecommerce->setApiKeys($api->params[0], $platforms[$api->params[0]]["client_id"],
                $platforms[$api->params[0]]["client_secret"],
                $platforms[$api->params[0]]["client_scope"],
                $platforms[$api->params[0]]["client_shop_domain"]);
        } catch (\Exception $e) {
            $api->setError($e->getMessage());
        }

        if(!$api->error && $api->params[1] != "auth") {
            if(!is_array($credentials[$api->params[0]])) {
                $api->setError("Please, assign credentials to ".$api->params[0]);
            }

            if(!$api->error) {
                try {
                    $ecommerce->setAccessToken($api->params[0], $credentials[$api->params[0]]);
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
                    $value["platforms"] = $platforms;
                    break;
                // The rest of ecommerce platforms.
                default:
                    switch ($api->params[1]) {
                        // Auth into an ecommerce platform and show the credentials in the ecommerce platform
                        case "auth":
                            $redirectUrl = Ecommerce::generateRequestUrl() . "api/ecommerce/" .
                                $api->params[0] . "/auth/endcallback";

                            if ($api->params[2] == "endcallback") {
                                $code = $_GET["code"];

                                try {
                                    $value = $ecommerce->confirmAuthorization($api->params[0], $code);
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            } else {
                                $authUrl = "";
                                try {
                                    $authUrl = $ecommerce->requestAuthorization($api->params[0], $redirectUrl);
                                    header("Location: " . $authUrl);
                                    exit;
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            }
                            break;
                        // Check Ecommerce platforms Credentials
                        // Just get profile to check if credentials are ok
                        case "check":
                            try {
                                $profile = $ecommerce->checkCredentials($api->params[0], array(
                                    "access_token" => $credentials[$api->params[0]]["access_token"]
                                ));
                                $_SESSION["params_ecommerce_platforms"][$api->params[0]]["user_id"] = $profile["user_id"];
                                $value = $_SESSION["params_ecommerce_platforms"][$api->params[0]];
                            } catch (\Exception $e) {
                                $api->setError($e->getMessage());
                            }
                            break;
                        // Export
                        case "export":
                            switch($api->params[2]) {
                                case "product":
                                    if ("all" === $api->params[3]) {
                                        try {
                                            $value = $ecommerce->exportAllProducts($api->params[0], $api->params[3],
                                                $api->params[4]);
                                        } catch (\Exception $e) {
                                            $api->setError($e->getMessage());
                                        }
                                    } else {
                                        try {
                                            $value = $ecommerce->exportProducts($api->params[0], $api->params[3],
                                                $api->params[4]);
                                        } catch (\Exception $e) {
                                            $api->setError($e->getMessage());
                                        }
                                    }
                                    break;
                                case "collection":
                                    // List of products in a collection
                                    if ("product" === $api->params[4]) {
                                        try {
                                            $value = $ecommerce->exportProducts($api->params[0], $api->params[5],
                                                $api->params[6], $api->params[3]);
                                        } catch (\Exception $e) {
                                            $api->setError($e->getMessage());
                                        }
                                    // List of collections
                                    } else {
                                        try {
                                            $value = $ecommerce->exportCollections($api->params[0], $api->params[3], $api->params[4]);
                                        } catch (\Exception $e) {
                                            $api->setError($e->getMessage());
                                        }
                                    }
                                    break;
                            }
                            break;
                        case "shop":
                            switch($api->params[2]) {
                                case "info":
                                    try {
                                        $value = $ecommerce->getShop($api->params[0]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                case "shipping":
                                    try {
                                        $value = $ecommerce->getShopShippingZones($api->params[0]);
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
                // Save E-COMMERCE PLATFORM in session
                case "auth":
                    $_SESSION["params_ecommerce_platforms"][$api->params[0]] = $api->formParams;
                    $value = $_SESSION["params_ecommerce_platforms"][$api->params[0]];
                    break;
                case "create":
                    switch($api->params[2]) {
                        case "product":
                            try {
                                $params = array(
                                    "title"         =>      $api->formParams["title"],
                                    "body_html"     =>      $api->formParams["body_html"],
                                    "vendor"        =>      $api->formParams["vendor"],
                                    "product_type"  =>      $api->formParams["product_type"],
                                    "published"     =>      $api->formParams["published"],
                                    "images"        =>      $api->formParams["images"],
                                    "variants"      =>      $api->formParams["variants"],
                                    "options"       =>      $api->formParams["options"]
                                );
                                $value = $ecommerce->createProduct($api->params[0], $params);
                            } catch (\Exception $e) {
                                $api->setError($e->getMessage());
                            }
                            break;
                    }
            }
            break;
    }
}

$api->addReturnData($value);