<?php
/*++++++++++++++++++++++++++++++++++++++++++++++++
 * 
 * CLASS CHART
 * CREATED BY WESAM GERGES
 * 05-12-2011
 * 
 * 
 * $chart1 = new NewChart($type,$values,$title, $seriesName = "", $xValues = null,$yAxisTitle,$target="chart_adhoc", $properties=array());
 * 
 * 
 * ++++++++++++++++++++++++++++++++++++++++++++++++
 */


  class NewChart {

    private $_data;
	private $numberOfData=1;
    // CDUKES 2011-12-16: Added " = null" to $yAxisTitle in order to fix error on graph display:
    // Error was:
    // Warning: Missing argument 6 for NewChart::__construct(), called in /var/www/logzilla/html/includes/portlets/portlet-chart_adhoc.php on line 271 and defined in /var/www/logzilla/html/includes/Chart.php on line 20
    public function __construct($type,$values,$title, $seriesName = "", $xValues = null,$yAxisTitle = null,$target="chart_adhoc", $properties=array()){
      	
      	$this->_data = $properties;
		$this->numberOfData = 1;
			  
	  	$this->chartType($type,$xValues,$yAxisTitle);				
		$this->chartData($values,$seriesName,$xValues,$yAxisTitle);
		$this->setTitle($title);
		$this->setTargetElement($target);
		$this->_data['tooltip']['formatter'] = " fn";
	  	 
    }
 
/*
 * TO CHANGE THE CHART TITLE
 * 
 */
	function setTitle($title)
	{
		$this->_data['title']['text'] = $title;		
	}
	
	function setMargin($margin){
		$this->_data['title']['margin'] = $margin;
	}
	
	/**
	 * TO SET THE TARGET DOM ELEMENT THAT WILL HOLD THE CHART
	 * 
	 * 
	 * 
	 */
	
	function setTargetElement($target)
	{
		$this->_data['chart']['renderTo'] =  $target;
	}
	/**
	 * INDECATE THE CHART TYPE:
	 * PIE  LINE  BAR
	 * 
	 * 
	 */
	function chartType($type,$xValues = null,$yAxisTitle="")
	{
		$this->_data['chart']['type'] = $type;
		$this->_data['series'][0]['type'] = $type;
		if($type == 'pie')
			$this->_data['plotOptions'][$type]['events']['click'] = "fun;}";
		else{			
		 	$this->_data['xAxis']['categories'] = $xValues;			
			$this->_data['yAxis']['title']['text'] = "";			
		}
		
		$this->_data['legend']['enabled'] = false;	
		$this->_data['chart']['height'] = "";
		$this->_data['chart']['width'] = "";
		$this->_data['chart']['backgroundColor'] = '#FFFFDF';
		//$this->_data['plotOptions']['series']['color'] = '#FFFFDF55';
		//$this->_data['exporting']['enabled'] = false;
	}
	function setMarker($state){
		$this->_data['plotOptions']['series']['marker']['enabled']= $state;
	}
	function chartData($values, $seriesName = "" )
	{		
		$this->_data['series'][$this->numberOfData++]['name'] = $seriesName;
		$this->_data['series'][$this->numberOfData++]['data'] = $values;					
	}

	function rotateXLabels($rotation = -45,$aling = 'right', $font = 'bold 18px Verdana, sans-serif' )
	{
		$this->_data["xAxis"]["labels"]["rotation"] = $rotation;
		$this->_data["xAxis"]["labels"]["align"] = $aling;
		$this->_data["xAxis"]["labels"]["style"]["font"] = $font;		
	}
	
	function setTooltip($toolTip)
	{
		$this->_data['tooltip']['formatter'] = $toolTip;
		
	}
	/*
	 * CONVERING THE ATTRIBUTE ARRAY TO JSON STRING
	 * 
	 */
	
	
	function toJSON()
	{		
		return json_encode($this->_data);
	}
	
	 
  }

?>
