<?php

/*
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2009 gdd.net
 * All rights reserved.
 *
 * Changelog:
 * 2009-12-13 - created
 *
 */

session_start();
$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
if ((has_portlet_access($_SESSION['username'], 'Search Options') == TRUE) || ($_SESSION['AUTHTYPE'] == "none")) {
    $dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
    // -------------------------
    // Get Message count and duplicate calculation
    // -------------------------
    if ($_SESSION['SHOWCOUNTS'] == "1") {
        if ($_SESSION['DEDUP'] == "1") {
            $sql = "SELECT (SELECT value FROM cache WHERE name='msg_sum') as count_all, (SELECT TABLE_ROWS FROM information_schema.tables WHERE table_schema = DATABASE() AND TABLE_NAME='".$_SESSION["TBL_MAIN"]."') as count";
            $result = perform_query($sql, $dbLink, $_REQUEST['pageId']);
            $line = fetch_array($result);
            $sumcnt = $line['count_all'];
            $count = $line['count'];
            if ($count > 0) {
                $r = ( $sumcnt - $count );
                $percent = ($r/$sumcnt) * 100;
            }
            /* To be enabled after Sphinx counts are correct
            // Get total message count directly from cache table
            $sql = "SELECT value FROM cache WHERE name='msg_sum'";
            $result = perform_query($sql, $dbLink, $_REQUEST['pageId']);
            $line = fetch_array($result);
            $msg_sum = $line['value'];
            // Get db row count from Sphinx because count(*) is too slow on large DB's
            $spx_sql = 'select * from distributed limit 1';
            $array = spx_query($spx_sql);
            $count_star = $array[2][1];
             echo "msg_sum = ".humanReadable($msg_sum) . "\n";
             echo "count_star = ".humanReadable($count_star);
            // simple test for new (or empty) databases so we don't divide by zero on new installs
            if ($count_star > 0) {
                $r = ( $msg_sum - $count_star );
                $percent = ($r/$msg_sum) * 100;
            }
            */
            // safety net
            if ($percent < 0) $percent = 0;
        }
    }
    ?>

    <!-- BEGIN HTML for search options -->
    <table id="tbl_search_options" cellpadding="0" cellspacing="0" width="100%" border="0">
        <thead class="ui-widget-header">
            <tr>
                <th width="30%"></th>
                <th width="60%"></th>
                <th width="10%"></th>
            </tr>
        </thead>
        <tbody>
            <?php  if ( $_SESSION["DEDUP"] == "1" ) { ?>
            <tr>
                <td>Duplicates <?php echo sprintf ("%.2f%%", $percent)?></td>
                <td>
                    <select name="dupop" id="dupop" multiple size=1>
                        <option value="gt">></option>
                        <option value="lt"><</option>
                        <option value="eq">=</option>
                        <option value="gte">>=</option>
                        <option value="lte"><=</option>
                    </select>
                </td>
                <td>
                    <input type=text class="rounded ui-widget ui-corner-all" name="dupcount" id="dupcount" value="0" size="3" />
                </td>
            </tr>
            <?php  } ?>

            <tr>
                <td>Sort Order</td>
                <td>
                    <select name="orderby" id="orderby" multiple size=1>
                        <option value="id">Database ID</option>
                        <option value="counter">Count</option>
                        <option value="facility">Facility</option>
                        <option value="severity">Severity</option>
                        <?php if ($_SESSION['DEDUP'] == "1") { ?>
                        <option value="fo">First Occurrence</option>
                        <?php } ?>
                        <option value="lo" selected>Last Occurrence</option>
                    </select>
                </td>
            </tr>

            <tr>
                <td>Search Order</td>
                <td>
                    <select name="order" id="order" multiple size=1>
                        <option value="ASC">Ascending</option>
                        <option value="DESC" selected>Descending</option>
                    </select>
                </td>
            </tr>

            <tr>
                <td>Limit</td>
                <td>
                    <select name="limit" id="limit" multiple size=0>
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option selected value="100">100</option>
                        <option value="150">150</option>
                        <option value="250">250</option>
                        <option value="500">500</option>
                        <option value="1000">1000</option>
                        <option value="5000">5000</option>
                    </select>
                </td>
            </tr>

            <tr>
                <td>Group By</td>
                <td>
                    <select name="groupby" id="groupby" multiple size=1>
                        <option selected value="">No Grouping</option>
                        <option  value="host_crc">Host</option>
                        <option value="msg_crc">Message</option>
                        <option value="program">Program</option>
                        <option value="facility">Facility</option>
                        <option value="severity">Severity</option>
                        <option value="mne">Mnemonic</option>
                        <option value="notes_crc">Notes</option>
                        <?php if($_SESSION['SNARE'] == "1") {?>
                        <option value="eid">Windows EventId</option>
                        <?php } ?>
                    </select>
                </td>
            </tr>

            <tr>
                <td>Chart Type</td>
                <td>
                    <select name="chart_type" id="chart_type" multiple size=1>
                        <option selected value="">No Chart</option>
                        <option value="pie">Pie</option>
                        <option value="bar">Bar</option>
                        <option value="line">Line</option>
                    </select>
                </td>
            </tr>



            <tr>
                <td>Live Scroll (Tail)</td>
                <td>
                    <select name="tail" id="tail" multiple size=1>
                        <option selected value="off">Off
                        <option value="1000">1 Second
                        <option value="5000">5 Seconds
                        <option value="15000">15 Seconds
                        <option value="30000">30 Seconds
                        <option value="60000">1 Minute
                        <option value="300000">5 Minutes
                    </select>
                </td>
            </tr>

            <!-- Suppression disabled in this version
            <tr>
                <td>Show</td>
                <td>
                    <select name="show_suppressed" id="show_suppressed" multiple size=1>
                        <option selected value="all">All Events</option>
                        <option value="suppressed">Suppressed Events</option>
                        <option value="unsuppressed">Unsuppressed Events</option>
                    </select>
                </td>
            </tr>
            -->
        </tbody>
    </table>
    <!-- END HTML for search options -->
    <?php } else { ?>
    <script type="text/javascript">
        $('#portlet_Search_Options').remove()
        </script>
        <?php } ?>
