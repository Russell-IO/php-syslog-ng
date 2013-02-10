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
$basePath2 = dirname( __FILE__ );
// include the jqUtils Class. The class is needed in all jqSuite components.

require_once $basePath2."/grid/php/jqUtils.php";

// include the jqChart Class
require_once $basePath2."/grid/php/jqChart.php";

  class jqNewChart {
	public $_data;
	public $chartType;
	public $seriesName;
	
    public function __construct($type,$values,$title, $seriesname = "", $xValues = null)
    {
    	$this->_data = new jqChart();
		$this->chartType = $type;
		$this->seriesName = $seriesname;
		$this->setTitle($title);
		$this->chartData($values,$seriesname);
		$this->chartType($type,$xValues);
    }
	
	 // INDECATE THE CHART TYPE:  PIE  LINE  BAR
	  
	 
	function chartType($type,$xValues)
	{
		$this->_data->setChartOptions("defaultSeriesType",$type);			
		if($type != 'pie')
		{		  
		 // $this->setXAxis($formatter);//"d = new Date(this.value);return d.getDate()+'<br/>'+weekdaystxt[d.getDay()][0];");
		 
		  $this->_data->setyAxis("min",0);
		  $this->_data->setyAxis("title",array("text"=>"Events"));						 			
		}
		$this->_data->setLegend(array("enabled"=> false)) ;
		$this->_data->setChartOptions("backgroundColor",'#FFDDAA');
	}
	function setXAxisData($xValues,$formatter = " return this.value;")
	{
		$this->_data->setxAxis(array("categories"=>$xValues,
		    'title'=>array('text'=>''),"labels"=>array("formatter"=>"js:function(){  {$formatter}  }")
				));
	}
	function setXAxis($formatter = " return this.value; ", $tickInterval = 86400000 ,$axisType = "datetime")
	{
		//if($this->chartType != 'pie')
		{			
		  $this->_data->setxAxis(array("tickInterval"=> $tickInterval,//"categories"=>$xValues,
		    'title'=>array('text'=>''),"type"=>$axisType,
		    "labels"=>array("formatter"=>"js:function(){  {$formatter}  }")	)); 
		}
	}
	
	public function setInterval( $formatter = " return this.value; ",$pointStart, $pointInterval = 86400000 ,$axisType = "datetime" )
	{
		$this->_data->setxAxis(array("tickInterval"=> $pointInterval,//"categories"=>$xValues,
		    'title'=>array('text'=>''),"type"=>$axisType,
		    "labels"=>array("formatter"=>"js:function(){  {$formatter}  }")	)); 
		    
		$this->_data->setSeriesOption($this->seriesName,array("pointInterval"=> $pointInterval, "pointStart"=>$pointStart));
	}
	
	function setChartOptions()
	{
		$this->_data->setChartOptions("reflow",true);
    }
	
	function setTitle($title)
	{
		$this->_data->setTitle('text',$title);		
	}
	
	public function setTooltip($toolTip)
	{
		$this->_data->setTooltip(array("formatter"=>"function(){  ".$toolTip.";}"));		
	}
	
	public function chartData($values, $seriesName = "" )
	{		
		if($values)
		$this->_data->addSeries($seriesName, $values );	 
    }
    
	
	public function setSeriesOption( $seriesName , $type)
	{		
		
		$this->_data->setSeriesOption($seriesName, $type);
		/*	 
		"MPD",array("pointInterval"=> 24*3600*1000, 
							"pointStart"=>"js:Date.UTC({$date[0]}, ".($date[1]-1).",".($date[2]+1)." )"	));
		*/
    }
	
	function setMarker($state)
	{
		$this->_data->setPlotOptions(array( "series"=>array("marker"=>array("enabled"=>$state)) )) ;
	}
	
	
	function rotateXLabels($rotation = -45, $x = 0, $y = 0,$align = 'right', $font = 'bold 18px Verdana, sans-serif' )
	{
		 $this->_data->setxAxis(array("labels"=>array( 
	        "rotation"=> $rotation,
	        "align"=>$align,
	        "x"=> $x,
	        "y"=> $y,
	        "style"=>array("font"=>$font) 
		    ) 
			)); 				
	}
	
	public function setTitleMargin($margin){
		//$this->_data['title']['margin'] = $margin;
		$this->_data->setTitle('margin',$margin);
	}
	
	/*
	function addJSCode( $code )
	{
		$this->_data->setJSCode($code);
	}
	*/
	public function OnClick($fn)
	{
		$this->_data->setChartEvent('click', "function(event){".$fn."}" ); 
	}
	public function addClick($fn)
	{
		$this->_data->setPlotOptions(array( 
    			"series"=>array(        
        		"point"=>array( 
            	"events"=>array( 
                "click"=>"js:function(event){".$fn."}" 
            	) 
        	) 
    	) 
	));
		
	}
	/*
	 * RENDERCHART:
	 * USED TO GENERATE THE CHART USING JQCHART FUNCTION RENDERCHART
	 * 
	 * 
	 */
	
	public function renderChart($target,$width="",$hight="")
	{
		$renderedChart = $this->_data->renderChart($target,false,"","","chart".$target);
		/* 
		 *SEARCH FOR THE END OF THE SCRIPT TO APPEND THE RESIZE 
		 *BECAUSE I NEED TO PUT A VARIABLES IN THE WIDTH AND HIGHT NOT CONSTANT NUMBERS 
		 *AND IT WILL PUT THEM IN A STRING.
		 * 
		 */ 
		 
		 if($width=="") return $renderedChart;
		 
		$position = strpos($renderedChart, "});</script>");
		return substr_replace($renderedChart, "chart".$target.".setSize(".$width.",".$hight.");", $position, 0); 	
	}
	
	
 }

	/*
	 * THIS IS HOW TO HANDEL A CLICK
	 * 
	 * 
	 * // Here wi attach a click function when cklic on series point 
	 * 
	 * 
	 */
	

?>

