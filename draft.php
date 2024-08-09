<?php

/***
 * Script for concatenating values into a text field
 */
function ExternalUserRequestUIChanges($instrument,$record,$event_id,$project_list){
//        if(PAGE == 'DataEntry/index.php' & $_GET["page"] == 'external_user_request'){
    if($instrument == 'external_user_request') {

        foreach ($project_list as $k => $v) {
            if ($k != 0) {
                $jsProjectList .= ",";
            }
            $jsProjectList .= "\"";
            $jsProjectList .= $v;
            $jsProjectList .= "\"";
        }


        $script = <<<SCRIPT
<script type="text/javascript">

 $(document).ready(function() {
     $('select[name=select_project] option').each(function()	
	{ 
		var project_list = ["", {$jsProjectList}]
		if($(this).text() == "" || project_list.find(element => element == $(this).text()))
		{
		} else {
			$(this).remove()
		}
	}
	)	
});
            </script>
SCRIPT;

        print $script;
    }
}

/***
 * Flex-field model - automatically add new field instances and save their values
 * on a pairing text field in JSON format so that one single Flex-Field can
 * have detail information.
 */

function flexField($fieldName, $flexOptions,$item){
    $script = <<<SCRIPT
<script type="text/javascript">

 $(document).ready(function() {     	
	$('input[name={$fieldName}]').hide().after("<select role=\"listbox\" aria-labelledby=\"label-select_project\" class=\"x-form-text x-form-field   \" 	name=\"flexfield{$item}\" tabindex=\"0\">	<option value=\"\"> </option>" +	
	"{$flexOptions} " + 
    "</select>")

    $('[name=flexfield{$item}]').blur(function () {
        $('[name={$fieldName}]').val($('[name=flexfield{$item}]').val())       
    });
	}
	)
            </script>
SCRIPT;
    print $script;
}

/***
 * Function used for building dropdown options for a flex-field
 */

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

/***
 * Popup for adding an option to a dropdown field on-the-fly
 */
//function popupDialog($extProjectID){
//        $URI = explode("/DataEntry/", $_SERVER['REQUEST_URI'])[0];
//        $script = <<<SCRIPT
//<script type="text/javascript">
//
// $(document).ready(function() {
//	$('#em42123').draggable();
//
//     $('[name=project_dag_em]').on('change', function(){
//    if( $('[name=project_dag_em]').val() == 99999 ){
//       $('#em42123').css('visibility', 'visible')
//    }
//    })
//    $('.closePopUp123').on('click', function(){
//       $('#em42123').css('visibility', 'hidden')
//    })
//
//    $('#new_group_button').click( function() {
//            $('#extDAG').prop("disabled", true);
//            $('#new_group_button').prop("disabled", true);
//            $('#spiner01').css("display", "inline-block");
//            $('#spiner02').css("display", "inline-block");
//
//            $.ajax({type: "POST",
//                data: {projectID : {$extProjectID},
//                       dagName : $('#extDAG').val()
//                    }
//                ,
//                url:'/..{$URI}/ExternalModules/?prefix=add_external_user&page=addDag',
//                success: function (response){
//                    $('#new_group_button').prop("enable", true);
//                    $('#spiner01').css('visibility', 'hidden');
//                    $('#success').css("display", "inline-block");
//                    var mydata= $.parseJSON(response);
//                    var dagid = Object.keys(mydata);
//                    var dagLabel = Object.values(mydata);
//                    $('[name=project_dag_em] option:nth-last-child(2)').after("<option value=" + dagid + ">" + dagLabel + "</option>")
//
//                }, error: function(XMLHttpRequest, textStatus, errorThrown) {
//                    alert("some error:" + XMLHttpRequest + textStatus + errorThrown);
//                    location.reload();
//                }
//            });
//
//        } );
//
//}
//)
//            </script>
//SCRIPT;
//
//        return $script;
//    }

?>