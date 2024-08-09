<?php
namespace MGB\flexField;


use \REDCap as REDCap;
// Adaptation of method from hooks framework for decoupling
function init_tags($instrument,$Proj) {
//    var_dump($elements);
    // Globals from REDCap application
//    global $elements, $Proj, $project_id;

    // This is an array of found functions as keys and arrays of matching fields as values
    // 'function' => 'parameters'
    static $tag_functions = array();

    // If already initialized, return $tag_functions array
//    print "Is tag function empty?\n";
    if (!empty($tag_functions)) return $tag_functions;
//    print "No - continue\n";
//    var_dump($elements);
    // Scan through instruments rendered by this page searching for @terms
    foreach ($Proj->metadata as $k => $element) {
        if($element['form_name'] == $instrument && strpos($element['misc'],'@HIDDEN') == FALSE){
//            print "Form is : $instrument --- and field is not hidden.\n";
//        }

        // Check if element is visible on this page
        // (alternatively we could take $Proj->forms[this field][fields] and subtract hide_fields...)
//        if (isset($element['field']) && $element['rr_type'] != 'hidden') {

            // Check for hook functions in search (field annotation) field
            $search = $element['misc'];
//            print "<pre>";
//            var_dump($search);
//            print "</pre>";
//            var_dump($element);
//            var_dump($search);
//            $search = $Proj->metadata[$element['field']]['misc'];

            // Use a strpos search initially as it is faster than regex search
            if (strpos($search,'@') !== false) {

                // We have a potential match - lets get all terms (separated by spaces)
                preg_match_all('/@\S+/', $search, $matches);

//                preg_match_all('^@[a-zA-Z]+\([a-zA-Z-0-9\,\s\w]+\)$', $search, $matches);

                if ($matches) {
//                    print "<pre>";
//                    var_dump($matches);
//                    print "</pre>";
                    // We have found matches - let's parse them
                    $matches = reset($matches);
                    foreach ($matches as $match) {
//                        print "<pre>";
//                        var_dump($match);
//                        print "</pre>";
                        // Some terms have a name=params format, if so, break out params (hook_details)
                        list($tag_name,$hook_details) = explode('(',$match);

                        // Remove the last closing-parenthesis from $hooks_details
                        $hook_details = explode(')', $hook_details)[0];

                        // Allow only uppercase alpha characters in base action tag as
                        // simple security check.  Could allow alnum if needed
                        $tag_array = explode('@',$tag_name);
                        $base_tag = $tag_array[1];
                        if (preg_match("/^([[:alpha:]])*$/", $base_tag)) {

                            $tag_functions[$tag_name] = array_merge(
                                isset($tag_functions[$tag_name]) ? $tag_functions[$tag_name] : array(),
                                array($element['field_name'] => array(
                                    'elements_index' => $k,
                                    'params' => $hook_details)
                                )
                            );
                        }
                    }
                }
            }
        }
    }
    return $tag_functions;
}

function getFieldsWithThisActionTag($actionTagName, $instrument, $Proj){
    $allActionTagfields =  init_tags($instrument, $Proj);
    $actionTagField = $allActionTagfields[$actionTagName];
    return $actionTagField;
}

?>