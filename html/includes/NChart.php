<?php
/*++++++++++++++++++++++++++++++++++++++++++++++++
 * 
 * CLASS CHART
 * CREATED BY WESAM GERGES
 * 08-12-2011
 * 
 * 
 * $chart1 = new NewChart($type,$values,$title, $seriesName = "", $xValues = null,$yAxisTitle,$target="chart_adhoc", $properties=array());
 * 
 * 
 * ++++++++++++++++++++++++++++++++++++++++++++++++
 */
$basePath = dirname( __FILE__ );
// include the jqUtils Class. The class is needed in all jqSuite components.

//require_once $basePath."/grid/php/jqUtils.php";

// include the jqChart Class
//require_once $basePath."/grid/php/jqChart.php";

  class jqNewChart {
	public $_data;
/*	
    public function __construct(){
    	$this->_data = new jqChart();
		
		$this->setTitle($title);
		$this->chartData($values,$seriesName);
		$this->target = $target;		
    }
	
	function setTitle($title)
	{
		$this->_data->setTitle(array('text'=>$title));		
	}
	
	function setTooltip($toolTip)
	{
		$this->_data->setTooltip(array("formatter"=>$toolTip));		
	}
	
	function chartData($values, $seriesName = "" )
	{		
		$this->_data->addSeries($seriesName, array($values ));	 
    }
	function CreatChart(){
		$this->_data->renderChart($target);
	}*/ 
 }
?>