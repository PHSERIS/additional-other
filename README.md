 # MGB Ad hoc Field - Additional Other - Oracle Flex Field

 The module provides means for adding ad hoc fields in case the user must include multiple answers for the same question. The functionality is an homage to Oracle's Flex Field functionality. Currently, the ad hoc field is limited to collect data when an option of 'Other' has been selected in a multiple choice field (dropdown field).

***

## How To Use

It is a common practice to have a dropdown field containing an option for "Other" for allowing the participant to manually enter an answer, in a text box field, if the prebuilt answers do not apply to them. 
It is this the setup needed for using an ad hoc Field, i.e. if the user is expected to provide more than one answer to "other",  adding the addhoc action tag transforms the "other text field" in a field that can be repeated and added on-demand.
Here's the expected syntax needed for adding an ad hoc action tag:

```php
@ADHOCFIELD(source-field,code-value,max-instances)
```
where:
 1. 'source-field' is the multiple choice field name that contains an option for 'other' (or its equivalent), 
 1. 'code-value' is the code given to the field's option of 'other' (can be a string or numeric), and 
 1. 'max-instances' is the limit of additional entries the user is allowed to use.

## Where Does the Data Go?

The data entered in every instance of the ad hoc field is saved, in a delimited format, in the text field in which the ad hoc action tag was added.
In this way, the data is saved can be retrieved through a standard REDCap Report.

***

## More Information Can Be Found on:

- [ ] [GitHub](https://github.com/PHSERIS/mgb-additional-other) 
- [ ] [MGB GitLab](https://gitlab-scm.partners.org/redcap_edc/mgb-flex-field)
- [ ] [Rerefence](https://docs.oracle.com/cd/A60725_05/html/comnls/us/fnd/10gch5.htm)

## Authors and acknowledgment
Ed Morales (Mass General Brigham)

## Project status
Project is currently on Production and waiting for enhancements.

***

## Downloading it from GitHub
Please note that if you download this module from GitHub you must:
- download the latest release
- the release is a zip file that includes the folder of the module
- you must rename this folder so it conforms to the External Module Framework expectations. You must replace the dashes with underscores, and prepend the version number with a "v". Here's an example of how this looks like:
- Downloaded from GitHub: mgb-additional-other-2.3.1
- Rename it to: mgb_additional_other_v2.3.1
- Finally, place the renamed folder in your REDCap's modules folder. Keep in mind that it is best to enable modules in a test environment first.