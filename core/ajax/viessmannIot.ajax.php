<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

try 
{
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) 
    {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }
    
    ajax::init();

    if (init('action') == 'lireTempInt') {
        $eqLogic = viessmannIot::byId(init('id'));
        if (!is_object($eqLogic)) {
            throw new Exception(__('Equipement non trouvé : ', __FILE__) . init('id'));
        } else {
            ajax::success($eqLogic->lireTempInt(init('startDate'),init('endDate'),init('dynamique')));
        }
    }

    if (init('action') == 'lireTempExt') {
        $eqLogic = viessmannIot::byId(init('id'));
        if (!is_object($eqLogic)) {
            throw new Exception(__('Equipement non trouvé : ', __FILE__) . init('id'));
        } else {
            ajax::success($eqLogic->lireTempExt(init('startDate'),init('endDate'),init('dynamique')));
        }
    }

    if (init('action') == 'lireTempCsg') {
        $eqLogic = viessmannIot::byId(init('id'));
        if (!is_object($eqLogic)) {
            throw new Exception(__('Equipement non trouvé : ', __FILE__) . init('id'));
        } else {
            ajax::success($eqLogic->lireTempCsg(init('startDate'),init('endDate'),init('dynamique')));
        }
    }

    throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));

} 
catch (Exception $e) 
{
    ajax::error(displayException($e), $e->getCode());
}
