<?php
use CloudFramework\Service\SocialNetworks\SocialNetworks;

$api->checkMethod('GET,POST,PUT');  // allowed methods to receive GET,POST etc..

// Check available Networks configured
if(!$api->error) {

    $networks =
        ['google'=>['available'=>$this->getConf('GoogleOauth') && strlen($this->getConf('GoogleOauth_CLIENT_ID')) && strlen($this->getConf('GoogleOauth_CLIENT_SECRET'))
            ,'active'=>$this->getConf('GoogleOauth')
            ,'client_id'=>(strlen($this->getConf('GoogleOauth_CLIENT_ID')))?$this->getConf('GoogleOauth_CLIENT_ID'):null
            ,'client_secret'=>(strlen($this->getConf('GoogleOauth_CLIENT_SECRET')))?$this->getConf('GoogleOauth_CLIENT_SECRET'):null
            ,'client_scope'=>(is_array($this->getConf('GoogleOauth_SCOPE'))) && (count($this->getConf('GoogleOauth_SCOPE')) > 0)?$this->getConf('GoogleOauth_SCOPE'):null
        ],
            'facebook'=>['available'=>$this->getConf('FacebookOauth') && strlen($this->getConf('FacebookOauth_CLIENT_ID')) && strlen($this->getConf('FacebookOauth_CLIENT_SECRET'))
                ,'active'=>$this->getConf('FacebookOauth')
                ,'client_id'=>(strlen($this->getConf('FacebookOauth_CLIENT_ID')))?$this->getConf('FacebookOauth_CLIENT_ID'):null
                ,'client_secret'=>(strlen($this->getConf('FacebookOauth_CLIENT_SECRET')))?$this->getConf('FacebookOauth_CLIENT_SECRET'):null
                ,'client_scope'=>(is_array($this->getConf('FacebookOauth_SCOPE'))) && (count($this->getConf('FacebookOauth_SCOPE')) > 0)?$this->getConf('FacebookOauth_SCOPE'):null
            ],
            'instagram'=>['available'=>$this->getConf('InstagramOauth') && strlen($this->getConf('InstagramOauth_CLIENT_ID')) && strlen($this->getConf('InstagramOauth_CLIENT_SECRET'))
                ,'active'=>$this->getConf('InstagramOauth')
                ,'client_id'=>(strlen($this->getConf('InstagramOauth_CLIENT_ID')))?$this->getConf('InstagramOauth_CLIENT_ID'):null
                ,'client_secret'=>(strlen($this->getConf('InstagramOauth_CLIENT_SECRET')))?$this->getConf('InstagramOauth_CLIENT_SECRET'):null
                ,'client_scope'=>(is_array($this->getConf('InstagramOauth_SCOPE'))) && (count($this->getConf('InstagramOauth_SCOPE')) > 0)?$this->getConf('InstagramOauth_SCOPE'):null
            ],
            'twitter'=>['available'=>$this->getConf('TwitterOauth') && strlen($this->getConf('TwitterOauth_KEY')) && strlen($this->getConf('TwitterOauth_SECRET'))
                ,'active'=>$this->getConf('TwitterOauth')
                ,'client_id'=>(strlen($this->getConf('TwitterOauth_KEY')))?"****":"missing"
                ,'client_secret'=>(strlen($this->getConf('TwitterOauth_SECRET')))?"****":"missing"
            ],
            'vkontakte'=>['available'=>$this->getConf('VKontakteOauth') && strlen($this->getConf('VKontakteOauth_APP_ID')) && strlen($this->getConf('VKontakteOauth_APP_ID'))
                ,'active'=>$this->getConf('VKontakteOauth')
                ,'client_id'=>(strlen($this->getConf('VKontakteOauth_APP_ID')))?"****":"missing"
                ,'client_secret'=>(strlen($this->getConf('VKontakteOauth_APP_SECRET')))?"****":"missing"
            ]
        ];
}

// The structure of the API call will be: (socialnetwork|status)/{verb}
// Check parameters and check if the social network is available..
$api->checkMandatoryParam(0,'Missing first parameter');
if(!$api->error && ($api->params[0] != 'status' || $api->method!= 'GET')) {
    $api->checkMandatoryParam(1, 'The API requires a second parameter');
    if(!$api->error) {
        $api->params[0] = strtolower($api->params[0]);
        if(!(array_key_exists($api->params[0],$networks) && $networks[$api->params[0]]['available'])) {
            $api->setError($api->params[0].' is not available');
        }
    }
}

$value =[];

// Get Social network object and credentials from Session.
$credentials = $_SESSION['params_socialnetworks'];
$sc = SocialNetworks::getInstance();

if(!$api->error) {
    if($api->params[0] != 'status') {
        try {
            $sc->setApiKeys($api->params[0], $networks[$api->params[0]]["client_id"],
                $networks[$api->params[0]]["client_secret"],
                $networks[$api->params[0]]["client_scope"]);
        } catch (\Exception $e) {
            $api->setError($e->getMessage());
        }

        if(!$api->error && $api->params[1] != 'auth') {
            if(!is_array($credentials[$api->params[0]])) {
                $api->setError('Please, assign credentials to '.$api->params[0]);
            }

            if(!$api->error) {
                try {
                    $sc->setAccessToken($api->params[0], $credentials[$api->params[0]]);
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
        case "GET":
            switch($api->params[0]) {
                case "status":
                    $value['credentials'] = $credentials;
                    $value['networks'] = $networks;
                    break;
                // The rest of social networks.
                default:
                    switch($api->params[1]) {
                        // Auth into a SOCIAL NETWORK and show the credentials in the social network
                        case "auth":
                            $redirectUrl = SocialNetworks::generateRequestUrl() . "api/socialnetworks/" .
                                $api->params[0] . "/auth/endcallback";

                            if ($api->params[2] == 'endcallback') {
                                $code = $_GET["code"];

                                try {
                                    $value = $sc->confirmAuthorization($api->params[0], $code, $redirectUrl);
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
                        // Check if GOOGLE Credentials are expired or revoked; if not, return credentials +
                        // expiry time updated + // user id
                        case "check":
                            if ("google" === $api->params[0]) {
                                try {
                                    $profile = $sc->checkCredentials($api->params[0], array(
                                        "access_token" => $credentials[$api->params[0]]["access_token"],
                                        "refresh_token" => $credentials[$api->params[0]]["refresh_token"],
                                        "id_token" => $credentials[$api->params[0]]["id_token"]
                                    ));
                                    $value = $_SESSION['params_socialnetworks'][$api->params[0]];
                                    $value["expires_in"] = $profile["expires_in"];
                                    $value["user_id"] = $profile["user_id"];
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            } else if ("instagram" === $api->params[0]) {
                                try {
                                    $profile = $sc->checkCredentials($api->params[0], array(
                                        "access_token" => $credentials[$api->params[0]]["access_token"]
                                    ));
                                    $value = $_SESSION['params_socialnetworks'][$api->params[0]];
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            } else if ("facebook" === $api->params[0]) {
                                try {
                                    $profile = $sc->checkCredentials($api->params[0], array(
                                        "access_token" => $credentials[$api->params[0]]["access_token"]
                                    ));
                                    $profile = json_decode($profile, true);
                                    $value = $_SESSION['params_socialnetworks'][$api->params[0]];
                                    $value["user_id"] = $profile["user_id"];
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            }
                            break;
                        // Refresh GOOGLE credentials and returned new ones
                        case "refresh":
                            try {
                                $value = $sc->refreshCredentials($api->params[0], array(
                                    "access_token" => $credentials[$api->params[0]]["access_token"],
                                    "refresh_token" => $credentials[$api->params[0]]["refresh_token"],
                                    "id_token" => $credentials[$api->params[0]]["id_token"]));
                                $_SESSION['params_socialnetworks'][$api->params[0]] = $value;
                            } catch (\Exception $e) {
                                $api->setError($e->getMessage());
                            }

                            break;
                        // Get SOCIAL NETWORK user profile
                        case "profile":
                            try {
                                $value = json_decode($sc->getProfile($api->params[0], $api->params[2]));
                            } catch (\Exception $e) {
                                $api->setError($e->getMessage());
                            }
                            break;
                        // EXPORT endpoints
                        case "export":
                            switch ($api->params[3]) {
                                case 'media':
                                    // Export media recently liked in INSTAGRAM
                                    if ("liked" === $api->params[4]) {
                                        try {
                                            $value = json_decode($sc->exportMediaRecentlyLiked($api->params[0],
                                                $api->params[2], $api->params[5],
                                                $api->params[6], $api->params[7]));
                                        } catch (\Exception $e) {
                                            $api->setError($e->getMessage());
                                        }
                                    // Export photos in an album FACEBOOK
                                    } else if (isset($api->params[7])) {
                                        try {
                                            $value = json_decode($sc->exportPhotosFromAlbum($api->params[0],
                                                SocialNetworks::ENTITY_USER, $api->params[2],
                                                $api->params[4], $api->params[5], $api->params[6], $api->params[7]));
                                        } catch (\Exception $e) {
                                            $api->setError($e->getMessage());
                                        }
                                    // Export images from GOOGLE drive, INSTAGRAM, FACEBOOK
                                    } else {
                                        try {
                                            $value = json_decode($sc->exportMedia($api->params[0],
                                                SocialNetworks::ENTITY_USER, $api->params[2],
                                                $api->params[4], $api->params[5], $api->params[6]));
                                        } catch (\Exception $e) {
                                            $api->setError($e->getMessage());
                                        }
                                    }
                                    break;
                                // Export people from GOOGLE+
                                case 'people':
                                    if ("post" === $api->params[4]) {
                                        // People who like, share or were shared an activity/post
                                        try {
                                            $value = json_decode($sc->getFollowersInfo($api->params[0], $api->params[2],
                                                $api->params[5]));
                                        } catch (\Exception $e) {
                                            $api->setError($e->getMessage());
                                        }
                                    } else if ("circle" === $api->params[4]) {
                                        // People in a circle
                                        try {
                                            $value = json_decode($sc->exportPeopleInCircle($api->params[0], $api->params[2],
                                                $api->params[5], $api->params[6], $api->params[7], $api->params[8]));
                                        } catch (\Exception $e) {
                                            $api->setError($e->getMessage());
                                        }
                                    } else {
                                        // People in Google circles
                                        try {
                                            $value = json_decode($sc->getFollowers($api->params[0], $api->params[2],
                                                $api->params[4], $api->params[5], $api->params[6]));
                                        } catch (\Exception $e) {
                                            $api->setError($e->getMessage());
                                        }
                                    }
                                    break;
                                // Export posts from GOOGLE+
                                case 'posts':
                                    try {
                                        $value = json_decode($sc->getPosts($api->params[0], $api->params[2],
                                            $api->params[5], $api->params[6], $api->params[7]));
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                // Export circles from GOOGLE+
                                case 'circles':
                                    try {
                                        $value = json_decode($sc->exportCircles($api->params[0], $api->params[2],
                                            $api->params[4], $api->params[5], $api->params[6]));
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                // Export INSTAGRAM Followers
                                case 'followers':
                                    try {
                                        $value = json_decode($sc->getFollowers($api->params[0], $api->params[2], 0,
                                            $api->params[4], $api->params[5]));
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                // Export INSTAGRAM Subscribers
                                case 'subscribers':
                                    try {
                                        $value = json_decode($sc->getSubscribers($api->params[0], $api->params[2], 0,
                                            $api->params[4], $api->params[5]));
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                // Export albums in FACEBOOK
                                case 'albums':
                                    try {
                                        $value = json_decode($sc->exportPhotosAlbumsList($api->params[0],
                                            SocialNetworks::ENTITY_USER, $api->params[2],
                                            $api->params[4], $api->params[5], $api->params[6]));
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                // Export pages in FACEBOOK
                                case 'pages':
                                    try {
                                        $value = json_decode($sc->exportPages($api->params[0], $api->params[2],
                                            $api->params[4], $api->params[5], $api->params[6]));
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                            }
                            break;
                        // FACEBOOK user endpoints
                        case "user":
                            break;
                        case "page":
                            if("export" === $api->params[3]) {
                                switch ($api->params[4]) {
                                    case "album":
                                        if ("media" === $api->params[6]) {
                                            try {
                                                $value = json_decode($sc->exportPhotosFromAlbum($api->params[0],
                                                    SocialNetworks::ENTITY_PAGE, $api->params[2], $api->params[5],
                                                    $api->params[7], $api->params[8], $api->params[9]));
                                            } catch (\Exception $e) {
                                                $api->setError($e->getMessage());
                                            }
                                        } else {
                                            try {
                                                $value = json_decode($sc->exportPhotosAlbumsList($api->params[0],
                                                    SocialNetworks::ENTITY_PAGE, $api->params[2],
                                                    $api->params[5], $api->params[6], $api->params[7]));
                                            } catch (\Exception $e) {
                                                $api->setError($e->getMessage());
                                            }
                                        }
                                        break;
                                }
                                break;
                            } else {
                                switch ($api->params[2]) {
                                    case 'export':
                                        try {
                                            $value = json_decode($sc->exportMedia($api->params[0],
                                                SocialNetworks::ENTITY_PAGE, $api->params[3],
                                                $api->params[5], $api->params[6], $api->params[7]));
                                        } catch (\Exception $e) {
                                            $api->setError($e->getMessage());
                                        }
                                        break;
                                    default:
                                        try {
                                            $value = json_decode($sc->getPage($api->params[0], $api->params[2]));
                                        } catch (\Exception $e) {
                                            $api->setError($e->getMessage());
                                        }
                                        break;
                                }
                            }

                            break;
                        // INSTAGRAM Relationships
                        case 'relationship':
                            try {
                                $value = json_decode($sc->getUserRelationship($api->params[0], $api->params[2],
                                    $api->params[3]));
                            } catch (\Exception $e) {
                                $api->setError($e->getMessage());
                            }
                            break;
                        // Revoke grant to GOOGLE credentials
                        case 'revoke':
                            try {
                                $value = json_decode($sc->revokeToken($api->params[0]));
                            } catch (\Exception $e) {
                                $api->setError($e->getMessage());
                            }
                            break;
                        // INSTAGRAM user searching
                        case 'search':
                            try {
                                $value = json_decode($sc->searchUsers($api->params[0], $api->params[2], $api->params[4],
                                    $api->params[5], $api->params[6], $api->params[7]));
                            } catch (\Exception $e) {
                                $api->setError($e->getMessage());
                            }
                            break;
                    }
                    break;
            }
            break;
        case "POST":
            switch($api->params[1]) {
                // Save SOCIAL NETWORK in session
                case "auth":
                    $_SESSION['params_socialnetworks'][$api->params[0]] = $api->formParams;
                    $value = $_SESSION['params_socialnetworks'][$api->params[0]];
                    break;
                // The rest of social networks.
                case "page":
                    switch ($api->params[3]) {
                        case 'create':
                            switch ($api->params[4]) {
                                case 'album':
                                    try {
                                        $value = json_decode($sc->createPhotosAlbum($api->params[0],
                                            SocialNetworks::ENTITY_PAGE, $api->params[2],
                                            $api->formParams["title"], $api->formParams["caption"]));
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                case 'post':
                                    $params["message"] = $api->formParams["message"];
                                    if (isset($api->formParams["link"])) {
                                        $params["link"] = $api->formParams["link"];
                                    }
                                    if (isset($api->formParams["object_attachment"])) {
                                        $params["object_attachment"] = $api->formParams["object_attachment"];
                                    }

                                    try {
                                        $value = json_decode($sc->post($api->params[0],
                                            SocialNetworks::ENTITY_PAGE, $api->params[2],
                                            $params));
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                            }
                            break;
                        // Import photo to FACEBOOK (page)
                        case 'import':
                            switch ($api->params[4]) {
                                case 'media':
                                    try {
                                        $parameters = array();
                                        $parameters["entity"] = SocialNetworks::ENTITY_PAGE;
                                        $parameters["id"] = $api->params[2];
                                        $parameters["media_type"] = $api->formParams["media_type"];
                                        $parameters["value"] = $api->formParams["value"];
                                        if (isset($api->formParams["title"])) {
                                            $parameters["title"] = $api->formParams["title"];
                                        }
                                        $value = json_decode($sc->importMedia($api->params[0], $parameters));
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                case 'album':
                                    try {
                                        $parameters = array();
                                        $parameters["entity"] = SocialNetworks::ENTITY_PAGE;
                                        $parameters["id"] = $api->params[2];
                                        $parameters["media_type"] = $api->formParams["media_type"];
                                        $parameters["value"] = $api->formParams["value"];
                                        if (isset($api->formParams["title"])) {
                                            $parameters["title"] = $api->formParams["title"];
                                        }
                                        $parameters["album_id"] = $api->params[5];
                                        $value = json_decode($sc->importMedia($api->params[0], $parameters));
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                            }
                            break;
                    }
                    break;
                // IMPORT endpoints
                case 'import':
                    switch ($api->params[3]) {
                        // Import media (video/image) to GOOGLE+
                        // Import photo to FACEBOOK (user)
                        case 'media':
                            try {
                                // Media title and album id is for FACEBOOK
                                $parameters = array();
                                $parameters["entity"] = SocialNetworks::ENTITY_USER;
                                $parameters["id"] = $api->params[2];
                                $parameters["media_type"] = $api->formParams["media_type"];
                                $parameters["value"] = $api->formParams["value"];
                                if (isset($api->formParams["title"])) {
                                    $parameters["title"] = $api->formParams["title"];
                                }
                                $parameters["album_id"] = $api->params[4];
                                $value = json_decode($sc->importMedia($api->params[0], $parameters));
                            } catch (\Exception $e) {
                                $api->setError($e->getMessage());
                            }
                            break;
                    }
                    break;
                case 'post':
                    // Send a post to GOOGLE +
                    $params = array();
                    if ("google" === $api->params[0]) {
                        $params["user_id"] = $api->params[2];
                        $params["content"] = $api->formParams["content"];
                        $params["access_type"] = $api->formParams["access_type"];
                        if (isset($api->formParams["attachment"])) {
                            $params["attachment"] = $api->formParams["attachment"];
                        }
                        if (isset($api->formParams["circle_id"])) {
                            $params["circle_id"] = $api->formParams["circle_id"];
                        }
                        if (isset($api->formParams["person_id"])) {
                            $params["person_id"] = $api->formParams["person_id"];
                        }
                        // Send a comment on a media in INSTAGRAM
                    } else if ("instagram" === $api->params[0]) {
                        $params["content"] = $api->formParams["content"];
                        $params["media_id"] = $api->formParams["media_id"];
                    } else if ("facebook" === $api->params[0]) {
                        $params["message"] = $api->formParams["message"];
                        if (isset($api->formParams["link"])) {
                            $params["link"] = $api->formParams["link"];
                        }
                        if (isset($api->formParams["object_attachment"])) {
                            $params["object_attachment"] = $api->formParams["object_attachment"];
                        }
                    }
                    try {
                        $value = json_decode($sc->post($api->params[0],
                            SocialNetworks::ENTITY_USER, $api->params[2],
                            $params));
                    } catch (\Exception $e) {
                        $api->setError($e->getMessage());
                    }
                    break;
                // Modify INSTAGRAM relationship
                case "relationship":
                    try {
                        $value = json_decode($sc->modifyUserRelationship($api->params[0], $api->params[3],
                            $api->params[4], $api->formParams["action"]));
                    } catch (\Exception $e) {
                        $api->setError($e->getMessage());
                    }
                    break;
                // Create photo album in FACEBOOK
                case "album":
                    try {
                        $value = json_decode($sc->createPhotosAlbum($api->params[0],
                            SocialNetworks::ENTITY_USER, $api->params[2],
                            $api->formParams["title"], $api->formParams["caption"]));
                    } catch (\Exception $e) {
                        $api->setError($e->getMessage());
                    }
                    break;
            }
            break;
    }
}

$api->addReturnData($value);