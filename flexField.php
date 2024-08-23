<?php
namespace MGB\flexField;

include "common/actionTagHelper.php";
use \REDCap as REDCap;
use \RedCapDB as RedCapDB;
use \DataAccessGroups as DataAccessGroups;
use \Files as Files;
use \Authentication as Authentication;
use \Message as Message;
use \UserRights as UserRights;
use \PhpOffice\PhpSpreadsheet\IOFactory as IOFactory;


class flexField extends \ExternalModules\AbstractExternalModule
{
    /***
     * Goal: implement an oracle flex field, i.e. a text field that can store structured data, i.e. comma delimited or JSON.
     * Use-case: allow saving a dynamic medication list on-the-fly on the same form.
     * Implementation:
     *      The Flex Field itself must be limited to Text Fields being used to store 'other' answers in drop down fields.
     *      The reason for this is to have means of managing where and when these flex fields are used and for avoiding users
     *      duplicating fields without a good reason.
     * How-to:
     *      1. Use a dropdown field and give it an option of 'Other'
     *      2. Add a text field for storing the answers for 'Other'; this field is usually given branching logic.
     *      3. It is this text field that must be given an action tag that will enable the flex field functionality in it.
     *      4. The action tag includes a limit of number of elements that are allowed. The action tag for is the following:
     *          - @FLEXFIELD=(Primary field,other-option-code-value,limit)
     */

    function __construct()
    {
        parent::__construct();

        // ADD SOME CONTEXT TO THE GLOBALS FOR THIS MODULE:
        $this->modulesActionTagName = "@ADHOCFIELD";
    }
    function redcap_data_entry_form_top($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance)
    {
        global $Proj;

        $flexFields = getFieldsWithThisActionTag($this->modulesActionTagName, $instrument, $Proj);
        $item = 0;
        foreach($flexFields as $fieldName=>$properties){

            $props = explode(',',$properties['params']);
            $flexFieldSource = $props[0];
            $otherOptionCodeValue = $props[1];
            $limitCount = $props[2];

            $record = is_null($record) ? $_GET['id'] : $record; // Surveys don't include a record id
            $ffData = '';
            if(is_null($record)){
                $retrievedData = '';
            } else {
                $data = REDCap::getData('json', $record, array($fieldName));
                $retrievedData = json_decode($data,TRUE)[0][$fieldName];
                $ffData = json_decode($data,TRUE);
            }

            if(count($ffData)>0){ // Data exists and must be rendered

                print $this->flexFieldTriggerCondition($flexFieldSource,$otherOptionCodeValue,$fieldName,$retrievedData,$limitCount);

            } else{ // Data does not exist and empty Flex Field must be rendered.
                // The functionality must be triggered when the answer-choice of "Other" is selected.
                // Add the client-side code to the "Other" option to add the first flex field.

                print $this->flexFieldTriggerCondition($flexFieldSource,$otherOptionCodeValue,$fieldName,$retrievedData,$limitCount);

            }
        }
    }


//    Bugs:
//          1) an additional flex field is added everytime the condition is met.
    function flexFieldTriggerCondition($fieldName,$expectedValue,$flexFieldName,$existingValues,$limitCount){
        if(is_null($existingValues)){
            $existingValues = '';
            $currentCount = 2;
        } else {
            $existingValues = explode("|",$existingValues);
            $currentCount = count($existingValues) + 1;
            $existingValues = array_reverse($existingValues);
            $existingValues = "'" . implode("','",$existingValues) . "'";
        }

        $scriptJS01 = <<<SCRIPT
<script type="text/javascript">
<!--here 123-->
 $(document).ready(function() {   
     function getValues02(){
	    var flexString = "";
	    var objects = $(".flexfield");
	    var objCount = 0;        
	    for (var obj of objects) {
	        
	        if(objCount == 0){
	            flexString = obj['value'];
	            objCount++;
	        } else {
	            flexString = flexString + "| " + obj['value'];	            
	        }
        }
	    $('[name={$flexFieldName}]').val(flexString);
	}
     // I think the rendering code, for existing values, goes here
     var counter = $currentCount;
     var limit = $limitCount;
          $("select[name={$fieldName}]").blur(function () {
              console.log("Blur on field: " + $(this).prop('name') + "this is its value's length: " + $(this).val().length);
              if($(this).val() == $expectedValue){
                  console.log('condition met; expected value was selected');
                  // Add the .hide() to the next line when ready to deploy
                  // i.e., $('input[name={flexFieldName}]').after(
                  $('input[name={$flexFieldName}]').hide().after("<div class=\"row ffInstance\"> <div id=\"test1\"> <input aria-labelledby=\"label-select_project\" class=\"x-form-text x-form-field flexfield\" type=\"text\"	name=\"flexfield1\" tabindex=\"0\">" +
    " <button type=\"button\" class=\"btn btn-info btn-sm\" id=\"addButton\" style=\"padding: 1px 1px 1px 1px;\"><i class=\"fas fa-plus-circle\"></i></button> </div></div>")
                  // attached the on.blur script to the newly created element
                    $('[name=flexfield1]').blur(function () {
                        getValues02();        
                    }); 
    
                     $('#test1').on('click', '#addButton',function( e ) {
                         console.log("here123");
                         if(counter <= limit){
       // e.preventDefault();
            var id = e.target.id;
            var container_id = 'test1';
            var newTextBoxDiv = $(document.createElement('div'))
         .attr("class", "row pt-2 ffInstance" ,"id", container_id + 'Div' + counter);
           
          var element = "<div id=\"test1\" style=\"padding-left: 15px\"> <input aria-labelledby=\"label-select_project\" class=\"x-form-text x-form-field flexfield\" type=\"text\"	name=\"flexfield" + counter + "\" tabindex=\"0\">";            
            
    newTextBoxDiv.after().html( element +
          '<button type=\"button\" class=\"btn btn-outline-info btn-sm\" id=\"substractButton\" style=\"padding: 1px 1px 1px 1px;margin-left: 5px;\"><i class="fas fa-minus-circle"></i></button>');
            
    newTextBoxDiv.last().appendTo("#" + container_id);
    
        // attached the on.blur script to the newly created element
        $('[name=flexfield'+ counter + ']').blur(function () {
            getValues02();        
    }); 
    
    counter++;   

    }
                     })
    
              } if($(this).val() != $expectedValue){
              // Consider adding an else-statement so that whenever the source field is not the expected value
              // any existing field using the flexfields class are not only removed, but their content is erased.
              $(".flexfield").closest('div.row').remove();
              counter = 2; // reset counter
              console.log("inside source field not expected value!!!")
              }
    });
     
	$(document).on('click', '#substractButton', function( e ) {
            // e.preventDefault();
            $(this).closest('div.row').remove();
            getValues02();           
            counter--;
        });
	
$('.flexfield').blur(function () {
                getValues02();
    });

    // Now prebuild fields for existing values
    
   function myFunction(value, index, array) {
        
        var index = array.length - index;
        if(index == 1){
            $('input[name={$flexFieldName}]').hide().after("<div class=\"row ffInstance\"> <div id=\"test1\"> <input aria-labelledby=\"label-select_project\" class=\"x-form-text x-form-field flexfield\" type=\"text\"	name=\"flexfield1\" tabindex=\"0\" value=\"" + value.trim() + "\" >" +
    " <button type=\"button\" class=\"btn btn-info btn-sm\" id=\"addButton\" style=\"padding: 1px 1px 1px 1px;\"><i class=\"fas fa-plus-circle\"></i></button> </div></div>")
                  // attached the on.blur script to the newly created element
                    $('[name=flexfield1]').blur(function () {
                        getValues02();        
                    });
            
            $('#test1').on('click', '#addButton',function( e ) {
        
                         if(counter <= limit){
       // e.preventDefault();
            var id = e.target.id;
            var container_id = 'test1';
            var newTextBoxDiv = $(document.createElement('div'))
         .attr("class", "row pt-2 ffInstance" ,"id", container_id + 'Div' + counter);
           
          var element = "<div id=\"test1\" style=\"padding-left: 15px\"> <input aria-labelledby=\"label-select_project\" class=\"x-form-text x-form-field flexfield\" type=\"text\"	name=\"flexfield" + counter + "\" tabindex=\"0\">";            
            
    newTextBoxDiv.after().html( element +
          '<button type=\"button\" class=\"btn btn-outline-info btn-sm\" id=\"substractButton\" style=\"padding: 1px 1px 1px 1px;margin-left: 5px;\"><i class="fas fa-minus-circle"></i></button>');
            
    newTextBoxDiv.last().appendTo("#" + container_id);
    
        // attached the on.blur script to the newly created element
        $('[name=flexfield'+ counter + ']').blur(function () {
            getValues02();        
    }); 
    
    counter++;   

    }
                     })
            
        } else if (index > 1) {
            // now prebuild the additional entries, using a minus button             
             var container_id = 'test1';
              var newTextBoxDiv = $(document.createElement('div'))
         .attr("class", "row pt-2 ffInstance" ,"id", container_id + 'Div' + counter);
             
             $('input[name={$flexFieldName}]').after("<div class=\"row pt-2 ffInstance\"><div id=\"test1\"> <input aria-labelledby=\"label-select_project\" class=\"x-form-text x-form-field flexfield\" type=\"text\"	name=\"flexfield" + index + "\" tabindex=\"0\" value=\"" + value.trim() + "\" >" +
    " <button type=\"button\" class=\"btn btn-outline-info btn-sm\" id=\"substractButton\" style=\"padding: 1px 1px 1px 1px;margin-left: 5px;\"><i class=\"fas fa-minus-circle\"></i></button> </div> </div>")
             
             
             // attached the on.blur script to the newly created element
        $('[name=flexfield'+ index + ']').blur(function () {
            getValues02();        
    }); 
             
        }
    }
    
    let bucketFlexField = [$existingValues];
    if(bucketFlexField.length > 0 && bucketFlexField[0].length > 0){
        bucketFlexField.forEach(myFunction);
    }
 })
            </script>
SCRIPT;
        return $scriptJS01;
    }

    function redcap_survey_page_top($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance)
    {
        global $Proj;

        $flexFields = getFieldsWithThisActionTag($this->modulesActionTagName, $instrument, $Proj);

        foreach($flexFields as $fieldName=>$properties){

            $props = explode(',',$properties['params']);
            $flexFieldSource = $props[0];
            $otherOptionCodeValue = $props[1];
            $limitCount = $props[2];

            if(is_null($record)){
                $retrievedData = NULL;
            } else {
                $data = REDCap::getData('json', $record, array($fieldName));
                $retrievedData = json_decode($data,TRUE)[0][$fieldName];
            }

            print $this->flexFieldTriggerCondition($flexFieldSource,$otherOptionCodeValue,$fieldName,$retrievedData,$limitCount);

        }
    }

}

