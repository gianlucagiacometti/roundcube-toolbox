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

$labels['tags-title'] = 'Etiquetas personales';
$labels['tags-empty'] = 'Todavía no hay etiquetas. Seleccione Nueva etiqueta para crear una.';
$labels['tags-newtag'] = 'Nueva etiqueta';
$labels['tags-showinlist'] = 'Mostrar etiquetas en la lista de mensajes';
$labels['tags-listhelp'] = 'Los mensajes etiquetados ocupan una línea adicional en la lista.';
$labels['tags-emptyvalues'] = 'No hay valores. Seleccione Nuevo valor para añadir uno.';
$labels['tags-newvalue'] = 'Nuevo valor';
$labels['tags-editname'] = 'Editar nombre';
$labels['tags-deletename'] = 'Eliminar nombre de etiqueta';
$labels['tags-editvalue'] = 'Editar valor';
$labels['tags-deletevalue'] = 'Eliminar valor';
$labels['tags-name'] = 'Nombre de etiqueta';
$labels['tags-value'] = 'Valor';
$labels['tags-color'] = 'Color';
$labels['tags-choosecolor'] = 'Elegir color de etiqueta';
$labels['tags-save'] = 'Guardar';
$labels['tags-cancel'] = 'Cancelar';
$labels['tags-newname'] = 'Nuevo nombre de etiqueta';
$labels['tags-editnametitle'] = 'Editar nombre de etiqueta';
$labels['tags-parentprefix'] = 'Etiqueta: ';
$labels['tags-confirmdeletegroup'] = '¿Eliminar permanentemente «{name}» y todos sus valores?';
$labels['tags-confirmdeletevalue'] = '¿Eliminar permanentemente «{value}» de «{name}»?';
$labels['tags-deleteirreversible'] = 'Esta acción no se puede deshacer.';
$labels['tags-unavailable'] = 'Las etiquetas no están disponibles.';
$labels['tags-noneassigned'] = 'No hay etiquetas asignadas';
$labels['tags-listprefix'] = 'Etiquetas: ';
$labels['tags-edit'] = 'Editar etiquetas';
$labels['tags-editorfailed'] = 'No se pudo cargar el editor de etiquetas. Compruebe la sesión y vuelva a cargar el editor.';
$labels['tags-defssaving'] = 'Guardando las definiciones de etiquetas…';
$labels['tags-definitions'] = 'Definiciones de etiquetas';
$labels['tags-defsloading'] = 'Cargando las definiciones de etiquetas…';
$labels['tags-opendefs'] = 'Abra Definiciones de etiquetas para crear nombres y valores.';
$labels['tags-loading'] = 'Cargando etiquetas…';
$labels['tags-saving'] = 'Guardando etiqueta…';
$labels['tags-saveunconfirmed'] = 'No se pudo confirmar el cambio. Vuelva a cargar las etiquetas antes de intentarlo de nuevo.';
$labels['tags-loadfailed'] = 'No se pudieron cargar las etiquetas. Inténtelo de nuevo.';
$labels['tags-updated'] = 'Etiquetas actualizadas.';
$labels['tags-updateunconfirmed'] = 'No se pudo confirmar el cambio de etiqueta.';
$labels['tags-reloadhint'] = 'Vuelva a cargar las etiquetas antes de intentarlo de nuevo.';
$labels['tags-selectone'] = 'Seleccione un solo mensaje para editar sus etiquetas.';
$labels['tags-savepending'] = 'Todavía se está guardando un cambio de etiquetas de este mensaje. Espere.';
$labels['tags-options'] = 'Opciones de etiquetas';
$labels['tags-message'] = 'Etiquetas del mensaje';
$labels['tags-reloadeditor'] = 'Recargar editor';
$labels['tags-reload'] = 'Recargar etiquetas';
$labels['tags-defssavingclose'] = 'Guardando las definiciones de etiquetas… El cuadro de diálogo se cerrará al terminar.';

/* Tags backend errors */
/* Localised only at the user-facing response boundary. */
$messages['tags-error-invalidrequest'] = 'Solicitud de etiqueta no válida';
$messages['tags-error-missingmessage'] = 'Este mensaje no está disponible en esta carpeta';
$messages['tags-error-missingtag'] = 'Esta definición de etiqueta ya no existe';
$messages['tags-error-tagsnotallowed'] = 'Esta carpeta no permite etiquetas personalizadas';
$messages['tags-error-updateunconfirmed'] = 'El servidor de correo no ha confirmado el cambio de etiqueta';
$messages['tags-error-verifyfailed'] = 'No se ha podido verificar la etiqueta actualizada del mensaje';
$messages['tags-error-invalidoperation'] = 'Operación de etiquetas no válida';
$messages['tags-error-unknownoperation'] = 'Operación de etiquetas desconocida';
$messages['tags-error-listsavefailed'] = 'No se ha podido guardar la opción de visualización en la lista de mensajes';
$messages['tags-error-invalidlistoption'] = 'Opción de visualización en la lista de mensajes no válida';
$messages['tags-error-invalidformvalue'] = 'Valor del formulario de etiquetas no válido';
$messages['tags-error-toomanynames'] = 'Se ha alcanzado el número máximo de nombres de etiquetas';
$messages['tags-error-unknownname'] = 'Nombre de etiqueta desconocido';
$messages['tags-error-unknowntag'] = 'Definición de etiqueta personal desconocida';
$messages['tags-error-toomanyvalues'] = 'Se ha alcanzado el número máximo de definiciones de etiquetas';
$messages['tags-error-restorefirst'] = 'Restaura la etiqueta antes de editarla';
$messages['tags-error-invalidstore'] = 'Almacén de definiciones de etiquetas no válido';
$messages['tags-error-corrupteddefinition'] = 'Definición de etiqueta personal dañada';
$messages['tags-error-corruptedtext'] = 'Texto de etiqueta personal dañado';
$messages['tags-error-duplicategroupid'] = 'Identificador de grupo de etiquetas duplicado';
$messages['tags-error-corruptedgroups'] = 'Grupos de etiquetas dañados';
$messages['tags-error-corruptedgroup'] = 'Grupo de etiquetas dañado';
$messages['tags-error-corruptedgroupname'] = 'Nombre de grupo de etiquetas dañado';
$messages['tags-error-duplicategroupname'] = 'Nombres de grupos de etiquetas duplicados';
$messages['tags-error-noparent'] = 'Un valor de etiqueta no tiene un grupo principal válido';
$messages['tags-error-definitionssavefailed'] = 'No se han podido guardar las definiciones de etiquetas personales';
$messages['tags-error-inactive'] = 'El nombre de la etiqueta no está activo';
$messages['tags-error-nameexists'] = 'Este nombre de etiqueta ya existe';
$messages['tags-error-valueexists'] = 'Este valor de etiqueta ya existe';
$messages['tags-error-invalidid'] = 'Identificador de etiqueta no válido';
$messages['tags-error-invalidname'] = 'El nombre de la etiqueta debe contener texto válido y no vacío';
$messages['tags-error-invalidvalue'] = 'El valor de la etiqueta debe contener texto válido y no vacío';
$messages['tags-error-invalidcolor'] = 'Color de etiqueta no válido';
$messages['tags-error-unexpected'] = 'No se ha podido completar la operación de etiquetas';

/* Cloud */
/* Author: Gianluca Giacometti */

$labels['cloud'] = 'Cloud';
$labels['cloud-googledrive'] = 'Google Drive';
$labels['cloud-dropbox'] = 'Dropbox';
$labels['cloud-onedrive'] = 'Microsoft OneDrive';
$labels['cloud-insertlink'] = 'Insertar un enlace a un archivo';
$labels['cloud-insertfile'] = 'Adjuntar un archivo';

/* Cloud Settings (PHP view) */
$labels['cloud-ui-generate-recovery'] = 'Generar una clave de recuperación';
$labels['cloud-ui-create-title'] = 'Crear tu bóveda personal de Cloud';
$labels['cloud-ui-create-explanation'] = 'Elige una contraseña para la bóveda (al menos 12 caracteres), una clave de recuperación generada o ambas. La clave solo se muestra una vez.';
$labels['cloud-ui-new-password-label'] = 'Contraseña de la bóveda (opcional si se elige una clave de recuperación)';
$labels['cloud-ui-confirm-password-label'] = 'Confirmar la contraseña de la bóveda';
$labels['cloud-ui-create-vault'] = 'Crear bóveda';
$labels['cloud-ui-locked'] = 'Bloqueada';
$labels['cloud-ui-unlock-connections'] = 'Desbloquea la bóveda para gestionar las conexiones.';
$labels['cloud-ui-reset-timer'] = 'Reiniciar temporizador';
$labels['cloud-ui-lock-now'] = 'Bloquear ahora';
$labels['cloud-ui-vault-password'] = 'Contraseña de la bóveda';
$labels['cloud-ui-recovery-key'] = 'Clave de recuperación';
$labels['cloud-ui-set-change-password'] = 'Establecer o cambiar la contraseña';
$labels['cloud-ui-generate-new-recovery'] = 'Generar una nueva clave de recuperación';
$labels['cloud-ui-vault-security'] = 'Seguridad de la bóveda';
$labels['cloud-ui-change-security-help'] = 'Cambiar contraseña o clave de recuperación';
$labels['cloud-ui-new-vault-password'] = 'Nueva contraseña de la bóveda';
$labels['cloud-ui-confirm-new-password'] = 'Confirmar la nueva contraseña de la bóveda';
$labels['cloud-ui-rotate-warning'] = 'Generar una nueva clave de recuperación invalida permanentemente la anterior.';
$labels['cloud-ui-personal-vault'] = 'Bóveda personal';
$labels['cloud-ui-unlock-info'] = 'Desbloquea la bóveda para gestionar las conexiones Cloud. El servidor vuelve a bloquearla tras 5 minutos sin actividad de Cloud o 15 minutos desde el último desbloqueo o reinicio manual del temporizador. Mientras esté desbloqueada, pulsa Reiniciar temporizador para renovar ambos límites, o Bloquear ahora para bloquearla de inmediato. La clave desbloqueada se guarda temporalmente en tu sesión autenticada del servidor.';
$labels['cloud-ui-unlock-method'] = 'Método de desbloqueo';
$labels['cloud-ui-unlock-secret'] = 'Contraseña de la bóveda / clave de recuperación';
$labels['cloud-ui-unlock-vault'] = 'Desbloquear bóveda';
$labels['cloud-ui-nextcloud-description'] = 'Conéctate con la URL WebDAV de Nextcloud y una contraseña de aplicación.';
$labels['cloud-ui-connect-account'] = 'Conectar cuenta';
$labels['cloud-ui-owncloud-description'] = 'Conéctate con la URL WebDAV de ownCloud y tus credenciales.';
$labels['cloud-ui-generic-webdav'] = 'WebDAV genérico';
$labels['cloud-ui-webdav-description'] = 'Conecta cualquier punto de acceso WebDAV HTTPS público compatible.';
$labels['cloud-ui-google-description'] = 'Autoriza con Google el acceso de solo lectura a tu Drive.';
$labels['cloud-ui-connect-google'] = 'Conectar cuenta de Google';
$labels['cloud-ui-google-aria'] = 'Conectar cuenta de Google Drive';
$labels['cloud-ui-onedrive-business'] = 'OneDrive Empresa';
$labels['cloud-ui-onedrive-business-description'] = 'Autoriza el acceso de solo lectura al OneDrive de tu trabajo o centro educativo.';
$labels['cloud-ui-connect-business'] = 'Conectar cuenta de empresa';
$labels['cloud-ui-onedrive-business-aria'] = 'Conectar cuenta de OneDrive Empresa';
$labels['cloud-ui-onedrive-personal'] = 'OneDrive Personal';
$labels['cloud-ui-onedrive-personal-description'] = 'Autoriza el acceso de solo lectura a tu OneDrive personal de Microsoft.';
$labels['cloud-ui-connect-personal'] = 'Conectar cuenta personal';
$labels['cloud-ui-onedrive-personal-aria'] = 'Conectar cuenta de OneDrive Personal';
$labels['cloud-ui-dropbox-description'] = 'Autoriza el acceso de solo lectura a tu cuenta de Dropbox.';
$labels['cloud-ui-connect-dropbox'] = 'Conectar cuenta de Dropbox';
$labels['cloud-ui-cloud-connections'] = 'Conexiones Cloud';
$labels['cloud-ui-choose-provider'] = 'Elige un proveedor. WebDAV utiliza credenciales de aplicación; los proveedores OAuth requieren la configuración del administrador.';
$labels['cloud-ui-connected-accounts'] = 'Cuentas conectadas';
$labels['cloud-ui-unlock-to-list'] = 'Desbloquea la bóveda para ver las cuentas.';
$labels['cloud-ui-connect-cloud-account'] = 'Conectar cuenta Cloud';
$labels['cloud-ui-close'] = 'Cerrar';
$labels['cloud-ui-close-connection'] = 'Cerrar el cuadro de conexión';
$labels['cloud-ui-account-name'] = 'Nombre de cuenta';
$labels['cloud-ui-my-cloud-account'] = 'Mi cuenta Cloud';
$labels['cloud-ui-public-webdav-url'] = 'URL WebDAV HTTPS pública completa';
$labels['cloud-ui-webdav-username'] = 'Nombre de usuario de WebDAV';
$labels['cloud-ui-webdav-password'] = 'Contraseña de aplicación WebDAV / contraseña';
$labels['cloud-ui-connection-needs-unlock'] = 'La bóveda Cloud debe estar desbloqueada antes de conectar una cuenta.';
$labels['cloud-ui-add-account'] = 'Añadir cuenta';
$labels['cloud-ui-browse-files'] = 'Explorar archivos Cloud';
$labels['cloud-ui-browse-explanation'] = 'Explora las carpetas y los archivos disponibles. Para adjuntar archivos a un mensaje, usa Adjuntar desde Cloud al redactar.';
$labels['cloud-ui-up-folder'] = 'Subir una carpeta';
$labels['cloud-ui-next-entries'] = 'Siguientes 100 elementos';
$labels['cloud-ui-save-recovery-title'] = 'Guarda ahora tu clave de recuperación';
$labels['cloud-ui-recovery-once-help'] = 'La clave de recuperación no podrá mostrarse de nuevo. Cópiala en un lugar seguro fuera de esta cuenta de correo web.';
$labels['cloud-ui-recovery-saved'] = 'Ya la he guardado — ocultar clave';


/* Cloud JavaScript interface */
/* Author: Gianluca Giacometti */

$labels['cloud-ui-unlocked'] = 'Desbloqueado';
$labels['cloud-ui-automatic-lock-in'] = 'Bloqueo automático en %s';
$labels['cloud-ui-unlock-expired'] = 'Desbloqueo caducado.';
$labels['cloud-ui-request-in-progress'] = 'Ya hay una operación de Cloud en curso.';
$labels['cloud-ui-processing-request'] = 'Procesando solicitud de Cloud…';
$labels['cloud-ui-request-send-failed'] = 'No se pudo enviar la solicitud de Cloud.';
$labels['cloud-ui-unlock-secret-required'] = 'Introduce la contraseña de la bóveda o la clave de recuperación para esta operación.';
$labels['cloud-ui-paste-recovery'] = 'Pega tu clave de recuperación';
$labels['cloud-ui-enter-vault-password'] = 'Introduce la contraseña de la bóveda';
$labels['cloud-ui-nextcloud-webdav-help'] = 'Usa la dirección WebDAV de los ajustes de Archivos de Nextcloud.';
$labels['cloud-ui-owncloud-webdav-help'] = 'Usa la dirección WebDAV de tu instalación ownCloud.';
$labels['cloud-ui-webdav-url-help'] = 'Introduce la URL HTTPS pública completa de tu carpeta WebDAV.';
$labels['cloud-ui-dialog-unsupported'] = 'Tu navegador no admite el diálogo de conexión a Cloud.';
$labels['cloud-ui-unlock-cloud-first'] = 'Primero desbloquea tu bóveda de Cloud.';
$labels['cloud-ui-connect-named'] = 'Conectar %s';
$labels['cloud-ui-my-named'] = 'Mi %s';
$labels['cloud-ui-account-unavailable'] = 'La cuenta de Cloud ya no está disponible. Actualiza la lista.';
$labels['cloud-ui-edit-named'] = 'Editar %s';
$labels['cloud-ui-cloud-account-default'] = 'cuenta de Cloud';
$labels['cloud-ui-cloud-account-title'] = 'Cuenta de Cloud';
$labels['cloud-ui-edit-webdav-help'] = 'Actualiza tu conexión WebDAV. Las credenciales guardadas nunca se muestran.';
$labels['cloud-ui-keep-username'] = 'Déjalo vacío para mantener el usuario actual';
$labels['cloud-ui-keep-app-password'] = 'Déjalo vacío para mantener la contraseña actual de la aplicación';
$labels['cloud-ui-keep-credentials-help'] = 'Deja el usuario o la contraseña en blanco para conservar el valor guardado.';
$labels['cloud-ui-save-changes'] = 'Guardar cambios';
$labels['cloud-ui-unlock-before-browse'] = 'Desbloquea la bóveda antes de explorar archivos.';
$labels['cloud-ui-my-drive'] = 'Mi unidad';
$labels['cloud-ui-loading-folder'] = 'Cargando carpeta…';
$labels['cloud-ui-folder-list-failed-start'] = 'No se pudo iniciar el listado de la carpeta.';
$labels['cloud-ui-select-valid-account'] = 'Desbloquea la bóveda y selecciona una cuenta WebDAV válida.';
$labels['cloud-ui-browse-files-named'] = 'Explorar archivos: %s';
$labels['cloud-ui-folder-empty'] = 'Esta carpeta está vacía.';
$labels['cloud-ui-bytes'] = 'bytes';
$labels['cloud-ui-no-cloud-accounts'] = 'No hay cuentas de Cloud conectadas.';
$labels['cloud-ui-browse-cloud-files'] = 'Explorar archivos de Cloud';
$labels['cloud-ui-edit-cloud-account'] = 'Editar cuenta de Cloud';
$labels['cloud-ui-edit-account'] = 'Editar cuenta';
$labels['cloud-ui-delete-cloud-account'] = 'Eliminar cuenta de Cloud';
$labels['cloud-ui-delete-account'] = 'Eliminar cuenta';
$labels['cloud-ui-folder-list-failed'] = 'Error al listar la carpeta. Consulta el aviso de error de Roundcube.';
$labels['cloud-ui-operation-failed'] = 'Error en la operación de Cloud.';
$labels['cloud-ui-vault-unlocked'] = 'Bóveda de Cloud desbloqueada.';
$labels['cloud-ui-vault-locked'] = 'Bóveda de Cloud bloqueada.';
$labels['cloud-ui-timer-reset'] = 'Temporizador de la bóveda reiniciado.';
$labels['cloud-ui-vault-created'] = 'Bóveda creada. Guarda la clave de recuperación si se ha generado.';
$labels['cloud-ui-google-open-failed'] = 'No se pudo abrir la autorización de Google. Permite las ventanas emergentes e inténtalo de nuevo.';
$labels['cloud-ui-microsoft-open-failed'] = 'No se pudo abrir la autorización de Microsoft. Permite las ventanas emergentes e inténtalo de nuevo.';
$labels['cloud-ui-personal-open-failed'] = 'No se pudo abrir la autorización de OneDrive Personal. Permite las ventanas emergentes e inténtalo de nuevo.';
$labels['cloud-ui-dropbox-open-failed'] = 'No se pudo abrir la autorización de Dropbox. Permite las ventanas emergentes e inténtalo de nuevo.';
$labels['cloud-ui-accounts-loaded'] = 'Cuentas de Cloud cargadas.';
$labels['cloud-ui-account-added'] = 'Cuenta de Cloud añadida.';
$labels['cloud-ui-account-updated'] = 'Cuenta de Cloud actualizada.';
$labels['cloud-ui-account-removed'] = 'Cuenta de Cloud eliminada.';
$labels['cloud-ui-vault-password-updated'] = 'Contraseña de la bóveda actualizada.';
$labels['cloud-ui-recovery-changed'] = 'Clave de recuperación cambiada. La anterior ya no funciona.';
$labels['cloud-ui-vault-password-mismatch'] = 'La contraseña de la bóveda y su confirmación no coinciden.';
$labels['cloud-ui-vault-password-length'] = 'La contraseña de la bóveda debe tener al menos 12 caracteres.';
$labels['cloud-ui-vault-method-required'] = 'Elige una contraseña de bóveda, una clave de recuperación o ambas.';
$labels['cloud-ui-google-popups'] = 'Permite las ventanas emergentes de este webmail para conectar Google Drive.';
$labels['cloud-ui-google-connected'] = 'Google Drive conectado.';
$labels['cloud-ui-google-auth-failed'] = 'Falló la autorización de Google Drive o caducó la bóveda.';
$labels['cloud-ui-onedrive-popups'] = 'Permite las ventanas emergentes de este webmail para conectar OneDrive.';
$labels['cloud-ui-onedrive-connected'] = 'Microsoft OneDrive conectado.';
$labels['cloud-ui-onedrive-auth-failed'] = 'Falló la autorización de Microsoft OneDrive o caducó la bóveda.';
$labels['cloud-ui-personal-popups'] = 'Permite las ventanas emergentes de este webmail para conectar OneDrive Personal.';
$labels['cloud-ui-personal-connected'] = 'OneDrive Personal conectado.';
$labels['cloud-ui-personal-auth-failed'] = 'Falló la autorización de OneDrive Personal o caducó la bóveda.';
$labels['cloud-ui-dropbox-popups'] = 'Permite las ventanas emergentes de este webmail para conectar Dropbox.';
$labels['cloud-ui-dropbox-connected'] = 'Dropbox conectado.';
$labels['cloud-ui-dropbox-auth-failed'] = 'Falló la autorización de Dropbox o caducó la bóveda.';
$labels['cloud-ui-unlock-first'] = 'Primero desbloquea la bóveda.';
$labels['cloud-ui-choose-provider-first'] = 'Primero elige un proveedor de Cloud.';
$labels['cloud-ui-account-edit-required'] = 'Introduce un nombre de cuenta y una URL WebDAV HTTPS pública.';
$labels['cloud-ui-account-new-required'] = 'Introduce nombre de cuenta, URL WebDAV HTTPS pública, usuario y contraseña de aplicación.';
$labels['cloud-ui-remove-account-confirm'] = '¿Eliminar esta cuenta de Cloud?';
$labels['cloud-ui-new-password-mismatch'] = 'La nueva contraseña de la bóveda y su confirmación no coinciden.';
$labels['cloud-ui-new-password-length'] = 'La nueva contraseña de la bóveda debe tener al menos 12 caracteres.';
$labels['cloud-ui-rotate-recovery-confirm'] = '¿Generar una nueva clave de recuperación? La anterior dejará de funcionar.';
$labels['cloud-ui-compose-send-failed'] = 'No se pudo enviar la solicitud de Cloud.';
$labels['cloud-ui-compose-choose-account'] = 'Elige una cuenta de Cloud';
$labels['cloud-ui-compose-select-account'] = 'Selecciona una cuenta para explorar.';
$labels['cloud-ui-compose-no-accounts'] = 'No hay cuentas conectadas. Añade una en los ajustes de Toolbox.';
$labels['cloud-ui-compose-attach'] = 'Adjuntar';
$labels['cloud-ui-compose-attach-named'] = 'Adjuntar %s';
$labels['cloud-ui-compose-invalid-id'] = 'Se guardó el archivo de Cloud, pero el ID del adjunto no es válido. Actualiza el borrador.';
$labels['cloud-ui-compose-attachment-default'] = 'Adjunto de Cloud';
$labels['cloud-ui-compose-remove-attachment'] = 'Quitar adjunto';
$labels['cloud-ui-compose-delete'] = 'Eliminar';
$labels['cloud-ui-compose-attached'] = 'Se adjuntó %s desde Cloud.';
$labels['cloud-ui-compose-request-failed'] = 'Falló la solicitud de Cloud.';
$labels['cloud-ui-compose-create-vault'] = 'Primero crea tu bóveda de Cloud en los ajustes de Toolbox.';
$labels['cloud-ui-compose-attach-from-cloud'] = 'Adjuntar desde Cloud';
$labels['cloud-ui-compose-unlock-help'] = 'Desbloquea tu bóveda de Cloud para adjuntar archivos.';
$labels['cloud-ui-compose-secret-label'] = 'Contraseña o clave de recuperación';
$labels['cloud-ui-compose-account'] = 'Cuenta de Cloud';
$labels['cloud-ui-compose-picker-unsupported'] = 'Tu navegador no admite el selector de Cloud.';
$labels['cloud-ui-compose-secret-required'] = 'Introduce la contraseña de la bóveda o la clave de recuperación.';
$labels['cloud-ui-compose-importing'] = 'Importando adjunto de Cloud…';

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

/* Cloud backend responses: translated only at user-facing boundaries */
$labels['cloud-backend-unlock-failed'] = 'No se pudo desbloquear la bóveda. Comprueba la contraseña o la clave de recuperación.';
$labels['cloud-backend-operation-failed'] = 'La operación de Cloud ha fallado. Consulta los detalles y los registros del servidor.';
$labels['cloud-backend-google-success'] = 'Cuenta de Google Drive conectada. Puedes cerrar esta ventana.';
$labels['cloud-backend-google-failed'] = 'Falló la conexión con Google Drive o caducó el desbloqueo de la bóveda. Puedes cerrar esta ventana.';
$labels['cloud-backend-business-success'] = 'Cuenta de OneDrive Business conectada. Puedes cerrar esta ventana.';
$labels['cloud-backend-business-failed'] = 'Falló la autorización de OneDrive Business o caducó el desbloqueo de la bóveda. Puedes cerrar esta ventana.';
$labels['cloud-backend-personal-success'] = 'Cuenta de OneDrive Personal conectada. Puedes cerrar esta ventana.';
$labels['cloud-backend-personal-failed'] = 'Falló la autorización de OneDrive Personal o caducó el desbloqueo de la bóveda. Puedes cerrar esta ventana.';
$labels['cloud-backend-dropbox-success'] = 'Cuenta de Dropbox conectada. Puedes cerrar esta ventana.';
$labels['cloud-backend-dropbox-failed'] = 'Falló la autorización de Dropbox o caducó el desbloqueo de la bóveda. Puedes cerrar esta ventana.';

// END OF FILE

