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

if ((null === $credentials) && ($api->params[1] !== "auth") && ($api->params[1] !== "home")) {
    header("Location: /api/ecommerce-concept/".$api->params[0]."/home");
    exit;
}

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
    $menuactive = 0;
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
                            $redirectUrl = Ecommerce::generateRequestUrl() . "api/ecommerce-concept/" .
                                $api->params[0] . "/auth/endcallback";

                            if ($api->params[2] == "endcallback") {
                                $code = $_GET["code"];

                                try {
                                    $value = $ecommerce->confirmAuthorization($api->params[0], $code);
                                    $_SESSION["params_ecommerce_platforms"][$api->params[0]] = $value;
                                    header("Location: /api/ecommerce-concept/".$api->params[0]."/shop/info");
                                    exit;
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
                                        $menuactive = 5;
                                        try {
                                            $value = $ecommerce->exportAllProducts($api->params[0], $api->params[3],
                                                $api->params[4]);
                                        } catch (\Exception $e) {
                                            $api->setError($e->getMessage());
                                        }
                                    } else {
                                        $menuactive = 4;
                                        try {
                                            $value = $ecommerce->exportProducts($api->params[0], $api->params[3],
                                                $api->params[4]);
                                        } catch (\Exception $e) {
                                            $api->setError($e->getMessage());
                                        }
                                    }
                                    break;
                                case "collection":
                                    $menuactive = 3;
                                    // List of products in a collection
                                    if ("product" === $api->params[4]) {
                                        try {
                                            $value = $ecommerce->exportProducts($api->params[0], $api->params[5],
                                                $api->params[6], $api->params[3]);
                                            $collection = $ecommerce->getCollection($api->params[0], $api->params[3]);
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
                                    $menuactive = 1;
                                    try {
                                        $value = $ecommerce->getShop($api->params[0]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                                case "shipping":
                                    $menuactive = 2;
                                    try {
                                        $value = $ecommerce->getShopShippingZones($api->params[0]);
                                    } catch (\Exception $e) {
                                        $api->setError($e->getMessage());
                                    }
                                    break;
                            }
                            break;
                        case "product":
                            switch($api->params[2]) {
                                case "new":
                                    $menuactive = 7;
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
                                $images = array();
                                $images[0] = array();
                                $images[0]["attachment"] = ($_FILES["image"]["name"] !== null)?base64_encode(file_get_contents($_FILES["image"]["tmp_name"])):"";
                                $variants = array();
                                $variants[0] = array();
                                $variants[0]["price"] = $api->formParams["price"];

                                $params = array(
                                    "title"         =>      $api->formParams["title"],
                                    "body_html"     =>      $api->formParams["body_html"],
                                    "vendor"        =>      $api->formParams["vendor"],
                                    "product_type"  =>      $api->formParams["product_type"],
                                    "published"     =>      $api->formParams["published"],
                                    "images"        =>      $images,
                                    "variants"      =>      $variants
                                );
                                $value = $ecommerce->createProduct($api->params[0], $params);
                                header("Location: /api/ecommerce-concept/".$api->params[0]."/export/product/50/1");
                                exit;
                            } catch (\Exception $e) {
                                $api->setError($e->getMessage());
                            }
                            break;
                    }
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
                        <i class="fa fa-shopping-cart"></i> <span>Shopify</span> <i class="fa fa-angle-left pull-right"></i>
                    </a>
                    <ul class="treeview-menu">
                        <li><a href="https://proof-of-concept-3.myshopify.com" target="new"><i class="fa fa-eye"></i> View online store</a></li>
                        <?php if (!isset($_SESSION["params_ecommerce_platforms"][$api->params[0]])) { ?>
                            <li><a href="/api/ecommerce-concept/<?php echo $api->params[0]; ?>/auth"><i class="fa fa-sign-in"></i> Authentication</a></li>
                        <?php } ?>
                        <?php if (isset($_SESSION["params_ecommerce_platforms"][$api->params[0]])) { ?>
                            <li<?php if ($menuactive == 1) { ?> class="active"<?php } ?>><a href="/api/ecommerce-concept/<?php echo $api->params[0]; ?>/shop/info"><i class="fa fa-shopping-basket"></i> Shop information</a></li>
                            <li<?php if ($menuactive == 2) { ?> class="active"<?php } ?>><a href="/api/ecommerce-concept/<?php echo $api->params[0]; ?>/shop/shipping"><i class="fa fa-truck"></i> Shipping zones</a></li>
                            <li<?php if ($menuactive == 3) { ?> class="active"<?php } ?>><a href="/api/ecommerce-concept/<?php echo $api->params[0]; ?>/export/collection/50/1"><i class="fa fa-object-group"></i> Collections</a></li>
                            <li<?php if ($menuactive == 4) { ?> class="active"<?php } ?>><a href="/api/ecommerce-concept/<?php echo $api->params[0]; ?>/export/product/50/1"><i class="fa fa-shopping-bag"></i> Products</a></li>
                            <li<?php if ($menuactive == 5) { ?> class="active"<?php } ?>><a href="/api/ecommerce-concept/<?php echo $api->params[0]; ?>/export/product/all"><i class="fa fa-arrow-left"></i> Export Products</a></li>
                            <!--<li<?php if ($menuactive == 6) { ?> class="active"<?php } ?>><a href="/"><i class="fa fa-arrow-right"></i> Import Products</a></li>-->
                            <li<?php if ($menuactive == 7) { ?> class="active"<?php } ?>><a href="/api/ecommerce-concept/shopify/product/new"><i class="fa fa-plus"></i> New product</a></li>
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
            <h1>
                <?php
                switch($menuactive) {
                    case 1:
                        echo "Shop information";
                        break;
                    case 2:
                        echo "Shipping zones";
                        break;
                    case 3:
                        echo "Collections";
                        break;
                    case 4:
                        echo "Products";
                        break;
                    case 5:
                        echo "Export products";
                        break;
                    case 7:
                        echo "New product";
                        break;
                }
                ?>
            </h1>
            <ol class="breadcrumb">
                <?php
                    if ($api->params[1] !== "home") {
                ?>
                <li<?php if ($api->params[1] === "home") { ?> class="active"<?php } ?>><a href="/api/ecommerce-concept/<?php echo $api->params[0]; ?>/home"><i class="fa fa-shopping-cart"></i> Shopify</a></li>
                <li class="active"><?php
                        switch($menuactive) {
                            case 1:
                                echo "Shop information";
                                break;
                            case 2:
                                echo "Shipping zones";
                                break;
                            case 3:
                                if ("product" !== $api->params[4]) {
                                    echo "Collections";
                                } else {
                                    echo "<a href='/api/ecommerce-concept/".$api->params[0]."/export/collection/50/1'>Collections</a></li><li class='active'>".$collection["title"];
                                }
                                break;
                            case 4:
                                echo "Products";
                                break;
                            case 5:
                                echo "Export products";
                                break;
                            case 7:
                                echo "New product";
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
                        <img class="img-responsive" src="/webapp/assets/dist/img/shopify.jpg"/>
                    </div>
                </div>
        <?php
            } else {
                //print_r($value);
                if ($menuactive == 1) {
                    ?>
                    <div class="row">
                        <div class="col-md-3">

                            <!-- Profile Image -->
                            <div class="box box-primary">
                                <div class="box-body box-profile">
                                    <img class="profile-user-img img-responsive img-circle"
                                         src="/webapp/assets/dist/img/bloombees.jpg" alt="User profile picture">

                                    <h3 class="profile-username text-center"><?php echo $value["name"]; ?></h3>

                                    <p class="text-muted text-center"><?php echo $value["address1"].", ".$value["zip"].", ".$value["city"].", ".$value["country_name"]; ?></p>

                                    <ul class="list-group list-group-unbordered">
                                        <li class="list-group-item">
                                            <b>ID</b> <a class="pull-right"><?php echo $value["id"]; ?></a>
                                        </li>
                                        <li class="list-group-item">
                                            <b>Domain</b> <a class="pull-right"><?php echo $value["domain"]; ?></a>
                                        </li>
                                        <li class="list-group-item">
                                            <b>Latitude</b> <a class="pull-right"><?php echo $value["latitude"]; ?></a>
                                        </li>
                                        <li class="list-group-item">
                                            <b>Longitude</b> <a class="pull-right"><?php echo $value["longitude"]; ?></a>
                                        </li>
                                    </ul>

                                </div>
                                <!-- /.box-body -->
                            </div>
                            <!-- /.box -->

                        </div>
                    </div>
                    <!-- /.row -->
                    <?php
                } else if ($menuactive == 2) {
                    ?>
                    <div class="row">
                        <?php
                            foreach($value as $shippingzone) {
                                foreach ($shippingzone["countries"] as $country) {
                                    ?>
                                    <div class="col-md-3">
                                        <!-- Profile Image -->
                                        <div class="box box-primary">
                                            <div class="box-body box-profile">
                                                <h3 class="profile-username text-center"><?php echo $country["name"]; ?></h3>
                                                <p class="text-muted text-center"><?php echo $shippingzone["name"]; ?>
                                                    , <?php echo $country["tax_name"] . " " . ($country["tax"] * 100) . "%"; ?></p>
                                                <ul class="list-group list-group-unbordered">
                                                    <?php foreach ($country["provinces"] as $province) { ?>
                                                        <li class="list-group-item">
                                                            <b><?php echo $province["name"]; ?></b>
                                                            <a class="pull-right">
                                                                <?php
                                                                if ($province["tax"] == 0) {
                                                                    echo $country["tax_name"] . " " . ($country["tax"] * 100) . "%";
                                                                } else {
                                                                    echo $province["tax_name"] . " " . ($province["tax"] * 100) . "%";
                                                                }
                                                                ?>
                                                            </a>
                                                        </li>
                                                    <?php } ?>
                                                </ul>

                                            </div>
                                            <!-- /.box-body -->
                                        </div>
                                        <!-- /.box -->

                                    </div>
                                    <?php
                                }
                            }
                        ?>
                    </div>
                    <!-- /.row -->
                    <?php
                } else if ($menuactive == 3) {
                    if ("product" === $api->params[4]) {
                        ?>
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="box">
                                    <div class="box-header">
                                        <h3 class="box-title">Products of collection <b><?php echo $collection["title"]; ?></b></h3>

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
                                                <th>ID</th>
                                                <th>Title</th>
                                                <th>Vendor</th>
                                                <th>Price</th>
                                                <th>Image</th>
                                                <th>Status</th>
                                            </tr>
                                            <?php foreach($value["products"] as $product) { ?>
                                                <tr>
                                                    <td><?php echo $product["id"]; ?></td>
                                                    <td><?php echo $product["title"]; ?></td>
                                                    <td><?php echo $product["vendor"]; ?></td>
                                                    <td><?php echo $product["variants"][0]["price"]; ?>€</td>
                                                    <td><?php if ($product["image"]["src"] !== null) { ?><img height="40px" src="<?php echo $product["image"]["src"]; ?>"><?php } ?></td>
                                                    <td><?php if ($product["published_at"] === null) { echo "Hidden"; } else { echo "Public"; } ?></td>
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
                    } else {
                        ?>
                        <div class="row">
                            <div class="col-md-3">
                                <!-- Profile Image -->
                                <div class="box box-primary">
                                    <div class="box-body box-profile">
                                        <ul class="list-group list-group-unbordered">
                                            <?php foreach ($value["collections"] as $collection) { ?>
                                                <li class="list-group-item">
                                                    <b><a href="#"><?php echo $collection["title"]; ?></a></b> <a
                                                        class="pull-right" href="/api/ecommerce-concept/<?php echo $api->params[0]; ?>/export/collection/<?php echo $collection["id"]; ?>/product/50/1">View products</a>
                                                </li>
                                            <?php } ?>
                                        </ul>

                                    </div>
                                    <!-- /.box-body -->
                                </div>
                                <!-- /.box -->

                            </div>
                        </div>
                        <!-- /.row -->
                        <?php
                    }
                } else if ($menuactive == 4) {
                    ?>
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="box">
                                <div class="box-header">
                                    <h3 class="box-title">Products</h3>

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
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Vendor</th>
                                            <th>Price</th>
                                            <th>Image</th>
                                            <th>Status</th>
                                        </tr>
                                        <?php foreach($value["products"] as $product) { ?>
                                        <tr>
                                            <td><?php echo $product["id"]; ?></td>
                                            <td><?php echo $product["title"]; ?></td>
                                            <td><?php echo $product["vendor"]; ?></td>
                                            <td><?php echo $product["variants"][0]["price"]; ?>€</td>
                                            <td><?php if ($product["image"]["src"] !== null) { ?><img height="40px" src="<?php echo $product["image"]["src"]; ?>"><?php } ?></td>
                                            <td><?php if ($product["published_at"] === null) { echo "Hidden"; } else { echo "Public"; } ?></td>
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
                } else if ($menuactive == 5) {
                    ?>
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="box">
                                <div class="box-header">
                                    <h3 class="box-title">Products</h3>

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
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Vendor</th>
                                            <th>Price</th>
                                            <th>Image</th>
                                            <th>Status</th>
                                        </tr>
                                        <?php foreach($value as $product) { ?>
                                            <tr>
                                                <td><?php echo $product->getId(); ?></td>
                                                <td><?php echo $product->getTitle(); ?></td>
                                                <td><?php echo $product->getVendor(); ?></td>
                                                <td><?php echo $product->getVariants()[0]->getPrice(); ?>€</td>
                                                <td><?php if (!empty($product->getImages()) && $product->getImages()[0]->getImage() !== null) { ?><img height="40px" src="<?php echo $product->getImages()[0]->getImage(); ?>"><?php } ?></td>
                                                <td><?php if ($product->getPublished()) { echo "Public"; } else { echo "Hidden"; } ?></td>
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
                } else if ($menuactive == 7) {
                    ?>
                    <div class="row">
                        <div class="col-md-12">
                            <!-- Horizontal Form -->
                            <div class="box box-info">
                                <div class="box-header with-border">

                                </div>
                                <!-- /.box-header -->
                                <!-- form start -->
                                <form enctype="multipart/form-data" class="form-horizontal" method="post" action="/api/ecommerce-concept/shopify/create/product">
                                    <div class="box-body">
                                        <div class="form-group">
                                            <label for="title" class="col-sm-2 control-label">Title</label>

                                            <div class="col-sm-10">
                                                <input class="form-control" id="title" name="title" placeholder="Title" type="text" required>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="description" class="col-sm-2 control-label">Description</label>

                                            <div class="col-sm-10">
                                                <textarea name="body_html" class="textarea" placeholder="Place some text here" style="width: 100%; height: 200px; font-size: 14px; line-height: 18px; border: 1px solid #dddddd; padding: 10px;"></textarea>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="price" class="col-sm-2 control-label">Price (€)</label>

                                            <div class="col-sm-10">
                                                <input class="form-control" id="price" name="price" placeholder="Price" type="number" required>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="product_type" class="col-sm-2 control-label">Product type</label>

                                            <div class="col-sm-10">
                                                <input class="form-control" id="product_type" name="product_type" placeholder="Product type" type="text">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="vendor" class="col-sm-2 control-label">Vendor</label>

                                            <div class="col-sm-10">
                                                <input class="form-control" id="vendor" name="vendor" placeholder="Vendor" type="text">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="image" class="col-sm-2 control-label">Image</label>
                                            <div class="col-sm-10">
                                                <input id="image" name="image" type="file">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-sm-offset-2 col-sm-10">
                                                <div class="checkbox">
                                                    <label>
                                                        <input type="checkbox" name="published"> Published
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- /.box-body -->
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
