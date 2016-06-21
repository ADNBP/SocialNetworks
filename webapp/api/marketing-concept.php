<?php
use CloudFramework\Service\SocialNetworks\Marketing;
use CloudFramework\Service\SocialNetworks\SocialNetworks;

$api->checkMethod("GET,POST,PUT");  // allowed methods to receive GET,POST etc..

// Check available Networks configured
if (!$api->error) {

    $networks =
        [
            "facebook" => ["available" => $this->getConf("FacebookOauth") && strlen($this->getConf("FacebookOauth_CLIENT_ID")) && strlen($this->getConf("FacebookOauth_CLIENT_SECRET"))
                , "active" => $this->getConf("FacebookOauth")
                , "client_id" => (strlen($this->getConf("FacebookOauth_CLIENT_ID"))) ? $this->getConf("FacebookOauth_CLIENT_ID") : null
                , "client_secret" => (strlen($this->getConf("FacebookOauth_CLIENT_SECRET"))) ? $this->getConf("FacebookOauth_CLIENT_SECRET") : null
                , "client_scope" => (is_array($this->getConf("FacebookOauth_SCOPE"))) && (count($this->getConf("FacebookOauth_SCOPE")) > 0) ? $this->getConf("FacebookOauth_SCOPE") : null
            ],
        ];
}

// The structure of the API call will be: (socialnetwork|status)/{verb}
// Check parameters and check if the social network is available..
$api->checkMandatoryParam(0, "Missing first parameter");
if (!$api->error && ($api->params[0] != "status" || $api->method != "GET")) {
    $api->checkMandatoryParam(1, "The API requires a second parameter");
    if (!$api->error) {
        $api->params[0] = strtolower($api->params[0]);
        if (!(array_key_exists($api->params[0], $networks) && $networks[$api->params[0]]["available"])) {
            $api->setError($api->params[0] . " is not available");
        }
    }
}

$value = [];

// Get Social network object and credentials from Session.
$credentials = $_SESSION["params_socialnetworks"];
$mkt = Marketing::getInstance();
$sn = SocialNetworks::getInstance();

if ((null === $credentials) && ($api->params[1] !== "auth") && ($api->params[1] !== "home")) {
    header("Location: /api/marketing-concept/" . $api->params[0] . "/home");
    exit;
}

if (!$api->error) {
    if ($api->params[0] != "status") {
        try {
            $mkt->setApiKeys($api->params[0], $networks[$api->params[0]]["client_id"],
                $networks[$api->params[0]]["client_secret"],
                $networks[$api->params[0]]["client_scope"]);
            $sn->setApiKeys($api->params[0], $networks[$api->params[0]]["client_id"],
                $networks[$api->params[0]]["client_secret"],
                $networks[$api->params[0]]["client_scope"]);
        } catch (\Exception $e) {
            $api->setError($e->getMessage());
        }

        if (!$api->error && $api->params[1] != "auth") {
            if (!is_array($credentials[$api->params[0]])) {
                $api->setError("Please, assign credentials to " . $api->params[0]);
            }

            if (!$api->error) {
                try {
                    $mkt->setAccessToken($api->params[0], $credentials[$api->params[0]]);
                    $sn->setAccessToken($api->params[0], $credentials[$api->params[0]]);
                } catch (\Exception $e) {
                    $api->setError($e->getMessage());
                }
            }
        }

    }
}

// END POINTS START HERE
if (!$api->error) {
    switch ($api->method) {
        // GET END POINTS
        case "GET":
            switch ($api->params[0]) {
                case "status":
                    $value["credentials"] = $credentials;
                    $value["networks"] = $networks;
                    break;
                // The rest of social networks.
                default:
                    switch ($api->params[1]) {
                        // Auth into a SOCIAL NETWORK and show the credentials in the social network
                        case "auth":
                            $redirectUrl = Marketing::generateRequestUrl() . "api/marketing-concept/" .
                                $api->params[0] . "/auth/endcallback";

                            if ($api->params[2] == "endcallback") {
                                $code = $_GET["code"];

                                try {
                                    $value = $mkt->confirmAuthorization($api->params[0], $code, $oauthVerifier, $redirectUrl);
                                    $_SESSION["params_socialnetworks"][$api->params[0]] = $value;
                                    header("Location: /api/marketing-concept/" . $api->params[0] . "/user/export/adaccount");
                                    exit;
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            } else {
                                $authUrl = "";
                                try {
                                    $authUrl = $mkt->requestAuthorization($api->params[0], $redirectUrl);
                                    header("Location: " . $authUrl);
                                    exit;
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            }
                            break;
                        case "user":
                            switch ($api->params[2]) {
                                case "adaccount":
                                    switch ($api->params[3]) {
                                        case "current":
                                            try {
                                                $value = $mkt->getCurrentUserAdAccount($api->params[0]);
                                            } catch (\Exception $e) {
                                                $api->setError($e->getMessage());
                                            }
                                            break;
                                    }
                                    break;
                                case "export":
                                    switch ($api->params[3]) {
                                        case "adaccount":
                                            $menuactive = 2;
                                            if ($api->params[5] === "campaign") {
                                                try {
                                                    $value = $mkt->exportUserAdAccountCampaigns($api->params[0], $api->params[4]);
                                                    $adAccount = $mkt->getAdAccount($api->params[0], $api->params[4]);
                                                } catch (\Exception $e) {
                                                    $api->setError($e->getMessage());
                                                }
                                            } else if ($api->params[5] === "adimage") {
                                                $menuactive = 6;
                                                try {
                                                    $value = $mkt->exportUserAdAccountAdImages($api->params[0], $api->params[4]);
                                                    $adAccount = $mkt->getAdAccount($api->params[0], $api->params[4]);
                                                } catch (\Exception $e) {
                                                    $api->setError($e->getMessage());
                                                }
                                            } else {
                                                $menuactive = 1;
                                                try {
                                                    $value = $mkt->exportUserAdAccounts($api->params[0]);
                                                } catch (\Exception $e) {
                                                    $api->setError($e->getMessage());
                                                }
                                            }
                                            break;
                                    }
                                    break;
                            }
                            break;
                        case "adaccount":
                            switch ($api->params[3]) {
                                case "info":
                                    try {
                                        $value = $mkt->getAdAccount($api->params[0], $api->params[2]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                // Create ad
                                case "create":
                                    $menuactive = 4;
                                    try {
                                        $adAccount = $mkt->getAdAccount($api->params[0], $api->params[2]);
                                        $campaigns = $mkt->exportUserAdAccountCampaigns($api->params[0], $api->params[2]);
                                        $pages = $sn->exportUserPages($api->params[0],"me",100,1);
                                        $countries = $mkt->searchGeolocationCode($api->params[0], "country", "*");
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                            }
                            break;
                        case "campaign":
                            switch ($api->params[3]) {
                                case "info":
                                    try {
                                        $value = $mkt->getCampaign($api->params[0], $api->params[2]);
                                        if ($api->params[4] === "ajax") {
                                            echo json_encode($value);
                                            exit;
                                        }
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
                                case "adset":
                                    $menuactive = 3;
                                    try {
                                        $value = $mkt->getCampaignAdSets($api->params[0], $api->params[2]);
                                        if ($api->params[4] === "ajax") {
                                            echo json_encode($value);
                                            exit;
                                        } else {
                                            $campaign = $mkt->getCampaign($api->params[0], $api->params[2]);
                                            $adAccount = $mkt->getAdAccount($api->params[0], "act_" . $campaign["account_id"]);
                                        }
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                case "ad":
                                    try {
                                        $value = $mkt->getCampaignAds($api->params[0], $api->params[2]);
                                        $api->addReturnData($value);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                            }
                            break;
                        case "adset":
                            if ($api->params[3] === "ad") {
                                $menuactive = 5;
                                try {
                                    $value = $mkt->getAdsetAds($api->params[0], $api->params[2]);
                                    $adset = $mkt->getAdSet($api->params[0], $api->params[2]);
                                    $campaign = $mkt->getCampaign($api->params[0],$adset["campaign_id"]);
                                    $adAccount = $mkt->getAdAccount($api->params[0], "act_" . $campaign["account_id"]);
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            } else {
                                try {
                                    $adset = $mkt->getAdSet($api->params[0], $api->params[2]);
                                    echo json_encode($adset);
                                    exit;
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            }
                            break;
                        case "ad":
                            try {
                                $previews = $mkt->getAdPreviews($api->params[0], $api->params[2], $api->params[4]);
                                $ad = $mkt->getAd($api->params[0], $api->params[2]);
                                echo json_encode(array(
                                    "advert_name" => $ad["name"],
                                    "body" => str_replace("scrolling=\"yes\"", "scrolling=\"no\"", $previews[0]["body"])
                                ));
                                exit;
                            } catch (\Exception $e) {
                                $api->setError($e->getMessage());
                            }
                            break;
                        case "page":
                            try {
                                $posts = $sn->exportPagePromotablePosts($api->params[0], $api->params[2], $api->params[6], $api->params[7]);
                                echo json_encode($posts);
                                exit;
                            } catch (\Exception $e) {
                                $api->setError($e->getMessage());
                            }
                            break;
                    }
                    break;
            }
            break;
        // POST END POINTS
        case "POST":
            switch ($api->params[1]) {
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
                                case "adcreative":
                                    switch ($api->params[5]) {
                                        case "post":
                                            $parameters = array();
                                            $parameters["name"] = $api->formParams["name"];
                                            $parameters["post_id"] = $api->formParams["post_id"];
                                            $value = $mkt->createExistingPostAdCreative(
                                                $api->params[0], $api->params[2], $parameters
                                            );
                                            break;
                                    }
                                    break;
                            }
                            break;
                        // Adset creation
                        case "campaign":
                            try {
                                $parameters = array();
                                $parameters["name"] = $api->formParams["name"];
                                if (isset($api->formParams["billing_event"])) {
                                    $parameters["billing_event"] = $api->formParams["billing_event"];
                                }
                                if (isset($api->formParams["countries"])) {
                                    $parameters["countries"] = $api->formParams["countries"];
                                }
                                if (isset($api->formParams["daily_budget"])) {
                                    $parameters["daily_budget"] = $api->formParams["daily_budget"];
                                }
                                if (isset($api->formParams["is_autobid"])) {
                                    $parameters["is_autobid"] = $api->formParams["is_autobid"];
                                }
                                $value = $mkt->createAdSet(
                                    $api->params[0], $api->params[2], $api->params[4], $parameters
                                );
                            } catch (\Exception $e) {
                                $api->setError($e->getMessage());
                            }
                            break;
                        // Ad creation
                        case "adset":
                            try {
                                $parameters = array();
                                $parameters["name"] = $api->formParams["name"];
                                $value = $mkt->createAd(
                                    $api->params[0], $api->params[2], $api->params[4], $api->params[6], $parameters
                                );
                            } catch (\Exception $e) {
                                $api->setError($e->getMessage());
                            }
                            break;
                    }
                    break;
                case "targeting":
                    switch ($api->params[2]) {
                        case "geocoding":
                            switch ($api->params[3]) {
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
                case "create":
                    switch ($api->params[2]) {
                        case "ad":
                            try {
                                $adAccountId = $api->params[3];
                                // Campaign
                                $parameters = array();
                                if ($api->formParams["campaign"] === "new") {
                                    // Create campaign
                                    $parameters = array();
                                    $parameters["name"] = $api->formParams["campaign_name"];
                                    if (isset($api->formParams["objective"])) {
                                        $parameters["objective"] = $api->formParams["campaign_objective"];
                                    }
                                    $value = $mkt->createCampaign(
                                        $api->params[0], $adAccountId, $parameters
                                    );
                                    $campaignId = $value["id"];
                                } else {
                                    $campaignId = $api->formParams["campaign_id"];
                                }

                                // Ad Set
                                if ($api->formParams["adset"] === "new") {
                                    $parameters = array();
                                    $parameters["name"] = $api->formParams["adset_name"];

                                    if ($api->formParams["budget_type"] == 0) {
                                        // Daily budget
                                        $parameters["daily_budget"] = $api->formParams["budget_amount"];
                                    } else {
                                        // Lifetime budget
                                        $parameters["lifetime_budget"] = $api->formParams["budget_amount"];
                                    }

                                    if ($api->formParams["start_time"] !== "") {
                                        $dateArr = explode("/", $api->formParams["start_time"]);
                                        $date = new DateTime($dateArr[2]."-".$dateArr[1]."-".$dateArr[0]);
                                        $parameters["start_time"] = $date->getTimestamp();
                                    }

                                    if ($api->formParams["end_time"] !== "") {
                                        $dateArr = explode("/", $api->formParams["end_time"]);
                                        $date = new DateTime($dateArr[2]."-".$dateArr[1]."-".$dateArr[0]);
                                        $parameters["end_time"] = $date->getTimestamp();
                                    }

                                    $parameters["countries"] = implode(",", $api->formParams["locations"]);
                                    $parameters["age_min"] = $api->formParams["age_min"];
                                    $parameters["age_max"] = $api->formParams["age_max"];

                                    if ($api->formParams["gender"] !== "0") {
                                        $parameters["gender"] = $api->formParams["gender"];
                                    }

                                    $parameters["page_types"] = $api->formParams["placements"];

                                    $parameters["billing_event"] = $api->formParams["billing_event"];

                                    $parameters["page_id"] =  $api->formParams["user_page"];

                                    $parameters["is_autobid"] = "true";

                                    $value = $mkt->createAdSet(
                                        $api->params[0], $adAccountId, $campaignId, $parameters
                                    );

                                    $adSetId = $value["id"];
                                } else {
                                    $adSetId = $api->formParams["adset_id"];
                                }

                                // Ad Creative
                                if ($api->formParams["post"] === "new") {
                                    $parameters = array();
                                    $parameters["page_id"] = $api->formParams["user_page"];
                                    $parameters["type"] = $api->formParams["advert_type"];
                                    $parameters["message"] = $api->formParams["advert_message"];
                                    $parameters["link"] = $api->formParams["advert_link"];
                                    $parameters["caption"] = $api->formParams["advert_caption"];
                                    if ($_FILES["advert_image"]["name"] !== null) {
                                        $parameters["image_file"] = $_FILES["advert_image"]["tmp_name"];
                                        $parameters["image_extension"] = pathinfo($_FILES["advert_image"]["name"], PATHINFO_EXTENSION);
                                    }

                                    $value = $mkt->createNewPostAdCreative(
                                        $api->params[0], $adAccountId, $parameters
                                    );

                                    $adCreativeId = $value["id"];
                                } else {
                                    $parameters = array();
                                    $parameters["name"] = $api->formParams["ad_name"];
                                    $parameters["post_id"] = $api->formParams["promotable_post"];
                                    $value = $mkt->createExistingPostAdCreative(
                                        $api->params[0], $adAccountId, $parameters
                                    );

                                    $adCreativeId = $value["id"];
                                }

                                // Ad
                                $parameters = array();
                                $parameters["name"] = $api->formParams["ad_name"];
                                $value = $mkt->createAd(
                                    $api->params[0], $adAccountId, $adSetId, $adCreativeId, $parameters
                                );

                                header("Location: /api/marketing-concept/" . $api->params[0] . "/adset/" . $adSetId . "/ad");
                            } catch (\Exception $e) {
                                $menuactive = 7;
                                $api->setError($e->getMessage());
                            }
                            break;
                    }
                    break;
            }
            break;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Bloombees Proofs of concept</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <!-- Bootstrap 3.3.6 -->
    <link rel="stylesheet" href="/webapp/assets/bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="/webapp/assets/plugins/select2/select2.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/webapp/assets/dist/css/AdminLTE.min.css">
    <!-- AdminLTE Skins. Choose a skin from the css/skins
         folder instead of downloading all of them to reduce the load. -->
    <link rel="stylesheet" href="/webapp/assets/dist/css/skins/_all-skins.min.css">
    <!-- iCheck -->
    <link rel="stylesheet" href="/webapp/assets/plugins/iCheck/flat/blue.css">
    <!-- Morris chart -->
    <link rel="stylesheet" href="/webapp/assets/plugins/morris/morris.css">
    <!-- jvectormap -->
    <link rel="stylesheet" href="/webapp/assets/plugins/jvectormap/jquery-jvectormap-1.2.2.css">
    <!-- Date Picker -->
    <link rel="stylesheet" href="/webapp/assets/plugins/datepicker/datepicker3.css">
    <!-- Daterange picker -->
    <link rel="stylesheet" href="/webapp/assets/plugins/daterangepicker/daterangepicker-bs3.css">
    <!-- bootstrap wysihtml5 - text editor -->
    <link rel="stylesheet" href="/webapp/assets/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

    <header class="main-header">
        <!-- Logo -->
        <a href="/" class="logo">
            <!-- mini logo for sidebar mini 50x50 pixels -->
            <span class="logo-mini"><b>C</b> BB</span>
            <!-- logo for regular state and mobile devices -->
            <span class="logo-lg"><b>Concept</b> Bloombees</span>
        </a>
        <!-- Header Navbar: style can be found in header.less -->
        <nav class="navbar navbar-static-top">
            <!-- Sidebar toggle button-->
            <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
                <span class="sr-only">Toggle navigation</span>
            </a>

            <div class="navbar-custom-menu">
            </div>
        </nav>
    </header>
    <!-- Left side column. contains the logo and sidebar -->
    <aside class="main-sidebar">
        <!-- sidebar: style can be found in sidebar.less -->
        <section class="sidebar">
            <!-- Sidebar user panel -->
            <!--<div class="user-panel">
                <div class="pull-left image">
                    <img src="/webapp/assets/dist/img/user2-160x160.jpg" class="img-circle" alt="User Image">
                </div>
                <div class="pull-left info">
                    <p>Alexander Pierce</p>
                    <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
                </div>
            </div>-->
            <!-- search form -->
            <!--<form action="#" method="get" class="sidebar-form">
                <div class="input-group">
                    <input type="text" name="q" class="form-control" placeholder="Search...">
              <span class="input-group-btn">
                <button type="submit" name="search" id="search-btn" class="btn btn-flat"><i class="fa fa-search"></i>
                </button>
              </span>
                </div>
            </form>-->
            <!-- /.search form -->
            <!-- sidebar menu: : style can be found in sidebar.less -->
            <ul class="sidebar-menu">
                <!--<li class="header">MAIN NAVIGATION</li>-->
                <li class="active treeview">
                    <a href="#">
                        <i class="fa fa-facebook-square"></i> <span>Facebook Ads</span> <i
                            class="fa fa-angle-left pull-right"></i>
                    </a>
                    <ul class="treeview-menu">
                        <?php if (!isset($_SESSION["params_socialnetworks"][$api->params[0]])) { ?>
                            <li><a href="/api/marketing-concept/<?php echo $api->params[0]; ?>/auth"><i
                                        class="fa fa-sign-in"></i> Authentication</a></li>
                        <?php } ?>
                        <?php if (isset($_SESSION["params_socialnetworks"][$api->params[0]])) { ?>
                            <li<?php if ($menuactive == 1) { ?> class="active"<?php } ?>><a
                                    href="/api/marketing-concept/<?php echo $api->params[0]; ?>/user/export/adaccount"><i
                                        class="fa fa-list"></i> Ad Accounts</a></li>
                            <!--<li<?php if ($menuactive == 2) { ?> class="active"<?php } ?>><a href="#"><i
                                        class="fa fa-calendar-o"></i> Campaigns</a></li>
                            <li<?php if ($menuactive == 3) { ?> class="active"<?php } ?>><a href="#"><i
                                        class="fa fa-object-group"></i> Advert Sets</a></li>
                            <li<?php if ($menuactive == 4) { ?> class="active"<?php } ?>><a href="#"><i
                                        class="fa fa-object-group"></i> New Advert</a></li>-->

                        <?php } ?>
                    </ul>
                </li>
            </ul>
        </section>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <h1>Facebook Ads</h1>
            <ol class="breadcrumb">
                <?php
                if ($api->params[1] !== "home") {
                ?>
                <li<?php if ($api->params[1] === "home") { ?> class="active"<?php } ?>><a
                        href="/api/marketing-concept/<?php echo $api->params[0]; ?>/home"><i
                            class="fa fa-facebook-square"></i> Facebook Ads</a></li>
                <li class="active"><?php
                    switch ($menuactive) {
                        case 1:
                            echo "Ad Accounts";
                            break;
                        case 2:
                            echo "<a href='/api/marketing-concept/" . $api->params[0] . "/user/export/adaccount'>Ad Accounts</a></li>
                                    <li class='active'><a href='/api/marketing-concept/" . $api->params[0] . "/user/export/adaccount/" . $adAccount["id"] . "/campaign'>" . $adAccount["name"] . "</a></li>
                                    <li class='active'>Campaigns";
                            break;
                        case 3:
                            echo "<a href='/api/marketing-concept/" . $api->params[0] . "/user/export/adaccount'>Ad Accounts</a></li>
                                    <li class='active'><a href='/api/marketing-concept/" . $api->params[0] . "/user/export/adaccount/" . $adAccount["id"] . "/campaign'>" . $adAccount["name"] . "</a></li>
                                    <li class='active'>" . $campaign["name"];
                            break;
                        case 4:
                        case 7:
                            echo "<a href='/api/marketing-concept/" . $api->params[0] . "/user/export/adaccount'>Ad Accounts</a></li>
                                    <li class='active'><a href='/api/marketing-concept/" . $api->params[0] . "/user/export/adaccount/" . $adAccount["id"] . "/campaign'>" . $adAccount["name"] . "</a></li>
                                    <li class='active'>New Advert";
                            break;
                        case 5:
                            echo "<a href='/api/marketing-concept/" . $api->params[0] . "/user/export/adaccount'>Ad Accounts</a></li>
                                    <li class='active'><a href='/api/marketing-concept/" . $api->params[0] . "/user/export/adaccount/" . $adAccount["id"] . "/campaign'>" . $adAccount["name"] . "</a></li>
                                    <li class='active'><a href='/api/marketing-concept/" . $api->params[0] . "/campaign/" . $campaign["id"] . "/adset'>" . $campaign["name"] . "</a></li>
                                    <li class='active'>" . $adset["name"];
                            break;
                        case 6:
                            echo "<a href='/api/marketing-concept/" . $api->params[0] . "/user/export/adaccount'>Ad Accounts</a></li>
                                    <li class='active'><a href='/api/marketing-concept/" . $api->params[0] . "/user/export/adaccount/" . $adAccount["id"] . "/campaign'>" . $adAccount["name"] . "</a></li>
                                    <li class='active'>Ad Images";
                            break;
                    }
                    }
                    ?>
                </li>
            </ol>
        </section>

        <!-- Main content -->
        <section class="content">
            <?php
            if ($api->params[1] == "home") {
                ?>
                <!-- Small boxes (Stat box) -->
                <div class="row">
                    <div style="opacity: 0.3">
                        <img class="img-responsive" src="/webapp/assets/dist/img/facebook.png"/>
                    </div>
                </div>
                <?php
            } else {
                //print_r($value);
                if ($menuactive == 1) {
                    ?>
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="box">
                                <div class="box-header">
                                    <h3 class="box-title">Ad Accounts</h3>
                                    <div class="box-tools"></div>
                                </div>
                                <!-- /.box-header -->
                                <div class="box-body table-responsive no-padding">
                                    <table class="table table-hover">
                                        <tbody>
                                        <tr>
                                            <th>Account</th>
                                            <th>ID</th>
                                            <th>Status</th>
                                            <th>Balance</th>
                                            <th>Properties</th>
                                        </tr>
                                        <?php foreach ($value as $adAccount) { ?>
                                            <tr>
                                                <td><?php echo $adAccount["name"]; ?>
                                                </td>
                                                <td><?php echo str_replace("act_", "", $adAccount["id"]); ?></td>
                                                <td><?php
                                                    $status = "ACTIVE";
                                                    switch ($adAccount["account_status"]) {
                                                        case 2:
                                                            $status = "DISABLED";
                                                            break;
                                                        case 3:
                                                            $status = "UNSETTLED";
                                                            break;
                                                        case 7:
                                                            $status = "PENDING_RISK_REVIEW";
                                                            break;
                                                        case 9:
                                                            $status = "IN_GRACE_PERIOD";
                                                            break;
                                                        case 100:
                                                            $status = "PENDING_CLOSURE";
                                                            break;
                                                        case 101:
                                                            $status = "CLOSED";
                                                            break;
                                                        case 102:
                                                            $status = "PENDING_SETTLEMENT";
                                                            break;
                                                        case 201:
                                                            $status = "ANY_ACTIVE";
                                                            break;
                                                        case 202:
                                                            $status = "ANY_CLOSED";
                                                            break;
                                                    }
                                                    echo $status;
                                                    ?></td>
                                                <td><?php echo $adAccount["balance"] / 100; ?>€</td>
                                                <td>
                                                    <a href="/api/marketing-concept/<?php echo $api->params[0]; ?>/user/export/adaccount/<?php echo $adAccount["id"]; ?>/campaign">Campaigns</a>&nbsp;&nbsp;
                                                    <a href="/api/marketing-concept/<?php echo $api->params[0]; ?>/user/export/adaccount/<?php echo $adAccount["id"]; ?>/adimage">Images</a>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-success">Tools</button>
                                                        <button type="button" class="btn btn-success dropdown-toggle"
                                                                data-toggle="dropdown">
                                                            <span class="caret"></span>
                                                            <span class="sr-only">Toggle Dropdown</span>
                                                        </button>
                                                        <ul class="dropdown-menu" role="menu">
                                                            <li><a id="newad<?php echo $campaign["id"]; ?>"
                                                                   href="/api/marketing-concept/<?php echo $api->params[0]; ?>/adaccount/<?php echo $adAccount["id"]; ?>/create/ad">New
                                                                    Advert</a></li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        <tr>
                                            <td>&nbsp;</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- /.box-body -->
                            </div>
                            <!-- /.box -->
                        </div>
                    </div>
                    <?php
                } else if ($menuactive == 2) {
                    ?>
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="box">
                                <div class="box-header">
                                    <h3 class="box-title">Campaigns</h3>

                                    <div class="box-tools"></div>
                                </div>
                                <!-- /.box-header -->
                                <div class="box-body table-responsive no-padding">
                                    <table class="table table-hover">
                                        <tbody>
                                        <tr>
                                            <th>Campaign name</th>
                                            <th>Objective</th>
                                            <th>Status</th>
                                            <th></th>
                                        </tr>
                                        <?php foreach ($value as $campaign) { ?>
                                            <tr>
                                                <td>
                                                    <a href="/api/marketing-concept/<?php echo $api->params[0]; ?>/campaign/<?php echo $campaign["id"]; ?>/adset"><?php echo $campaign["name"]; ?></a>
                                                </td>
                                                <td>
                                                    <?php
                                                    switch ($campaign["objective"]) {
                                                        case "POST_ENGAGEMENT":
                                                            echo "Promote your posts";
                                                            break;
                                                        case "CANVAS_APP_ENGAGEMENT":
                                                            echo "Increase the interaction with your application";
                                                            break;
                                                        case "CANVAS_APP_INSTALLS":
                                                            echo "Increase the installation of your application";
                                                            break;
                                                        case "EVENT_RESPONSES":
                                                            echo "Increase the number of attendants to your event";
                                                            break;
                                                        case "LOCAL_AWARENESS":
                                                            echo "Go to people who are near your business";
                                                            break;
                                                        case "MOBILE_APP_ENGAGEMENT":
                                                            echo "Increase the interaction with your mobile app";
                                                            break;
                                                        case "MOBILE_APP_INSTALLS":
                                                            echo "Increase the installations of your mobile app";
                                                            break;
                                                        case "OFFER_CLAIMS":
                                                            echo "Create offers for users to redeem in your establishment";
                                                            break;
                                                        case "PAGE_LIKES":
                                                            echo "Promote your page and get I like to connect with more people relevant.";
                                                            break;
                                                        case "PRODUCT_CATALOG_SALES":
                                                            echo "Promote a list of products you want to advertise on Facebook";
                                                            break;
                                                        case "LINK_CLICKS":
                                                            echo "Attract people to your website";
                                                            break;
                                                        case "CONVERSIONS":
                                                            echo "Increase conversions on your site. You will need a pixel conversion for your site before you can create this ad";
                                                            break;
                                                        case "VIDEO_VIEWS":
                                                            echo "Create ads that make more people watch a video ";
                                                            break;
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo $campaign["effective_status"]; ?></td>
                                            </tr>
                                        <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- /.box-body -->
                            </div>
                            <!-- /.box -->
                        </div>
                    </div>
                    <?php
                } else if ($menuactive == 3) {
                    ?>
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="box">
                                <div class="box-header">
                                    <h3 class="box-title">Advert Sets</h3>

                                    <div class="box-tools"></div>
                                </div>
                                <!-- /.box-header -->
                                <div class="box-body table-responsive no-padding">
                                    <table class="table table-hover">
                                        <tbody>
                                        <tr>
                                            <th>Advert Set Name</th>
                                            <th>Budget</th>
                                            <th>Budget remaining</th>
                                            <th>Billing event</th>
                                            <th>Status</th>
                                        </tr>
                                        <?php foreach ($value as $adset) { ?>
                                            <tr>
                                                <td>
                                                    <a href="/api/marketing-concept/<?php echo $api->params[0]; ?>/adset/<?php echo $adset["id"]; ?>/ad"><?php echo $adset["name"]; ?></a>
                                                </td>
                                                <td><?php
                                                    $budget = $adset["daily_budget"] / 100;
                                                    if ($budget > 0) {
                                                        echo $budget . "€<br/>Daily budget";
                                                    } else {
                                                        echo $adset["lifetime_budget"] / 100 . "€";
                                                    }
                                                    ?></td>
                                                <td><?php echo $adset["budget_remaining"] / 100 . "€"; ?></td>
                                                <td><?php echo $adset["billing_event"]; ?></td>
                                                <td><?php echo $campaign["effective_status"]; ?></td>
                                            </tr>
                                        <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- /.box-body -->
                            </div>
                            <!-- /.box -->
                        </div>
                    </div>
                    <?php
                } else if ($menuactive == 4) {
                    ?>
                    <div class="row">
                        <div class="col-md-12">
                            <!-- Horizontal Form -->
                            <div class="box box-info">
                                <!-- form start -->
                                <form class="form-horizontal" method="post" id="frm_ad"
                                      action="/api/marketing-concept/facebook/create/ad/<?php echo $api->params[2]; ?>"
                                      enctype="multipart/form-data">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">Campaign</h3>
                                    </div>
                                    <!-- /.box-header -->
                                    <div class="box-body">
                                        <div class="form-group">
                                            <label for="campaign" class="col-sm-2 control-label">Campaign</label>

                                            <div class="radio">
                                                <label>
                                                    <input name="campaign" id="campaign_new" value="new" checked=""
                                                           type="radio">
                                                    Create a new campaign
                                                </label>
                                            </div>
                                        </div>
                                        <div class="form-group" style="margin-top:-15px">
                                            <label for="campaign" class="col-sm-2 control-label">&nbsp;</label>

                                            <div class="radio">
                                                <label>
                                                    <input name="campaign" id="campaign_existing" value="existing"
                                                           type="radio">
                                                    Use Existing campaign
                                                </label>
                                            </div>
                                        </div>
                                        <div class="form-group" id="campaign_name">
                                            <label for="product_type" class="col-sm-2 control-label">Campaign
                                                name</label>

                                            <div class="col-sm-10">
                                                <input class="form-control" name="campaign_name"
                                                       placeholder="Campaign name" type="text">
                                            </div>
                                        </div>
                                        <div class="form-group" id="campaign_id">
                                            <label for="product_type" class="col-sm-2 control-label">Existing
                                                campaign</label>

                                            <div class="col-sm-10">
                                                <select class="form-control" name="campaign_id" id="existing_campaigns">
                                                    <?php foreach ($campaigns as $campaign) { ?>
                                                        <option
                                                            value="<?php echo $campaign["id"]; ?>"><?php echo $campaign["name"]; ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group" id="campaign_objective">
                                            <label for="product_type" class="col-sm-2 control-label">Campaign
                                                objective</label>

                                            <div class="col-sm-10">
                                                <select class="form-control" name="campaign_objective"
                                                        style="margin-top:10px">
                                                    <option value="POST_ENGAGEMENT">Promote your posts</option>
                                                    <option value="CANVAS_APP_ENGAGEMENT" disabled>Increase the
                                                        interaction with your application
                                                    </option>
                                                    <option value="CANVAS_APP_INSTALLS" disabled>Increase the
                                                        installation of your application
                                                    </option>
                                                    <option value="EVENT_RESPONSES" disabled>Increase the number of
                                                        attendants to your event
                                                    </option>
                                                    <option value="LOCAL_AWARENESS" disabled>Go to people who are near
                                                        your business
                                                    </option>
                                                    <option value="MOBILE_APP_ENGAGEMENT" disabled>Increase the
                                                        interaction with your mobile app
                                                    </option>
                                                    <option value="MOBILE_APP_INSTALLS" disabled>Increase the
                                                        installations of your mobile app
                                                    </option>
                                                    <option value="OFFER_CLAIMS" disabled>Create offers for users to
                                                        redeem in your establishment
                                                    </option>
                                                    <option value="PAGE_LIKES" disabled>Promote your page and get I like
                                                        to connect with more people relevant.
                                                    </option>
                                                    <option value="PRODUCT_CATALOG_SALES" disabled>Promote a list of
                                                        products you want to advertise on Facebook
                                                    </option>
                                                    <option value="LINK_CLICKS" disabled>Attract people to your
                                                        website
                                                    </option>
                                                    <option value="CONVERSIONS" disabled>Increase conversions on your
                                                        site. You will need a pixel conversion for your
                                                        site before you can create this ad
                                                    </option>
                                                    <option value="VIDEO_VIEWS" disabled>Create ads that make more
                                                        people watch a video
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="campaign" class="col-sm-2 control-label">Post</label>

                                            <div class="radio">
                                                <label>
                                                    <input name="post" id="post_new" value="new" checked=""
                                                           type="radio">
                                                    Create a new post
                                                </label>
                                            </div>
                                        </div>
                                        <div class="form-group" style="margin-top:-15px">
                                            <label for="campaign" class="col-sm-2 control-label">&nbsp;</label>

                                            <div class="radio">
                                                <label>
                                                    <input name="post" id="post_existing" value="existing"
                                                           type="radio">
                                                    Use Existing Post
                                                </label>
                                            </div>
                                        </div>
                                        <div class="form-group" id="user_pages">
                                            <label for="product_type" class="col-sm-2 control-label">User pages</label>

                                            <div class="col-sm-10">
                                                <select class="form-control" name="user_page" id="user_page">
                                                    <option value="0">Select page ...</option>
                                                    <?php foreach ($pages["pages"] as $page) { ?>
                                                        <option
                                                            value="<?php echo $page["id"]; ?>"><?php echo $page["name"]; ?></option>
                                                    <?php } ?>
                                                </select>
                                                <?php if (count($pages["pages"]) > 0) { ?>
                                                    <a id="page_url" target="_blank" style="display:none" href="#">Go to
                                                        page</a>
                                                <?php } ?>
                                            </div>
                                        </div>
                                        <div class="form-group" id="advert_types">
                                            <label for="product_type" class="col-sm-2 control-label">Advert type</label>

                                            <div class="col-sm-10">
                                                <select class="form-control" name="advert_type" id="advert_type">
                                                    <option value="1">Link Advert</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group new_post">
                                            <label for="post_title" class="col-sm-2 control-label">Advert
                                                message</label>

                                            <div class="col-sm-10">
                                                <input class="form-control" name="advert_message"
                                                       placeholder="Advert message" type="text">
                                            </div>
                                        </div>
                                        <div class="form-group new_post">
                                            <label for="post_body" class="col-sm-2 control-label">Advert link</label>

                                            <div class="col-sm-10">
                                                <input class="form-control" name="advert_link" placeholder="Advert link"
                                                       type="text">
                                            </div>
                                        </div>
                                        <div class="form-group new_post">
                                            <label for="post_url" class="col-sm-2 control-label">Advert caption</label>

                                            <div class="col-sm-10">
                                                <input class="form-control" name="advert_caption"
                                                       placeholder="Advert caption" type="text">
                                            </div>
                                        </div>
                                        <div class="form-group new_post">
                                            <label for="post_image" class="col-sm-2 control-label">Advert image</label>

                                            <div class="col-sm-10">
                                                <input class="form-control" name="advert_image" type="file">
                                            </div>
                                        </div>
                                        <div class="form-group" id="promotable_posts">
                                            <label for="product_type" class="col-sm-2 control-label">Promotable
                                                Post</label>

                                            <div class="col-sm-10">
                                                <select class="form-control" name="promotable_post"
                                                        id="promotable_post">
                                                    <option value="0">Select post ...</option>
                                                </select>
                                            </div>
                                        </div>

                                    </div>
                                    <!-- /.box-body -->
                                    <div class="box-header with-border">
                                        <h3 class="box-title">Advert Set</h3>
                                    </div>
                                    <!-- /.box-header -->
                                    <div class="box-body">
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label"><h4>General data</h4></label>
                                        </div>
                                        <div class="form-group">
                                            <label for="adset" class="col-sm-2 control-label">Advert Set</label>

                                            <div class="radio">
                                                <label>
                                                    <input name="adset" id="adset_new" value="new" checked=""
                                                           type="radio">
                                                    Create a new Advert Set
                                                </label>
                                            </div>
                                        </div>
                                        <div class="form-group" style="margin-top:-15px">
                                            <label for="adset" class="col-sm-2 control-label">&nbsp;</label>

                                            <div class="radio">
                                                <label>
                                                    <input name="adset" id="adset_existing" value="existing"
                                                           type="radio">
                                                    Use Existing Advert Set
                                                </label>
                                            </div>
                                        </div>
                                        <div class="form-group" id="adset_name">
                                            <label for="adset_name" class="col-sm-2 control-label">Advert Set
                                                name</label>

                                            <div class="col-sm-10">
                                                <input class="form-control" name="adset_name"
                                                       placeholder="Advert Set name" type="text">
                                            </div>
                                        </div>
                                        <div class="form-group" id="adsets">
                                            <label for="adset_id" class="col-sm-2 control-label">Advert Sets in
                                                campaign</label>

                                            <div class="col-sm-10">
                                                <select class="form-control" name="adset_id" id="adset_id"
                                                        style="margin-top:10px">
                                                    <option value="0">Select Advert Set ...</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label"><h4>Budget & Schedule</h4></label>
                                        </div>
                                        <div class="form-group" id="budget">
                                            <label for="adset_id" class="col-sm-2 control-label">Budget type</label>

                                            <div class="col-sm-10">
                                                <select class="form-control" name="budget_type" id="budget_type"
                                                        style="margin-top:10px">
                                                    <option value="0">Daily budget</option>
                                                    <option value="1">Lifetime budget</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group" id="adset_name">
                                            <label for="adset_name" class="col-sm-2 control-label">Budget amount</label>

                                            <div class="col-sm-10">
                                                <input class="form-control" name="budget_amount" id="budget_amount"
                                                       placeholder="€" type="number">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="adset_name" class="col-sm-2 control-label">Schedule
                                                start</label>

                                            <div class="col-sm-10">
                                                <div class="input-group date">
                                                    <div class="input-group-addon">
                                                        <i class="fa fa-calendar"></i>
                                                    </div>
                                                    <input class="form-control pull-right datepicker" name="start_time"
                                                           id="start_time" type="text" readonly>
                                                </div>
                                                <!-- /.input group -->
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="adset_name" class="col-sm-2 control-label">Schedule end</label>

                                            <div class="col-sm-10">
                                                <div class="input-group date">
                                                    <div class="input-group-addon">
                                                        <i class="fa fa-calendar"></i>
                                                    </div>
                                                    <input class="form-control pull-right datepicker" name="end_time"
                                                           id="end_time" type="text" readonly>
                                                </div>
                                                <!-- /.input group -->
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label"><h4>Audience</h4></label>
                                        </div>
                                        <div class="form-group" id="location">
                                            <label for="location" class="col-sm-2 control-label">Location</label>

                                            <div class="col-sm-10">
                                                <select class="form-control select2" name="locations[]" id="locations"
                                                        multiple="multiple" data-placeholder="Select countries"
                                                        style="width: 100%;">
                                                    <?php foreach ($countries as $country) { ?>
                                                        <option
                                                            value="<?php echo $country["country_code"]; ?>"><?php echo $country["country_name"]; ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group" id="age">
                                            <label for="age" class="col-sm-2 control-label">Age</label>

                                            <div class="col-sm-10">
                                                <select class="form-control" name="age_min" id="age_min"
                                                        style="margin-top:10px">
                                                    <?php for ($i = 13; $i < 65; $i++) { ?>
                                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                    <?php } ?>
                                                    <option value="65">65+</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group" id="age">
                                            <label for="age" class="col-sm-2 control-label">&nbsp;</label>

                                            <div class="col-sm-10">
                                                <select class="form-control" name="age_max" id="age_max"
                                                        style="margin-top:10px">
                                                    <?php for ($i = 13; $i < 65; $i++) { ?>
                                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                    <?php } ?>
                                                    <option value="65">65+</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="gender" class="col-sm-2 control-label">Gender</label>

                                            <div class="col-sm-10">
                                                <select class="form-control" name="gender" id="gender"
                                                        style="margin-top:10px">
                                                    <option value="0">All</option>
                                                    <option value="1">Male</option>
                                                    <option value="2">Female</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label"><h4>Placement</h4></label>
                                        </div>
                                        <div class="form-group">
                                            <label for="placements" class="col-sm-2 control-label">Placements</label>

                                            <div class="col-sm-10">
                                                <select class="form-control select2" name="placements[]" id="placements"
                                                        multiple="multiple" data-placeholder="Select placements"
                                                        style="width: 100%;">
                                                    <option value="mobilefeed">Mobile news feed</option>
                                                    <option value="desktopfeed">Desktop news feed</option>
                                                    <option value="rightcolumn">Desktop right column</option>
                                                    <option value="instagramstream" disabled="disabled">Instagram
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- /.box-body -->
                                    <div class="box-body">
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label"><h4>Optimisation & pricing</h4>
                                            </label>
                                        </div>
                                        <div class="form-group">
                                            <label for="adset" class="col-sm-2 control-label">When you are
                                                charged</label>

                                            <div class="radio">
                                                <label>
                                                    <input name="billing_event" id="billing_event" value="IMPRESSIONS"
                                                           checked="true"
                                                           type="radio">
                                                    Impression (CPM)
                                                </label>
                                            </div>
                                        </div>
                                        <div class="form-group" style="margin-top:-15px">
                                            <label for="adset" class="col-sm-2 control-label">&nbsp;</label>

                                            <div class="radio">
                                                <label>
                                                    <input name="billing_event" id="billing_event"
                                                           value="POST_ENGAGEMENT"
                                                           type="radio">
                                                    Post engagement
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- /.box-body -->
                                    <div class="box-header with-border">
                                        <h3 class="box-title">Advert</h3>
                                    </div>
                                    <!-- /.box-header -->
                                    <div class="box-body">
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label"><h4>General data</h4></label>
                                        </div>
                                        <div class="form-group" id="ad_name">
                                            <label for="ad_name" class="col-sm-2 control-label">Advert name</label>

                                            <div class="col-sm-10">
                                                <input class="form-control" name="ad_name"
                                                       placeholder="Advert name" type="text">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="box-footer">
                                        <button type="submit" class="btn btn-info pull-right">Create</button>
                                    </div>
                                    <!-- /.box-footer -->
                                </form>
                            </div>
                            <!-- /.box -->
                        </div>
                    </div>
                    <?php
                } else if ($menuactive == 5) {
                    ?>
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="box">
                                <div class="box-header">
                                    <h3 class="box-title">Adverts</h3>

                                    <div class="box-tools"></div>
                                </div>
                                <!-- /.box-header -->
                                <div class="box-body table-responsive no-padding">
                                    <table class="table table-hover">
                                        <tbody>
                                        <tr>
                                            <th>Advert Name</th>
                                            <th>Previews</th>
                                            <th>Status</th>
                                        </tr>
                                        <?php foreach ($value as $ad) { ?>
                                            <tr>
                                                <td>
                                                    <a href="#"><?php echo $ad["name"]; ?></a>
                                                </td>
                                                <td>|&nbsp;&nbsp;
                                                    <?php
                                                    foreach ($adset["targeting"]["page_types"] as $pageType) {
                                                        ?>
                                                        <a href="javascript:void(0)" class="preview"
                                                           id="<?php echo $ad["id"] . "_" . $pageType; ?>"><?php switch ($pageType) {
                                                                case "desktopfeed":
                                                                    echo "News Feed on Facebook Desktop";
                                                                    break;
                                                                case "rightcolumn":
                                                                    echo "Right column on Facebook Desktop";
                                                                    break;
                                                                case "mobilefeed":
                                                                    echo "News Feed on Facebook Mobile";
                                                                    break;
                                                            }
                                                            ?></a>&nbsp;&nbsp;|&nbsp;&nbsp;
                                                        <?php
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo $ad["effective_status"]; ?></td>
                                            </tr>
                                        <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- /.box-body -->
                            </div>
                            <!-- /.box -->
                        </div>
                    </div>
                    <div class="col-xs-6">
                        <div class="box">
                            <div class="box-header">
                                <h5 class="box-title">Advert name: <strong><span id="advert_name"></span></strong></h5>
                                <br/>
                                <h5 class="box-title">Preview: <strong><span id="preview"></span></strong></h5>
                            </div>
                            <div class="box-body" id="preview_body"></div>
                        </div>
                    </div>
                    <?php
                } else if ($menuactive == 6) {
                    ?>
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="box">
                                <div class="box-header">
                                    <h3 class="box-title">Ad Images</h3>

                                    <div class="box-tools"></div>
                                </div>
                                <!-- /.box-header -->
                                <div class="box-body table-responsive no-padding">
                                    <table class="table table-hover">
                                        <tbody>
                                        <tr>
                                            <th>AD Image Name</th>
                                            <th>Hash</th>
                                            <th>Status</th>
                                            <th>Preview</th>
                                        </tr>
                                        <?php foreach ($value as $adimage) { ?>
                                            <tr>
                                                <td><?php echo $adimage["name"]; ?></a></td>
                                                <td><?php echo $adimage["hash"]; ?></td>
                                                <td><?php echo $adimage["status"]; ?></td>
                                                <td><img src="<?php echo $adimage["url_128"]; ?>" height="40"></td>
                                            </tr>
                                        <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- /.box-body -->
                            </div>
                            <!-- /.box -->
                        </div>
                    </div>
                    <?php
                } else if ($menuactive == 7) {
                    ?>
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="box">
                                <div class="box-header">
                                    <h3 class="box-title">New Advert</h3>

                                    <div class="box-tools"></div>
                                </div>
                                <!-- /.box-header -->
                                <div class="box-body"><?php echo $e->getMessage(); ?></div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->
    <footer class="main-footer">
        <div class="pull-right hidden-xs">
            <b>Version</b> 2.3.3
        </div>
        <strong>Copyright &copy; <?php echo date("Y"); ?> <a href="http://bloombees.com">Bloombees</a>.</strong> All
        rights
        reserved.
    </footer>

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
        <!-- Create the tabs -->
        <ul class="nav nav-tabs nav-justified control-sidebar-tabs">
            <li><a href="#control-sidebar-home-tab" data-toggle="tab"><i class="fa fa-home"></i></a></li>
            <li><a href="#control-sidebar-settings-tab" data-toggle="tab"><i class="fa fa-gears"></i></a></li>
        </ul>
        <!-- Tab panes -->
        <div class="tab-content">
            <!-- Home tab content -->
            <div class="tab-pane" id="control-sidebar-home-tab">
                <h3 class="control-sidebar-heading">Recent Activity</h3>
                <ul class="control-sidebar-menu">
                    <li>
                        <a href="javascript:void(0)">
                            <i class="menu-icon fa fa-birthday-cake bg-red"></i>

                            <div class="menu-info">
                                <h4 class="control-sidebar-subheading">Langdon's Birthday</h4>

                                <p>Will be 23 on April 24th</p>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)">
                            <i class="menu-icon fa fa-user bg-yellow"></i>

                            <div class="menu-info">
                                <h4 class="control-sidebar-subheading">Frodo Updated His Profile</h4>

                                <p>New phone +1(800)555-1234</p>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)">
                            <i class="menu-icon fa fa-envelope-o bg-light-blue"></i>

                            <div class="menu-info">
                                <h4 class="control-sidebar-subheading">Nora Joined Mailing List</h4>

                                <p>nora@example.com</p>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)">
                            <i class="menu-icon fa fa-file-code-o bg-green"></i>

                            <div class="menu-info">
                                <h4 class="control-sidebar-subheading">Cron Job 254 Executed</h4>

                                <p>Execution time 5 seconds</p>
                            </div>
                        </a>
                    </li>
                </ul>
                <!-- /.control-sidebar-menu -->

                <h3 class="control-sidebar-heading">Tasks Progress</h3>
                <ul class="control-sidebar-menu">
                    <li>
                        <a href="javascript:void(0)">
                            <h4 class="control-sidebar-subheading">
                                Custom Template Design
                                <span class="label label-danger pull-right">70%</span>
                            </h4>

                            <div class="progress progress-xxs">
                                <div class="progress-bar progress-bar-danger" style="width: 70%"></div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)">
                            <h4 class="control-sidebar-subheading">
                                Update Resume
                                <span class="label label-success pull-right">95%</span>
                            </h4>

                            <div class="progress progress-xxs">
                                <div class="progress-bar progress-bar-success" style="width: 95%"></div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)">
                            <h4 class="control-sidebar-subheading">
                                Laravel Integration
                                <span class="label label-warning pull-right">50%</span>
                            </h4>

                            <div class="progress progress-xxs">
                                <div class="progress-bar progress-bar-warning" style="width: 50%"></div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)">
                            <h4 class="control-sidebar-subheading">
                                Back End Framework
                                <span class="label label-primary pull-right">68%</span>
                            </h4>

                            <div class="progress progress-xxs">
                                <div class="progress-bar progress-bar-primary" style="width: 68%"></div>
                            </div>
                        </a>
                    </li>
                </ul>
                <!-- /.control-sidebar-menu -->

            </div>
            <!-- /.tab-pane -->
            <!-- Stats tab content -->
            <div class="tab-pane" id="control-sidebar-stats-tab">Stats Tab Content</div>
            <!-- /.tab-pane -->
            <!-- Settings tab content -->
            <div class="tab-pane" id="control-sidebar-settings-tab">
                <form method="post">
                    <h3 class="control-sidebar-heading">General Settings</h3>

                    <div class="form-group">
                        <label class="control-sidebar-subheading">
                            Report panel usage
                            <input type="checkbox" class="pull-right" checked>
                        </label>

                        <p>
                            Some information about this general settings option
                        </p>
                    </div>
                    <!-- /.form-group -->

                    <div class="form-group">
                        <label class="control-sidebar-subheading">
                            Allow mail redirect
                            <input type="checkbox" class="pull-right" checked>
                        </label>

                        <p>
                            Other sets of options are available
                        </p>
                    </div>
                    <!-- /.form-group -->

                    <div class="form-group">
                        <label class="control-sidebar-subheading">
                            Expose author name in posts
                            <input type="checkbox" class="pull-right" checked>
                        </label>

                        <p>
                            Allow the user to show his name in blog posts
                        </p>
                    </div>
                    <!-- /.form-group -->

                    <h3 class="control-sidebar-heading">Chat Settings</h3>

                    <div class="form-group">
                        <label class="control-sidebar-subheading">
                            Show me as online
                            <input type="checkbox" class="pull-right" checked>
                        </label>
                    </div>
                    <!-- /.form-group -->

                    <div class="form-group">
                        <label class="control-sidebar-subheading">
                            Turn off notifications
                            <input type="checkbox" class="pull-right">
                        </label>
                    </div>
                    <!-- /.form-group -->

                    <div class="form-group">
                        <label class="control-sidebar-subheading">
                            Delete chat history
                            <a href="javascript:void(0)" class="text-red pull-right"><i class="fa fa-trash-o"></i></a>
                        </label>
                    </div>
                    <!-- /.form-group -->
                </form>
            </div>
            <!-- /.tab-pane -->
        </div>
    </aside>
    <!-- /.control-sidebar -->
    <!-- Add the sidebar's background. This div must be placed
         immediately after the control sidebar -->
    <div class="control-sidebar-bg"></div>
</div>
<!-- ./wrapper -->

<!-- jQuery 2.2.0 -->
<script src="/webapp/assets/plugins/jQuery/jQuery-2.2.0.min.js"></script>
<!-- jQuery UI 1.11.4 -->
<script src="https://code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
    $.widget.bridge('uibutton', $.ui.button);
</script>
<!-- Bootstrap 3.3.6 -->
<script src="/webapp/assets/bootstrap/js/bootstrap.min.js"></script>
<!-- Select2 -->
<script src="/webapp/assets/plugins/select2/select2.full.min.js"></script>
<!-- Morris.js charts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
<script src="/webapp/assets/plugins/morris/morris.min.js"></script>
<!-- Sparkline -->
<script src="/webapp/assets/plugins/sparkline/jquery.sparkline.min.js"></script>
<!-- jvectormap -->
<script src="/webapp/assets/plugins/jvectormap/jquery-jvectormap-1.2.2.min.js"></script>
<script src="/webapp/assets/plugins/jvectormap/jquery-jvectormap-world-mill-en.js"></script>
<!-- jQuery Knob Chart -->
<script src="/webapp/assets/plugins/knob/jquery.knob.js"></script>
<!-- daterangepicker -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.11.2/moment.min.js"></script>
<script src="/webapp/assets/plugins/daterangepicker/daterangepicker.js"></script>
<!-- datepicker -->
<script src="/webapp/assets/plugins/datepicker/bootstrap-datepicker.js"></script>
<!-- Bootstrap WYSIHTML5 -->
<script src="/webapp/assets/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js"></script>
<!-- Slimscroll -->
<script src="/webapp/assets/plugins/slimScroll/jquery.slimscroll.min.js"></script>
<!-- FastClick -->
<script src="/webapp/assets/plugins/fastclick/fastclick.js"></script>
<!-- AdminLTE App -->
<script src="/webapp/assets/dist/js/app.min.js"></script>
<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<script src="/webapp/assets/dist/js/pages/dashboard.js"></script>
<!-- AdminLTE for demo purposes -->
<script src="/webapp/assets/dist/js/demo.js"></script>
<script>
    $(function () {
        //bootstrap WYSIHTML5 - text editor
        $(".textarea").wysihtml5({
            "html": true,
            "required": true
        });
    });

    //Date picker
    $('.datepicker').datepicker({
        autoclose: true,
        startDate: new Date(),
        format: "dd/mm/yyyy"
    });

    //Initialize Select2 Elements
    $(".select2").select2();

    $("input[name='campaign']").click(function () {
        if ($(this).val() === "new") {
            $("#campaign_id").hide();
            $("#campaign_name").show();
            $("#adset_new").click();
            $('#adset_id').empty();
            $('#adset_id').append($('<option>', { value: 0, text: "Select Advert Set ..." }));
        } else {
            $("#campaign_name").hide();
            $("#campaign_id").show();

            $.ajax({
                url: "/api/marketing-concept/<?php echo $api->params[0]; ?>/campaign/"+$("#existing_campaigns").val()+"/info/ajax",
                dataType: "json"
            }).done(function( response ) {

            });

            $.ajax({
                url: "/api/marketing-concept/<?php echo $api->params[0]; ?>/campaign/"+$("#existing_campaigns").val()+"/adset/ajax",
                dataType: "json"
            }).done(function( response ) {
                $('#adset_id').empty();
                $('#adset_id').append($('<option>', { value: 0, text: "Select Advert Set ..." }));
                for (var i = 0; i < response.length; i++) {
                    $('#adset_id').append($('<option>', {
                        value: response[i].id,
                        text: response[i].name
                    }));
                }
            });
        }
    });

    $("input[name='campaign']:first").click();

    $("#existing_campaigns").change(function () {
        $.ajax({
            url: "/api/marketing-concept/<?php echo $api->params[0]; ?>/campaign/"+$(this).val()+"/adset/ajax",
            dataType: "json"
        }).done(function( response ) {
            $('#adset_id').empty();
            $('#adset_id').append($('<option>', { value: 0, text: "Select Advert Set ..." }));
            for (var i = 0; i < response.length; i++) {
                $('#adset_id').append($('<option>', {
                    value: response[i].id,
                    text: response[i].name
                }));
            }
        });
    });

    $("#promotable_posts").hide();

    $("input[name='post']").click(function () {
        if ($(this).val() === "new") {
            $("#promotable_posts").hide();
            $("#advert_types").show();
            $(".new_post").show();
        } else {
            $("#promotable_posts").show();
            $("#advert_types").hide();
            $(".new_post").hide();
        }
    });

    $("input[name='adset']").click(function () {
        if ($(this).val() === "new") {
            $("#adset_name").show();
            $("#adsets").hide();
            $("input[name='billing_event']").val(["IMPRESSIONS"]);
            $("#budget_type").val("0");
            $("#budget_amount").val("");
            $("#start_time").val("");
            $("#end_time").val("");
            $("#age_min").val(13);
            $("#age_max").val(13);
            $("#gender").val(0);

            $("#locations").val(null).trigger("change");
            $("#placements").val(null).trigger("change");

        } else {
            $("#adset_name").hide();
            $("#adsets").show();
        }
    });

    $("input[name='adset']:first").click();

    $("#adset_id").change(function() {
        $.ajax({
            url: "/api/marketing-concept/<?php echo $api->params[0]; ?>/adset/"+$(this).val()+"/info",
            dataType: "json"
        }).done(function( response ) {
            $("input[name='billing_event']").val([response.billing_event]);
            if (response.daily_budget != "0") {
                $("#budget_type").val("0");
                $("#budget_amount").val(parseInt(response.daily_budget)/100)
            } else if (response.lifetime_budget != "0") {
                $("#budget_type").val("1");
                $("#budget_amount").val(parseInt(response.lifetime_budget)/100);
            }
            if (null != response.start_time) {
                var date = new Date( response.start_time );
                $("#start_time").val($.datepicker.formatDate("dd/mm/yy",date));
            }
            if (null != response.end_time) {
                var date = new Date( response.end_time );
                $("#end_time").val($.datepicker.formatDate("dd/mm/yy",date));
            }
            $("#age_min").val(response.targeting.age_min);
            $("#age_max").val(response.targeting.age_max);
            if (response.targeting.genders != null) {
                $("#gender").val(response.targeting.genders);
            } else {
                $("#gender").val(0);
            }

            $("#locations").val(response.targeting.geo_locations.countries).trigger("change");
            $("#placements").val(response.targeting.page_types).trigger("change");
        });
    });

    $("#user_page").change(function () {
        if ($(this).val() > 0) {
            $("#page_url").attr("href", "https://www.facebook.com/" + $(this).val());
            $("#page_url").show();
        } else {
            $("#page_url").attr("href", "#" + $(this).val());
            $("#page_url").hide();
        }
        $.ajax({
            url: "/api/marketing-concept/<?php echo $api->params[0]; ?>/page/"+$(this).val()+"/export/post/promotable/100/1",
            dataType: "json"
        }).done(function( response ) {
            for (var i = 0; i < response.posts.length; i++) {
                $('#promotable_post').append($('<option>', {
                    value: response.posts[i].id,
                    text: response.posts[i].message
                }));
            }
        });
    });

    $("#user_page").change();

    $("#frm_ad").on("submit", function() {
        if ($("input[name='campaign']:checked").val() == "new") {
            if ($("input[name='campaign_name']").val() == "") {
                alert("Campaign name is required");
                $("input[name='campaign_name']").focus();
                return false;
            }
        }

        if ($("#user_page").val() == 0) {
            alert("User page is required");
            $("#user_page").focus();
            return false;
        }

        if ($("input[name='post']:checked").val() == "new") {
            if ($("input[name='advert_message']").val() == "") {
                alert("Advert message is required");
                $("input[name='advert_message']").focus();
                return false;
            }

            if ($("input[name='advert_link']").val() == "") {
                alert("Advert link is required");
                $("input[name='advert_link']").focus();
                return false;
            }

            if ($("input[name='advert_caption']").val() == "") {
                alert("Advert caption is required");
                $("input[name='advert_caption']").focus();
                return false;
            }
        } else {
            if ($("#promotable_post").val() == 0) {
                alert("Promotable post is required");
                $("#promotable_post").focus();
                return false;
            }
        }

        if ($("input[name='adset']:checked").val() == "new") {
            if ($("input[name='adset_name']").val() == "") {
                alert("Adset name is required");
                $("input[name='adset_name']").focus();
                return false;
            } else if ($("#budget_amount").val() == "") {
                alert("Budget amount is required");
                $("#budget_amount").focus();
                return false;
            } else if ($("#start_time").val() == "") {
                alert("Schedule start is required");
                $("#start_time").focus();
                return false;
            } else if ($("#locations").val() == null) {
                alert("Location is required");
                $("#locations").focus();
                return false;
            } else if ($("#placements").val() == null) {
                alert("At least one placement is required");
                $("#placements").focus();
                return false;
            }
        } else if (($("input[name='adset']:checked").val() == "existing") && ($("#adset_id").val() == "0")) {
            alert("Adset must be selected");
            $("#adset_id").focus();
            return false;
        }

        if ($("input[name='ad_name']").val() == "") {
            alert("Ad name is required");
            $("input[name='ad_name']").focus();
            return false;
        }
    });

    $(".preview").click(function() {
        var id_format = $(this).attr("id").split("_");
        var adformat = "DESKTOP_FEED_STANDARD";
        if (id_format[1] === "rightcolumn") {
            adformat = "RIGHT_COLUMN_STANDARD";
        } else if (id_format[1] === "mobilefeed") {
            adformat = "MOBILE_FEED_STANDARD";
        }

        $.ajax({
            url: "/api/marketing-concept/<?php echo $api->params[0]; ?>/ad/"+id_format[0]+"/adpreview/"+adformat,
            dataType: "json"
        }).done(function( response ) {
            $("#advert_name").html(response.advert_name);
            if (id_format[1] === "desktopfeed") {
                $("#preview").html("Desktop news feed");
            } else if (id_format[1] === "rightcolumn") {
                $("#preview").html("Desktop right column");
            } else if (id_format[1] === "mobilefeed") {
                $("#preview").html("Mobile news feed");
            }
            $("#preview_body").html(response.body);
        });
    });
</script>
</body>
</html>
