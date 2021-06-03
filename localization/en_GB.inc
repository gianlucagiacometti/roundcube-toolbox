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
$messages['aliases-noaliases'] = 'No aliases found';
$messages['aliases-novalidalias'] = 'Alias name empty';


/* Forward */
/* Author: Gianluca Giacometti */

$labels['forward'] = 'Forward addresses';
$labels['forward-manage'] = 'Manage forward addresses';
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
$labels['vacation-manage'] = 'Manage out of office message';
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


/* Purge */
/* Author: Gianluca Giacometti */

$labels['purge'] = 'Purge';
$labels['purge-manage'] = 'Manage purge options';
$labels['purge-domainsettings'] = 'Domain settings';
$labels['purge-trash'] = 'Keep messages in Rubbish folder for';
$labels['purge-junk'] = 'Keep messages in Spam folder for';
$labels['purge-domainvalue'] = 'default domain value';
$labels['purge-always'] = 'ever';
$labels['purge-day'] = 'day';
$labels['purge-days'] = 'days';


/* Preview */
/* Author:  */

$labels['preview'] = 'Message preview';
$labels['preview-manage'] = 'Configure message preview';
$labels['preview-disable-message'] = 'Disable message preview';
$labels['preview-markasread-doubleclick'] = 'Mark a message read also by double clicking with mouse';


/* Customise */
/* Author: Gianluca Giacometti */

$labels['customise'] = 'Customise';
$labels['customise-manage'] = 'Customise layout for domain';
$labels['customise-purge'] = 'Purge';
$labels['customise-purge-trash'] = 'Keep messages in Rubbish folder for';
$labels['customise-purge-junk'] = 'Keep messages in Spam folder for';
$labels['customise-blankpage'] = 'Customised blank page';
$labels['customise-blankpage-skin'] = 'Blank page';
$labels['customise-blankpage-skin-default'] = 'Default Roundcube blank page';
$labels['customise-blankpage-skin-image'] = 'Blank page with just a centered image';
$labels['customise-blankpage-skin-url'] = 'Local or external page url';
$labels['customise-blankpage-skin-custom'] = 'Custom-made page';
$labels['customise-blankpage-image'] = 'Image';
$labels['customise-blankpage-url'] = 'Address';
$labels['customise-additional-css'] = 'Customised CSS';
$labels['customise-logo'] = 'Customised logo';
$labels['customise-logo-template'] = 'Template';
$labels['customise-logo-template-new'] = 'New customised logo';
$labels['customise-logo-template-all'] = 'All templates';
$labels['customise-logo-type'] = 'Type';
$labels['customise-logo-type-all'] = 'All types';
$labels['customise-logo-type-favicon'] = 'Favicon';
$labels['customise-logo-type-print'] = 'Print';
$labels['customise-logo-type-small'] = 'Small';
$labels['customise-logo-type-dark'] = 'Logo in dark mode';
$labels['customise-logo-type-small-dark'] = 'Small in dark mode';
$labels['customise-logo-add-template'] = 'Add template';
$labels['customise-logo-image'] = 'Image';

$messages['customise-logo-customisedlogoexists'] = 'Customised logo already exists';
$messages['customise-logo-deletecustomisedlogo'] = 'Do you really want to delete this customised logo?';
$messages['customise-logo-deleteallcustomisedlogos'] = 'Do you really want to delete all customised logos for this skin?';
$messages['customise-logo-emptycustomisedlogotemplate'] = 'The logo template cannot be empty';
$messages['customise-logo-emptycustomisedlogoimage'] = 'The logo image cannot be empty';
$messages['customise-logo-invalidcustomisedlogoimage'] = 'The logo image is invalid';
$messages['customise-logo-nocustomisedlogo'] = 'No customised logo found';


/* Detach Attachments */
/* Author: Gianluca Giacometti */

$labels['attachment-expiry-date'] = 'Download expires on';
$labels['attachment-maxuploadsize'] = 'NOTE: If one attachment is greater than $singlesize MB or the entire set of attachments is grater than $totalsize MB the recipient will receive a link to a zip file, which will expire in $lifespan days';


/* Tags */
/* Author: Gianluca Giacometti */

$labels['tags'] = 'Tags';
$labels['tags-label'] = 'Etichetta';
$labels['tags-labels'] = 'Etichette';


/* Cloud */
/* Author: Gianluca Giacometti */

$labels['cloud'] = 'Cloud';
$labels['cloud-googledrive'] = 'Google Drive';
$labels['cloud-dropbox'] = 'Dropbox';
$labels['cloud-onedrive'] = 'Microsoft OneDrive';
$labels['cloud-insertlink'] = 'Insert a link to a file';
$labels['cloud-insertfile'] = 'Attach a file';


/* Safe login */
/* Author: Gianluca Giacometti */

$labels['safelogin'] = 'Safe login';
$labels['safelogin-continent'] = 'Continent';
$labels['safelogin-countryisocode'] = 'Country Code';
$labels['safelogin-countryname'] = 'Country';
$labels['safelogin-cityname'] = 'City';
$labels['safelogin-location'] = 'Location';
$labels['safelogin-lastlogin'] = 'Last login';
$labels['safelogin-history'] = 'Access history';
$labels['safelogin-browser'] = 'Browser';
$labels['safelogin-os'] = 'Operating system';


/* Folders */
/* Author: Gianluca Giacometti */

$labels['folders'] = 'Riepilogo cartelle';
$labels['folders-numberoffiles'] = 'Numero file';
