<?php
 /*
 * Project:     EQdkp-Plus Raidlogimport
 * License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:		http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:       2008
 * Date:        $Date: 2009-08-08 01:02:35 +0200 (sáb, 08 ago 2009) $
 * -----------------------------------------------------------------------
 * @author      $Author: hoofy_leon $
 * @copyright   2008-2009 hoofy_leon
 * @link        http://eqdkp-plus.com
 * @package     raidlogimport
 * @version     $Rev: 5570 $
 *
 * $Id: lang_main.php 5570 2009-08-07 23:02:35Z hoofy_leon $
 */
	$lang['raidlogimport'] = 'Importador de registros de banda';
	$lang['action_raidlogimport_dkp'] = 'DKP';
	$lang['action_raidlogimport_bz_upd'] = 'Jefe / Zona editado/a';
	$lang['action_raidlogimport_bz_add'] = 'Jefe / Zona añadido/a';
	$lang['action_raidlogimport_bz_del'] = 'Jefe / Zona borrado/a';
	$lang['action_raidlogimport_alias_upd'] = 'Alias editado';
	$lang['action_raidlogimport_alias_add'] = 'Alias añadido';
	$lang['action_raidlogimport_alias_del'] = 'Alias borrado';
	$lang['action_raidlogimport_config'] = 'Ajustes del Importador de DKP';
	$lang['raidlogimport_long_desc'] = '';
	$lang['raidlogimport_short_desc'] = 'Importar cadenas DKP';
    $lang['links'] = 'Enlaces';

	//permissions
	$lang['raidlogimport_bz'] = 'Gestión de Jefe/Zona';
	$lang['raidlogimport_dkp'] = 'Importar DKP';
	$lang['raidlogimport_alias'] = 'Gestión de alias';

	//Aliase
	$lang['rli_addalias'] = 'Añadir alias';
	$lang['rli_showalias'] = 'Mostrar, editar o borrar alias';
	$lang['rli_earner'] = 'Miembros, que obtiene DKP';
	$lang['rli_replace'] = 'Nombre, que será reemplazado en bandas.';
	$lang['rli_alias_exists'] = '¡El alias ya existe!';
	$lang['rli_of'] = 'Los DKP de';
	$lang['rli_get'] = 'consigue';
	$lang['rli_edit'] = 'Editar alias';
	$lang['rli_alias_no_delete'] = '¡El Alias no pudo ser borrado!';
	$lang['rli_alias_delete'] = 'Alias eliminado.';
	$lang['rli_alias_no_update'] = '¡No se pudo guardar el alias!';
	$lang['rli_alias_update'] = 'Alias guardado.';
	$lang['rli_del']	= 'Borrar alias';
	$lang['rli_upd'] = 'Actualizar alias';
	$lang['rli_menu_alias'] = 'Administrar alias';
	$lang['rli_suc'] = 'Alias guardado';


	//Bz
	$lang['rli_bz_bz'] = 'Jefes / Zonas';
	$lang['bz_boss'] = 'Jefes';
	$lang['bz_boss_s'] = 'Jefe';
	$lang['bz_boss_oz'] = 'Jefes sin Zona';
	$lang['bz_zone'] = 'Zonas';
	$lang['bz_zone_s'] = 'Zona';
	$lang['bz_no_zone'] = 'sin Zona';
	$lang['bz_no_diff'] = 'sin Dificultad';
	$lang['bz_string'] = 'Cadena';
	$lang['bz_bnote'] = 'Nota';
	$lang['bz_bonus'] = 'Bonus DKP / DKP/h';
	$lang['bz_zevent'] = 'Evento';
	$lang['bz_update'] = 'Añadir nuevo / Editar marcado';
	$lang['bz_delete'] = 'Borrar marcado';
	$lang['bz_upd'] = 'Editar Jefes / Zonas';
	$lang['bz_type'] = 'Tipo';
	$lang['bz_note_event'] = 'Nota / Evento';
	$lang['bz_save'] = 'Guardar';
	$lang['bz_yes'] = '¡Si!';
	$lang['bz_no'] = '¡No!';
	$lang['bz_del'] = 'Borrar Jefes / Zonas';
	$lang['bz_confirm_del'] = '¿Realmente quieres borrarlo?';
	$lang['bz_no_del'] = 'Los datos no se borraron';
	$lang['bz_del_suc'] = 'Datos correctamente borrados.';
	$lang['bz_tozone'] = 'En zono';
	$lang['bz_no_save'] = '¡Los datos no se guardaron!';
	$lang['bz_save_suc'] = 'Datos guardados correctamente.';
	$lang['bz_suc'] = 'Jefes / Zonas guardados';
	$lang['bz_missing_values'] = 'Todos los campos deben ser completados.';
	$lang['bz_sort'] = 'Orden';

	//dkp
	$lang['rli_dkp_insert'] = 'Insertar cadena DKP';
	$lang['rli_send'] = 'Enviar';
	$lang['rli_raidinfo'] = 'Informaciones de Banda';
	$lang['rli_start'] = 'Inicio';
	$lang['rli_end'] = 'Fin';
	$lang['rli_bosskills'] = 'Muertes de Jefes';
	$lang['rli_cost'] = 'Coste';
	$lang['rli_success'] = 'Éxito';
	$lang['rli_error'] = 'Los datos no se guardaron debido a un error';
	$lang['rli_no_mem_create'] = ' no se pudo crear. Por favor, añádelo manualmente.';
	$lang['rli_mem_auto'] = ' fue creado automáticamente.';
	$lang['rli_raid_to'] = 'Banda a %1$s en %2$s';
	$lang['rli_t_dkp'] = 'DKP por tiempo';
	$lang['rli_b_dkp'] = 'DKP por jefe';
	$lang['rli_looter'] = 'Despojador';
	$lang['xml_error'] = 'Error XML. ¡Por favor, revisa el registro!';
	$lang['parse_error'] = '¡Error de análisis!';
	$lang['rli_clock'] = '';
	$lang['rli_hour'] = 'hora';
	$lang['rli_att'] = 'Asistencia';
	$lang['rli_checkmem'] = 'Revisar datos de miembro';
	$lang['rli_back2raid'] = 'Volver a bandas';
	$lang['rli_checkraid'] = 'Revisar bandas';
	$lang['rli_checkitem'] = 'Revisar objetos';
	$lang['rli_itempage'] = 'Página de objeto ';
	$lang['rli_back2mem'] = 'Volver a miembros';
	$lang['rli_back2item'] = 'Volver a objetos';
    $lang['rli_checkadj'] = 'Revisar ajustes';
    $lang['rli_calc_note_value'] = 'Recalcular valor de banda y nota de banda';
    $lang['rli_calc_event_boss'] = 'Recalcular todo';
	$lang['rli_insert'] = 'Insertar DKP';
	$lang['rli_adjs'] = 'Ajustes';
	$lang['rli_partial_raid'] = 'Asistencia parcial a la banda';
	$lang['rli_add_raid'] = 'Añadir banda';
	$lang['rli_add_raids'] = 'Añadir bandas';
	$lang['rli_add_mem'] = 'Añadir miembro';
	$lang['rli_add_mems'] = 'Añadir miembros';
	$lang['rli_add_item'] = 'Añadir objeto';
	$lang['rli_add_items'] = 'Añadir objetos';
	$lang['rli_item_id'] = 'ID de objeto';
	$lang['rli_add_adj'] = 'Añadir ajuste';
	$lang['rli_add_adjs'] = 'Añadir ajustes';
	$lang['rli_add_bk'] = 'Añadir derrota de jefe';
	$lang['rli_add_bks'] = 'Añadir derrotas de jefes';
	$lang['rli_imp_no_suc'] = 'Importación fallida';
	$lang['rli_imp_suc'] = 'Importación con éxito';
	$lang['rli_members_needed'] = 'No se han introducido miembros.';
	$lang['rli_raids_needed'] = 'No se han introducido bandas.';
	$lang['rli_missing_values'] = 'Faltan algunos valores. Por favor: ';
	$lang['rli_miss'] = 'Faltan los siguientes nodo: ';
	$lang['rli_lgaobk'] = 'Los registros de asistencia de la hermandad en las muertes de jefes deben ser desactivados antes del seguimiento. Si quieres importar el registro de todas maneras, deberás borrar todas las inclusiones que han tenido lugar a la vez que las muertes de los jefes.';
	$lang['wrong_format'] = 'El formato del registro de la banda no coincide con el analizador.';
	$lang['eqdkp_format'] = 'Por favor, ajusta las opciones de CT-Raidtracker para <img src="'.$eqdkp_root_path.'plugins/raidlogimport/images/eqdkp_options.png">';
	$lang['plus_format'] = 'Por favor establece la exportación de tu addon al formato EQdkpPlus XML';
	$lang['magicdkp_format'] = 'Ocurrió un error.';
	$lang['wrong_game'] = '¡El juego del que has exportado el registro y el que has especificado en la configuración no coinciden!';
	$lang['wrong_settings'] = '<img src="$eqdkp_root_path'.'images/error.png" alt="error"> ¡Ajustes erróneos!';
	$lang['wrong_settings_1'] = $lang['wrong_settings'].' No puedes combinar '.$lang['raidcount_1'].' sin DKP por Tiempo.';
	$lang['wrong_settings_2'] = $lang['wrong_settings'].' No puedes combinar '.$lang['raidcount_2'].' sin DKP por Jefe.';
	$lang['wrong_settings_3'] = $lang['wrong_settings'].' No puedes combinar '.$lang['raidcount_3'].' sin DKP por jefe y/o DKP por Tiempo.';
	$lang['rli_process'] = 'Procesar';
	$lang['translate_items'] = 'Traducir objetos';
	$lang['get_itemid'] = 'Cagar ID de objeto';
	$lang['translate_items_tip'] = 'Tras la traducción, pulsa en "Actualizar" para rellenar los nuevos datos de objeto en el formulario.';
	$lang['raidval_nullsum_later'] = 'Con el sistema de suma nula el valor de la banda se introducirá después.';
	$lang['check_raidval'] = 'Revisar valores de banda';
	$lang['rli_log_lang'] = '¿En qué idioma están los objetos del registro?';
	$lang['form_null_sum'] = 'Fórmula: Costes de objetos / Número de miembros ';
	$lang['form_null_sum_1'] = $lang['form_null_sum'].'en la Banda';
	$lang['form_null_sum_2'] = $lang['form_null_sum'].'en el Sistema';
	$lang['rli_choose_mem'] = 'Elige un miembro ...';
	$lang['rli_go_on'] = 'Siguiente';

	//config
	$lang['multidkp_need'] = "<a onmouseover=\"return overlib('<div class=\'pk_tt_help\' style=\'display:block\'>                  <div class=\'pktooldiv\'>                  <table cellpadding=\'0\' border=\'0\' class=\'borderless\'>                  <tr><td>                    ¡Activar para funcionalidad con MultiDKP!                  </td>                  </tr>                  </table></div></div>', MOUSEOFF, HAUTO, VAUTO,  FULLHTML, WRAP);\" onmouseout=\"return nd();\"><img src='$eqdkp_root_path"."images/info.png' alt='help'></a>";
	$lang['new_member_rank'] = 'Rango por defecto para los miembros creados automáticamente.';
	$lang['raidcount'] = '¿Cómo deberían crearse las bandas?';
	$lang['raidcount_0'] = 'Una banda para todo';
	$lang['raidcount_1'] = 'Una banda por hora';
	$lang['raidcount_2'] = 'Una banda por jefe';
	$lang['raidcount_3'] = 'Una banda por jefe y hora';
	$lang['attendence_begin'] = 'Bonus por asistencia durante el inicio de la banda';
	$lang['attendence_end'] = 'Bonus por permanencia en la banda hasta el final';
	$lang['config_success'] = 'Configuración correcta';
	$lang['event_boss'] = '¿Existe un evento para cada jefe?';
	$lang['event_boss_1'] = 'Si';
	$lang['event_boss_2'] = 'Usa el nombre del jefe como nota de banda';
	$lang['attendence_raid'] = '¿Crear una banda extra para asistencia?';
	$lang['loottime'] = 'Tiempo en segundos que se tarda en repartir el botín.';
	$lang['attendence_time'] = 'Tiempo en segundos que duran las invitaciones / fin de la banda.';
	$lang['rli_inst_version'] = 'Versión instalada';
	$lang['adj_parse'] = 'Delimitador entre el motivo y el valor de un ajuste';
	$lang['bz_parse'] = 'Delimitador entre las Cadenas que pertenecen a un "evento".';
	$lang['parser'] = '¿En qué formato XML está la cadena?';
	$lang['parser_eqdkp'] = 'MLDKP 1.1 / Plugin EQdkp';
	$lang['parser_plus'] = 'Formato XML EQdkpPlus XML';
	$lang['parser_magicdkp'] = 'MagicDKP';
	$lang['parser_eq'] = 'Everquest';
	$lang['rli_man_db_up'] = 'Forzar actualización de la BD';
	$lang['rli_upd_check'] = '¿Activar revisión de actualizaciones?';
	$lang['use_dkp'] = '¿Qué DKP se debe usar?';
	$lang['use_dkp_1'] = 'DKP por Jefes';
	$lang['use_dkp_2'] = 'DKP por Tiempo';
	$lang['use_dkp_4'] = 'DKP por Evento';
	$lang['null_sum'] = '¿Usar sistema de suma nula?';
	$lang['null_sum_0'] = 'No';
	$lang['null_sum_1'] = 'Cada miembro en la banda consigue los DKP';
	$lang['null_sum_2'] = 'Cada miembro en el sistema consigue los DKP';
	$lang['item_save_lang'] = '¿En qué idioma deberían guardarse los objetos en la BD?';
	$lang['deactivate_adj'] = "¿Desactivar ajustes? <a onmouseover=\"return overlib('<div class=\'pk_tt_help\' style=\'display:block\'>                  <div class=\'pktooldiv\'>                  <table cellpadding=\'0\' border=\'0\' class=\'borderless\'>                  <tr><td>                   ¡Esto elimina la ganancia parcial de DKP por miembro! Todo el mundo consigue todo o nada                 </td>                  </tr>                  </table></div></div>', MOUSEOFF, HAUTO, VAUTO,  FULLHTML, WRAP);\" onmouseout=\"return nd();\"><img src='$eqdkp_root_path"."images/error.gif' alt='warn'></a>";
	$lang['addinfo_am'] = "<a onmouseover=\"return overlib('<div class=\'pk_tt_help\' style=\'display:block\'>                  <div class=\'pktooldiv\'>                  <table cellpadding=\'0\' border=\'0\' class=\'borderless\'>                  <tr><td>                   Este ajuste permite restas de DKP si el miembro no ha participado en el número de bandas indicado. Si usas un sistema de suma nula, los DKP se restarán por un objeto.                </td>                  </tr>                  </table></div></div>', MOUSEOFF, HAUTO, VAUTO,  FULLHTML, WRAP);\" onmouseout=\"return nd();\"><img src='$eqdkp_root_path"."images/error.gif' alt='warn'></a>";
	$lang['auto_minus'] = '¿Activar resta automática?'.$lang['addinfo_am'];
	$lang['am_raidnum'] = 'Número de bandas para resta automática';
	$lang['am_value'] = 'Cantidad de DKP restados';
	$lang['am_value_raids'] = 'Valor DKP = DKP del último número de bandas';
	$lang['am_allxraids'] = "¿Restaurar contador de bandas en Resta de DKP? <a onmouseover=\"return overlib('<div class=\'pk_tt_help\' style=\'display:block\'><div class=\'pktooldiv\'>                  <table cellpadding=\'0\' border=\'0\' class=\'borderless\'><tr><td>Ejemplo: Un miembro pierde DKP tras no asistir a 3 bandas. En la 4ª banda tampoco aparece. Si esta opción se desactiva el miembro volverá a perder DKP. Si está activa perderá DKP cuando se cumplan 6 Bandas sin asistir. </td></tr></table></div></div>', MOUSEOFF, HAUTO, VAUTO,  FULLHTML, WRAP);\" onmouseout=\"return nd();\"><img src='$eqdkp_root_path"."images/info.png' alt='help'></a>";
	$lang['addinfo_am'] = "<a onmouseover=\"return overlib('<div class=\'pk_tt_help\' style=\'display:block\'><div class=\'pktooldiv\'>                  <table cellpadding=\'0\' border=\'0\' class=\'borderless\'><tr><td> Cuando se usa, un miembro, que no haya participado en las últimas X bandas pierde una cantidad de DKP. Si se usa el sistema de suma negativa, se le concederá un objeto al miembro, si no recibirá un ajuste. <td></tr></table></div></div>', MOUSEOFF, HAUTO, VAUTO,  FULLHTML, WRAP);\" onmouseout=\"return nd();\"><img src='$eqdkp_root_path"."images/info.png' alt='help'></a>";
	$lang['am_name'] = 'falta de participación';
	$lang['title_am'] = 'Resta automática';
	$lang['title_adj'] = 'Ajustes';
	$lang['title_att'] = 'Asistencia';
	$lang['title_general'] = 'General';
	$lang['title_loot'] = 'Botín / Objetos';
	$lang['title_parse'] = 'Ajustes de análisis';
	$lang['title_hnh_suffix'] = 'Heróico / No Heróico';
	$lang['title_member'] = 'Ajustes de miembro';
	$lang['ignore_dissed'] = '¿Ignorar desencantados y botín de banco?';
	$lang['ignore_dissed_1'] = 'Ignorar desencantado';
	$lang['ignore_dissed_2'] = 'Ignorar banco';
	$lang['member_miss_time'] = 'Tiempo en segundos que un miembro puede perder sin ser registrado.';
	$lang['s_member_rank'] = '¿Mostrar rango de miembro?';
	$lang['s_member_rank_1'] = 'Vista de miembros';
	$lang['s_member_rank_2'] = 'Vista de botín';
	$lang['s_member_rank_4'] = 'Vista de ajustes';
	$lang['member_start'] = 'DKP iniciales que gana un miembro cuando se crea automáticamente';
	$lang['member_start_name'] = 'DKP iniciales'; //value is used for reason of adjustment
	$lang['member_start_event'] = 'Evento para DKP iniciales';
	$lang['att_note_begin'] = 'nota de banda para la asistencia inicial a la banda';
	$lang['att_note_end'] = 'nota de banda para la asistencia final a la banda';
	$lang['raid_note_time']	= 'nota de banda de las bandas por hora';
	$lang['raid_note_time_0'] = '20:00-21:00, 21:00-22:00, etc.';
	$lang['raid_note_time_1'] = '1.Hora, 2.Hora, etc.';
	$lang['timedkp_handle']	= "Cálculo de los DKP por tiempo <a onmouseover=\"return overlib('<div class=\'pk_tt_help\' style=\'display:block\'><div class=\'pktooldiv\'>                  <table cellpadding=\'0\' border=\'0\' class=\'borderless\'><tr><td> 0: álculo exacto por minuto <br /> >0: minutos, después el miembro gana DPS completo de la hora </td></tr></table></div></div>', MOUSEOFF, HAUTO, VAUTO,  FULLHTML, WRAP);\" onmouseout=\"return nd();\"><img src='$eqdkp_root_path"."images/info.png' alt='help'></a>";
	 $lang['member_display'] = '¿Cómo mostrar la lista de miembros?';
	 $lang['member_display_1'] = 'Múltiples casillas de selección';
	 $lang['member_display_0'] = 'Selección múltiple';
    $lang['member_display_add'] = " <a onmouseover=\"return overlib('<div class=\'pk_tt_help\' style=\'display:block\'><div class=\'pktooldiv\'>                  <table cellpadding=\'0\' border=\'0\' class=\'borderless\'><tr><td>Si quieres usar <nobr>".$lang['member_display_1']."</nobr>, tienes que tener la librería GD (Extensión PHP).<br /> Estás usando la siguiente versión de la librería GD:<br />%s</td></tr></table></div></div>', MOUSEOFF, HAUTO, VAUTO,  FULLHTML, WRAP);\" onmouseout=\"return nd();\"><img src='$eqdkp_root_path"."images/error.gif' alt='warn'></a>";
	 $lang['no_gd_lib'] = '<span class="negative">no GD-lib found</span>';
	 $lang['bz_dep_match'] = '¿Se deberían evaluar los activadores de jefe dependiendo de su zona?';

    //portal
    $lang['p_rli_zone_display'] = '¿Qué zonas deberían ser mostradas?';
    $lang['dkpvals'] = 'Valores DKP';
?>