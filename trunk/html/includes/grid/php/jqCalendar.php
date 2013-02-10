<?php
/**
 * Description of jqCalendar
 *
 * @author tony
 */
class jqCalendar {
	public $version = '4.3.2.0';

	private $coptions = array(
		"disabled"=>false,
		"dateFormat"=>"dd/mm/yy"
	);

	public $buttonIcon = false;
	public $buttonOnly = false;
	public $fontsize = '69%';

	function __construct()
	{
		return true;
	}

	public function getOption($option)
	{
		if(array_key_exists($option, $this->coptions))
			return $this->coptions[$option];
		else
			return false;
	}

	public function setOption($option, $value=null)
	{
		if(isset ($option) ) {
			if(is_array($option)) {
				foreach($option as $key => $value) {
					$this->coptions[$key] = $value;
				}
				return true;
			} else if($value != null) {
				$this->coptions[$option] = $value;
				return true;
			}
		}
		return false;
	}

	public function setEvent($event, $code)
	{
		if(isset ($event) && isset($code) ) {
			$this->coptions[$event] = "js:".$code;
		}
	}

	public function renderCalendar($element, $script=true, $echo = true)
	{
		$s = "";
		if($script) {
			$s .= "<script type='text/javascript'>";
			$s .= "jQuery(document).ready(function() {";
		}
		$s .= "if(jQuery.ui) { if(jQuery.ui.datepicker) { ";
		if($this->buttonIcon || $this->buttonOnly) {
			$s .= "jQuery('".$element."').after('<button>Calendar</button>').next()";
			$s .= ".button({icons:{primary: 'ui-icon-calendar'}, text:false})";
			$s .= ".css({'font-size':'".$this->fontsize."'})";
			$s .= ".click(function(e){jQuery('".$element."').datepicker('show');return false;});";
		}
		if($this->buttonOnly) {
			$this->setOption('showOn', 'button');
		}
		$s .= "jQuery('".$element."').datepicker(".jqGridUtils::encode($this->coptions).");";
		if($this->buttonOnly) {
			// delete the auto generated button.
			$s .= "jQuery('.ui-datepicker-trigger').remove();";
		}
		$s .= "jQuery('.ui-datepicker').css({'font-size':'".$this->fontsize."'});";
		$s .= "} }";
		if($script) $s .= " });</script>";
		if($echo) {
			echo $s;
		}  else {
			return $s;
		}
	}
}
?>
