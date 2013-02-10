$(document).ready(function(){
	if(createRememberButton('mnemonics','portlet-header_Mnemonics')){
		handleRememberClickEventForCheckBox('mnemonics','portlet_Mnemonics');

	}	
	setRememberedCheckboxes('mnemonics','portlet_Mnemonics');
	//setTimeout(function(){	setRememberedCheckboxesForDialog('mnemonics','mne_dialog',11); },5000);
	if(createRememberButton('snare_EventId','portlet-header_Snare_EventId')){
		handleRememberClickEventForCheckBox('snare_EventId','portlet-content_Snare_EventId');
	}	
	setRememberedCheckboxes('snare_EventId','portlet-content_Snare_EventId');
	//setTimeout(function(){	setRememberedCheckboxesForDialog2('snare_EventId','gbox_eidgrid',12); },5000);
	if(createRememberButton('hosts','portlet-header_Hosts')){
		handleRememberClickEventForCheckBox('hosts','portlet-content_Hosts');
	}	
	setRememberedCheckboxes('hosts','portlet-content_Hosts');
	//setTimeout(function(){	setRememberedCheckboxesForDialog3('hosts','host_dialog',14); },5000);
	if(createRememberButton('programs','portlet-header_Programs')){
		handleRememberClickEventForCheckBox('programs','portlet_Programs');

	}	
	setRememberedCheckboxes('programs','portlet_Programs');
	//setTimeout(function(){	setRememberedCheckboxesForDialog4('Programs','prg_dialog',13); },5000);
	if(createRememberButton('severities','portlet-header_Severities')){
		handleRememberClickEvent('severities','portlet-content_Severities');
	}	
	setRememberedSelectBoxes('severities','portlet-content_Severities'); 
	if(createRememberButton('facilities','portlet-header_Facilities')){
		handleRememberClickEvent('facilities','portlet-content_Facilities');
	}	
	setRememberedSelectBoxes('facilities','portlet-content_Facilities'); 
	if(createRememberButton('programs','portlet-header_Programs')){
		handleRememberClickEventForCheckBox('programs','portlet-content_Programs');
	}	
	setRememberedCheckboxes('programs','portlet-content_Programs'); 
	
	if(createRememberButton('search_Options','portlet-header_Search_Options')){
		var portletNames=new Array();
		portletNames[0]='orderby';
		portletNames[1]='order';
		portletNames[2]='groupby';
		portletNames[3]='chart_type';
		portletNames[4]='tail';
		portletNames[5]='show_suppressed';
		portletNames[6]='dupop';
		portletNames[7]='dupcount';
		portletNames[8]='limit';
		handleRememberClickEventForArray(portletNames,'search_Options',null);
	}
	setRememberedText('search_Options','dupcount');
	setRememberedComboBox('search_Options','orderby'); 
	setRememberedComboBox('search_Options','order'); 
	setRememberedComboBox('search_Options','groupby'); 
	setRememberedComboBox('search_Options','chart_type'); 
	setRememberedComboBox('search_Options','tail');
	setRememberedComboBox('search_Options','show_suppressed'); 
	setRememberedComboBox('search_Options','dupop'); 
	setRememberedComboBox('search_Options','limit'); 
	if(createRememberButton('fo_checkbox','portlet-header_Date_and_Time')){
		var portletNames=new Array();
		portletNames[0]='lo_time_end';
		portletNames[1]='lo_time_start';
		portletNames[2]='lo_date';
		portletNames[3]='fo_time_start';
		portletNames[4]='fo_time_end';
		portletNames[5]='fo_date';
		handleRememberClickEventForCheckBoxAndCommon('fo_checkbox','portlet-content_Message',portletNames);
	}	
	setRememberedCheckboxes('fo_checkbox','portlet-content_Date_and_Time'); 
	setRememberedText('fo_checkbox','lo_time_end');
	setRememberedText('fo_checkbox','lo_time_start');
	setRememberedText('fo_checkbox','lo_date');
	setRememberedText('fo_checkbox','fo_time_end');
	setRememberedText('fo_checkbox','fo_time_start');
	setRememberedText('fo_checkbox','fo_date');
	if(createRememberButton('q_type','portlet-header_Messages')){
		var portletNames=new Array();
		portletNames[0]='q_type';
		portletNames[1]='msg_mask';
		handleRememberClickEventForArray(portletNames,'q_type',null);
	}	
	setRememberedRadioButtons('q_type','portlet-content_Messages');
	setRememberedText('q_type','msg_mask');
	$('#btnReset').click(function(){
            $(this).effect('explode');
	if($('#mnemonics-remember-span').attr('class')=='ui-icon ui-icon-locked')
		$('#mnemonics-remember-span').click();
	if($('#programs-remember-span').attr('class')=='ui-icon ui-icon-locked')
		$('#programs-remember-span').click();
	if($('#snare_EventId-remember-span').attr('class')=='ui-icon ui-icon-locked')
		$('#snare_EventId-remember-span').click();
	if($('#hosts-remember-span').attr('class')=='ui-icon ui-icon-locked')
		$('#hosts-remember-span').click();
	if($('#search_Options-remember-span').attr('class')=='ui-icon ui-icon-locked')
		$('#search_Options-remember-span').click();
	if($('#fo_checkbox-remember-span').attr('class')=='ui-icon ui-icon-locked')
		$('#fo_checkbox-remember-span').click();
	if($('#severities-remember-span').attr('class')=='ui-icon ui-icon-locked')
		$('#severities-remember-span').click();
	if($('#facilities-remember-span').attr('class')=='ui-icon ui-icon-locked')
		$('#facilities-remember-span').click();
	if($('#programs-remember-span').attr('class')=='ui-icon ui-icon-locked')
		$('#programs-remember-span').click();
	if($('#q_type-remember-span').attr('class')=='ui-icon ui-icon-locked')
		$('#q_type-remember-span').click();
		uncheckAllCheckBoxes();
	    $('select').each(function (){
            $(this).find('option').each(function(i, opt) {
                opt.selected = opt.defaultSelected;
            });
	    });
         location.reload();
	});
	
 });
function uncheckAllCheckBoxes(){
	$('input:checkbox').each(function(){
		if($(this).is(':checked') && $(this).attr('id')!='lo_checkbox')
			$(this).attr('checked',false);
	});
}

//to remember  check box 
function setRememberedCheckboxes(portletName,portletDivName){
	
	$("#"+portletDivName+" input:checkbox").each(function(){
		var checkedArray=$.cookie('portlet_'+portletName+'_allSelected')==null?null:$.cookie('portlet_'+portletName+'_allSelected').split(',');
	if(existinCookie($(this).attr('value'),checkedArray) && (! $(this).is(':checked'))){
		$(this).click();
		if(!$(this).is(':checked'))
			$(this).attr('checked','checked');
		
	}
	$(this).click(function(){
		if($.cookie('remember-'+portletName+'-cookie')!=null){
			var allSelectedCheckboxes=$("#"+portletDivName+" input:checkbox").serializeArray();
			var storelist=new Array();
			for(var i=0;i<allSelectedCheckboxes.length;i++){
				storelist[i]=allSelectedCheckboxes[i].value;	
			}
			$.cookie('portlet_'+portletName+'_allSelected',storelist, { path: '/', expires: 365 });
		}	
	})	
	});
	
}
//remeber dialogbox checkboxes
function setRememberedCheckboxesForDialog(portletName,portletDivName,startpositionForSubString,mainDivName){
	$("#"+portletDivName+" input:checkbox").each(function(){
		var checkedArray=$.cookie('portlet_'+portletName+'_allSelected')==null?null:$.cookie('portlet_'+portletName+'_allSelected').split(',');
	var matchingValue=$(this).attr('id').substring(startpositionForSubString);
	if(existinCookie(matchingValue,checkedArray) && (! $(this).is(':checked')) ){
		$(this).click();
		if(!$(this).is(':checked'))
			$(this).attr('checked','checked');
	}
	$(this).click(function(){
		if($.cookie('remember-'+portletName+'-cookie')!=null){
			if($(this).is(':checked')){
				var checkedValue=$(this).attr('id').substring(startpositionForSubString);
			var storelist=$.cookie('portlet_'+portletName+'_allSelected')==null?null:$.cookie('portlet_'+portletName+'_allSelected').split(',');
				storelist[storelist.length]=$(this).attr('id').substring(startpositionForSubString);
			$.cookie('portlet_'+portletName+'_allSelected',storelist, { path: '/', expires: 365 });
		}
		}	
	});
	});
}
//remeber dialogbox checkboxes
function setRememberedCheckboxesForDialog2(portletName,portletDivName,startpositionForSubString){
	$("#"+portletDivName+" input:checkbox").each(function(){
		var checkedArray=$.cookie('portlet_'+portletName+'_allSelected')==null?null:$.cookie('portlet_'+portletName+'_allSelected').split(',');
	var matchingValue=$(this).attr('id').substring(startpositionForSubString);
	if(existinCookie(matchingValue,checkedArray) && (!$(this).is(':checked')) ){
			$(this).attr('checked','checked');
	}
	$(this).click(function(){
		if($.cookie('remember-'+portletName+'-cookie')!=null){
			var allSelectedCheckboxes=$("#"+portletDivName+" input:checkbox").serializeArray();
			var storelist=new Array();
			for(var i=0;i<allSelectedCheckboxes.length;i++){
				storelist[i]=allSelectedCheckboxes[i].value;	
			}
			$.cookie('portlet_'+portletName+'_allSelected',storelist, { path: '/', expires: 365 });
		}	
	});
	});
}
//remeber dialogbox checkboxes
function setRememberedCheckboxesForDialog3(portletName,portletDivName,startpositionForSubString){
	$("#"+portletDivName+" input:checkbox").each(function(){
		var checkedArray=$.cookie('portlet_'+portletName+'_allSelected')==null?null:$.cookie('portlet_'+portletName+'_allSelected').split(',');
	var matchingValue=$(this).attr('id').substring(startpositionForSubString);
	if(existinCookie(matchingValue,checkedArray) &&  $(this).attr('checked') !='checked')
		$(this).attr('checked','checked');
	$(this).click(function(){
		if($.cookie('remember-'+portletName+'-cookie')!=null){
			var allSelectedCheckboxes=$("#"+portletDivName+" input:checkbox").serializeArray();
			var storelist=new Array();
			for(var i=0;i<allSelectedCheckboxes.length;i++){
				storelist[i]=allSelectedCheckboxes[i].value;	
			}
			$.cookie('portlet_'+portletName+'_allSelected',storelist, { path: '/', expires: 365 });
		}	
	});
	});
}
	
//remeber dialogbox checkboxes
function setRememberedCheckboxesForDialog4(portletName,portletDivName,startpositionForSubString){
	$("#"+portletDivName+" input:checkbox").each(function(){
		var checkedArray=$.cookie('portlet_'+portletName+'_allSelected')==null?null:$.cookie('portlet_'+portletName+'_allSelected').split(',');
	var matchingValue=$(this).attr('id').substring(startpositionForSubString);
	if(existinCookie(matchingValue,checkedArray) &&  $(this).attr('checked') !='checked')
		$(this).attr('checked','checked');
	$(this).click(function(){
		if($.cookie('remember-'+portletName+'-cookie')!=null){
			var allSelectedCheckboxes=$("#"+portletDivName+" input:checkbox").serializeArray();
			var storelist=new Array();
			for(var i=0;i<allSelectedCheckboxes.length;i++){
				storelist[i]=allSelectedCheckboxes[i].value;	
			}
			$.cookie('portlet_'+portletName+'_allSelected',storelist, { path: '/', expires: 365 });
		}	
	});
	});
}
	
//to remember select box data(with multiple select)
function setRememberedSelectBoxes(portletName,portletDivName){
	var checkedArray=$.cookie('portlet_'+portletName+'_allSelected')==null?null:$.cookie('portlet_'+portletName+'_allSelected').split(',');
		$("#"+portletName).change(function(){
		if($.cookie('remember-'+portletName+'-cookie')!=null){
			$.cookie('portlet_'+portletName+'_allSelected',$('#'+portletName).val(), { path: '/', expires: 365 });
		}	
	});

	$("#"+portletName+" option").each(function(){
	if(existinCookie($(this).attr('value'),checkedArray) && $.cookie('remember-'+portletName+'-cookie')!=null){		
		$(this).attr('selected','selected'); 
	}
	
	}); 
}
//to remember select box data (not multiple select)
function setRememberedComboBox(portletName,fieldName){
	var cookieValue=$.cookie('portlet_'+fieldName+'_allSelected');
	$('#'+fieldName).change(function(){
		if($.cookie('remember-'+portletName+'-cookie')!=null){
			$.cookie('portlet_'+fieldName+'_allSelected',$('#'+fieldName).val(), { path: '/', expires: 365 });
		}	
	});
	if($.cookie('remember-'+portletName+'-cookie')!=null){
		$('#'+fieldName).val(cookieValue);
	}	
}
//to remember text box data(not working properly now)
function setRememberedText(portletName,fieldName){
	var cookieValue=$.cookie('portlet_'+fieldName+'_allSelected');
	$("#"+fieldName).change(function(){
	if($.cookie('remember-'+portletName+'-cookie')!=null){
		$.cookie('portlet_'+fieldName+'_allSelected',$('#'+fieldName).val(), { path: '/', expires: 365 }); 
	}
	});
	if($.cookie('remember-'+portletName+'-cookie')!=null){
		$('#'+fieldName).val($.cookie('portlet_'+fieldName+'_allSelected')); 
	}
}
//ro remember radio button
function setRememberedRadioButtons(portletName,portletDivName){
		var cookieValue=$.cookie('portlet_'+portletName+'_allSelected');
	$("#"+portletDivName+" input:radio").each(function(){
		$(this).click(function(){
			if($.cookie('remember-'+portletName+'-cookie')!=null){
				$.cookie('portlet_'+portletName+'_allSelected',$(this).attr('value'), { path: '/', expires: 365 }); 
			}
		});
		if($.cookie('remember-'+portletName+'-cookie')!=null){
			if( $(this).attr('value')==cookieValue){
				$(this).attr('checked','checked'); 
			}else{
				$(this).attr('checked',false);
			}	
		}
		}
	);
}
// check the exitance of a value in an array(in aleter can cuse $.inArray() of jquery
function existinCookie(value,listArray){
	if(listArray !=null){
		for(var i=0;i<listArray.length;i++){
			if(value==listArray[i]){
				//alert("v:"+value+" L: "+listArray);
				return true;
			}
		}
	}
	return false;
}
// create a remmeber button  
function createRememberButton(portletName,portletHeaderName){
if($.cookie('remember-'+portletName+'-cookie')==null){
	$("#"+portletName+"-remember").detach();
 	$('#'+portletHeaderName).append($('<div class="off" style="float:right" id="'+portletName+'-remember"><span  class="ui-icon ui-icon-unlocked" id="'+portletName+'-remember-span" style="float:right"></span></div>'));
}else{
	$("#"+portletName+"-remember").detach();
 	$('#'+portletHeaderName).append($('<div class="on" style="float:right" id="'+portletName+'-remember"><span id="'+portletName+'-remember-span" class="ui-icon ui-icon-locked" style="float:right"></span></div>'));
}
return true;
}

//handle click event for of remember for checkbox
function handleRememberClickEventForCheckBox(portletName,portletDivName){
	$("#"+portletName+"-remember").click(function(){
		if($('#'+portletName+'-remember').attr('class')=="off"){
			$('#'+portletName+'-remember-span').remove();
			$('#'+portletName+'-remember').append($('<span id="'+portletName+'-remember-span" class="ui-icon ui-icon-locked" style="float:right"></span>'));
			$('#'+portletName+'-remember').removeClass('off').addClass('on');
			var allSelectedCheckboxes=$("#"+portletDivName+" input:checkbox").serializeArray();
			var storelist=new Array();
			for(var i=0;i<allSelectedCheckboxes.length;i++){
				storelist[i]=allSelectedCheckboxes[i].value;	
			}			
			$.cookie('portlet_'+portletName+'_allSelected',storelist, { path: '/', expires: 365 });
$.cookie('remember-'+portletName+'-cookie',true, { path: '/', expires: 365 });
		}else{
			$('#'+portletName+'-remember-span').remove();
			$('#'+portletName+'-remember').append($('<span id="'+portletName+'-remember-span" class="ui-icon ui-icon-unlocked" style="float:right"></span>'));
			$('#'+portletName+'-remember').removeClass('on').addClass('off');
			$.cookie('portlet_'+portletName+'_allSelected',null);
			$.cookie('remember-'+portletName+'-cookie',null);
		}
	});
}
//handle click event for of remember for checkbox and text or any id
function handleRememberClickEventForCheckBoxAndCommon(portletName,portletDivName,fieldArray){
	$("#"+portletName+"-remember").click(function(){
		if($('#'+portletName+'-remember').attr('class')=="off"){
			$('#'+portletName+'-remember-span').remove();
			$('#'+portletName+'-remember').append($('<span id="'+portletName+'-remember-span" class="ui-icon ui-icon-locked" style="float:right"></span>'));
			$('#'+portletName+'-remember').removeClass('off').addClass('on');
			var allSelectedCheckboxes=$("#"+portletDivName+" input:checkbox").serializeArray();
			var storelist=new Array();
			for(var i=0;i<allSelectedCheckboxes.length;i++){
				storelist[i]=allSelectedCheckboxes[i].value;	
			}
			$.cookie('portlet_'+portletName+'_allSelected',storelist, { path: '/', expires: 365 });
			for(var i=0;i<fieldArray.length;i++){
				$.cookie('portlet_'+fieldArray[i]+'_allSelected',$('#'+fieldArray[i]).val());
			}
$.cookie('remember-'+portletName+'-cookie',true, { path: '/', expires: 365 });
		}else{
			$('#'+portletName+'-remember-span').remove();
			$('#'+portletName+'-remember').append($('<span id="'+portletName+'-remember-span" class="ui-icon ui-icon-unlocked" style="float:right"></span>'));
			$('#'+portletName+'-remember').removeClass('on').addClass('off');
			$.cookie('portlet_'+portletName+'_allSelected',null);
			for(var i=0;i<fieldArray.length;i++){
				$.cookie('portlet_'+fieldArray[i]+'_allSelected',null);
			}
			$.cookie('remember-'+portletName+'-cookie',null);
		}
	});
}

//handle click event of remember  for any id
function handleRememberClickEvent(portletName,portletDivName){
	$("#"+portletName+"-remember").click(function(){
		if($('#'+portletName+'-remember').attr('class')=="off"){
         // alert("turning cookie on");
        var str = "";
         $("#"+portletName+" option:selected").each(function () {
             str += $(this).text() + " \n";
             });
            /*if (str == "") {
                alert("Please select items to be remembered prior to locking the portlet");
            } else */
            {
			    $('#'+portletName+'-remember-span').remove();
			    $('#'+portletName+'-remember').append($('<span id="'+portletName+'-remember-span" class="ui-icon ui-icon-locked" style="float:right"></span>'));
			    $('#'+portletName+'-remember').removeClass('off').addClass('on');
			    $.cookie('portlet_'+portletName+'_allSelected',$('#'+portletName).val(), { path: '/', expires: 365 });			
			    $.cookie('remember-'+portletName+'-cookie',true, { path: '/', expires: 365 });
                }
		}else{
			$('#'+portletName+'-remember-span').remove();
			$('#'+portletName+'-remember').append($('<span id="'+portletName+'-remember-span" class="ui-icon ui-icon-unlocked" style="float:right"></span>'));
			$('#'+portletName+'-remember').removeClass('on').addClass('off');
			$.cookie('portlet_'+portletName+'_allSelected',null);
			$.cookie('remember-'+portletName+'-cookie',null);
		}
	});
}

//handle click evernt of remember for a list of id to remember 
function handleRememberClickEventForArray(rememberFields,portletName,portletDivName){
	$("#"+portletName+"-remember").click(function(){
		if($('#'+portletName+'-remember').attr('class')=="off"){
			$('#'+portletName+'-remember-span').remove();
			$('#'+portletName+'-remember').append($('<span id="'+portletName+'-remember-span" class="ui-icon ui-icon-locked" style="float:right"></span>'));
			$('#'+portletName+'-remember').removeClass('off').addClass('on');
			for(var i=0;i<rememberFields.length;i++){
				$.cookie('portlet_'+rememberFields[i]+'_allSelected',$('#'+rememberFields[i]).val(), { path: '/', expires: 365 });
				
			}
$.cookie('remember-'+portletName+'-cookie',true, { path: '/', expires: 365 });
		}else{
			$('#'+portletName+'-remember-span').remove();
			$('#'+portletName+'-remember').append($('<span id="'+portletName+'-remember-span" class="ui-icon ui-icon-unlocked" style="float:right"></span>'));
			$('#'+portletName+'-remember').removeClass('on').addClass('off');
			for(var i=0;i<rememberFields.length;i++){
				$.cookie('portlet_'+rememberFields[i]+'_allSelected',null);
			}
			$.cookie('remember-'+portletName+'-cookie',null);
		}
	});
}
