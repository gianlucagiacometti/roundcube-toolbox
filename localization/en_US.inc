<?php


$labels = array();
$messages = array();


/* TOOLBOX */
/* Author: Gianluca Giacometti */

$labels['toolbox'] = 'Toolbox';
$labels['toolbox-description'] = 'Set of tools to improve the webmail experience';
$labels['toolbox-usersettings'] = 'User settings';
$labels['toolbox-arialabeltoolform'] = 'Tool settings form';
$labels['toolbox-addresses'] = 'Addresses';
$labels['toolbox-deleteall'] = 'Delete all';
$labels['toolbox-enabled'] = 'Enabled';
$labels['toolbox-disabled'] = 'Disabled';

$messages['toolbox-driverconnecterror'] = 'Driver connection error';
$messages['toolbox-driverprocesserror'] = 'Driver execution error';
$messages['toolbox-driverdefaulterror'] = 'Unknown driver execution error';
$messages['toolbox-unknowntool'] = 'Unknown tool';
$messages['toolbox-internalerror'] = 'Unknown execution error';
$messages['toolbox-datasuccessfullysaved'] = 'Data successfully saved';
$messages['toolbox-datasaveerror'] = 'Error saving data';


/* Aliases */
/* Author: Gianluca Giacometti */

$labels['aliases'] = 'Aliases';
$labels['aliases-manage'] = 'Manage aliases';
$labels['aliases-aliasname'] = 'Alias';
$labels['aliases-addaddress'] = 'Add alias';

$messages['aliases-aliasexists'] = 'Alias already exists';
$messages['aliases-aliasexistsindomain'] = 'Alias already exists in domain';
$messages['aliases-aliasdeleteconfirm'] = 'Are you sure you want to delete this alias?';
$messages['aliases-aliasdeleted'] = 'Alias deleted successfully';
$messages['aliases-aliasdeletederror'] = 'Error while deleting alias';
$messages['aliases-aliascreated'] = 'Alias created successfully';
$messages['aliases-aliascreatederror'] = 'Error while creating alias';
$messages['aliases-aliasupdated'] = 'Alias updated successfully';
$messages['aliases-aliasupdatederror'] = 'Error while updating alias';
$messages['aliases-aliasnameerror'] = 'Alias contains characters not allowed';
$messages['aliases-noalias'] = 'No aliases found';
$messages['aliases-novalidalias'] = 'Alias name empty';


/* Forward */
/* Author: Gianluca Giacometti */

$labels['forward'] = 'Forward addresses';
$labels['forward-address'] = 'Forward address';
$labels['forward-addaddress'] = 'Add forward address';
$labels['forward-keepcopies'] = 'Keep copies of original messages';

$messages['forward-invalidaddress'] = 'E-mail address not valid';
$messages['forward-atleastoneaddress'] = 'At least one forward address is required if no copy is kept in mailbox';
$messages['forward-emptyaddress'] = 'The forward address cannot be empty';
$messages['forward-deleteaddress'] = 'Do you really want to delete this address?';
$messages['forward-deletealladdresses'] = 'Delete all addresses';
$messages['forward-addressexists'] = 'This address already exists';
$messages['forward-noaddress'] = 'No address found';


/* Vacation */
/* Author: Gianluca Giacometti */

$labels['vacation'] = 'Out of office';
$labels['vacation-subject'] = 'Subject:';
$labels['vacation-body'] = 'Message:';
$labels['vacation-active'] = 'Active';
$labels['vacation-activefrom'] = 'Active from:';
$labels['vacation-activeuntil'] = 'Active until:';
$labels['vacation-intervaltime'] = 'Reply type:';
$labels['vacation-replyonce'] = 'Reply once to each address';
$labels['vacation-replytoeverymail'] = 'Reply to every message';
$labels['vacation-replyonceperday'] = 'Reply once per day to each address';
$labels['vacation-replyonceperweek'] = 'Reply once per week to each address';
$labels['vacation-htmleditor'] = 'Activate HTML editor';

$messages['vacation-invalidstartdate'] = 'Please select a valid start date.';
$messages['vacation-invalidenddate'] = 'Please select a valid end date.';
$messages['vacation-invaliddateinterval'] = 'The date interval is not valid.';
$messages['vacation-nosubject'] = 'Please fill the subject of your out-of-office.';
$messages['vacation-nomessage'] = 'Please fill the message of your out-of-office.';
$messages['vacation-driverdefaulterror'] = 'Unknown execution error';
$messages['vacation-driverconnecterror'] = 'Error during connection';
$messages['vacation-driverprocesserror'] = 'Error during processing';


/* Purge */
/* Author: Gianluca Giacometti */

$labels['purge'] = 'Purge';
$labels['purge-domainsettings'] = 'Domain settings';
$labels['purge-trash'] = 'Keep messages in Trash folder for';
$labels['purge-junk'] = 'Keep messages in Junk folder for';
$labels['purge-domainvalue'] = 'default domain value';
$labels['purge-always'] = 'ever';
$labels['purge-day'] = 'day';
$labels['purge-days'] = 'days';


/* Customise */
/* Author: Gianluica Giacometti */

$labels['customise'] = 'Customisz';
$labels['customise-manage'] = 'Customize layout for domain';
$labels['customise-purge'] = 'Purge';
$labels['customise-purge-trash'] = 'Keep messages in Junk folder for';
$labels['customise-purge-junk'] = 'Keep messages in Spam folder for';
$labels['customise-blankpage'] = 'Customized blank page';
$labels['customise-blankpage-skin'] = 'Blank page';
$labels['customise-blankpage-skin-default'] = 'Default Roundcube blank page';
$labels['customise-blankpage-skin-image'] = 'Blank page with just a centered image';
$labels['customise-blankpage-skin-url'] = 'Local or external page url';
$labels['customise-blankpage-skin-custom'] = 'Custom-made page';
$labels['customise-blankpage-image'] = 'Image';
$labels['customise-blankpage-url'] = 'Address';
$labels['customise-additional-css'] = 'Customized CSS';
