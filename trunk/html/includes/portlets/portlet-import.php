<?php
/*
 * portlet-import.php
 *
 * Developed by Thomas Honzik (thomas@honzik.at)
 * Copyright (c) 2011 LogZilla, LLC
 * All rights reserved.
 * Last updated on 2011-03-09

 * Changelog:
 * 2011-02-28 - created
 *
 */

$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
require_once ($basePath ."/../grid/php/jqGrid.php");
$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
$spx_ip = $_SESSION[SPX_SRV]; 
$spx_port = $_SESSION[SPX_PORT]; 
$scl = @new mysqli(SPHINXHOST,'','','',SPHINXPORT);
if (mysqli_connect_errno())
    echo( sprintf("Sphinxql error in connect: %s\n", mysqli_connect_error() . "<br>The Sphinx daemon may not be running."));

    //---------------------------------------------------
    // The get_input statements below are used to get
    // POST, GET, COOKIE or SESSION variables.
    // Note that PLURAL words below are arrays.
    //---------------------------------------------------


    if ((has_portlet_access($_SESSION['username'], 'Import') == TRUE) || ($_SESSION['AUTHTYPE'] == "none")) { 


        // Find available dates

        $sql_online = "SELECT lo div (24*60*60) as dst from distributed group by dst limit 512";
        $result = $scl->query($sql_online);

        if ($result) {
            while ($live = $result->fetch_assoc()) {
                $online_array[] = date('Y-m-d',$live['dst']*(24*60*60) ); }
        }

        // Get archive disk path

        $sql = "SELECT value FROM settings where name='ARCHIVE_PATH'";
        $archive_path = fetch_array( perform_query($sql, $dbLink, $_SERVER['PHP_SELF'])); 

        if ($handle = opendir($archive_path['value'])) {
            while (false !== ($file = readdir($handle))) {
                if ( preg_match("/^dumpfile/",$file) ) {
                    $file_array[] = substr($file,9,4)."-".substr($file,13,2)."-".substr($file,15,2);

                }
            }
            closedir($handle);
        }

        $import_runfile = $archive_path['value'].'/import.running';
        $import_running = is_file($import_runfile);


        $restore_runfile = $archive_path['value'].'/restore.running';
        $restore_running = is_file($restore_runfile);

        // Get a list of all non-empty dates

        // desc order of records due user-request
        // cdukes: changed to asc order for new interface
        $sql = "SELECT archive, records FROM archives where records>0 order by archive asc";
        $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']); 
        $count = mysql_num_rows($result);
        $all_dates = array();
        $dates_start_of_week = array();
        while ($row = fetch_array($result)) {
            $date = substr($row['archive'],9,4)."-".substr($row['archive'],13,2)."-".substr($row['archive'],15,2);
            list($start_date, $end_date) = get_week($date);
            $all_dates[] = $date;
            $dates_start_of_week[] = $start_date;
            $dates_month[] = date("F", strtotime($date));
        }
        $all_dates = array_unique($all_dates);
        sort($all_dates);
        $dates_start_of_week = array_unique($dates_start_of_week);




        if ($dates_month) {
            $dates_month = array_unique($dates_month);

            ?>
            <script>
                $(function() {
                    $( "#div_admin_accordian" ).accordion({
                        navigation: true,
                        collapsible: true
                    });
                });
            </script>

            <div id="div_adminMenu" style="padding:2px; width:20%; height:600px;" class="ui-widget-content">

                <div id="div_admin_accordian">
                    <?php
                    foreach ($dates_month as $month) {
                    echo "<h3><a href='#'>$month</a></h3><div>";
                        foreach ($all_dates as $day) {
                        if (date('F', strtotime($day)) === "$month") {
                        echo "<a href='#' class='adminItem' id='$day'>".date('l, F d, Y', strtotime($day))."</a><br /> ";
                        }
                        }
                        echo "</div>";
                    } ?>
                </div>

            </div><!-- End div_adminMenu -->
            <div id="div_adminMenu_content" style="width:77%; height:91%;position: absolute; left: 21%; top: 6%;" class="ui-widget-content">
                <div id="dlg">
                    <table border="0" width="100%">
                        <thead>
                            <tr>
                                <th colspan=2 width="100%">Archive Information</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="padding: 10px;">
                                    <button id='btnImport'>Import/Check Status</button><br />
                                    <span id="dlg_content">Please select an available date from the left menu</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="dlg_desc">
                    <span id="dlg_desc_content"></span>
                </div>
            </div>


            <?php
            } else {
            echo "There are no archived tables available for import<br />";
            } // end test for dates_month
            } // end portlet permission check
            ?>

            <script type="text/javascript"> 
                $(document).ready(function(){
                        // Make the buttons jQuery styled buttons
                        $("button").button();
                        $("#btnImport").hide();

                        // set the click action for date links on the left menu
                        $('.adminItem').click(function() {
                            // date_str will be the date, e.g: 2012-01-31
                            date_str = $(this).attr('id');
                            $.get("includes/ajax/import.php?action=info&date_str=" +date_str,
                                function(data){
                                $("#dlg_content").html(data);
                                $("#btnImport").show();
                                });
                            })

                        // Set click action for button
                        $("#btnImport").click( function() { 
                            $.get("includes/ajax/import.php?action=import&date_str="+date_str, function(data){
                                if (data.indexOf("RUNNING") != -1) {
                                $("#dlg_content").html("Import has begun...");
                                } else {
                                $("#dlg_content").html(data);
                                }
                                });
                            }); // end btn
                }); // end doc ready
</script>
