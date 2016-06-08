<?php
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
$mkt = Marketing::getInstance();

if ((null === $credentials) && ($api->params[1] !== "auth") && ($api->params[1] !== "home")) {
    header("Location: /api/marketing-concept/".$api->params[0]."/home");
    exit;
}

if(!$api->error) {
    if($api->params[0] != "status") {
        try {
            $mkt->setApiKeys($api->params[0], $networks[$api->params[0]]["client_id"],
                $networks[$api->params[0]]["client_secret"],
                $networks[$api->params[0]]["client_scope"]);
        } catch (\Exception $e) {
            $api->setError($e->getMessage());
        }

        if(!$api->error && $api->params[1] != "auth") {
            if(!is_array($credentials[$api->params[0]])) {
                $api->setError("Please, assign credentials to ".$api->params[0]);
            }

            if(!$api->error) {
                try {
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
                            $redirectUrl = Marketing::generateRequestUrl() . "api/marketing-concept/" .
                                $api->params[0] . "/auth/endcallback";

                            if ($api->params[2] == "endcallback") {
                                $code = $_GET["code"];

                                try {
                                    $value = $mkt->confirmAuthorization($api->params[0], $code, $oauthVerifier, $redirectUrl);
                                    $_SESSION["params_socialnetworks"][$api->params[0]] = $value;
                                    header("Location: /api/marketing-concept/".$api->params[0]."/user/export/adaccount");
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
                                case "export":
                                    switch($api->params[3]) {
                                        case "adaccount":
                                            $menuactive = 2;
                                            if ($api->params[5] === "campaign") {
                                                try {
                                                    $value = $mkt->exportUserAdAccountCampaigns($api->params[0], $api->params[4]);
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
                            switch($api->params[3]) {
                                case "info":
                                    try {
                                        $value = $mkt->getAdAccount($api->params[0], $api->params[2]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
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
                                case "adset":
                                    $menuactive = 3;
                                    try {
                                        $value = $mkt->getCampaignAdSets($api->params[0], $api->params[2]);
                                        $campaign = $mkt->getCampaign($api->params[0], $api->params[2]);
                                        $adAccount = $mkt->getAdAccount($api->params[0], "act_".$campaign["account_id"]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                case "ad":
                                    try {
                                        $value = $mkt->getCampaignAds($api->params[0], $api->params[2]);
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
                            $parameters = array();
                            $parameters["name"] = $api->formParams["name"];
                            $value = $mkt->createAd(
                                $api->params[0], $api->params[2], $api->params[4], $api->params[6],$parameters
                            );
                            break;
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
                        <i class="fa fa-facebook-square"></i> <span>Facebook Ads</span> <i class="fa fa-angle-left pull-right"></i>
                    </a>
                    <ul class="treeview-menu">
                        <?php if (!isset($_SESSION["params_socialnetworks"][$api->params[0]])) { ?>
                            <li><a href="/api/marketing-concept/<?php echo $api->params[0]; ?>/auth"><i class="fa fa-sign-in"></i> Authentication</a></li>
                        <?php } ?>
                        <?php if (isset($_SESSION["params_socialnetworks"][$api->params[0]])) { ?>
                            <li<?php if ($menuactive == 1) { ?> class="active"<?php } ?>><a href="/api/marketing-concept/<?php echo $api->params[0]; ?>/user/export/adaccount"><i class="fa fa-list"></i> Ad Accounts</a></li>
                            <li<?php if ($menuactive == 2) { ?> class="active"<?php } ?>><a href="#"><i class="fa fa-calendar-o"></i> Campaigns</a></li>
                            <li<?php if ($menuactive == 3) { ?> class="active"<?php } ?>><a href="#"><i class="fa fa-object-group"></i> Ad Sets</a></li>
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
                <li<?php if ($api->params[1] === "home") { ?> class="active"<?php } ?>><a href="/api/marketing-concept/<?php echo $api->params[0]; ?>/home"><i class="fa fa-facebook-square"></i> Facebook Ads</a></li>
                <li class="active"><?php
                    switch($menuactive) {
                        case 1:
                            echo "Ad Accounts";
                            break;
                        case 2:
                            echo "<a href='/api/marketing-concept/".$api->params[0]."/user/export/adaccount'>Ad Accounts</a></li><li class='active'>".$adAccount["name"];
                            break;
                        case 3:
                            echo "<a href='/api/marketing-concept/".$api->params[0]."/user/export/adaccount'>Ad Accounts</a></li>
                                    <li class='active'><a href='/api/marketing-concept/".$api->params[0]."/user/export/adaccount/".$adAccount["id"]."/campaign'>".$adAccount["name"]."</a></li>
                                    <li class='active'>".$campaign["name"];
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

                                    <div class="box-tools">
                                        <div class="input-group input-group-sm" style="width: 150px;">
                                            <input name="table_search" class="form-control pull-right" placeholder="Search" type="text">

                                            <div class="input-group-btn">
                                                <button type="submit" class="btn btn-default"><i class="fa fa-search"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- /.box-header -->
                                <div class="box-body table-responsive no-padding">
                                    <table class="table table-hover">
                                        <tbody><tr>
                                            <th>Account</th>
                                            <th>ID</th>
                                            <th>Status</th>
                                            <th>Balance</th>
                                        </tr>
                                        <?php foreach($value as $adAccount) { ?>
                                            <tr>
                                                <td><a href="/api/marketing-concept/<?php echo $api->params[0]; ?>/user/export/adaccount/<?php echo $adAccount["id"]; ?>/campaign"><?php echo $adAccount["name"]; ?></a></td>
                                                <td><?php echo str_replace("act_","",$adAccount["id"]); ?></td>
                                                <td><?php
                                                    $status = "ACTIVE";
                                                    switch($adAccount["account_status"]) {
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
                                                <td><?php echo $adAccount["balance"]/100; ?>€</td>
                                            </tr>
                                        <?php } ?>
                                        </tbody></table>
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

                                    <div class="box-tools">
                                        <div class="input-group input-group-sm" style="width: 150px;">
                                            <input name="table_search" class="form-control pull-right" placeholder="Search" type="text">

                                            <div class="input-group-btn">
                                                <button type="submit" class="btn btn-default"><i class="fa fa-search"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- /.box-header -->
                                <div class="box-body table-responsive no-padding">
                                    <table class="table table-hover">
                                        <tbody><tr>
                                            <th>Campaign name</th>
                                            <th>Status</th>
                                        </tr>
                                        <?php foreach($value as $campaign) { ?>
                                            <tr>
                                                <td><a href="/api/marketing-concept/<?php echo $api->params[0]; ?>/campaign/<?php echo $campaign["id"]; ?>/adset"><?php echo $campaign["name"]; ?></a></td>
                                                <td><?php echo $campaign["effective_status"]; ?></td>
                                            </tr>
                                        <?php } ?>
                                        </tbody></table>
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
                                    <h3 class="box-title">Ad Sets</h3>

                                    <div class="box-tools">
                                        <div class="input-group input-group-sm" style="width: 150px;">
                                            <input name="table_search" class="form-control pull-right" placeholder="Search" type="text">

                                            <div class="input-group-btn">
                                                <button type="submit" class="btn btn-default"><i class="fa fa-search"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- /.box-header -->
                                <div class="box-body table-responsive no-padding">
                                    <table class="table table-hover">
                                        <tbody><tr>
                                            <th>Ad Set Name</th>
                                            <th>Budget</th>
                                            <th>Budget remaining</th>
                                            <th>Billing event</th>
                                            <th>Status</th>
                                        </tr>
                                        <?php foreach($value as $adset) { ?>
                                            <tr>
                                                <td><a href="/api/marketing-concept/<?php echo $api->params[0]; ?>/campaign/<?php echo $campaign["id"]; ?>/adset"><?php echo $adset["name"]; ?></a></td>
                                                <td><?php
                                                    $budget = $adset["daily_budget"]/100;
                                                    if ($budget > 0) {
                                                        echo $budget . "€<br/>Daily budget";
                                                    } else {
                                                        echo $adset["lifetime_budget"]/100 . "€";
                                                    }
                                                ?></td>
                                                <td><?php echo $adset["budget_remaining"]/100 . "€"; ?></td>
                                                <td><?php echo $adset["billing_event"]; ?></td>
                                                <td><?php echo $campaign["effective_status"]; ?></td>
                                            </tr>
                                        <?php } ?>
                                        </tbody></table>
                                </div>
                                <!-- /.box-body -->
                            </div>
                            <!-- /.box -->
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
        <strong>Copyright &copy; <?php echo date("Y"); ?> <a href="http://bloombees.com">Bloombees</a>.</strong> All rights
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
</script>
</body>
</html>
