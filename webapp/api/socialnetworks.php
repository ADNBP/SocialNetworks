<?php
use DPZ\Flickr;
use CloudFramework\Service\SocialNetworks\SocialNetworks;

$api->checkMethod("GET,POST,PUT");  // allowed methods to receive GET,POST etc..

// Check available Networks configured
if(!$api->error) {

    $networks =
        ["google"=>["available"=>$this->getConf("GoogleOauth") && strlen($this->getConf("GoogleOauth_CLIENT_ID")) && strlen($this->getConf("GoogleOauth_CLIENT_SECRET"))
            ,"active"=>$this->getConf("GoogleOauth")
            ,"client_id"=>(strlen($this->getConf("GoogleOauth_CLIENT_ID")))?$this->getConf("GoogleOauth_CLIENT_ID"):null
            ,"client_secret"=>(strlen($this->getConf("GoogleOauth_CLIENT_SECRET")))?$this->getConf("GoogleOauth_CLIENT_SECRET"):null
            ,"client_scope"=>(is_array($this->getConf("GoogleOauth_SCOPE"))) && (count($this->getConf("GoogleOauth_SCOPE")) > 0)?$this->getConf("GoogleOauth_SCOPE"):null
        ],
            "facebook"=>["available"=>$this->getConf("FacebookOauth") && strlen($this->getConf("FacebookOauth_CLIENT_ID")) && strlen($this->getConf("FacebookOauth_CLIENT_SECRET"))
                ,"active"=>$this->getConf("FacebookOauth")
                ,"client_id"=>(strlen($this->getConf("FacebookOauth_CLIENT_ID")))?$this->getConf("FacebookOauth_CLIENT_ID"):null
                ,"client_secret"=>(strlen($this->getConf("FacebookOauth_CLIENT_SECRET")))?$this->getConf("FacebookOauth_CLIENT_SECRET"):null
                ,"client_scope"=>(is_array($this->getConf("FacebookOauth_SCOPE"))) && (count($this->getConf("FacebookOauth_SCOPE")) > 0)?$this->getConf("FacebookOauth_SCOPE"):null
            ],
            "instagram"=>["available"=>$this->getConf("InstagramOauth") && strlen($this->getConf("InstagramOauth_CLIENT_ID")) && strlen($this->getConf("InstagramOauth_CLIENT_SECRET"))
                ,"active"=>$this->getConf("InstagramOauth")
                ,"client_id"=>(strlen($this->getConf("InstagramOauth_CLIENT_ID")))?$this->getConf("InstagramOauth_CLIENT_ID"):null
                ,"client_secret"=>(strlen($this->getConf("InstagramOauth_CLIENT_SECRET")))?$this->getConf("InstagramOauth_CLIENT_SECRET"):null
                ,"client_scope"=>(is_array($this->getConf("InstagramOauth_SCOPE"))) && (count($this->getConf("InstagramOauth_SCOPE")) > 0)?$this->getConf("InstagramOauth_SCOPE"):null
            ],
            "pinterest"=>["available"=>$this->getConf("PinterestOauth") && strlen($this->getConf("PinterestOauth_CLIENT_ID")) && strlen($this->getConf("PinterestOauth_CLIENT_SECRET"))
                ,"active"=>$this->getConf("PinterestOauth")
                ,"client_id"=>(strlen($this->getConf("PinterestOauth_CLIENT_ID")))?$this->getConf("PinterestOauth_CLIENT_ID"):null
                ,"client_secret"=>(strlen($this->getConf("PinterestOauth_CLIENT_SECRET")))?$this->getConf("PinterestOauth_CLIENT_SECRET"):null
                ,"client_scope"=>(is_array($this->getConf("PinterestOauth_SCOPE"))) && (count($this->getConf("PinterestOauth_SCOPE")) > 0)?$this->getConf("PinterestOauth_SCOPE"):null
            ],
            "twitter"=>["available"=>$this->getConf("TwitterOauth") && strlen($this->getConf("TwitterOauth_CLIENT_ID")) && strlen($this->getConf("TwitterOauth_CLIENT_SECRET"))
                ,"active"=>$this->getConf("TwitterOauth")
                ,"client_id"=>(strlen($this->getConf("TwitterOauth_CLIENT_ID")))?$this->getConf("TwitterOauth_CLIENT_ID"):null
                ,"client_secret"=>(strlen($this->getConf("TwitterOauth_CLIENT_SECRET")))?$this->getConf("TwitterOauth_CLIENT_SECRET"):null
                ,"client_scope"=>(is_array($this->getConf("TwitterOauth_SCOPE")))?$this->getConf("TwitterOauth_SCOPE"):null
            ],
            "vkontakte"=>["available"=>$this->getConf("VkontakteOauth") && strlen($this->getConf("VkontakteOauth_CLIENT_ID")) && strlen($this->getConf("VkontakteOauth_CLIENT_SECRET"))
                ,"active"=>$this->getConf("VkontakteOauth")
                ,"client_id"=>(strlen($this->getConf("VkontakteOauth_CLIENT_ID")))?$this->getConf("VkontakteOauth_CLIENT_ID"):null
                ,"client_secret"=>(strlen($this->getConf("VkontakteOauth_CLIENT_SECRET")))?$this->getConf("VkontakteOauth_CLIENT_SECRET"):null
                ,"client_scope"=>(is_array($this->getConf("VkontakteOauth_SCOPE")))?$this->getConf("VkontakteOauth_SCOPE"):null
            ],
            "tumblr"=>["available"=>$this->getConf("TumblrOauth") && strlen($this->getConf("TumblrOauth_CLIENT_ID")) && strlen($this->getConf("TumblrOauth_CLIENT_SECRET"))
                ,"active"=>$this->getConf("TumblrOauth")
                ,"client_id"=>(strlen($this->getConf("TumblrOauth_CLIENT_ID")))?$this->getConf("TumblrOauth_CLIENT_ID"):null
                ,"client_secret"=>(strlen($this->getConf("TumblrOauth_CLIENT_SECRET")))?$this->getConf("TumblrOauth_CLIENT_SECRET"):null
                ,"client_scope"=>(is_array($this->getConf("TumblrOauth_SCOPE")))?$this->getConf("TumblrOauth_SCOPE"):null
            ],
            "flickr"=>["available"=>$this->getConf("FlickrOauth") && strlen($this->getConf("FlickrOauth_CLIENT_ID")) && strlen($this->getConf("FlickrOauth_CLIENT_SECRET"))
                ,"active"=>$this->getConf("FlickrOauth")
                ,"client_id"=>(strlen($this->getConf("FlickrOauth_CLIENT_ID")))?$this->getConf("FlickrOauth_CLIENT_ID"):null
                ,"client_secret"=>(strlen($this->getConf("FlickrOauth_CLIENT_SECRET")))?$this->getConf("FlickrOauth_CLIENT_SECRET"):null
                ,"client_scope"=>(is_array($this->getConf("FlickrOauth_SCOPE")))?$this->getConf("FlickrOauth_SCOPE"):null
            ],
            "reddit"=>["available"=>$this->getConf("RedditOauth") && strlen($this->getConf("RedditOauth_CLIENT_ID")) && strlen($this->getConf("RedditOauth_CLIENT_SECRET"))
                ,"active"=>$this->getConf("RedditOauth")
                ,"client_id"=>(strlen($this->getConf("RedditOauth_CLIENT_ID")))?$this->getConf("RedditOauth_CLIENT_ID"):null
                ,"client_secret"=>(strlen($this->getConf("RedditOauth_CLIENT_SECRET")))?$this->getConf("RedditOauth_CLIENT_SECRET"):null
                ,"client_scope"=>(is_array($this->getConf("RedditOauth_SCOPE")))?$this->getConf("RedditOauth_SCOPE"):null
            ],
            "youtube"=>["available"=>$this->getConf("GoogleOauth") && strlen($this->getConf("GoogleOauth_CLIENT_ID")) && strlen($this->getConf("GoogleOauth_CLIENT_SECRET"))
                ,"active"=>$this->getConf("GoogleOauth")
                ,"client_id"=>(strlen($this->getConf("GoogleOauth_CLIENT_ID")))?$this->getConf("GoogleOauth_CLIENT_ID"):null
                ,"client_secret"=>(strlen($this->getConf("GoogleOauth_CLIENT_SECRET")))?$this->getConf("GoogleOauth_CLIENT_SECRET"):null
                ,"client_scope"=>(is_array($this->getConf("GoogleOauth_SCOPE"))) && (count($this->getConf("GoogleOauth_SCOPE")) > 0)?$this->getConf("GoogleOauth_SCOPE"):null
            ]
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

if(!$api->error) {
    if($api->params[0] != "status") {
        try {
            if ("flickr" === $api->params[0]) {
                $redirectUrl = SocialNetworks::generateRequestUrl() . "api/socialnetworks/" .
                    $api->params[0] . "/auth/endcallback";

                $sc->setApiKeys($api->params[0], $networks[$api->params[0]]["client_id"],
                    $networks[$api->params[0]]["client_secret"],
                    $networks[$api->params[0]]["client_scope"],
                    $redirectUrl);
            } else {
                $sc->setApiKeys($api->params[0], $networks[$api->params[0]]["client_id"],
                    $networks[$api->params[0]]["client_secret"],
                    $networks[$api->params[0]]["client_scope"]);
            }
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
                    switch($api->params[1]) {
                        // Auth into a SOCIAL NETWORK and show the credentials in the social network
                        case "auth":
                            $redirectUrl = SocialNetworks::generateRequestUrl() . "api/socialnetworks/" .
                                $api->params[0] . "/auth/endcallback";

                            if ($api->params[2] == "endcallback") {
                                $oauthVerifier = null;
                                if (("twitter" === $api->params[0]) || ("tumblr" === $api->params[0])) {
                                    $code = $_GET["oauth_token"];
                                    $oauthVerifier = $_GET["oauth_verifier"];
                                } else {
                                    $code = $_GET["code"];
                                }

                                try {
                                    $value = $sc->confirmAuthorization($api->params[0], $code, $oauthVerifier, $redirectUrl);
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            } else {
                                $authUrl = "";
                                try {
                                    $value = $sc->requestAuthorization($api->params[0], $redirectUrl);
                                    if ("flickr" !== $api->params[0]) {
                                        header("Location: " . $value);
                                        exit;
                                    }
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            }
                            break;
                        // Check SOCIAL NETWORKS Credentials
                        //      GOOGLE Check if credentials are expired or revoked; if not, return credentials +
                        //      expiry time updated + user id
                        //      Rest of SOCIAL NETWORKS Just get profile to check if credentials are ok
                        case "check":
                            if (("google" === $api->params[0]) || ("youtube" === $api->params[0])) {
                                try {
                                    $profile = $sc->checkCredentials($api->params[0], array(
                                        "access_token" => $credentials[$api->params[0]]["access_token"],
                                        "refresh_token" => $credentials[$api->params[0]]["refresh_token"],
                                        "id_token" => $credentials[$api->params[0]]["id_token"]
                                    ));
                                    $_SESSION["params_socialnetworks"][$api->params[0]]["expires_in"] = $profile["expires_in"];
                                    $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"] = $profile["user_id"];
                                    $value = $_SESSION["params_socialnetworks"][$api->params[0]];
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            } else if ("instagram" === $api->params[0]) {
                                try {
                                    $profile = $sc->checkCredentials($api->params[0], array(
                                        "access_token" => $credentials[$api->params[0]]["access_token"]
                                    ));
                                    $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"] = $profile["user_id"];
                                    $value = $_SESSION["params_socialnetworks"][$api->params[0]];
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            } else if ("facebook" === $api->params[0]) {
                                try {
                                    $profile = $sc->checkCredentials($api->params[0], array(
                                        "access_token" => $credentials[$api->params[0]]["access_token"]
                                    ));
                                    $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"] = $profile["user_id"];
                                    $value = $_SESSION["params_socialnetworks"][$api->params[0]];
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            } else if ("pinterest" === $api->params[0]) {
                                try {
                                    $profile = $sc->checkCredentials($api->params[0], array(
                                        "access_token" => $credentials[$api->params[0]]["access_token"]
                                    ));
                                    $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"] = $profile["user_id"];
                                    $_SESSION["params_socialnetworks"][$api->params[0]]["user_name"] = $profile["raw"]["username"];
                                    $value = $_SESSION["params_socialnetworks"][$api->params[0]];
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            } else if ("twitter" === $api->params[0]) {
                                try {
                                    $profile = $sc->checkCredentials($api->params[0], array(
                                        "access_token" => $credentials[$api->params[0]]["access_token"],
                                        "access_token_secret" => $credentials[$api->params[0]]["access_token_secret"],
                                    ));
                                    $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"] = $profile["user_id"];
                                    $value = $_SESSION["params_socialnetworks"][$api->params[0]];
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            } else if ("vkontakte" === $api->params[0]) {
                                try {
                                    $profile = $sc->checkCredentials($api->params[0], array(
                                        "access_token" => $credentials[$api->params[0]]["access_token"],
                                        "user_id" => $credentials[$api->params[0]]["user_id"]
                                    ));
                                    $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"] = $profile["id"];
                                    $value = $_SESSION["params_socialnetworks"][$api->params[0]];
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            } else if ("tumblr" === $api->params[0]) {
                                try {
                                    $profile = $sc->checkCredentials($api->params[0], array(
                                        "access_token" => $credentials[$api->params[0]]["access_token"],
                                        "access_token_secret" => $credentials[$api->params[0]]["access_token_secret"],
                                    ));
                                    $_SESSION["params_socialnetworks"][$api->params[0]]["name"] = $profile["name"];
                                    $value = $_SESSION["params_socialnetworks"][$api->params[0]];
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            }  else if ("flickr" === $api->params[0]) {
                                try {
                                    $profile = $sc->checkCredentials($api->params[0], $_SESSION[Flickr::SESSION_OAUTH_DATA]);
                                    $_SESSION["params_socialnetworks"][$api->params[0]]["checked"] = "success";
                                    $value = $_SESSION["params_socialnetworks"][$api->params[0]];
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            } else if ("reddit" === $api->params[0]) {
                                try {
                                    $profile = $sc->checkCredentials($api->params[0], array(
                                        "access_token" => $credentials[$api->params[0]]["access_token"]
                                    ));
                                    $value = $_SESSION["params_socialnetworks"][$api->params[0]];
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
                                $_SESSION["params_socialnetworks"][$api->params[0]] = $value;
                            } catch (\Exception $e) {
                                $api->setError($e->getMessage());
                            }

                            break;
                        // Revoke grant to GOOGLE credentials
                        case "revoke":
                            try {
                                $value = $sc->revokeToken($api->params[0]);
                            } catch (\Exception $e) {
                                $api->setError($e->getMessage());
                            }
                            break;
                        // Get SOCIAL NETWORK user profile
                        case "profile":
                            try {
                                $value = $sc->getProfile($api->params[0],
                                    $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"]);
                            } catch (\Exception $e) {
                                $api->setError($e->getMessage());
                            }
                            break;
                        case "export":
                            switch($api->params[2]) {
                                // Get YOUTUBE video categories
                                case "videocategory":
                                    try {
                                        $value = $sc->exportVideoCategories($api->params[0]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                // Get YOUTUBE playlists
                                case "playlist":
                                    try {
                                        $value = $sc->exportPlaylists($api->params[0], $api->params[3], $api->params[4],
                                            $api->params[5]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                // Get YOUTUBE videos
                                case "video":
                                    try {
                                        $value = $sc->exportVideos($api->params[0], $api->params[3], $api->params[4],
                                            $api->params[5]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                            }
                            break;
                        case "post":
                            try {
                                $value = $sc->getVideo($api->params[0], $api->params[2]);
                            } catch (\Exception $e) {
                                $api->setError($e->getMessage());
                            }
                            break;
                        // SOCIAL NETWORKS USER END POINTS
                        case "user":
                            switch($api->params[2]) {
                                // Get TWITTER user home timeline
                                case "timeline":
                                    try {
                                        $value = $sc->getUserTimeline($api->params[0],
                                            $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                case "export":
                                    switch($api->params[3]) {
                                        // GOOGLE Circles end points
                                        case "circle":
                                            // People in an user circle
                                            if ("people" === $api->params[5]) {
                                                try {
                                                    $value = $sc->exportUserPeopleInCircle($api->params[0],
                                                        $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"],
                                                        $api->params[4], $api->params[6], $api->params[7], $api->params[8]);
                                                } catch (\Exception $e) {
                                                    $api->setError($e->getMessage());
                                                }
                                            // Export circles from GOOGLE+
                                            } else {
                                                try {
                                                    $value = $sc->exportUserCircles($api->params[0],
                                                        $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"],
                                                        $api->params[4], $api->params[5], $api->params[6]);
                                                } catch (\Exception $e) {
                                                    $api->setError($e->getMessage());
                                                }
                                            }
                                            break;
                                        // SOCIAL NETWORKS People end points
                                        case "people":
                                            // People in Google + circles
                                            try {
                                                $value = $sc->exportUserPeopleInAllCircles($api->params[0],
                                                    $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"],
                                                    $api->params[4], $api->params[5], $api->params[6]);
                                            } catch (\Exception $e) {
                                                $api->setError($e->getMessage());
                                            }
                                            break;
                                        case "follower":
                                            // INSTAGRAM Followers
                                            if ("instagram" === $api->params[0]) {
                                                try {
                                                    $value = $sc->exportUserFollowers($api->params[0],
                                                        $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"],
                                                        0, $api->params[4], $api->params[5]);
                                                } catch (\Exception $e) {
                                                    $api->setError($e->getMessage());
                                                }
                                            // PINTEREST / TWITTER Followers
                                            } else {
                                                try {
                                                    $value = $sc->exportUserFollowers($api->params[0],
                                                        $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"],
                                                        $api->params[4], $api->params[5], $api->params[6]);
                                                } catch (\Exception $e) {
                                                    $api->setError($e->getMessage());
                                                }
                                            }
                                            break;
                                        case "subscriber":
                                        case "friend":
                                            // INSTAGRAM Subscribers
                                            if ("instagram" === $api->params[0]) {
                                                try {
                                                    $value = $sc->exportUserSubscribers($api->params[0],
                                                        $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"],
                                                        0, $api->params[4], $api->params[5]);
                                                } catch (\Exception $e) {
                                                    $api->setError($e->getMessage());
                                                }
                                            // PINTEREST Subscribers / TWITTER friends
                                            } else {
                                                try {
                                                    $value = $sc->exportUserSubscribers($api->params[0],
                                                        $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"],
                                                        $api->params[4], $api->params[5], $api->params[6]);
                                                } catch (\Exception $e) {
                                                    $api->setError($e->getMessage());
                                                }
                                            }
                                            break;
                                        case "media":
                                            // Export media recently liked in INSTAGRAM
                                            if ("liked" === $api->params[4]) {
                                                try {
                                                    $value = $sc->exportUserMediaRecentlyLiked($api->params[0],
                                                        $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"],
                                                        $api->params[5], $api->params[6], $api->params[7]);
                                                } catch (\Exception $e) {
                                                    $api->setError($e->getMessage());
                                                }
                                            // Export media from GOOGLE drive, INSTAGRAM
                                            } else {
                                                try {
                                                    $value = $sc->exportUserMedia($api->params[0],
                                                        $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"],
                                                        $api->params[4], $api->params[5], $api->params[6]);
                                                } catch (\Exception $e) {
                                                    $api->setError($e->getMessage());
                                                }
                                            }
                                            break;
                                        case "photo":
                                            // Export user photos from FACEBOOK / VKONTAKTE / FLICKR
                                            try {
                                                $value = $sc->exportUserPhotos($api->params[0],
                                                    $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"],
                                                    $api->params[4], $api->params[5], $api->params[6]);
                                            } catch (\Exception $e) {
                                                $api->setError($e->getMessage());
                                            }
                                            break;
                                        case "post":
                                            // TUMBLR posts end points
                                            if ("tumblr" === $api->params[0]) {
                                                switch($api->params[5]) {
                                                    case "photo":
                                                        try {
                                                            $value = $sc->exportUserPhotoPosts($api->params[0],
                                                                $api->params[4], $api->params[6]);
                                                        } catch (\Exception $e) {
                                                            $api->setError($e->getMessage());
                                                        }
                                                        break;
                                                    // Tumblr dashboard posts
                                                    default:
                                                        if ("dashboard" === $api->params[4]) {
                                                            try {
                                                                $value = $sc->exportUserDashboardPosts($api->params[0],
                                                                    $api->params[5]);
                                                            } catch (\Exception $e) {
                                                                $api->setError($e->getMessage());
                                                            }
                                                        } else if ("liked" === $api->params[4]) {
                                                            try {
                                                                $value = $sc->exportUserLikedPosts($api->params[0],
                                                                    $api->params[5]);
                                                            } catch (\Exception $e) {
                                                                $api->setError($e->getMessage());
                                                            }
                                                        }
                                                        break;
                                                }
                                            // GOOGLE Posts/Activities end points
                                            } else {
                                                // GOOGLE People who like, share or were shared an activity/post
                                                if ("people" === $api->params[5]) {
                                                    try {
                                                        $value = $sc->exportUserPeopleInPost($api->params[0],
                                                            $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"],
                                                            $api->params[4]);
                                                    } catch (\Exception $e) {
                                                        $api->setError($e->getMessage());
                                                    }
                                                    // Export posts from GOOGLE+
                                                } else {
                                                    try {
                                                        $value = $sc->exportUserPosts($api->params[0],
                                                            $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"],
                                                            $api->params[4], $api->params[5], $api->params[6]);
                                                    } catch (\Exception $e) {
                                                        $api->setError($e->getMessage());
                                                    }
                                                }
                                            }
                                            break;
                                        // Tumblr blogs
                                        case "blog":
                                            switch($api->params[4]) {
                                                case "followed":
                                                    try {
                                                        $value = $sc->exportUserFollowedBlogs($api->params[0],
                                                            $api->params[5]);
                                                    } catch (\Exception $e) {
                                                        $api->setError($e->getMessage());
                                                    }
                                                    break;
                                            }
                                            break;
                                        case "album":
                                            // Export photos inside an album FACEBOOK / FLICKR
                                            if ("photo" === $api->params[5]) {
                                                try {
                                                    $value = $sc->exportPhotosFromAlbum($api->params[0],
                                                        $api->params[4], $api->params[6], $api->params[7], $api->params[8]);
                                                } catch (\Exception $e) {
                                                    $api->setError($e->getMessage());
                                                }
                                            // Export albums for a FACEBOOK / FLICKR user
                                            } else {
                                                try {
                                                    $value = $sc->exportPhotosUserAlbumsList($api->params[0],
                                                        $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"],
                                                        $api->params[4], $api->params[5], $api->params[6]);
                                                } catch (\Exception $e) {
                                                    $api->setError($e->getMessage());
                                                }
                                            }
                                            break;
                                        // Export user's pages in FACEBOOK
                                        case "page":
                                            try {
                                                $value = $sc->exportUserPages($api->params[0],
                                                    $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"],
                                                    $api->params[4], $api->params[5], $api->params[6]);
                                            } catch (\Exception $e) {
                                                $api->setError($e->getMessage());
                                            }
                                            break;
                                        // Export user's pins in PINTEREST
                                        case "pin":
                                            try {
                                                $value = $sc->exportUserPins($api->params[0],
                                                    $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"], null,
                                                    $api->params[4], $api->params[5], $api->params[6]);
                                            } catch (\Exception $e) {
                                                $api->setError($e->getMessage());
                                            }
                                            break;
                                        // Export pins liked by the user in PINTEREST
                                        case "like":
                                            try {
                                                $value = $sc->exportUserPinsLiked($api->params[0],
                                                    $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"], null,
                                                    $api->params[4], $api->params[5], $api->params[6]);
                                            } catch (\Exception $e) {
                                                $api->setError($e->getMessage());
                                            }
                                            break;
                                        // Export user's boards in PINTEREST
                                        case "board":
                                            try {
                                                $value = $sc->exportUserBoards($api->params[0],
                                                    $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"], null,
                                                    $api->params[4], $api->params[5], $api->params[6]);
                                            } catch (\Exception $e) {
                                                $api->setError($e->getMessage());
                                            }
                                            break;
                                        // Export user's following boards / interests in PINTEREST
                                        case "following":
                                            switch($api->params[4]) {
                                                case "board":
                                                    try {
                                                        $value = $sc->exportUserFollowingBoards($api->params[0],
                                                            $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"],
                                                            $api->params[5], $api->params[6], $api->params[7]);
                                                    } catch (\Exception $e) {
                                                        $api->setError($e->getMessage());
                                                    }
                                                    break;
                                                case "interest":
                                                    try {
                                                        $value = $sc->exportUserFollowingInterests($api->params[0],
                                                            $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"],
                                                            $api->params[5], $api->params[6], $api->params[7]);
                                                    } catch (\Exception $e) {
                                                        $api->setError($e->getMessage());
                                                    }
                                                    break;
                                            }
                                            break;
                                    }
                                    break;
                                // INSTAGRAM Relationships end points
                                case "relationship":
                                    try {
                                        $value = $sc->getUserRelationship($api->params[0],
                                            $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"], $api->params[4]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                case "search":
                                    switch($api->params[3]) {
                                        // INSTAGRAM / PINTEREST user searching
                                        case "user":
                                            try {
                                                $value = $sc->searchUsers($api->params[0],
                                                    $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"],
                                                    $api->params[4], $api->params[5], $api->params[6], $api->params[7]);
                                            } catch (\Exception $e) {
                                                $api->setError($e->getMessage());
                                            }
                                            break;
                                        // PINTEREST pins searching
                                        case "pin":
                                            try {
                                                $value = $sc->exportUserPins($api->params[0],
                                                    $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"],
                                                    $api->params[4],
                                                    $api->params[5], $api->params[6], $api->params[7]);
                                            } catch (\Exception $e) {
                                                $api->setError($e->getMessage());
                                            }
                                            break;
                                        // PINTEREST boards searching
                                        case "board":
                                            try {
                                                $value = $sc->exportUserBoards($api->params[0],
                                                    $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"],
                                                    $api->params[4],
                                                    $api->params[5], $api->params[6], $api->params[7]);
                                            } catch (\Exception $e) {
                                                $api->setError($e->getMessage());
                                            }
                                            break;
                                    }
                                    break;
                                // FLICKR Add photo to album
                                case "add":
                                    try {
                                        $value = $sc->addUserPhotoToAlbum($api->params[0],
                                            $api->params[4], $api->params[6]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                    break;
                            }
                            break;
                        // GET PAGE endpoints
                        case "page":
                            switch($api->params[3]) {
                                // FACEBOOK Page Info
                                case "info":
                                    try {
                                        $value = $sc->getPage($api->params[0], $api->params[2]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                case "export":
                                    switch ($api->params[4]) {
                                        // Export page's photos from FACEBOOK
                                        case "photo":
                                            try {
                                                $value = $sc->exportPagePhotos($api->params[0],
                                                    $api->params[2],
                                                    $api->params[5], $api->params[6], $api->params[7]);
                                            } catch (\Exception $e) {
                                                $api->setError($e->getMessage());
                                            }
                                            break;
                                        case "album":
                                            // Export photos from a page's album in FACEBOOK
                                            if ("photo" === $api->params[6]) {
                                                try {
                                                    $value = $sc->exportPhotosFromAlbum($api->params[0],
                                                        $api->params[5], $api->params[7], $api->params[8], $api->params[9]);
                                                } catch (\Exception $e) {
                                                    $api->setError($e->getMessage());
                                                }
                                            } else {
                                                // Export page's albums from FACEBOOK
                                                try {
                                                    $value = $sc->exportPhotosPageAlbumsList($api->params[0],
                                                         $api->params[2],
                                                        $api->params[5], $api->params[6], $api->params[7]);
                                                } catch (\Exception $e) {
                                                    $api->setError($e->getMessage());
                                                }
                                            }
                                            break;
                                        case "post":
                                            if ("promotable" === $api->params[5]) {
                                                // Export page's promotable posts from FACEBOOK
                                                try {
                                                    $value = $sc->exportPagePromotablePosts($api->params[0],
                                                        $api->params[2],
                                                        $api->params[6], $api->params[7], $api->params[8]);
                                                } catch (\Exception $e) {
                                                    $api->setError($e->getMessage());
                                                }
                                            } else {
                                                // Export page's feed from FACEBOOK
                                                try {
                                                    $value = $sc->exportPagePosts($api->params[0],
                                                        $api->params[2],
                                                        $api->params[5], $api->params[6], $api->params[7]);
                                                } catch (\Exception $e) {
                                                    $api->setError($e->getMessage());
                                                }
                                            }
                                            break;
                                    }
                                    break;
                            }
                            break;
                        // GET TWITTER tweets endpoints
                        case "tweet":
                            switch($api->params[3]) {
                                // TWITTER Tweet Info
                                case "info":
                                    try {
                                        $value = $sc->getUserTweet($api->params[0], $api->params[2]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                // TWITTER Tweet deletion
                                case "delete":
                                    try {
                                        $value = $sc->deleteUserTweet($api->params[0], $api->params[2]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                            }
                            break;
                        // GET PINTEREST boards endpoints
                        case "board":
                            switch($api->params[4]) {
                                // PINTEREST Board Info
                                case "info":
                                    try {
                                        $value = $sc->getUserBoard($api->params[0], $api->params[2], $api->params[3]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                // PINTEREST Delete Board
                                case "delete":
                                    try {
                                        $value = $sc->deleteUserBoard($api->params[0], $api->params[2], $api->params[3]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                // PINTEREST Export Pins from a Board
                                case "export":
                                    try {
                                        $value = $sc->exportPinsFromUserBoard($api->params[0],
                                            $api->params[2], $api->params[3],
                                            $api->params[6], $api->params[7],$api->params[8]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                            }
                            break;
                        // GET PINTEREST pins endpoints
                        case "pin":
                            switch($api->params[3]) {
                                // PINTEREST Pin Info
                                case "info":
                                    try {
                                        $value = $sc->getUserPin($api->params[0], $api->params[2]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                // PINTEREST Delete Pin
                                case "delete":
                                    try {
                                        $value = $sc->deleteUserPin($api->params[0], $api->params[2]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                            }
                            break;
                        // GET TUMBLR blogs endopoints
                        case "blog":
                            switch($api->params[3]) {
                                // TUMBLR Blog Info
                                case "info":
                                    try {
                                        $value = $sc->getUserBlog($api->params[0], $api->params[2]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                // TUMBLR Blog Avatar
                                case "avatar":
                                    try {
                                        $value = $sc->getUserBlogAvatar($api->params[0], $api->params[2],
                                                                        $api->params[4]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                case "export":
                                    switch($api->params[4]) {
                                        // TUMBLR Blog likes from authenticated user
                                        case "post":
                                            try {
                                                $value = $sc->getUserBlogLikes($api->params[0], $api->params[2],
                                                    $api->params[6]);
                                            } catch (\Exception $e) {
                                                $api->setError($e->getMessage());
                                            }
                                            break;
                                        // TUMBLR Blog followers
                                        case "follower":
                                            try {
                                                $value = $sc->getUserBlogFollowers($api->params[0], $api->params[2],
                                                    $api->params[5]);
                                            } catch (\Exception $e) {
                                                $api->setError($e->getMessage());
                                            }
                                            break;
                                    }
                                    break;
                            }
                            break;
                        // YOUTUBE
                        case "playlist":
                            if ("video" === $api->params[3]) {
                                if ("add" === $api->params[5]) {
                                    try {
                                        $value = $sc->addVideoToPlaylist($api->params[0], $api->params[2], $api->params[4]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                } else if ("remove" === $api->params[5]) {
                                    try {
                                        $value = $sc->removeVideoFromPlaylist($api->params[0], $api->params[2], $api->params[4]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                }
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
                    if ("flickr" === $api->params[0]) {
                        $_SESSION[Flickr::SESSION_OAUTH_DATA] = $api->formParams;
                    }
                    $_SESSION["params_socialnetworks"][$api->params[0]] = $api->formParams;
                    $value = $_SESSION["params_socialnetworks"][$api->params[0]];
                    break;
                // The rest of social networks.
                // POST USER endpoints
                case "user":
                    switch($api->params[2]) {
                        // USER Import endpoints
                        case "import":
                            break;
                        // USER Upload endpoints
                        case "upload":
                            switch($api->params[3]) {
                                // Upload media (video/image) to GOOGLE+
                                case "media":
                                    try {
                                        $parameters = array();
                                        $parameters["media_type"] = $api->formParams["media_type"];
                                        $parameters["value"] = $api->formParams["value"];
                                        if (isset($api->formParams["title"])) {
                                            $parameters["title"] = $api->formParams["title"];
                                        }
                                        $value = $sc->uploadUserMedia($api->params[0],
                                            $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"], $parameters);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                // Upload photo to facebook user's bio album
                                case "photo":
                                    try {
                                        if ("facebook" === $api->params[0]) {
                                            $parameters = array();
                                            $parameters["media_type"] = $api->formParams["media_type"];
                                            $parameters["value"] = $api->formParams["value"];
                                            if (isset($api->formParams["title"])) {
                                                $parameters["title"] = $api->formParams["title"];
                                            }
                                            $value = $sc->uploadUserPhoto($api->params[0],
                                                $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"], $parameters);
                                        } else if ("flickr" === $api->params[0]) {
                                            $parameters = array();
                                            $parameters["media_type"] = $api->formParams["media_type"];
                                            $parameters["value"] = $api->formParams["value"];
                                            if (isset($api->formParams["title"])) {
                                                $parameters["title"] = $api->formParams["title"];
                                            }
                                            if (isset($api->formParams["description"])) {
                                                $parameters["description"] = $api->formParams["description"];
                                            }
                                            if (isset($api->formParams["tags"])) {
                                                $parameters["tags"] = $api->formParams["tags"];
                                            }
                                            if (isset($api->formParams["is_public"])) {
                                                $parameters["is_public"] = $api->formParams["is_public"];
                                            }
                                            if (isset($api->formParams["is_friend"])) {
                                                $parameters["is_friend"] = $api->formParams["is_friend"];
                                            }
                                            if (isset($api->formParams["is_family"])) {
                                                $parameters["is_family"] = $api->formParams["is_family"];
                                            }
                                            if (isset($api->formParams["safety_level"])) {
                                                $parameters["safety_level"] = $api->formParams["safety_level"];
                                            }
                                            if (isset($api->formParams["content_type"])) {
                                                $parameters["content_type"] = $api->formParams["content_type"];
                                            }
                                            if (isset($api->formParams["hidden"])) {
                                                $parameters["hidden"] = $api->formParams["hidden"];
                                            }
                                            $value = $sc->uploadUserPhoto($api->params[0],
                                                $_SESSION[Flickr::SESSION_OAUTH_DATA]["user_nsid"], $parameters);
                                        }
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                // Upload photo to FACEBOOK user's album
                                case "album":
                                    try {
                                        $parameters = array();
                                        $parameters["media_type"] = $api->formParams["media_type"];
                                        $parameters["value"] = $api->formParams["value"];
                                        if (isset($api->formParams["title"])) {
                                            $parameters["title"] = $api->formParams["title"];
                                        }
                                        $parameters["album_id"] = $api->params[4];
                                        $value = $sc->uploadUserPhoto($api->params[0],
                                            $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"], $parameters);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                            }
                            break;
                        case "create":
                            switch ($api->params[3]) {
                                // Create a post in GOOGLE +
                                case "post":
                                    $params = array();
                                    if ("google" === $api->params[0]) {
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
                                        $params["attachment"] = $api->formParams["attachment"];
                                    } else if ("facebook" === $api->params[0]) {
                                        $params["content"] = $api->formParams["content"];
                                        if (isset($api->formParams["attachment"])) {
                                            $params["attachment"] = $api->formParams["attachment"];
                                        }
                                        if (isset($api->formParams["link"])) {
                                            $params["link"] = $api->formParams["link"];
                                        }
                                    } else if ("pinterest" === $api->params[0]) {
                                        $params["username"] = $api->formParams["username"];
                                        $params["boardname"] = $api->formParams["boardname"];
                                        $params["content"] = $api->formParams["content"];
                                        if (isset($api->formParams["link"])) {
                                            $params["link"] = $api->formParams["link"];
                                        }
                                        if (isset($api->formParams["attachment_type"])) {
                                            $params["attachment_type"] = $api->formParams["attachment_type"];
                                        }
                                        if (isset($api->formParams["attachment"])) {
                                            $params["attachment"] = $api->formParams["attachment"];
                                        }
                                    } else if ("twitter" === $api->params[0]) {
                                        $params["content"] = $api->formParams["content"];
                                        if (isset($api->formParams["attachment"])) {
                                            $params["attachment"] = $api->formParams["attachment"];
                                        }
                                        if (isset($api->formParams["in_reply_to_status_id"])) {
                                            $params["in_reply_to_status_id"] = $api->formParams["in_reply_to_status_id"];
                                        }
                                    } else if ("vkontakte" === $api->params[0]) {
                                        $params["content"] = $api->formParams["content"];
                                        if (isset($api->formParams["attachment"])) {
                                            $params["attachment"] = $api->formParams["attachment"];
                                        }
                                        if (isset($api->formParams["link"])) {
                                            $params["link"] = $api->formParams["link"];
                                        }
                                    } else if ("tumblr" === $api->params[0]) {
                                        $params["type"] = $api->formParams["type"];
                                        $params["blogname"] = $api->formParams["blogname"];
                                        $params["attachment_type"] = $api->formParams["attachment_type"];
                                        $params["attachment"] = $api->formParams["attachment"];
                                        if (isset($api->formParams["content"])) {
                                            $params["content"] = $api->formParams["content"];
                                        }
                                        if (isset($api->formParams["link"])) {
                                            $params["link"] = $api->formParams["link"];
                                        }
                                        if (isset($api->formParams["state"])) {
                                            $params["state"] = $api->formParams["state"];
                                        }
                                    } else if ("flickr" === $api->params[0]) {
                                        $params["content"] = $api->formParams["content"];
                                        $params["attachment"] = $api->formParams["attachment"];
                                    }
                                    try {
                                        $value = $sc->post($api->params[0],
                                            $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"],
                                            $params);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                // Create photo album in FACEBOOK / FLICKR for an user
                                case "album":
                                    try {
                                        $value = $sc->createUserPhotosAlbum($api->params[0],
                                            $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"],
                                            $api->formParams["title"], $api->formParams["caption"],
                                            $api->formParams["primary_photo_id"]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                case "board":
                                    // Create a pin in PINTEREST
                                    if ("pin" === $api->params[6]) {
                                        try {
                                            $value = $sc->createUserPin($api->params[0],
                                                $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"],
                                                $api->params[4], $api->params[5],
                                                $api->formParams["content"], $api->formParams["link"],
                                                $api->formParams["attachment_type"],$api->formParams["attachment"]);
                                        } catch (\Exception $e) {
                                            $api->setError($e->getMessage());
                                        }
                                        // Create a board in PINTEREST
                                    } else {
                                        try {
                                            $value = $sc->createUserBoard($api->params[0],
                                                $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"],
                                                $api->formParams["name"], $api->formParams["description"]);
                                        } catch (\Exception $e) {
                                            $api->setError($e->getMessage());
                                        }
                                    }
                                    break;
                            }
                            break;
                        case "relationship":
                            // Modify INSTAGRAM/PINTEREST user/user relationship
                            if ("user" === $api->params[3]) {
                                try {
                                    $value = $sc->modifyUserRelationship($api->params[0],
                                        $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"],
                                        $api->params[4], $api->formParams["action"]);
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            // Modify PINTEREST user/board relationship
                            } else if ("board" === $api->params[3]) {
                                try {
                                    $value = $sc->modifyBoardRelationship($api->params[0],
                                        $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"],
                                        $api->params[4], $api->formParams["action"]);
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            // Modify TUMBLR user/blog relationship
                            } else if ("blog" === $api->params[3]) {
                                try {
                                    $value = $sc->modifyBlogRelationship($api->params[0],
                                        $api->params[4], $api->formParams["action"]);
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            // Modify TUMBLR user/post relationship
                            } else if ("post" === $api->params[3]) {
                                try {
                                    $value = $sc->modifyPostRelationship($api->params[0],
                                        $api->params[4], $api->params[5], $api->formParams["action"]);
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            }
                            break;
                    }
                    break;
                // POST PAGE endpoints
                case "page":
                    switch ($api->params[3]) {
                        case "create":
                            switch ($api->params[4]) {
                                // Create photo album in a FACEBOOK page
                                case "album":
                                    try {
                                        $value = $sc->createPagePhotosAlbum($api->params[0], $api->params[2],
                                            $api->formParams["title"], $api->formParams["caption"]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                // Create a post in a FACEBOOK page
                                case "post":
                                    $params["content"] = $api->formParams["content"];
                                    if (isset($api->formParams["attachment"])) {
                                        $params["attachment"] = $api->formParams["attachment"];
                                    }
                                    if (isset($api->formParams["link"])) {
                                        $params["link"] = $api->formParams["link"];
                                    }

                                    try {
                                        $value = $sc->pagePost($api->params[0], $api->params[2], $params);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                // Create a tab in a FACEBOOK page
                                case "tab":
                                    $params["app_id"] = $api->formParams["app_id"];
                                    $params["custom_name"] = $api->formParams["custom_name"];
                                    if (isset($api->formParams["custom_image_url"])) {
                                        $params["custom_image_url"] = $api->formParams["custom_image_url"];
                                    }
                                    if (isset($api->formParams["position"])) {
                                        $params["position"] = $api->formParams["position"];
                                    }

                                    try {
                                        $value = $sc->createPageTab($api->params[0], $api->params[2], $params);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                            }
                            break;
                        case "upload":
                            switch ($api->params[4]) {
                                // Import photo to FACEBOOK page
                                case "photo":
                                    try {
                                        $parameters = array();
                                        $parameters["media_type"] = $api->formParams["media_type"];
                                        $parameters["value"] = $api->formParams["value"];
                                        if (isset($api->formParams["title"])) {
                                            $parameters["title"] = $api->formParams["title"];
                                        }
                                        $value = $sc->uploadPagePhoto($api->params[0],
                                            $api->params[2], $parameters);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                // Import photo to FACEBOOK page's album
                                case "album":
                                    try {
                                        $parameters = array();
                                        $parameters["media_type"] = $api->formParams["media_type"];
                                        $parameters["value"] = $api->formParams["value"];
                                        if (isset($api->formParams["title"])) {
                                            $parameters["title"] = $api->formParams["title"];
                                        }
                                        $parameters["album_id"] = $api->params[5];
                                        $value = $sc->uploadPagePhoto($api->params[0],
                                            $api->params[2], $parameters);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                            }
                            break;
                        case "simulate":
                            switch ($api->params[4]) {
                                // Simulate the embedded page when a FACEBOOK page tab is active/clicked
                                case "tab":
                                    try {
                                        $value = $sc->simulatePageTab($api->params[0], $api->formParams["signed_request"],
                                            $api->formParams["app_secret"]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                            }
                            break;
                    }
                    break;
                // POST Boards endpoints
                case "board":
                    switch($api->params[4]) {
                        // PINTEREST Board edition
                        case "edit":
                            try {
                                $value = $sc->editUserBoard($api->params[0],
                                    $api->params[2], $api->params[3],
                                    $api->formParams["name"], $api->formParams["description"]);
                            } catch (\Exception $e) {
                                $api->setError($e->getMessage());
                            }
                            break;
                    }
                    break;
                // POST Pins endpoints
                case "pin":
                    switch($api->params[3]) {
                        // PINTEREST Pin edition
                        case "edit":
                            try {
                                $value = $sc->editUserPin($api->params[0],
                                    $api->params[2], $api->formParams["board"], $api->formParams["note"],
                                    $api->formParams["link"]);
                            } catch (\Exception $e) {
                                $api->setError($e->getMessage());
                            }
                            break;
                    }
                    break;
                case "create":
                    // YOUTUBE upload video
                    if ($api->params[2] === "post") {
                        try {
                            $parameters = array();
                            $parameters["title"] = $api->formParams["title"];
                            $parameters["description"] = $api->formParams["description"];
                            $parameters["status"] = $api->formParams["status"];
                            $parameters["category_id"] = $api->formParams["category_id"];
                            $parameters["tags"] = $api->formParams["tags"];
                            $parameters["media_type"] = $api->formParams["media_type"];
                            $parameters["value"] = $api->formParams["value"];
                            $value = $sc->post($api->params[0],
                                $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"], $parameters);
                        } catch (\Exception $e) {
                            $api->setError($e->getMessage());
                        }
                    // YOUTUBE create playlist
                    } else if ($api->params[2] === "playlist") {
                        $parameters = array();
                        $parameters["title"] = $api->formParams["title"];
                        $parameters["description"] = $api->formParams["description"];
                        $parameters["status"] = $api->formParams["status"];
                        $value = $sc->createPlaylist($api->params[0], $parameters);
                    }
                    break;
                // YOUTUBE Video thumbnail
                case "video":
                    try {
                        $parameters = array();
                        $parameters["media_type"] = $api->formParams["media_type"];
                        $parameters["value"] = $api->formParams["value"];
                        $value = $sc->setVideoThumbnail($api->params[0], $api->params[2], $parameters);
                    } catch (\Exception $e) {
                        $api->setError($e->getMessage());
                    }
                    break;
            }
            break;
    }
}

$api->addReturnData($value);