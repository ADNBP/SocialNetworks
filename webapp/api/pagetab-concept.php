<?php
use DPZ\Flickr;
use CloudFramework\Service\SocialNetworks\SocialNetworks;

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

if ((null === $credentials) && ($api->params[1] !== "auth") && ($api->params[1] !== "home")) {
    header("Location: /api/pagetab-concept/" . $api->params[0] . "/home");
    exit;
}

if(!$api->error) {
    if($api->params[0] != "status") {
        try {
            $sc->setApiKeys($api->params[0], $networks[$api->params[0]]["client_id"],
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
                            $redirectUrl = SocialNetworks::generateRequestUrl() . "api/pagetab-concept/" .
                                $api->params[0] . "/auth/endcallback";

                            if ($api->params[2] == "endcallback") {
                                $code = $_GET["code"];

                                try {
                                    $value = $sc->confirmAuthorization($api->params[0], $code, $oauthVerifier, $redirectUrl);
                                    $_SESSION["params_socialnetworks"][$api->params[0]] = $value;
                                    header("Location: /api/pagetab-concept/" . $api->params[0] . "/check");
                                    exit;
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            } else {
                                $authUrl = "";
                                try {
                                    $value = $sc->requestAuthorization($api->params[0], $redirectUrl);
                                    header("Location: " . $value);
                                    exit;
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            }
                            break;
                        case "check":
                            try {
                                $profile = $sc->checkCredentials($api->params[0], array(
                                    "access_token" => $credentials[$api->params[0]]["access_token"]
                                ));
                                $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"] = $profile["user_id"];
                                header("Location: /api/pagetab-concept/" . $api->params[0] . "/user/export/page/10/1/0");
                                exit;
                            } catch (\Exception $e) {
                                $api->setError($e->getMessage());
                            }
                            break;
                        case "user":
                            switch ($api->params[2]) {
                                case "export":
                                    switch ($api->params[3]) {
                                        // Export user's pages in FACEBOOK
                                        case "page":
                                            $menuactive = 1;
                                            try {
                                                $value = $sc->exportUserPages($api->params[0],
                                                    $_SESSION["params_socialnetworks"][$api->params[0]]["user_id"],
                                                    $api->params[4], $api->params[5], $api->params[6]);
                                            } catch (\Exception $e) {
                                                $api->setError($e->getMessage());
                                            }
                                            break;
                                    }
                            }
                            break;
                        case "page":
                            switch ($api->params[3]) {
                                case "tab":
                                    if ($api->params[4] === "create") {
                                        $menuactive = 3;
                                        try {
                                            $value = $sc->getPage($api->params[0], $api->params[2]);
                                        } catch (\Exception $e) {
                                            $api->setError($e->getMessage());
                                        }
                                        break;
                                    } else if ($api->params[5] === "remove") {
                                        $menuactive = 2;
                                        try {
                                            $value = $sc->removePageTab($api->params[0], $api->params[2], $api->params[4]);
                                        } catch (\Exception $e) {
                                            $api->setError($e->getMessage());
                                        }
                                        break;
                                    } else {
                                        // Page tabs list
                                        $menuactive = 2;
                                        try {
                                            $value = $sc->getPage($api->params[0], $api->params[2]);
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
            break;
        // POST END POINTS
        case "POST":
            switch ($api->params[1]) {
                // Save SOCIAL NETWORK in session
                case "auth":
                    $_SESSION["params_socialnetworks"][$api->params[0]] = $api->formParams;
                    $value = $_SESSION["params_socialnetworks"][$api->params[0]];
                    break;
                case "page":
                    switch ($api->params[3]) {
                        case "tab":
                            if ($api->params[4] === "create") {
                                $menuactive = 2;
                                try {
                                    $parameters = [];
                                    $parameters["app_id"] = $api->formParams["app_id"];
                                    $parameters["custom_name"] = $api->formParams["custom_name"];
                                    $parameters["position"] = $api->formParams["position"];
                                    $value = $sc->createPageTab($api->params[0], $api->params[2], $parameters);
                                } catch (\Exception $e) {
                                    $api->setError($e->getMessage());
                                }
                            }
                            break;
                    }
                    break;
                case "brand":
                    try {
                        $value = $sc->simulatePageTab($api->params[0], $api->formParams["signed_request"], "c03931f518d2e785af6d9bd83c8f7686");
                        _printe("Redirecting to the store related to the facebook page with ID ".$value["page"]["id"]." ...");
                    } catch (\Exception $e) {
                        $api->setError($e->getMessage());
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
            <!-- sidebar menu: : style can be found in sidebar.less -->
            <ul class="sidebar-menu">
                <!--<li class="header">MAIN NAVIGATION</li>-->
                <li class="active treeview">
                    <a href="#">
                        <i class="fa fa-facebook-square"></i> <span>Facebook Page Tab</span> <i
                            class="fa fa-angle-left pull-right"></i>
                    </a>
                    <ul class="treeview-menu">
                        <?php if (!isset($_SESSION["params_socialnetworks"][$api->params[0]])) { ?>
                            <li><a href="/api/pagetab-concept/<?php echo $api->params[0]; ?>/auth"><i
                                        class="fa fa-sign-in"></i> Authentication</a></li>
                        <?php } ?>
                        <?php if (isset($_SESSION["params_socialnetworks"][$api->params[0]])) { ?>
                            <li<?php if ($menuactive == 1) { ?> class="active"<?php } ?>><a
                                    href="/api/pagetab-concept/<?php echo $api->params[0]; ?>/user/export/page/10/1/0"><i
                                        class="fa fa-list"></i> User pages</a></li>
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
            <h1>Facebook Page Tabs</h1>
            <ol class="breadcrumb">
                <?php
                if ($api->params[1] !== "home") {
                ?>
                <li<?php if ($api->params[1] === "home") { ?> class="active"<?php } ?>><a
                        href="/api/marketing-concept/<?php echo $api->params[0]; ?>/home"><i
                            class="fa fa-facebook-square"></i> Facebook Page Tabs</a></li>
                <li class="active"><?php
                    switch ($menuactive) {
                        case 1:
                            echo "User pages";
                            break;
                        case 2:
                            echo "<a href='/api/pagetab-concept/" . $api->params[0] . "/user/export/page/10/1/0'>User pages</a></li>
                                    <li class='active'>".$value["name"]." page tabs";
                            break;
                        case 3:
                            echo "<a href='/api/pagetab-concept/" . $api->params[0] . "/user/export/page/10/1/0'>User pages</a></li>
                                    <li class='active'>".$value["name"]." page - New tab";
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
                        <img class="img-responsive" src="/webapp/assets/dist/img/tabs.jpg"/>
                    </div>
                </div>
                <?php
            } else {
                //_printe($value);
                if ($menuactive == 1) {
                    ?>
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="box">
                                <div class="box-header">
                                    <h3 class="box-title">User pages</h3>
                                    <div class="box-tools"></div>
                                </div>
                                <!-- /.box-header -->
                                <div class="box-body table-responsive no-padding">
                                    <table class="table table-hover">
                                        <tbody>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Category</th>
                                            <th></th>
                                        </tr>
                                        <?php foreach ($value["pages"] as $page) { ?>
                                            <tr>
                                                <td><?php echo $page["id"]; ?></td>
                                                <td><?php echo $page["name"]; ?></td>
                                                <td><?php echo $page["category"]; ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-success">Tools</button>
                                                        <button type="button" class="btn btn-success dropdown-toggle"
                                                                data-toggle="dropdown">
                                                            <span class="caret"></span>
                                                            <span class="sr-only">Toggle Dropdown</span>
                                                        </button>
                                                        <ul class="dropdown-menu" role="menu">
                                                            <li><a id="tabs<?php echo $page["id"]; ?>"
                                                                   href="/api/pagetab-concept/<?php echo $api->params[0]; ?>/page/<?php echo $page["id"]; ?>/tab">Tabs</a></li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        <tr>
                                            <td>&nbsp;</td>
                                        </tr>
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
                                    <h3 class="box-title"><?php echo $value["name"]; ?> page tabs</h3>
                                    <div class="box-tools"><button type="button" class="btn btn-success" id="newpagetab">New Page Tab</button></div>
                                </div>
                                <!-- /.box-header -->
                                <div class="box-body table-responsive no-padding">
                                    <table class="table table-hover">
                                        <tbody>
                                        <tr>
                                            <th>ID</th>
                                            <th>Tab name</th>
                                            <th></th>
                                        </tr>
                                        <?php foreach ($value["tabs"] as $tab) { ?>
                                            <tr>
                                                <td>
                                                    <a target="_new" href="https://www.facebook.com/<?php echo $tab["link"]; ?>"><?php echo $tab["id"]; ?></a>
                                                </td>
                                                <td>
                                                    <a target="_new" href="https://www.facebook.com/<?php echo $tab["link"]; ?>"><?php echo $tab["name"]; ?></a>
                                                </td>
                                                <td><?php if (isset($tab["custom_name"])) { ?>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-success">Tools</button>
                                                        <button type="button" class="btn btn-success dropdown-toggle"
                                                                data-toggle="dropdown">
                                                            <span class="caret"></span>
                                                            <span class="sr-only">Toggle Dropdown</span>
                                                        </button>
                                                        <ul class="dropdown-menu" role="menu">
                                                            <li><a id="removetab<?php echo $value["id"]; ?>"
                                                                   href="/api/pagetab-concept/<?php echo $api->params[0]; ?>/page/<?php echo $value["id"]; ?>/tab/<?php echo str_replace($value["id"]."/tabs/", "", $tab["id"]); ?>/remove">Remove tab</a></li>
                                                        </ul>
                                                    </div><?php } ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        </tbody>
                                    </table>
                                    <br/><br/>
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
                        <div class="col-md-12">
                            <!-- Horizontal Form -->
                            <div class="box box-info">
                                <!-- form start -->
                                <form class="form-horizontal" method="post" id="frm_ad"
                                      action="/api/pagetab-concept/facebook/page/<?php echo $api->params[2]; ?>/tab/create"
                                      enctype="multipart/form-data">
                                    <div class="box-header with-border">
                                        <h3 class="box-title"><?php echo $value["name"]; ?> page - New tab</h3>
                                    </div>
                                    <!-- /.box-header -->
                                    <div class="box-body">
                                        <div class="form-group">
                                            <label for="product_type" class="col-sm-2 control-label">App ID</label>

                                            <div class="col-sm-10">
                                                <input class="form-control" name="app_id" type="text" readonly value="1070672553027353">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="product_type" class="col-sm-2 control-label">Page tab name</label>

                                            <div class="col-sm-10">
                                                <input class="form-control" name="custom_name"
                                                       placeholder="Page tab name" type="text">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="product_type" class="col-sm-2 control-label">Page tab position</label>

                                            <div class="col-sm-10">
                                                <select class="form-control" name="position">
                                                    <?php foreach($value["tabs"] as $key=>$tab) { ?>
                                                    <option value="<?php echo $key+1; ?>"><?php echo $key+1; ?></option>
                                                    <?php } ?>
                                                </select>
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
    $("#newpagetab").click(function() {
        window.location.replace("/api/pagetab-concept/facebook/page/<?php echo $value["id"]; ?>/tab/create")
    });
</script>
</body>
</html>
Contact GitHub