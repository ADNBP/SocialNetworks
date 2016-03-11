<?php
    require_once("vendor/autoload.php");

    include_once ("ADNBP/__performance.php"); // it include __p(),_print(),_printe() funtions to trace and performance specific points
    require_once ("ADNBP/class/ADNBP.php");
    $adnbp = new ADNBP();
    $adnbp -> run();