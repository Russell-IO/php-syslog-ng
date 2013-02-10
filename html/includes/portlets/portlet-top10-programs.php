<?php

/*
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2010 LogZilla, LLC
 * All rights reserved.
 * Last updated on 2010-06-15
 *
 * Changelog:
 * 2009-12-13 - created
 *
 */

session_start();
$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
if ((has_portlet_access($_SESSION['username'], 'Programs') == TRUE) || ($_SESSION['AUTHTYPE'] == "none")) {
    $array = spx_query("select *, count(*) x from distributed group by program order by x desc limit 10");
    for ($i = 1; $i <= count($array); $i++) {
        $id = $array[$i][0];
        switch($id) {
            case 'total':
                $total = $array[$i][1];
                break;
            case 'total_found':
                $total_found = $array[$i][1];
                break;
            case 'time':
                $time = $array[$i][1];
                break;
        }
        $ids[] = $array[$i][0];
        $host_crcs[] = $array[$i][1];
        $facilities[] = $array[$i][2];
        $severities[] = $array[$i][3];
        $programs[] = $array[$i][4];
        $msg_crc[] = $array[$i][5];
        $mnes[] = $array[$i][6];
        $eids[] = $array[$i][7];
        $notes_crcs[] = $array[$i][8];
        $counters[] = $array[$i][9];
        $fos[] = $array[$i][10];
        $los[] = $array[$i][11];
        $counts[] = $array[$i][12];
        $prgname = crc2prg($array[$i][4]);
        $arr["$prgname"] = $array[$i][12];
    }
    // pop the array because there's a blank from the meta info
    array_pop($arr);
    // echo "<pre>";
    // echo "Debug:\n<br>";
    // echo "Total = $total<br>";
    $pievalues = json_encode($arr);
    $_SESSION['top10p'] = $pievalues;
    // echo $pie;
    // die($_SESSION['top10p']);

?>
<!-- 2. Add the JavaScript to initialize the chart on document ready -->
        <script type="text/javascript">
        
        $("#portlet-content_Programs").width("96%");
            var chart;
            var piedata = <?php echo ($_SESSION['top10p'])?>;
            // alert(piedata);
            $(document).ready(function() {
                chart = new Highcharts.Chart({
                    chart: {
                        renderTo: 'portlet-content_Programs',
                        plotBackgroundColor: null,
                        plotBorderWidth: null,
                        plotShadow: false
                    },
                    title: {
                        text: 'Browser market shares at a specific website, 2010'
                    },
                    tooltip: {
                        formatter: function() {
                            return '<b>'+ this.point.name +'</b>: '+ this.percentage +' %';
                        }
                    },
                    plotOptions: {
                        pie: {
                            allowPointSelect: true,
                            cursor: 'pointer',
                            dataLabels: {
                                enabled: false
                            },
                            showInLegend: true
                        }
                    },
                    series: [{
                        type: 'pie',
                        name: 'Top 10 Programs',
                        data: [
                            piedata,
                            ['Firefox',   45.0],
                            ['IE',       26.8],
                            {
                                name: 'Chrome',    
                                y: 12.8,
                                sliced: true,
                                selected: true
                            },
                            ['Safari',    8.5],
                            ['Opera',     6.2],
                            ['Others',   0.7]
                        ]
                    }]
                });
        // $("#portlet-content_Programs").css(style="width: 800px; height: 400px; margin: 0 auto");
            });
                
        </script>
<?php
} else { ?>
<script type="text/javascript">
$('#portlet_Programs').remove()
</script>
<?php } ?>
