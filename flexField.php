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
     * Use-case: allow saving a dynamic medication list on-the-fly on the same form. Data must be exported separately.
     * Implementation:
     *      All fields must be capable of becoming flex fields - use the JSON FlexField approach
     *      Every flex field must have its corresponding text field for storing the flex field data
     *      Add the @FLEXFIELD action-tag to the text field.
     *      The action-tag must include a limit of allowed elements. Use the following form:
     *          - @FLEXFIELD=(Primary field,other-option-code-value,limit)
     * ------------------------- review the comments below ---------------------
     *      The action-tag must specify the field (name) it corresponds to. For instance:
     *          - for single item flex-field, i.e. list of phone numbers:  @FLEXFIELD=(limit, Primary field)
     *          - for multiple item flex-field, i.e. medication plus dosage: @FLEXFIELD=(limit, Medication, dosage)
     *      The flex field action-tag must be placed on the text field where the flex-field data is to be saved.
     *          All fields listed in the action-tag (primary and detail fields) must be hidden from the UI.
     */

// Issues & Pending Items:
//          1. Done: Field options must be read from the Data Dictionary
//          2. Fixed: Since we're replacing the original field, with the flex-field source element using JQUERY, the code
//              for dynamically adding the next element is not reading the JQUERY-built field.
//              - Unless we find a way for making the code wait for the field to be loaded, the code for each
//                flex field will have to be generated server side.
//              - Instead, don't hide the original field; add to it the new elements using JQUERY.
//          3. Fixed: The comment bubble is being obstructed by the additional flex-field elements
//          4. Done: Store all flex-field values in its corresponding flex-field
//          5. The current code only supports single item Flex-Fields.
//Next steps:
//          1. Done: Adjust the padding of the flex-field source element so it doesn't obstruct the comment bubble
//          2. Done: Improve the size of the + and - buttons
//          3. Done: The + button only needs to be present on the first element
//          4. Done: The minus sign button should remove its element and not the last element
//          5. Done: Field options must be read from the Data Dictionary
//          6. Done: Currently, the action-tag is placed on the dropdown field and not the text field. Change the code so
//              the text field is the Flex-field, and hides the dropdown field (as described
//              in the Implementation section above).
//          6a. Done: Fix the init_tags function in the action-tag common file and parse the configuration value of each flex field.
//          7. Done: Hide the Flex-Field with Jquery
//          8. Done: Ensure there's a limit to amount of maximum items per flex field.
//          9. Build a JQUERY code that builds the JSON array and assigns it to the flex field bucket. For instance:
//              { "flex field Source Name": ["selected option code 1", "selected option code 2", etc.] }
//          10. The code saves flex field values, and deletes values appropriately. The next step is to render
//              the saved values and selections in their respective fields. The solution can be built as part of
//              an if-statement for when the flexfield contains data.
//          11. the id of the added elements are conflicting after adding and deleting them. The resulting saved values are not correct.


    function redcap_data_entry_form_top($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance)
    {
        global $Proj;

        print '<div class="yellow">Special announcement text to display at the very bottom
            of every data entry form.</div>';
        $flexFields = getFieldsWithThisActionTag("@FLEXFIELD", $instrument, $Proj);
        $item = 0;
        foreach($flexFields as $fieldName=>$properties){
            print "<pre>";
//            var_dump($flexFields);
//            print "</pre>";

            $props = explode(',',$properties['params']);
            var_dump($props);
            $flexFieldSource = $props[0];
            $otherOptionCodeValue = $props[1];
            $limitCount = $props[2];

            print "This is REDCap::getData\n";
            print "record before:";
            var_dump($record);
            $record = is_null($record) ? $_GET['id'] : $record;
            print "record after:";
            var_dump($record);
            $data = REDCap::getData('json', $record, array($fieldName));

            var_dump(json_decode($data,TRUE));
            $retrievedData = json_decode($data,TRUE)[0]['race_flex_field'];
            var_dump($retrievedData);
            print "</pre>";

            $ffData = json_decode($data,TRUE);
            if(count($ffData)>0){ // Data exists and must be rendered
                print "<pre>";
                print "inside first if";
                print "</pre>";

                print $this->flexFieldTriggerCondition($flexFieldSource,$otherOptionCodeValue,$fieldName,$retrievedData,$limitCount);
//                print $this->buildExistingTextFields($retrievedData,$fieldName);



                foreach($ffData as $k=>$v){
                    $savedValues = explode(",", $v[$properties["elements_index"]]);
                    $flexOptionsBlank = $this->buildDropDownOptions($this->dropDownOptions($Proj, $flexFieldSource),"");
                    $i = 1;
                    foreach($savedValues as $kk=>$vv){
                        print "<pre>";
                        var_dump($savedValues);
                        var_dump($vv);
                        var_dump(count($savedValues));

                        $flexOptions[count($savedValues)-$i++] = $this->buildDropDownOptions($this->dropDownOptions($Proj, $flexFieldSource),$vv);
                        var_dump($i);
                        print "</pre>";
                        $item++;
                    }
                    print "<pre>";
//                    var_dump(count($ffData));
//                    var_dump($flexOptions);
//                    var_dump(ksort($flexOptions,SORT_NUMERIC ));
//                    ksort($flexOptions, SORT_NATURAL);
//                    var_dump(array_reverse($flexOptions,FALSE));
//                    $flexOptions = ksort($flexOptions,SORT_NUMERIC );
                    print "</pre>";
                    foreach(array_reverse($flexOptions) as $kkk=>$vvv){
                        $kkk++;
                        print "<pre>";
                        print "this is the index: $kkk and this is the value: $vvv \n";
                        print "</pre>";


//                        print $this->flexField2($flexFieldSource,$vvv,$item++,$fieldName,$limitCount,$kkk,$flexOptionsBlank,count($flexOptions));

                    }
                }
//                $flexOptions2 = $this->buildDropDownOptions($this->dropDownOptions($Proj, $flexFieldSource),"");
//                print $this->flexField3($flexFieldSource,$flexOptions2,$item++,$fieldName,$limitCount);

            } else{ // Data does not exist and empty Flex Field must be rendered.
                // The functionality must be triggered when the answer-choice of "Other" is selected.
                // Add the client-side code to the "Other" option to add the first flex field.
                print "<pre>";
                print "inside first else";
                print "</pre>";

                print $this->flexFieldTriggerCondition($flexFieldSource,$otherOptionCodeValue,$fieldName,$retrievedData,$limitCount);
//                $flexOptions = $this->buildDropDownOptions($this->dropDownOptions($Proj, $flexFieldSource),"");
                $item++;
//                print $this->flexField($flexFieldSource,$flexOptions,$item++,$fieldName,$limitCount);
            }
        }
    }

    function flexField($fieldName, $flexOptions, $item, $flexFieldBucket,$limitCount){

        // Note: the element type can be easily changed to input (i.e. text field) or select (i.e. dropdown field)
        // on row 44.

        // Script: hide the Primary field and add the flex field elements
        $script = <<<SCRIPT
<script type="text/javascript">

 $(document).ready(function() {  
     function getValues01(){
	    var flexString = "";
	    var objects = $(".flexfield");
	    var objCount = 0; 
	    for (var obj of objects) {	     
	        if(objCount == 0){
	            flexString = $("[name=" + obj['name'] + "]").val();
	            objCount++;
	        } else {
	            flexString = flexString + ", " + $("[name=" + obj['name'] + "]").val();	            
	        }
        }

	    $('[name={$flexFieldBucket}]').val(flexString);
	}
     function formatFlexFieldData() {
         // create a JSON string with the values of every flex field item.
         console.log("test my function");
     }
     
	$('select[name={$fieldName}]').hide().after("<div id=\"test1\"> <select role=\"listbox\" aria-labelledby=\"label-select_project\" class=\"x-form-text x-form-field flexfield\" 	name=\"flexfield{$item}\" tabindex=\"0\">	<option value=\"\"> </option>" +	
	"{$flexOptions} " + 
    "</select> <button type=\"button\" class=\"btn btn-info btn-sm\" id=\"addButton\" style=\"padding: 1px 1px 1px 1px;\"><i class=\"fas fa-plus-circle\"></i></button> </div>")

    
     $('.flexfield').blur(function () {
         getValues01();
            console.log("Blur on field: " + $(this).prop('name'));
    
//         if($(this).val() == ""){
//             
//             $('[name={$flexFieldBucket}]').val($(this).val());
//         } else {
//             $('[name={$flexFieldBucket}]').val($('[name={$flexFieldBucket}]').val() + ", " + $(this).val());             
//         }
        
        formatFlexFieldData();
    });
	
	}
	)
            </script>
SCRIPT;
        print $script;

        // JS script with the multiple magic - it's the code that repeats the field
        $script2 = <<<SCRIPT
<script type="text/javascript">

 $(document).ready(function() {     	
	var counter = 2;
	var limit = $limitCount;	
	
	function getValues02(){
	    var flexString = "";
	    var objects = $(".flexfield");
	    var objCount = 0;        
	    for (var obj of objects) {
	        
	        if(objCount == 0){
	            flexString = $("[name=" + obj['name'] + "]").val();
	            objCount++;
	        } else {
	            flexString = flexString + ", " + $("[name=" + obj['name'] + "]").val();	            
	        }
        }
	    $('[name={$flexFieldBucket}]').val(flexString);
	}
	
   $('#test1').on('click', '#addButton',function( e ) {
            
       if(counter <= limit){
       // e.preventDefault();
            var id = e.target.id;
            var container_id = 'test1';
            // console.log('test123: ' + id);
            // console.log(e.target);
            // console.log(e.target.closest('select'));
            // console.log(e.target.closest('div.container').id);

            var newTextBoxDiv = $(document.createElement('div'))
         .attr("class", "row pt-2" ,"id", container_id + 'Div' + counter);
           
          var element = "<div id=\"test1\" style=\"padding-left: 15px\"> <select role=\"listbox\" aria-labelledby=\"label-select_project\" class=\"x-form-text x-form-field flexfield\" 	name=\"flexfield" + counter + "\" tabindex=\"0\">	<option value=\"\"> </option>" +	 
    "{$flexOptions} </select> </div>";  
            
           // var element = "<div id=\"test1\" style=\"padding-left: 15px\"> <select role=\"listbox\" aria-labelledby=\"label-select_project\" class=\"x-form-text x-form-field   \" 	name=\"flexfield\" tabindex=\"0\">	<option value=\"\"> </option>" +	 
    // "</select> <button type=\"button\" class=\"btn btn-info btn-sm\" id=\"addButton\" style=\"padding: 2px 2px 2px 2px;\"><i class=\"fas fa-plus-circle\"></i></button> </div>";
           
           
           //  var element = e.target.closest('div.row');
           // var element = element.outerHTML.split('selected')[0] + element.outerHTML.split('selected')[1];
           // var element = element.split('<button type="button" class="btn btn-info btn-sm" id="addButton">+</button>')[0];
           // var element = element.split('<div class="row">')[1];
           // var element = element.split('</div>')[0] + "</div>" + element.split('</div>')[1] + "</div>" + element.split('</div>')[2] + "</div>"; 
    newTextBoxDiv.after().html( element +
          '<button type=\"button\" class=\"btn btn-outline-info btn-sm\" id=\"substractButton\" style=\"padding: 1px 1px 1px 1px;margin-left: 5px;\"><i class="fas fa-minus-circle"></i></button>');
            
          
    newTextBoxDiv.last().appendTo("#" + container_id);
    
    // attached the on.blur script to the newly created element
        $('[name=flexfield'+ counter + ']').blur(function () {
            getValues02();        
    });    
    
    counter++;   

    }
        });

        $(document).on('click', '#substractButton', function( e ) {
            // e.preventDefault();
            $(this).closest('div.row').remove();
             getValues02();
            counter--;
        });
	
	}
	)
            </script>
SCRIPT;
        print $script2;

        $script3 = <<<SCRIPT
<script type="text/javascript">

 $(document).ready(function() {     	
	
   $('#$flexFieldBucket-tr').hide()
	
	}
	)
            </script>
SCRIPT;
        // Uncomment the next line for hiding the flex-field.
        //        print $script3;
    }

    function buildDropDownOptions($dropdownOptions,$existing_value){
        foreach($dropdownOptions as $k=>$v){
            $jsDAGOptions .= "<option value=\\\"$k\\\"";
            // if the value saved is the same as the option's value, then set it to selected
            if($k == $existing_value){
                $jsDAGOptions .= " selected = \\\"\\\">";
            } else {
                $jsDAGOptions .= ">";
            }
            $jsDAGOptions .= "$v</option>";
        }
        return $jsDAGOptions;
    }

//          // test script for adding new element:
//          $('#first_name-tr').addClass( "myClass yourClass" );
//            $('.myClass input').val()
//            var plusButton = "<button type=\"button\" class=\"btn btn-info btn-sm\" id=\"addButton\" style=\"padding: 2px 2px 2px 2px;\"><i class=\"fas fa-plus-circle\"></i></button>";
//            $('.myClass input').after(plusButton)
////          var newElement = "<tr id=\"first_name-tr\" sq_id=\"first_name\"><td class=\"labelrc col-7\"><label class=\"fl\" id=\"label-first_name\" aria-hidden=\"true\"><table class=\"form-label-table\" role=\"presentation\" cellspacing=\"0\" cellpadding=\"0\"><tbody><tr><td>&emsp;5. First Name</td><td style=\"width:40px;padding-left:5px;text-align:right;\" class=\"rc-field-icons invisible_in_print\"><img src=\"/redcap/redcap_v11.1.26/Resources/images/history.png\" style=\"margin-bottom:1px;visibility:hidden;\" alt=\"\"><br>	<a href=\"javascript:;\" tabindex=\"-1\" onclick=\"dataResPopup('first_name',65289,null,null,null,1);return false;\"><img id=\"dc-icon-first_name\" src=\"/redcap/redcap_v11.1.26/Resources/images/balloon_left_bw2.gif\" title=\"View comment log\" onmouseover=\"dc1(this)\" onmouseout=\"dc2(this)\" alt=\"\"></a>		</td> </tr></tbody></table></label></td><td class=\"data col-5\">	<input autocomplete=\"new-password\" aria-labelledby=\"label-first_name\" class=\"x-form-text x-form-field \" type=\"text\" name=\"first_name\" value=\"\" tabindex=\"0\"> <div id=\"first_name_MDLabel\" class=\"MDLabel\" style=\"display:none\" code=\"\" label=\"\"></div></td></tr>"
//          $('#first_name-tr').after(newElement)

/***
 * Jquery code for getting a field's option values:
 *          var values = $.map(options ,function(option) {
                                return option.value;
                                });
 * Jquery code for getting a field's option labels:
 *          var labels = $.map(options ,function(option) {
                                return option.text;
                                });
 */

    function dropDownOptions($Proj,$fieldName){
        $codedOptions = explode(" \\n ",$Proj->metadata[$fieldName]['element_enum']);
        foreach($codedOptions as $k=>$v){
            $codeAndValue = explode(", ",$v);
            $dropdownOptions[$codeAndValue[0]] = $codeAndValue[1];
        }
        return $dropdownOptions;
    }

    function flexField2($fieldName, $flexOptions, $item, $flexFieldBucket,$limitCount,$currentItem,$flexOptionsBlank,$totalAnswers){

        // Note: the element type can be easily changed to input (i.e. text field) or select (i.e. dropdown field)
        // on row 44.

        // Script: hide the Primary field and add the flex field elements
        $elementCounter = $totalAnswers + 1;
        if($currentItem == $totalAnswers){

            $script = <<<SCRIPT
<script type="text/javascript">
console.log("first item");
 $(document).ready(function() {  
     function getValues01(){
	    var flexString = "";
	    var objects = $(".flexfield");
	    var objCount = 0; 
	    for (var obj of objects) {	     
	        if(objCount == 0){
	            flexString = $("[name=" + obj['name'] + "]").val();
	            objCount++;
	        } else {
	            flexString = flexString + ", " + $("[name=" + obj['name'] + "]").val();	            
	        }
        }

	    $('[name={$flexFieldBucket}]').val(flexString);
	}
     // function formatFlexFieldData() {
     //     // create a JSON string with the values of every flex field item.
     //     console.log("test my function");
     // }
     
	$('select[name={$fieldName}]').hide().after("<div id=\"test1\" class=\"ff\"> <select role=\"listbox\" aria-labelledby=\"label-select_project\" class=\"x-form-text x-form-field flexfield\" 	name=\"flexfield{$item}\" tabindex=\"0\">	<option value=\"\"> </option>" +	
	"{$flexOptions} " + 
    "</select> <button type=\"button\" class=\"btn btn-info btn-sm\" id=\"addButton\" style=\"padding: 1px 1px 1px 1px;\"><i class=\"fas fa-plus-circle\"></i></button> </div>")

    
     $('.flexfield').blur(function () {
         getValues01();
            // console.log("Blur on field: " + $(this).prop('name'));
        
        // formatFlexFieldData();
    });    
    
     //
     var counter = $elementCounter;
	var limit = $limitCount;	
	
	function getValues02(){
	    var flexString = "";
	    var objects = $(".flexfield");
	    var objCount = 0;        
	    for (var obj of objects) {
	        
	        if(objCount == 0){
	            flexString = $("[name=" + obj['name'] + "]").val();
	            objCount++;
	        } else {
	            flexString = flexString + ", " + $("[name=" + obj['name'] + "]").val();	            
	        }
        }
	    $('[name={$flexFieldBucket}]').val(flexString);
	}
	
   $('#test1').on('click', '#addButton',function( e ) {
            
       if(counter <= limit){
       // e.preventDefault();
            var id = e.target.id;
            var container_id = 'test1';
            // console.log('test123: ' + id);
            // console.log(e.target);
            // console.log(e.target.closest('select'));
            // console.log(e.target.closest('div.container').id);

            var newTextBoxDiv = $(document.createElement('div'))
         .attr("class", "row pt-2" ,"id", container_id + 'Div' + counter);
           
          var element = "<div id=\"test" + counter + "\" style=\"padding-left: 15px\"> <select role=\"listbox\" aria-labelledby=\"label-select_project\" class=\"x-form-text x-form-field flexfield\" 	name=\"flexfield" + counter + "\" tabindex=\"0\">	<option value=\"\"> </option>" +	 
    "{$flexOptionsBlank} </select> </div>";            
            
    newTextBoxDiv.after().html( element +
          '<button type=\"button\" class=\"btn btn-outline-info btn-sm\" id=\"substractButton\" style=\"padding: 1px 1px 1px 1px;margin-left: 5px;\"><i class="fas fa-minus-circle"></i></button>');
    
    // var lastElement = $(".ff").last();
    var addTo = $('.ff').last().prop("id");
    newTextBoxDiv.last().appendTo("#" + addTo);
    
    // newTextBoxDiv.last().appendTo("#" + container_id);
    
    // attached the on.blur script to the newly created element
        $('[name=flexfield'+ counter + ']').blur(function () {
            getValues02();        
    });    
    
    counter++;   

    }
        });

        $(document).on('click', '#substractButton', function( e ) {
            // e.preventDefault();
            $(this).closest('div').remove();
             getValues02();
            counter--;
        });
     //
     
	})
            </script>
SCRIPT;
            print $script;
        } else {
            $itemTemp = $item - 1;
            $minusScript = <<<SCRIPT
<script type="text/javascript">
console.log("not first item");
$(document).ready(function() {      
    $('select[name={$fieldName}]').after("<div id=\"test{$item}\" class =\"ff\"> <select role=\"listbox\" aria-labelledby=\"label-select_project\" class=\"x-form-text x-form-field flexfield\" 	name=\"flexfield{$item}\" tabindex=\"0\">	<option value=\"\"> </option>" +	
	"{$flexOptions} " + 
    "</select> <button type=\"button\" class=\"btn btn-outline-info btn-sm\" id=\"substractButton\" style=\"padding: 1px 1px 1px 1px;\"><i class=\"fas fa-minus-circle\"></i></button> </div>");
    });
</script>
SCRIPT;
print $minusScript;
        }



        // JS script with the multiple magic - it's the code that repeats the field
        $script2 = <<<SCRIPT
<script type="text/javascript">

 $(document).ready(function() {     	
	var counter = 2;
	var limit = $limitCount;	
	
	function getValues02(){
	    var flexString = "";
	    var objects = $(".flexfield");
	    var objCount = 0;        
	    for (var obj of objects) {
	        
	        if(objCount == 0){
	            flexString = $("[name=" + obj['name'] + "]").val();
	            objCount++;
	        } else {
	            flexString = flexString + ", " + $("[name=" + obj['name'] + "]").val();	            
	        }
        }
	    $('[name={$flexFieldBucket}]').val(flexString);
	}
	
   $('#test1').on('click', '#addButton',function( e ) {
            
       if(counter <= limit){
       // e.preventDefault();
            var id = e.target.id;
            var container_id = 'test1';
            // console.log('test123: ' + id);
            // console.log(e.target);
            // console.log(e.target.closest('select'));
            // console.log(e.target.closest('div.container').id);

            var newTextBoxDiv = $(document.createElement('div'))
         .attr("class", "row pt-2" ,"id", container_id + 'Div' + counter);
           
          var element = "<div id=\"test1\" style=\"padding-left: 15px\"> <select role=\"listbox\" aria-labelledby=\"label-select_project\" class=\"x-form-text x-form-field flexfield\" 	name=\"flexfield" + counter + "\" tabindex=\"0\">	<option value=\"\"> </option>" +	 
    "{$flexOptions} </select> </div>";            
            
    newTextBoxDiv.after().html( element +
          '<button type=\"button\" class=\"btn btn-outline-info btn-sm\" id=\"substractButton\" style=\"padding: 1px 1px 1px 1px;margin-left: 5px;\"><i class="fas fa-minus-circle"></i></button>');
            
    newTextBoxDiv.last().appendTo("#" + container_id);
    
    // attached the on.blur script to the newly created element
        $('[name=flexfield'+ counter + ']').blur(function () {
            getValues02();        
    });    
    
    counter++;   

    }
        });

        $(document).on('click', '#substractButton', function( e ) {
            // e.preventDefault();
            $(this).closest('div.row').remove();
             getValues02();
            counter--;
        });
	
	}
	)
            </script>
SCRIPT;
//        print $script2;

        $script3 = <<<SCRIPT
<script type="text/javascript">

 $(document).ready(function() {     	
	
   $('#$flexFieldBucket-tr').hide()
	
	}
	)
            </script>
SCRIPT;
        // Uncomment the next line for hiding the flex-field.
        //        print $script3;
    }


//    Bugs:
//          1) an additional flex field is added everytime the condition is met.
    function flexFieldTriggerCondition($fieldName,$expectedValue,$flexFieldName,$existingValues,$limitCount){
        $existingValues = explode(",",$existingValues);
        $currentCount = count($existingValues) + 1;
        $existingValues = array_reverse($existingValues);
        $existingValues = "'" . implode("','",$existingValues) . "'";
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
                // flexString = $("[name=" + obj['name'] + "]").val();
	            objCount++;
	        } else {
	            flexString = flexString + ", " + obj['value'];
                // flexString = flexString + ", " + $("[name=" + obj['name'] + "]").val();	            
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
                  $('input[name={$flexFieldName}]').after("<div class=\"row ffInstance\"> <div id=\"test1\"> <input aria-labelledby=\"label-select_project\" class=\"x-form-text x-form-field flexfield\" type=\"text\"	name=\"flexfield1\" tabindex=\"0\">" +
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
            // console.log('test123: ' + id);
            // console.log(e.target);
            // console.log(e.target.closest('select'));
            // console.log(e.target.closest('div.container').id);

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
              // $("#substractButton").remove();
              // $("#addButton").remove();

               // $(".flexfield").remove();
               // $("#test1").remove();
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
                console.log("Blur on field: " + $(this).prop('name'));
    });

    // Now prebuild fields for existing values
    
   function myFunction(value, index, array) {
        console.log(value + " this is index: " + index);
        var index = array.length - index;
        if(index == 1){
            $('input[name={$flexFieldName}]').after("<div class=\"row ffInstance\"> <div id=\"test1\"> <input aria-labelledby=\"label-select_project\" class=\"x-form-text x-form-field flexfield\" type=\"text\"	name=\"flexfield1\" tabindex=\"0\" value=\"" + value.trim() + "\" >" +
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
            // console.log('test123: ' + id);
            // console.log(e.target);
            // console.log(e.target.closest('select'));
            // console.log(e.target.closest('div.container').id);

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
             console.log("inside else -  this is counter: " + counter);
             
             var container_id = 'test1';
              var newTextBoxDiv = $(document.createElement('div'))
         .attr("class", "row pt-2 ffInstance" ,"id", container_id + 'Div' + counter);
             
             $('input[name={$flexFieldName}]').after("<div class=\"row pt-2 ffInstance\"><div id=\"test1\"> <input aria-labelledby=\"label-select_project\" class=\"x-form-text x-form-field flexfield\" type=\"text\"	name=\"flexfield" + index + "\" tabindex=\"0\" value=\"" + value.trim() + "\" >" +
    " <button type=\"button\" class=\"btn btn-outline-info btn-sm\" id=\"substractButton\" style=\"padding: 1px 1px 1px 1px;margin-left: 5px;\"><i class=\"fas fa-minus-circle\"></i></button> </div> </div>")
             // newTextBoxDiv.last().appendTo("#" + container_id);
             
             // attached the on.blur script to the newly created element
        $('[name=flexfield'+ index + ']').blur(function () {
            getValues02();        
    }); 
             
        }
    }
    
    let bucketFlexField = [$existingValues];
    if(bucketFlexField.length > 0 && bucketFlexField[0].length > 0){
        bucketFlexField.forEach(myFunction);
        console.log("Existing Values are being retrieved - bucket size: " + bucketFlexField.length);
        console.log(bucketFlexField[0].length)
    }
 })
            </script>
SCRIPT;
        return $scriptJS01;
    }

    function buildExistingTextFields($existingValues,$flexFieldName){
        $existingValues = explode(",",$existingValues);
        $existingValues = array_reverse($existingValues);
        $existingValues = "'" . implode("','",$existingValues) . "'";
        $scriptJS02 = <<<SCRIPT
<script type="text/javascript">    
    console.log("here123: 2");
    
 $(document).ready(function() {
    let bucketFlexField = [$existingValues];
    console.log(bucketFlexField);
    
    function myFunction(value, index, array) {
        console.log(value + " this is index: " + index);
        var index = array.length - index;
         var container_id = 'test1';
         var newTextBoxDiv = $(document.createElement('div')).attr("class", "row pt-2" ,"id", container_id + 'Div' + index);  
         var element = "<div id=\"test1\" style=\"padding-left: 15px\"> <input aria-labelledby=\"label-select_project\" class=\"x-form-text x-form-field flexfield\" type=\"text\"	name=\"flexfield" + index + "\" tabindex=\"0\" value=\"" + value.trim() + "\">";            
         var addButton = " <button type=\"button\" class=\"btn btn-info btn-sm\" id=\"addButton\" style=\"padding: 1px 1px 1px 1px;\"><i class=\"fas fa-plus-circle\"></i></button>";   
    
         if(index == 1){
             $('input[name={$flexFieldName}]').after(element + addButton);
         } else {
            $('input[name={$flexFieldName}]').after(element +
            '<button type=\"button\" class=\"btn btn-outline-info btn-sm\" id=\"substractButton\" style=\"padding: 1px 1px 1px 1px;margin-left: 5px;\"><i class="fas fa-minus-circle"></i></button>');
         }   
    newTextBoxDiv.last().appendTo("#" + container_id);
    
        // attached the on.blur script to the newly created element
        $('[name=flexfield'+ index + ']').blur(function () {
            console.log('here');
    }); 
        
    }    
    
    bucketFlexField.forEach(myFunction);


 }
 )
 </script>
SCRIPT;
    return $scriptJS02;
    }
}

