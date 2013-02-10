<script type="text/javascript">
$(document).ready(function(){
// Get rid of the portlet header for Chart displays
$('.portlet-header').remove()
}); //end doc ready
</script>
<?php 

# Get array with num values for time range from now - num * step to now.
# If special defined, it can be one of:
# monthly - step is ignored, it returns data for last $num months
# weekly_sun - step is ignored, returns data for last $num weeks, week starts on Sunday
# weekly_mon - step is ignored, returns data for last $num weeks, week starts on Monday
# daily - step is ignored, returns data for last $num days (each starting at midnight localtime)
function get_data( $num, $step, $special = "" ) {

    $now = time();
    if( $step <= 0 ) 
        die( "Step must be grater than 0" );

    // Initialize array with start of each period, and value of zeros
    // This is tricky for periods like day or longer, as we have to account DST,
    // different start of week and different length of month
    $output = array();
    if( $special == 'daily' ) {
        $step = 24*60*60;
        $start = $now - $step * $num;
        for( $ts = $start; $ts < $now; $ts += 1.5*$step ) {
            list($ss, $mm, $hh, $d, $m, $y) = localtime($ts);
            $ts = mktime(0, 0, 0, $m+1, $d, $y+1900);
            $output[$ts] = array( 'count' => 0, 'sum' => 0 );
        }
    }
    elseif( $special == 'weekly_mon' || $special == 'weekly_sun' ) {
        $step = 7*24*60*60;
        $start = $now - $step * $num;

        $t = localtime($start, true);
        // move back to the nearest monday or sunday
        if( $special == 'weekly_mon' ) {
            $start -= 24*60*60 * (($t['tm_wday']-1)%7);
        }
        else {
            $start -= 24*60*60 * $t['tm_wday'];
        }

        for( $ts = $start; $ts < $now; $ts += $step + 12*60*60 ) {
            list($ss, $mm, $hh, $d, $m, $y) = localtime($ts);
            $ts = mktime(0, 0, 0, $m+1, $d, $y+1900);
            $output[$ts] = array( 'count' => 0, 'sum' => 0 );
        }
    }
    elseif( $special == 'monthly' ) {
        $step = 31*24*60*60;
        $start = $now - $step * $num;

        list($ss, $mm, $hh, $d, $m, $y) = localtime($start);
        // move back to the start of the month
        $start = mktime( 0, 0, 0, $m+1, 1, $y+1900 );

        for( $ts = $start; $ts < $now; $ts += $step * 1.5 ) {
            list($ss, $mm, $hh, $d, $m, $y) = localtime($ts);
            $ts = mktime(0, 0, 0, $m+1, 1, $y+1900);
            $output[$ts] = array( 'count' => 0, 'sum' => 0 );
        }
    }
    else {
        $start = $now - $step * $num;
        $start = floor($start/$step)*$step;
        for( $ts = $start; $ts < $start + $num * $step; $ts += $step ) {
            $output[$ts] = array( 'count' => 0, 'sum' => 0 );
        }
    }

    global $dbLink;

    if( $step >= 3600 )
        $table = 'events_per_hour';
    elseif( $step >= 60 ) 
        $table = 'events_per_minute';
    else
        $table = 'events_per_second';

    $sql = "SELECT ts_from, count FROM $table " .
        "WHERE name = 'msg' AND ts_from > $start ";
    $res = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);

    // We can get data with higher resolution than requested, thus we need to aggreate them,
    // so we iterate all values we got and put them to the proper time slot (sum and count)
    while( $row = fetch_array($res) ) {
        // find proper index in $output array - max index which is less or equal to $t
        $ts = 0;
        foreach( array_keys( $output ) as $t ) {
            if( $t > $row['ts_from'] )
                break;
            $ts = $t;
        }

        if( $ts ) {
            if( ! is_nan($v) ) {
                $output[$ts]['count']++;
                $output[$ts]['sum'] += $row['count'];
            }
        }
    }

    // If last item has no data, remove it.
    $last = end($output);
    if( $last['count'] == 0 ) {
        array_pop($output);
    }
    // Then remove first till we get no more data than requested
    while( count($output) > $num ) {
        reset($output);
        unset($output[key($output)]);
    }

    // Now iterate slots, check if we got enough data and if so - put average value, 
    // if not - just put zero
    foreach( $output as $t => $v ) {
        if( $v['count'] > 0 ) 
            $output[$t] = $v['sum'];
        else
            $output[$t] = 0;
    }

    return $output;
}


function mps(){
    $chartId = "chart_mps";
    $title =  "Last Minute" ;

    $data = get_data( 60, 1 );

    $values = array();
    $labels = array();
    $averages = array();

    $sum = 0;
    foreach( $data as $ts => $v ) {
        $values[] = $v;
        $labels[] = ( $ts % 10 == 0 ? strftime( "%H:%M:%S", $ts ) : '' );
        $sum += $v;
    }
    $avg = sprintf( "%0.2f", $sum / count($values) );
    $averages = array_fill( 0, count($values), $avg );

    print_r( $array );
    $tchart = new jqNewChart("areaspline",$values,$title,"MPS",$labels);
    $tchart->chartData($averages,"average");//alert(this.value); return  this value; 
    $tchart->setXAxisData($labels);
    $tchart->setSeriesOption("average",array("type"=>"spline"));

    $tchart->setMarker(false);
    $tchart->setTooltip(" return this.point.y + ' events'");

    echo $tchart->renderChart($chartId);
}

function mpm(){
    $chartId = "chart_mpm";
    $title =  "Last Hour" ;

    $data = get_data( 60, 60 );

    $values = array();
    $labels = array();
    $averages = array();

    $sum = 0;
    foreach( $data as $ts => $v ) {
        $values[] = $v;
        $labels[] = ( $ts % 600 == 0 ? strftime( "%H:%M", $ts ) : '' );
        $sum += $v;
    }
    $avg = sprintf( "%0.2f", $sum / count($values) );
    $averages = array_fill( 0, count($values), $avg );

    print_r( $array );
	$tchart = new jqNewChart("areaspline",$values,$title,"MPS",$labels);
	$tchart->chartData($averages,"average");
	$tchart->setXAxisData($labels);
	$tchart->setSeriesOption("average",array("type"=>"spline"));
	

	$tchart->setMarker(false);
	$tchart->setTooltip(" return this.point.y + ' events'");
		
	echo $tchart->renderChart($chartId);
}

function mph(){
    $chartId = "chart_mph";
    $title =  "Last 24 hours" ;    
    
    $data = get_data( 24, 60*60 );

    $values = array();
    $labels = array();

    foreach( $data as $ts => $v ) {
        $values[] = $v;
        $labels[] = strftime( "%a, %I%p", $ts );
    }

    $tchart = new jqNewChart( "column", $values, $title, " ", $labels );
	$tchart->setXAxisData($labels);
    $tchart->setTooltip(" return this.x.replace('</b><br>','</b> - ') " .
        "+ ' <br/> '+humanReadable(this.y) + ' events'");

    echo $tchart->renderChart($chartId);
}
  
function mpd(){
    $chartId = "chart_mpd";
    $title =  "Last 30 days" ;    
    
    $data = get_data( 30, 60*60*24, 'daily' );

    $values = array();
    $labels = array();

    foreach( $data as $ts => $v ) {
        $values[] = $v;
        $labels[] = strftime( "%b, %d", $ts );
    }

    $tchart = new jqNewChart( "column", $values, $title, " ", $labels );
	$tchart->setXAxisData($labels);
    $tchart->setTooltip(" return this.x.replace('</b><br>','</b> - ') " .
        "+ ' <br/> '+humanReadable(this.y) + ' events'");

    echo $tchart->renderChart($chartId);
}
  
function  mpw()
{
    $chartId = "chart_mpw";
    $title = "Last 20 weeks";

    $sow = $_SESSION['CHART_SOW'];

    $data = get_data( 20, 60*60*24*7, ($sow == 'Sun' ? 'weekly_sun' : 'weekly_mon') );

    $values = array();
    $labels = array();

    foreach( $data as $ts => $v ) {
        $values[] = $v;
        $labels[] = strftime( "%b %d", $ts ) . '<br/>' . strftime( "%b %d", $ts + 6*24*60*60 );
    }

    $tchart = new jqNewChart( "column", $values, $title, " ", $labels );
	$tchart->setXAxisData($labels);
    $tchart->setTooltip(" return this.x.replace('</b><br>','</b> - ') " .
        "+ ' <br/> '+humanReadable(this.y) + ' events'");

    echo $tchart->renderChart($chartId);
}

function mmo()
{
    $chartId = "chart_mmo";
    $title =  "Last 12 months";
 
    $data = get_data( 12, 60*60*24*31, 'monthly' );

    $values = array();
    $labels = array();

    foreach( $data as $ts => $v ) {
        $values[] = $v;
        $labels[] = strftime( "%b %Y", $ts );
    }

    $tchart = new jqNewChart( "column", $values, $title, " ", $labels );
	$tchart->setXAxisData($labels);
    $tchart->setTooltip(" return this.x.replace('</b><br>','</b> - ') " .
        "+ ' <br/> '+humanReadable(this.y) + ' events'");

    echo $tchart->renderChart($chartId);
}
?>
