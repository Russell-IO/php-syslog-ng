<?php
/**
 * @author  Tony Tomov, (tony@trirand.com)
 * @copyright TriRand Ltd
 * @version 4.3.2
 * @package jqChart
 *
 * @abstract
 * A PHP class to work with jqChart jQuery plugin.
 *
 */

class jqChart
{
	public $version = '4.3.2';
	/**
	 * Stores all the chart options
	 * @var array
	 */
	private $coptions = array();
	/**
	 * Stores the connection in case of Db data
	 * @var resource
	 */
	private $conn;
	/**
	 * Stores the database type - i.e mysql, postgres etc.
	 * @var string
	 */
	private $dbtype;
	/**
	 * Javascript code to be executed aftere the chart render
	 * @var string
	 */
	private $jscode;
	/**
	 * index of the series
	 * @var integer
	 */
	private $i_serie_index;
	/**
	 * Contain the name of the theme.
	 * @var string 
	 */
	private $theme = '';

	function __construct($db=null) {
		if(class_exists('jqGridDB') && $db)
			$interface = jqGridDB::getInterface();
		else
			$interface = 'chartarray';
		$this->conn = $db;
		if($interface == 'pdo')
		{
			try {
				$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$this->dbtype = $this->conn->getAttribute(PDO::ATTR_DRIVER_NAME);
			} catch (Exception $e) {
				
			}
		} else {
			$this->dbtype = $interface;
		}

		# Set Default Values
		$this->coptions['credits']['enabled'] = false;
		$this->coptions['chart']['renderTo'] = '';
		$this->coptions['series'] = array();
		$this->i_serie_index = 0;
		$this->jscode = false;
	}

	protected function convertVar($value, $type)
	{
		switch ($type)
		{
			case 'int':
				return (int)$value;
			case 'numeric':
				return (float)$value;
			default :
				return $value;
		}
	}

	/**
	 * Return a array representation of the sql query. Also return only the
	 * the values of the first column
	 * @param string $sql The sql query string
	 * @param array $params array of parameters passed to the query
	 * @param mixed $limit the number of records to retrieve. if false - all
	 * @param number $offset how many record to skip. 0 - none
	 * @return array of the values of the first column of the query
	 */
	protected function getSQLSerie($sql, $params=null, $limit = false, $offset=0)
	{
		$retarr = array();
		if($this->dbtype != 'chartarray' && $this->conn)
		{
			try {
				if($limit && $limit > 0) {
					$sql = jqGridDB::limit($sql, $this->dbtype, $limit, $offset );
				}
				$sersql = jqGridDB::prepare($this->conn, $sql, $params, true);
				jqGridDB::execute($sersql, $params);
				$xy = false;
				$ncols = jqGridDB::columnCount($sersql);
				if($ncols > 1) {
					$xy = true;
				}
				for ($i=0; $i < $ncols; $i++) {
					$field = jqGridDB::getColumnMeta($i,$sersql);
					$typearr[$i] = jqGridDB::MetaType($field, $this->dbtype);
				}
				while($r = jqGridDB::fetch_num($sersql) )
				{
					$retarr[] = $xy ? array($this->convertVar($r[0],$typearr[0]) ,$this->convertVar($r[1],$typearr[1])) : $this->convertVar($r[0],$typearr[0]);
				}
				jqGridDB::closeCursor($sersql);
			} catch (Exception $e) {
				echo $e->getMessage();
				return false;
			}
		}
		return $retarr;
	}
	/**
	 * Return all the option for the Chart
	 * @return array 
	 */
	public function getChartOptions()
	{
		return $this->coptions;
	}

	/**
	 * Options regarding the chart area and plot area as well as general chart options.
	 *
	 * @param mixed $name the option name for the chart. Can be a string or array.
	 * When used as array a key value pair should be defined, where the key is the name
	 * @param mixed $mixvalue is the value option in case the name is string
	 * @return jqChart instance
	 */
	public function setChartOptions($name, $mixvalue='')
	{
		if($mixvalue == '') {
			if(is_array($name) && count($name) > 0 ) {
				foreach($name as $key =>$val)
				{
					$this->coptions['chart'][$key] = $val;
				}
			}
		} else {
			$name = trim($name);
			if($name != ''){
				$this->coptions['chart'][$name] = $mixvalue;
			}
		}
		return $this;
	}
	
	/**
	 * Set event listeners for the chart.
	 *
	 * @param string $name The name of the event, Can be click, load, redraw, selection
	 * See documentation for more details
	 * @param string $jscode The javascript code associated with this event
	 * @return jqChart  instance
	 */
	public function setChartEvent($name, $jscode)
	{
		$name = trim($name);
		if($name != ''){
			$this->coptions['chart']['events'][$name] = "js:".$jscode;
		}
		return $this;
	}

	/**
	 * Set array containing the default colors for the chart's series.
	 * When all colors are used, new colors are pulled from the start again.
	 * Defaults to:
	 * array('#4572A7', '#AA4643', '#89A54E', '#80699B', '#3D96AE', '#DB843D', '#92A8CD', '#A47D7C', 	'#B5CA92')
	 * @param array $avalue values to be set for the colors
	 * @return jqChart instance
	 */
	public function setColors($avalue){
		if(is_array($avalue) && count($avalue) > 0){
			$this->coptions['colors'] = $avalue;
		}
		return $this;
	}

	/**
	 * Set HTML labels that can be positioined anywhere in the chart area.
	 *
	 * @param mixed $name the option name for the label. Can be a string or array.
	 * When used as array a key value pair should be defined, where the key is the name
	 * @param mixed $mixvalue a value in case the name is a string
	 * @return jqChart instance
	 */
	public function setLabels($name, $mixvalue=''){
		if($mixvalue == '') {
			if(is_array($name) && count($name) > 0 ) {
				foreach($name as $key =>$val)
				{
					$this->coptions['labels'][$key] = $val;
				}
			}
		} else {
			$name = trim($name);
			if($name != ''){
				$this->coptions['labels'][$name] = $mixvalue;
			}
		}
		return $this;
	}

	/**
	 * Set a language object. The default language is English. For detailed info on the
	 * object rehfer to the documentation
	 *
	 * @param mixed $name the option name for the language. Can be a string or array.
	 * When used as array a key value pair should be defined, where the key is the name
	 * @param mixed $mixvalue a value in case the name is a string
	 * @return jqChart instance
	 */
	public function setLanguage($name, $mixvalue=''){
		if($mixvalue == '') {
			if(is_array($name) && count($name) > 0 ) {
				foreach($name as $key =>$val)
				{
					$this->coptions['lang'][$key] = $val;
				}
			}
		} else {
			$name = trim($name);
			if($name != ''){
				$this->coptions['lang'][$name] = $mixvalue;
			}
		}
		return $this;
	}

	/**
	 * Set the legend. The legend is a box containing a symbol and name for
	 * each series item or point item in the chart.
	 *
	 * @param mixed $name the option name for the legend. Can be a string or array.
	 * When used as array a key value pair should be defined, where the key is the name
	 * @param mixed $mixvalue a value option in case the name is a string
	 * @return jqChart instance
	 */
	public function setLegend($name, $mixvalue=''){
		if($mixvalue == '') {
			if(is_array($name) && count($name) > 0 ) {
				foreach($name as $key =>$val)
				{
					$this->coptions['legend'][$key] = $val;
				}
			}
		} else {
			$name = trim($name);
			if($name != ''){
				$this->coptions['legend'][$name] = $mixvalue;
			}
		}
		return $this;
	}
	/**
	 * Set the loading options which control the appearance of the loading
	 * screen that covers the plot area on chart operations
	 *
	 * @param mixed $name the option name for the loading. Can be a string or array.
	 * When used as array a key value pair should be defined, where the key is the name
	 * @param mixed $mixvalue a value option in case the name is a string
	 * @return jqChart instance
	 */
	public function setLoading($name, $mixvalue = ''){
		if($mixvalue == '') {
			if(is_array($name) && count($name) > 0 ) {
				foreach($name as $key =>$val)
				{
					$this->coptions['loading'][$key] = $val;
				}
			}
		} else {
			$name = trim($name);
			if($name != ''){
				$this->coptions['loading'][$name] = $mixvalue;
			}
		}
		return $this;
	}
	/**
	 * Set the plot options for the chart.
	 * The plotOptions is a wrapper object for config objects for each series type.
	 * The config objects for each series can also be overridden for each
	 * series item as given in the series array
	 * @param mixed $name the name of tyhe option as per documentation
	 * @param array $avalue array of options = key value pair
	 * @return jqChart
	 */
	public function setPlotOptions($name, $avalue=''){
		if($avalue == '') {
			if(is_array($name) && count($name) > 0 ) {
				foreach($name as $key =>$val)
				{
					$this->coptions['plotOptions'][$key] = $val;
				}
			}
		} else {
			$name = trim($name);
			if($name != ''){
				if(is_array($avalue) && count($avalue) > 0){
					$this->coptions['plotOptions'][$name] = $avalue;
				}
			}
		}
		return $this;
	}
	/**
	 * Set the subtitle of the chart
	 * 
	 * @param mixed $name the option name for the loading. Can be a string or array.
	 * When used as array a key value pair should be defined, where the key is the name
	 * @param mixed $mixvalue a value option in case the name is a string
	 * @return jqChart instance
	 */
	public function setSubtitle($name, $mixvalue=''){
		if($mixvalue == '') {
			if(is_array($name) && count($name) > 0 ) {
				foreach($name as $key =>$val)
				{
					$this->coptions['subtitle'][$key] = $val;
				}
			}
		} else {
			$name = trim($name);
			if($name != ''){
				$this->coptions['subtitle'][$name] = $mixvalue;
			}
		}
		return $this;
	}
	/**
	 * Set the main title of the chart
	 *
	 * @param mixed $name the option name for the loading. Can be a string or array.
	 * When used as array a key value pair should be defined, where the key is the name
	 * @param mixed $mixvalue a value option in case the name is a string
	 * @return jqChart instance
	 */
	public function setTitle($name, $mixvalue=''){
		if($mixvalue == '') {
			if(is_array($name) && count($name) > 0 ) {
				foreach($name as $key =>$val)
				{
					$this->coptions['title'][$key] = $val;
				}
			}
		} else {
			$name = trim($name);
			if($name != ''){
				$this->coptions['title'][$name] = $mixvalue;
			}
		}
		return $this;
	}

	/**
	 * Set options for the tooltip that appears when the user hovers over a series or point
	 * 
	 * @param mixed $name the option name for the loading. Can be a string or array.
	 * When used as array a key value pair should be defined, where the key is the name
	 * @param mixed $mixvalue a value option in case the name is a string
	 * @return jqChart instance
	 */
	public function setTooltip($name, $mixvalue=''){
		if($mixvalue == '') {
			if(is_array($name) && count($name) > 0 ) {
				foreach($name as $key =>$val)
				{
					if($key=='formatter') $val = "js:".$val;
					$this->coptions['tooltip'][$key] = $val;
				}
			}
		} else {
			$name = trim($name);
			if($name != ''){
				if($name=='formatter') $mixvalue = "js:".$mixvalue;
				$this->coptions['tooltip'][$name] = $mixvalue;
			}
		}
		return $this;
	}

	/**
	 * Set the X axis or category axis. Normally this is the horizontal axis,
	 * though if the chart is inverted this is the vertical axis.
	 * In case of multiple axes, the xAxis node is an array of configuration objects.
	 *
	 * @param mixed $name the option name for the loading. Can be a string or array.
	 * When used as array a key value pair should be defined, where the key is the name
	 * @param mixed $mixvalue a value option in case the name is a string
	 * @return jqChart instance
	 */
	public function setxAxis($name, $mixvalue=''){
		if($mixvalue == '') {
			if(is_array($name) && count($name) > 0 ) {
				foreach($name as $key =>$val)
				{
					$this->coptions['xAxis'][$key] = $val;
				}
			}
		} else {
			$name = trim($name);
			if($name != ''){
				$this->coptions['xAxis'][$name] = $mixvalue;
			}
		}
		return $this;
	}
	/**
	 * Set the Y axis or value axis. Normally this is the vertical axis, though
	 * if the chart is inverted this is the horiontal axis. In case of multiple
	 * axes, the yAxis node is an array of configuration objects.
	 *
	 * @param mixed $name the option name for the loading. Can be a string or array.
	 * When used as array a key value pair should be defined, where the key is the name
	 * @param mixed $mixvalue a value option in case the name is a string
	 * @return jqChart instance
	 */
	public function setyAxis($name, $mixvalue=''){
		if($mixvalue === '') {
			if(is_array($name) && count($name) > 0 ) {
				foreach($name as $key =>$val)
				{
					$this->coptions['yAxis'][$key] = $val;
				}
			}
		} else {
			$name = trim($name);
			if($name != ''){
				$this->coptions['yAxis'][$name] = $mixvalue;
			}
		}
		return $this;
	}
	/**
	 * Set options for the Exporting module
	 *
	 * @param mixed $name the option name for the loading. Can be a string or array.
	 * When used as array a key value pair should be defined, where the key is the name
	 * @param mixed $mixvalue a value option in case the name is a string
	 * @return jqChart instance
	 */
	public function setExporting($name, $mixvalue=''){
		if($mixvalue === '') {
			if(is_array($name) && count($name) > 0 ) {
				foreach($name as $key =>$val)
				{
					$this->coptions['exporting'][$key] = $val;
				}
			}
		} else {
			$name = trim($name);
			if($name != ''){
				$this->coptions['exporting'][$name] = $mixvalue;
			}
		}
		return $this;
	}
	/**
	 * Set  collection of options for buttons and menus appearing in the
	 * exporting module.
	 *
	 * @param mixed $name the option name for the loading. Can be a string or array.
	 * When used as array a key value pair should be defined, where the key is the name
	 * @param mixed $mixvalue a value option in case the name is a string
	 * @return jqChart instance
	 */
	public function setNavigation($name, $mixvalue=''){
		if($mixvalue === '') {
			if(is_array($name) && count($name) > 0 ) {
				foreach($name as $key =>$val)
				{
					$this->coptions['navigation'][$key] = $val;
				}
			}
		} else {
			$name = trim($name);
			if($name != ''){
				$this->coptions['navigation'][$name] = $mixvalue;
			}
		}
		return $this;
	}
	/**
	 * Add a data to the series with a given name. If the name exists the data will
	 * be overwritten. Data can be added via array, sql query or javascript function
	 *
	 * @param string $name the name of the chart. This will be displayed in the chart
	 * @param mixed $value can be array, sql query or java script function.
	 * @param array $params parameters passed to the query in case of SQL data
	 * @param mixed $limit if set to number the number of records to retrieve
	 * @param integer $offset how many records to skip in case of sql.
	 * @return jqChart
	 */
	public function addSeries($name, $value, $params =  null, $limit=false, $offset=0)
	{
		$datafunc = false;
		if($name != '') {
			if(is_string($value)) {
				if(strpos($value,'js:')===0) {
					$datafunc = true;
					$mixvalue = $value;
				} else {
					$mixvalue = $this->getSQLSerie($value, $params, $limit, $offset);
				}
			} else {
				$mixvalue = $value;
			}
			if(is_array($mixvalue) || $datafunc)
			{
				$f=false;
				foreach($this->coptions['series'] as $index => $serie){
					if(strtolower($serie['name']) == strtolower($name)){
						$f=$index;
						break;
					}
				}

				if( $f!==false ){
					if($datafunc) {
						// function
						$this->coptions['series'][$f]['data'] = $mixvalue;
					} else {
						if(empty($mixvalue)) {
							$this->coptions['series'][$f]['data'] = $mixvalue;
						} else {
							foreach($mixvalue as $val){
								$val = (is_numeric($val)) ? (float)$val : $val;
								$this->coptions['series'][$f]['data'][] = $val;
							}
						}
					}
				} else {
					$this->coptions['series'][$this->i_serie_index]['name'] = $name;
					if($datafunc) {
						$this->coptions['series'][$this->i_serie_index]['data'] = $mixvalue;
					} else {
						if(empty ($mixvalue)) {
							$this->coptions['series'][$this->i_serie_index]['data'] = $mixvalue;
						} else {
							foreach($mixvalue as $val){
								$val = (is_numeric($val)) ? (float)$val : $val;
								$this->coptions['series'][$this->i_serie_index]['data'][] = $val;
							}
						}
					}
					$this->i_serie_index++;
				}
			}
		}
		return $this;
	}
	/**
	 * Set a various options for a serie.
	 *
	 * @param string $name the name for the serie
	 * @param mixed $option can be a array or string. If array a key value pair
	 * should be used, where key is the properti value is the optinvalue
	 * @param mixed $value the value of the option value in case the option is a string
	 * @return jqChart
	 */
	public function setSeriesOption($name='', $option='', $value=''){
		$name = trim($name);
		if($name !== '' && $option){
			$f=false;
			foreach($this->coptions['series'] as $index => $serie){
				if(strtolower($serie['name']) == strtolower($name)){
					$f=$index;
					break;
				}
			}

			if( $f !== false ){
				if(is_array($option) && count($option)>0) {
					foreach($option as $key => $val) {
						$this->coptions['series'][$f][$key] = $val;
					}
				} else {
					$this->coptions['series'][$f][$option] = $value;
				}
			}
		}
		return $this;

	}
	/**
	 * Put a javascript code after all things are created. The method is executed
	 * only once when the chart is created.
	 * @param string $code - javascript to be executed
	 */
	public function setJSCode($code) {
		if(strlen($code)>0) {
			$this->jscode = 'js:'.$code;
		}
		return $this;
	}
	/**
	 * Set a theme - Can be grid, gray, dark-blue, dark-green
	 * @param string $theme the name of the theme
	 * @return jqChart 
	 */
	public function setTheme($theme = '')
	{
		if($theme && strlen($theme)>0) {
			$this->theme = $theme;
		} else {
			$this->theme = '';
		}
		return $this;
	}

	/**
	 * Main method which construct the chart based on the options set
	 * with the previous methods
	 *
	 * @param string $div_id the id of the chart element in the DOM. If empty
	 * the default name 'jqchart' is used.
	 * @param boolean $createlem if set to true a div element is created. If the
	 * option is set to false the previous option should be set in order to render
	 * the chart to a existing element.
	 * @param mixed $width set the width of the chart. If  a number is used the
	 * width is created in pixels. Have sense only if $createlem is true
	 * @param <type> $height set the height of the chart. If  a number is used the
	 * height is created in pixels. Have sense only if $createlem is true
	 * @param string $chart the name which is used when a javascript chart object
	 * is created. Can be used later to refer to the chart. The default
	 * name is 'chart'
	 *
	 * @return string
	 */
	public function renderChart($div_id='',$createlem=true, $width='800',$height='400', $chart='chart'){
		if($div_id == '') $div_id = 'jqchart';
		$this->coptions['chart']['renderTo'] = $div_id;
		$width = is_numeric($width) ? $width.'px' : $width;
		$height = is_numeric($height) ? $height.'px' : $height;
		$dim = "width:".$width.";height:".$height.";margin: 0 auto;";
		$s = "";
		if($createlem)
		{
			$s .= '<div id="'.$div_id.'" style="'.$dim.'"></div>';
		}
		$s .= '<script type="text/javascript">';
		$s .= 'jQuery(document).ready(function(){';
		if($this->theme && strlen($this->theme)>0) {
			if(strpos($this->theme,'.js') === false) {
				$themeFile = $this->theme.".js";
			} else {
				$themeFile = $this->theme;
			}
			try {
				$theme = file_get_contents($themeFile);
				if($theme !== false) {
					$s .= $theme;
				}
			} catch (Exception $e) {

			}
		}
		if(isset ($this->coptions['lang']))
		{
			$s .= 'Highcharts.setOptions({lang:'.jqGridUtils::encode($this->coptions["lang"]).'});';
			unset($this->coptions['lang']);
		}
		$s .= 'var '.$chart.' = new Highcharts.Chart('.jqGridUtils::encode($this->coptions).');';
		if($this->jscode) {
			$s .= jqGridUtils::encode($this->jscode);
		}
		$s .= '});';
		$s .= '</script>';
		return $s;
	}
}
?>
