<?php


$labels = array();
$messages = array();


/* TOOLBOX */
/* Author: Gianluca Giacometti */

$labels['toolbox'] = 'Herramientas';
$labels['toolbox-description'] = 'Conjunto de herramientas para mejorar la experiencia de correo web';
$labels['toolbox-usersettings'] = 'Configuración de usuario';
$labels['toolbox-arialabeltoolform'] = 'Configuración de herramientas';
$labels['toolbox-addresses'] = 'Direcciones';
$labels['toolbox-deleteall'] = 'Borrar todo';
$labels['toolbox-enabled'] = 'Habilitado';
$labels['toolbox-disabled'] = 'Deshabilitado';

$messages['toolbox-driverconnecterror'] = 'Error de conexión del controlador';
$messages['toolbox-driverprocesserror'] = 'Error de ejecución del controlador';
$messages['toolbox-driverdefaulterror'] = 'Error de ejecución del controlador desconocido';
$messages['toolbox-unknowntool'] = 'Herramienta desconocida';
$messages['toolbox-internalerror'] = 'Error de ejecución desconocido';
$messages['toolbox-datasuccessfullysaved'] = 'Datos guardados con éxito';
$messages['toolbox-datasaveerror'] = 'Error al guardar datos';


/* Aliases */
/* Author: Gianluca Giacometti */

$labels['aliases'] = 'Alias';
$labels['aliases-manage'] = 'Administrar alias';
$labels['aliases-aliasname'] = 'Alias';
$labels['aliases-addaddress'] = 'Añadir alias';

$messages['aliases-aliasexists'] = 'Alias ya existe';
$messages['aliases-aliasexistsindomain'] = 'Alias ya existe en el dominio';
$messages['aliases-aliasdeleteconfirm'] = '¿Estás seguro de que quieres eliminar este alias?';
$messages['aliases-aliasdeleted'] = 'Alias eliminado con éxito';
$messages['aliases-aliasdeletederror'] = 'Error al eliminar el alias';
$messages['aliases-aliascreated'] = 'Alias creado con éxito';
$messages['aliases-aliascreatederror'] = 'Error al crear alias';
$messages['aliases-aliasupdated'] = 'Alias actualizado con éxito';
$messages['aliases-aliasupdatederror'] = 'Error al actualizar el alias';
$messages['aliases-aliasnameerror'] = 'El alias contiene caracteres no permitidos';
$messages['aliases-noaliases'] = 'No se encontraron alias';
$messages['aliases-novalidalias'] = 'Nombre de alias vacío';


/* Forward */
/* Author: Gianluca Giacometti */

$labels['forward'] = 'Direcciones de reenvío';
$labels['forward-manage'] = 'Gestionar direcciones de reenvío';
$labels['forward-address'] = 'Dirección de reenvío';
$labels['forward-addaddress'] = 'Agregar dirección de reenvío';
$labels['forward-keepcopies'] = 'Guardar copias de los mensajes originales';

$messages['forward-invalidaddress'] = 'Dirección de correo electrónico no válida';
$messages['forward-atleastoneaddress'] = 'Se requiere al menos una dirección de reenvío si no se guarda ninguna copia en el buzón';
$messages['forward-emptyaddress'] = 'La dirección de reenvío no puede estar vacía';
$messages['forward-deleteaddress'] = '¿Realmente quieres eliminar esta dirección?';
$messages['forward-deletealladdresses'] = 'Borrar todas las direcciones';
$messages['forward-addressexists'] = 'Esta dirección ya existe';
$messages['forward-noaddress'] = 'No se encontró la dirección';


/* Vacation */
/* Author: Gianluca Giacometti */

$labels['vacation'] = 'Fuera de la oficina';
$labels['vacation-manage'] = 'Gestionar mensaje de fuera de la oficina';
$labels['vacation-subject'] = 'Asunto:';
$labels['vacation-body'] = 'Mensaje:';
$labels['vacation-active'] = 'Activo';
$labels['vacation-activefrom'] = 'Activo desde:';
$labels['vacation-activeuntil'] = 'Activo hasta:';
$labels['vacation-intervaltime'] = 'Tipo de respuesta:';
$labels['vacation-replyonce'] = 'Responder una vez a cada dirección';
$labels['vacation-replytoeverymail'] = 'Responder a cada mensaje';
$labels['vacation-replyonceperday'] = 'Responder una vez al día a cada dirección';
$labels['vacation-replyonceperweek'] = 'Responder una vez por semana a cada dirección';
$labels['vacation-htmleditor'] = 'Activar editor HTML';

$messages['vacation-invalidstartdate'] = 'Selecciona una fecha de inicio válida.';
$messages['vacation-invalidenddate'] = 'Selecciona una fecha de finalización válida.';
$messages['vacation-invaliddateinterval'] = 'El intervalo de fechas no es válido.';
$messages['vacation-nosubject'] = 'Por favor completa el asunto de tu ausencia.';
$messages['vacation-nomessage'] = 'Por favor completa el mensaje de tu ausencia.';
$messages['vacation-driverdefaulterror'] = 'Error de ejecución desconocido';
$messages['vacation-driverconnecterror'] = 'Error durante la conexión';
$messages['vacation-driverprocesserror'] = 'Error durante el procesamiento';


/* Purge */
/* Author: Gianluca Giacometti */

$labels['purge'] = 'Purgar';
$labels['purge-manage'] = 'Administrar opciones de purga';
$labels['purge-domainsettings'] = 'Configuración de dominio';
$labels['purge-trash'] = 'Mantenga los mensajes en la carpeta Papelera durante';
$labels['purge-junk'] = 'Mantenga los mensajes en la carpeta Basura durante';
$labels['purge-domainvalue'] = 'default domain value';
$labels['purge-always'] = 'siempre';
$labels['purge-day'] = 'días';
$labels['purge-days'] = 'días';


/* Preview */
/* Author: Gianluca Giacometti */

$labels['preview'] = 'Vista previa del mensaje';
$labels['preview-manage'] = 'Configurar vista previa del mensaje';
$labels['preview-disable-message'] = 'Deshabilitar vista previa de mensajes';
$labels['preview-markasread-doubleclick'] = 'Marcar un mensaje leído también haciendo doble clic con el ratón';


/* Customise */
/* Author: Gianluica Giacometti */

$labels['customise'] = 'Personalizar';
$labels['customise-manage'] = 'Personalizar diseño para dominio';
$labels['customise-purge'] = 'Purgar';
$labels['customise-purge-trash'] = 'Mantener los mensajes en la carpeta de Basura durante';
$labels['customise-purge-junk'] = 'Mantener los mensajes en la carpeta de correo no deseado durante';
$labels['customise-blankpage'] = 'Página en blanco personalizada';
$labels['customise-blankpage-skin'] = 'Página en blanco';
$labels['customise-blankpage-skin-default'] = 'Página en blanco predeterminada de Roundcube';
$labels['customise-blankpage-skin-image'] = 'Página en blanco con solo una imagen centrada';
$labels['customise-blankpage-skin-url'] = 'URL de la página local o externa';
$labels['customise-blankpage-skin-custom'] = 'Página a medida';
$labels['customise-blankpage-image'] = 'Imágen';
$labels['customise-blankpage-url'] = 'Dirección';
$labels['customise-additional-css'] = 'CSS personalizado';
$labels['customise-logo'] = 'Logotipo personalizado';
$labels['customise-logo-template'] = 'Plantilla';
$labels['customise-logo-template-new'] = 'Nuevo logotipo personalizado';
$labels['customise-logo-template-all'] = 'Todas las plantillas';
$labels['customise-logo-type'] = 'Tipo';
$labels['customise-logo-type-all'] = 'Todos los tipos';
$labels['customise-logo-type-favicon'] = 'Favicon';
$labels['customise-logo-type-print'] = 'Imprimir';
$labels['customise-logo-type-small'] = 'Pequeño';
$labels['customise-logo-type-dark'] = 'Logo en modo oscuro';
$labels['customise-logo-type-small-dark'] = 'Pequeño en modo oscuro';
$labels['customise-logo-add-template'] = 'Añadir plantilla';
$labels['customise-logo-image'] = 'Imágen';

$messages['customise-logo-customisedlogoexists'] = 'El logotipo personalizado ya existe';
$messages['customise-logo-deletecustomisedlogo'] = '¿Realmente quieres eliminar este logotipo personalizado?';
$messages['customise-logo-deleteallcustomisedlogos'] = '¿Realmente quieres eliminar todos los logotipos personalizados?';
$messages['customise-logo-emptycustomisedlogotemplate'] = 'La plantilla de logotipo no puede estar vacía';
$messages['customise-logo-emptycustomisedlogoimage'] = 'La imagen del logotipo no puede estar vacía';
$messages['customise-logo-invalidcustomisedlogoimage'] = 'La imagen del logotipo no es válida';
$messages['customise-logo-nocustomisedlogo'] = 'No se encontró ningún logotipo personalizado';


/* Detach Attachments */
/* Author: Gianluca Giacometti */

$labels['attachment-expiry-date'] = 'La descarga caduca el';
$labels['attachment-maxuploadsize'] = 'NOTA: Si un archivo adjunto tiene más de $singlesize MB o el conjunto completo de archivos adjuntos tiene más de $totalsize MB, el destinatario recibirá un enlace a un archivo zip, que caducará en $lifespan días';


/* Tags */
/* Author: Gianluca Giacometti */

$labels['tags'] = 'Etiquetas';
$labels['tags-label'] = 'Etiqueta';
$labels['tags-labels'] = 'Etiquetas';


/* Cloud */
/* Author: Gianluca Giacometti */

$labels['cloud'] = 'Cloud';
$labels['cloud-googledrive'] = 'Google Drive';
$labels['cloud-dropbox'] = 'Dropbox';
$labels['cloud-onedrive'] = 'Microsoft OneDrive';
$labels['cloud-insertlink'] = 'Insertar un enlace a un archivo';
$labels['cloud-insertfile'] = 'Adjuntar un archivo';


/* Safe login */
/* Author: Gianluca Giacometti */

$labels['safelogin'] = 'Inicio de sesión seguro';
$labels['safelogin-continent'] = 'Continente';
$labels['safelogin-countryisocode'] = 'Código de País';
$labels['safelogin-countryname'] = 'País';
$labels['safelogin-cityname'] = 'Ciudad';
$labels['safelogin-location'] = 'Ubicación';
$labels['safelogin-lastlogin'] = 'Ultimo inicio de sesión';
$labels['safelogin-history'] = 'Historial de acceso';
$labels['safelogin-browser'] = 'Navegador';
$labels['safelogin-os'] = 'Sistema Operativo';


/* Folders */
/* Author: Gianluca Giacometti */

$labels['folders'] = 'Resumen de carpeta';
$labels['folders-numberoffiles'] = 'Número de archivos';
