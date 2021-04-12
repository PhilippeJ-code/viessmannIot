<?php

/* This file is part of Jeedom
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

  require_once __DIR__  . '/../../../../core/php/core.inc.php';
  include __DIR__ . '/../php/viessmannApi.php';

  class viessmannIot extends eqLogic
  {
      const HEATING_CIRCUITS = "heating.circuits";

      const OUTSIDE_TEMPERATURE = "heating.sensors.temperature.outside";
      const HOT_WATER_STORAGE_TEMPERATURE = "heating.dhw.sensors.temperature.hotWaterStorage";
      const DHW_TEMPERATURE = "heating.dhw.temperature.main";
      const HEATING_BURNER = "heating.burner";
      const HEATING_DHW_ONETIMECHARGE = "heating.dhw.oneTimeCharge";
      const HEATING_DHW_SCHEDULE = "heating.dhw.schedule";

      const ACTIVE_MODE = "operating.modes.active";
      const ACTIVE_PROGRAM = "operating.programs.active";
      const PUMP_STATUS = "circulation.pump";
      const HEATING_BOILER_SENSORS_TEMPERATURE = "heating.boiler.sensors.temperature.commonSupply";

      const STANDBY_MODE = "operating.modes.standby";
      const HEATING_MODE = "operating.modes.heating";
      const DHW_MODE = "operating.modes.dhw";
      const DHW_AND_HEATING_MODE = "operating.modes.dhwAndHeating";
      const COOLING_MODE = "operating.modes.cooling";
      const DHW_AND_HEATING_COOLING_MODE = "operating.modes.dhwAndHeatingCooling";
      const HEATING_COOLING_MODE = "operating.modes.heatingCooling";
      const NORMAL_STANDBY_MODE = "operating.modes.normalStandby";
      const HEATING_SCHEDULE = "heating.schedule";
      const HEATING_FROSTPROTECTION = "frostprotection";
  
      const SENSORS_TEMPERATURE_SUPPLY = "sensors.temperature.supply";
      const HEATING_CURVE = "heating.curve";
      const COMFORT_PROGRAM = "operating.programs.comfort";
      const NORMAL_PROGRAM = "operating.programs.normal";
      const REDUCED_PROGRAM = "operating.programs.reduced";
      const SENSORS_TEMPERATURE_ROOM = "sensors.temperature.room";
      const ECO_PROGRAM = "operating.programs.eco";
      const PRESSURE_SUPPLY = "heating.sensors.pressure.supply";

      // Supprimer les commandes
      //
      public function deleteAllCommands()
      {
          $cmds = $this->getCmd();
          foreach ($cmds as $cmd) {
              if ($cmd->getLogicalId() != 'refresh' && $cmd->getLogicalId() != 'refreshDate') {
                  $cmd->remove();
              }
          }
      }

      // Créer les commandes
      //
      public function createCommands($viessmannApi)
      {
          $circuitId = trim($this->getConfiguration('circuitId', '0'));

          $features = $viessmannApi->getArrayFeatures();
          $n = count($features["data"]);

          $n = count($features["data"]);
          for ($i=0; $i<$n; $i++) {
              if ($features["data"][$i]["isEnabled"] == true) {
                  log::add('viessmannIot', 'debug', $features["data"][$i]["feature"]);
              }
          }

          for ($i=0; $i<$n; $i++) {
              if ($features["data"][$i]["feature"] == self::OUTSIDE_TEMPERATURE && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'outsideTemperature');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Température extérieure', __FILE__));
                      $obj->setUnite('°C');
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('numeric');
                  $obj->setLogicalId('outsideTemperature');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::PUMP_STATUS) && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'pumpStatus');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Statut circulateur', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('string');
                  $obj->setLogicalId('pumpStatus');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == self::HOT_WATER_STORAGE_TEMPERATURE && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'hotWaterStorageTemperature');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Température eau chaude', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('numeric');
                  $obj->setLogicalId('hotWaterStorageTemperature');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == self::DHW_TEMPERATURE && $features["data"][$i]["isEnabled"] == true) {
                  $objDhw = $this->getCmd(null, 'dhwTemperature');
                  if (!is_object($objDhw)) {
                      $objDhw = new viessmannIotCmd();
                      $objDhw->setName(__('Consigne eau chaude', __FILE__));
                      $objDhw->setIsVisible(1);
                      $objDhw->setIsHistorized(0);
                  }
                  $objDhw->setEqLogic_id($this->getId());
                  $objDhw->setType('info');
                  $objDhw->setSubType('numeric');
                  $objDhw->setLogicalId('dhwTemperature');
                  $objDhw->setConfiguration('minValue', 10);
                  $objDhw->setConfiguration('maxValue', 60);
                  $objDhw->save();
                  
                  if (isset($features["data"][$i]["commands"]["setTargetTemperature"])) {
                      $obj = $this->getCmd(null, 'dhwSlider');
                      if (!is_object($obj)) {
                          $obj = new viessmannIotCmd();
                          $obj->setUnite('°C');
                          $obj->setName(__('Slider consigne eau chaude ', __FILE__));
                          $obj->setIsVisible(1);
                          $obj->setIsHistorized(0);
                      }
                      $obj->setEqLogic_id($this->getId());
                      $obj->setType('action');
                      $obj->setSubType('slider');
                      $obj->setLogicalId('dhwSlider');
                      $obj->setValue($objDhw->getId());
                      $obj->setConfiguration('minValue', 10);
                      $obj->setConfiguration('maxValue', 60);
                      $obj->save();
                  }
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::ACTIVE_MODE) && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'activeMode');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Mode activé', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('string');
                  $obj->setLogicalId('activeMode');
                  $obj->save();
                  
                  $nc = count($features["data"][$i]["commands"]["setMode"]["params"]["mode"]["constraints"]["enum"]);
                  for ($j=0; $j<$nc; $j++) {
                      if ($features["data"][$i]["commands"]["setMode"]["params"]["mode"]["constraints"]["enum"][$j] == 'standby') {
                          $obj = $this->getCmd(null, 'modeStandby');
                          if (!is_object($obj)) {
                              $obj = new viessmannIotCmd();
                              $obj->setName(__('Mode mise en veille', __FILE__));
                          }
                          $obj->setEqLogic_id($this->getId());
                          $obj->setLogicalId('modeStandby');
                          $obj->setType('action');
                          $obj->setSubType('other');
                          $obj->save();
                      } elseif ($features["data"][$i]["commands"]["setMode"]["params"]["mode"]["constraints"]["enum"][$j] == 'heating') {
                          $obj = $this->getCmd(null, 'modeHeating');
                          if (!is_object($obj)) {
                              $obj = new viessmannIotCmd();
                              $obj->setName(__('Mode chauffage', __FILE__));
                          }
                          $obj->setEqLogic_id($this->getId());
                          $obj->setLogicalId('modeHeating');
                          $obj->setType('action');
                          $obj->setSubType('other');
                          $obj->save();
                      } elseif ($features["data"][$i]["commands"]["setMode"]["params"]["mode"]["constraints"]["enum"][$j] == 'dhw') {
                          $obj = $this->getCmd(null, 'modeDhw');
                          if (!is_object($obj)) {
                              $obj = new viessmannIotCmd();
                              $obj->setName(__('Mode eau chaude', __FILE__));
                          }
                          $obj->setEqLogic_id($this->getId());
                          $obj->setLogicalId('modeDhw');
                          $obj->setType('action');
                          $obj->setSubType('other');
                          $obj->save();
                      } elseif ($features["data"][$i]["commands"]["setMode"]["params"]["mode"]["constraints"]["enum"][$j] == 'dhwAndHeating') {
                          $obj = $this->getCmd(null, 'modeDhwAndHeating');
                          if (!is_object($obj)) {
                              $obj = new viessmannIotCmd();
                              $obj->setName(__('Mode chauffage et eau chaude', __FILE__));
                          }
                          $obj->setEqLogic_id($this->getId());
                          $obj->setLogicalId('modeDhwAndHeating');
                          $obj->setType('action');
                          $obj->setSubType('other');
                          $obj->save();
                      } elseif ($features["data"][$i]["commands"]["setMode"]["params"]["mode"]["constraints"]["enum"][$j] == 'forcedReduced') {
                          $obj = $this->getCmd(null, 'modeForcedReduced');
                          if (!is_object($obj)) {
                              $obj = new viessmannIotCmd();
                              $obj->setName(__('Marche réduite permanente', __FILE__));
                          }
                          $obj->setEqLogic_id($this->getId());
                          $obj->setLogicalId('modeForcedReduced');
                          $obj->setType('action');
                          $obj->setSubType('other');
                          $obj->save();
                      } elseif ($features["data"][$i]["commands"]["setMode"]["params"]["mode"]["constraints"]["enum"][$j] == 'forcedNormal') {
                          $obj = $this->getCmd(null, 'modeForcedNormal');
                          if (!is_object($obj)) {
                              $obj = new viessmannIotCmd();
                              $obj->setName(__('Marche normale permanente', __FILE__));
                          }
                          $obj->setEqLogic_id($this->getId());
                          $obj->setLogicalId('modeForcedNormal');
                          $obj->setType('action');
                          $obj->setSubType('other');
                          $obj->save();
                      }
                  }
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::ACTIVE_PROGRAM) && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'activeProgram');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Programme activé', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('string');
                  $obj->setLogicalId('activeProgram');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == self::HEATING_BURNER && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'isHeatingBurnerActive');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Bruleur activé', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('binary');
                  $obj->setLogicalId('isHeatingBurnerActive');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::STANDBY_MODE) && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'isStandbyModeActive');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Veille activée', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('binary');
                  $obj->setLogicalId('isStandbyModeActive');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::HEATING_MODE) && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'isHeatingModeActive');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Chauffage activé', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('binary');
                  $obj->setLogicalId('isHeatingModeActive');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::DHW_MODE) && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'isDhwModeActive');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Eau chaude activée', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('binary');
                  $obj->setLogicalId('isDhwModeActive');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::DHW_AND_HEATING_MODE) && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'isDhwAndHeatingModeActive');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Eau chaude et chauffage activés', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('binary');
                  $obj->setLogicalId('isDhwAndHeatingModeActive');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::COOLING_MODE) && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'isCoolingModeActive');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Refroidissement activé', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('binary');
                  $obj->setLogicalId('isCoolingModeActive');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::DHW_AND_HEATING_COOLING_MODE) && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'isDhwAndHeatingCoolingModeActive');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Eau chaude, chauffage et refroidissement activés', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('binary');
                  $obj->setLogicalId('isDhwAndHeatingCoolingModeActive');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::HEATING_COOLING_MODE) && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'isHeatingCoolingModeActive');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Chauffage et refroidissement activés', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('binary');
                  $obj->setLogicalId('isHeatingCoolingModeActive');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::NORMAL_STANDBY_MODE) && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'isNormalStandbyModeActive');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Veille normale activée', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('binary');
                  $obj->setLogicalId('isNormalStandbyModeActive');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::SENSORS_TEMPERATURE_SUPPLY) && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'supplyProgramTemperature');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Température de départ', __FILE__));
                      $obj->setUnite('°C');
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('numeric');
                  $obj->setLogicalId('supplyProgramTemperature');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::COMFORT_PROGRAM) && $features["data"][$i]["isEnabled"] == true) {
                  $objComfort = $this->getCmd(null, 'comfortProgramTemperature');
                  if (!is_object($objComfort)) {
                      $objComfort = new viessmannIotCmd();
                      $objComfort->setName(__('Consigne de confort', __FILE__));
                      $objComfort->setUnite('°C');
                      $objComfort->setIsVisible(1);
                      $objComfort->setIsHistorized(0);
                  }
                  $objComfort->setEqLogic_id($this->getId());
                  $objComfort->setType('info');
                  $objComfort->setSubType('numeric');
                  $objComfort->setLogicalId('comfortProgramTemperature');
                  $objComfort->setConfiguration('minValue', 3);
                  $objComfort->setConfiguration('maxValue', 37);
                  $objComfort->save();
              
                  $obj = $this->getCmd(null, 'comfortProgramSlider');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setUnite('°C');
                      $obj->setName(__('Slider consigne de confort', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('action');
                  $obj->setSubType('slider');
                  $obj->setLogicalId('comfortProgramSlider');
                  $obj->setValue($objComfort->getId());
                  $obj->setConfiguration('minValue', 3);
                  $obj->setConfiguration('maxValue', 37);
                  $obj->save();

                  $obj = $this->getCmd(null, 'isActivateComfortProgram');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Programme comfort actif', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('binary');
                  $obj->setLogicalId('isActivateComfortProgram');
                  $obj->save();

                  $obj = $this->getCmd(null, 'activateComfortProgram');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Activer programme confort', __FILE__));
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setLogicalId('activateComfortProgram');
                  $obj->setType('action');
                  $obj->setSubType('other');
                  $obj->save();
          
                  $obj = $this->getCmd(null, 'deActivateComfortProgram');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Désactiver programme confort', __FILE__));
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setLogicalId('deActivateComfortProgram');
                  $obj->setType('action');
                  $obj->setSubType('other');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::NORMAL_PROGRAM) && $features["data"][$i]["isEnabled"] == true) {
                  $objNormal = $this->getCmd(null, 'normalProgramTemperature');
                  if (!is_object($objNormal)) {
                      $objNormal = new viessmannIotCmd();
                      $objNormal->setName(__('Consigne normale', __FILE__));
                      $objNormal->setUnite('°C');
                      $objNormal->setIsVisible(1);
                      $objNormal->setIsHistorized(0);
                  }
                  $objNormal->setEqLogic_id($this->getId());
                  $objNormal->setType('info');
                  $objNormal->setSubType('numeric');
                  $objNormal->setLogicalId('normalProgramTemperature');
                  $objNormal->setConfiguration('minValue', 3);
                  $objNormal->setConfiguration('maxValue', 37);
                  $objNormal->save();
              
                  $obj = $this->getCmd(null, 'normalProgramSlider');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setUnite('°C');
                      $obj->setName(__('Slider consigne normale', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('action');
                  $obj->setSubType('slider');
                  $obj->setLogicalId('normalProgramSlider');
                  $obj->setValue($objNormal->getId());
                  $obj->setConfiguration('minValue', 3);
                  $obj->setConfiguration('maxValue', 37);
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::REDUCED_PROGRAM) && $features["data"][$i]["isEnabled"] == true) {
                  $objReduced = $this->getCmd(null, 'reducedProgramTemperature');
                  if (!is_object($objReduced)) {
                      $objReduced = new viessmannIotCmd();
                      $objReduced->setName(__('Consigne réduite', __FILE__));
                      $objReduced->setUnite('°C');
                      $objReduced->setIsVisible(1);
                      $objReduced->setIsHistorized(0);
                  }
                  $objReduced->setEqLogic_id($this->getId());
                  $objReduced->setType('info');
                  $objReduced->setSubType('numeric');
                  $objReduced->setLogicalId('reducedProgramTemperature');
                  $objReduced->setConfiguration('minValue', 3);
                  $objReduced->setConfiguration('maxValue', 37);
                  $objReduced->save();

                  $obj = $this->getCmd(null, 'reducedProgramSlider');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setUnite('°C');
                      $obj->setName(__('Slider consigne réduite', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('action');
                  $obj->setSubType('slider');
                  $obj->setLogicalId('reducedProgramSlider');
                  $obj->setValue($objReduced->getId());
                  $obj->setConfiguration('minValue', 3);
                  $obj->setConfiguration('maxValue', 37);
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::ECO_PROGRAM) && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'isActivateEcoProgram');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Programme éco actif', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('binary');
                  $obj->setLogicalId('isActivateEcoProgram');
                  $obj->save();

                  $obj = $this->getCmd(null, 'activateEcoProgram');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Activer programme éco', __FILE__));
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setLogicalId('activateEcoProgram');
                  $obj->setType('action');
                  $obj->setSubType('other');
                  $obj->save();
          
                  $obj = $this->getCmd(null, 'deActivateEcoProgram');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Désactiver programme éco', __FILE__));
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setLogicalId('deActivateEcoProgram');
                  $obj->setType('action');
                  $obj->setSubType('other');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::SENSORS_TEMPERATURE_ROOM) && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'roomTemperature');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Température intérieure', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('numeric');
                  $obj->setLogicalId('roomTemperature');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == self::HEATING_DHW_ONETIMECHARGE && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'isOneTimeDhwCharge');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Forcer Eau chaude', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('binary');
                  $obj->setLogicalId('isOneTimeDhwCharge');
                  $obj->save();
        
                  $obj = $this->getCmd(null, 'startOneTimeDhwCharge');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Activer demande eau chaude', __FILE__));
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setLogicalId('startOneTimeDhwCharge');
                  $obj->setType('action');
                  $obj->setSubType('other');
                  $obj->save();
        
                  $obj = $this->getCmd(null, 'stopOneTimeDhwCharge');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Désactiver demande eau chaude', __FILE__));
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setLogicalId('stopOneTimeDhwCharge');
                  $obj->setType('action');
                  $obj->setSubType('other');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::HEATING_CURVE) && $features["data"][$i]["isEnabled"] == true) {
                  $objSlope = $this->getCmd(null, 'slope');
                  if (!is_object($objSlope)) {
                      $objSlope = new viessmannIotCmd();
                      $objSlope->setName(__('Pente', __FILE__));
                      $objSlope->setIsVisible(1);
                      $objSlope->setIsHistorized(0);
                  }
                  $objSlope->setEqLogic_id($this->getId());
                  $objSlope->setType('info');
                  $objSlope->setSubType('numeric');
                  $objSlope->setLogicalId('slope');
                  $objSlope->setConfiguration('minValue', 0.2);
                  $objSlope->setConfiguration('maxValue', 3.5);
                  $objSlope->save();
              
                  $objShift = $this->getCmd(null, 'shift');
                  if (!is_object($objShift)) {
                      $objShift = new viessmannIotCmd();
                      $objShift->setName(__('Parallèle', __FILE__));
                      $objShift->setIsVisible(1);
                      $objShift->setIsHistorized(0);
                  }
                  $objShift->setEqLogic_id($this->getId());
                  $objShift->setType('info');
                  $objShift->setSubType('numeric');
                  $objShift->setLogicalId('shift');
                  $objShift->setConfiguration('minValue', -13);
                  $objShift->setConfiguration('maxValue', 40);
                  $objShift->save();

                  $obj = $this->getCmd(null, 'slopeSlider');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Slider pente', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('action');
                  $obj->setSubType('slider');
                  $obj->setLogicalId('slopeSlider');
                  $obj->setValue($objSlope->getId());
                  $obj->setConfiguration('minValue', 0.2);
                  $obj->setConfiguration('maxValue', 3.5);
                  $optParam = $obj->getDisplay('parameters');
                  if (!is_array($optParam)) {
                      $optParam = array();
                  }
                  $optParam['step'] = 0.1;
                  $obj->setDisplay('parameters', $optParam);
                  $obj->save();

                  $obj = $this->getCmd(null, 'shiftSlider');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Slider parallèle', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('action');
                  $obj->setSubType('slider');
                  $obj->setLogicalId('shiftSlider');
                  $obj->setValue($objShift->getId());
                  $obj->setConfiguration('minValue', -13);
                  $obj->setConfiguration('maxValue', 40);
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == self::HEATING_DHW_SCHEDULE && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'dhwSchedule');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Programmation eau chaude', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('string');
                  $obj->setLogicalId('dhwSchedule');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::HEATING_SCHEDULE) && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'heatingSchedule');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Programmation chauffage', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('string');
                  $obj->setLogicalId('heatingSchedule');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::HEATING_FROSTPROTECTION) && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'frostProtection');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Protection gel', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('string');
                  $obj->setLogicalId('frostProtection');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == self::HEATING_BOILER_SENSORS_TEMPERATURE && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'boilerTemperature');
                  if (!is_object($obj)) {
                      $obj = new viessmannCmd();
                      $obj->setName(__('Température eau radiateur', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('numeric');
                  $obj->setLogicalId('boilerTemperature');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == self::PRESSURE_SUPPLY && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'pressureSupply');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Pression installation', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('numeric');
                  $obj->setLogicalId('pressureSupply');
                  $obj->save();
              }
          }
      }

      // Accès au serveur Viessmann
      //
      public function getViessmann()
      {
          $clientId = trim($this->getConfiguration('clientId', ''));
          $codeChallenge = trim($this->getConfiguration('codeChallenge', ''));

          $userName = trim($this->getConfiguration('userName', ''));
          $password = trim($this->getConfiguration('password', ''));

          $installationId = trim($this->getConfiguration('installationId', ''));
          $serial = trim($this->getConfiguration('serial', ''));

          $deviceId = trim($this->getConfiguration('deviceId', '0'));
          $circuitId = trim($this->getConfiguration('circuitId', '0'));

          $isVicare = $this->getConfiguration('isVicare', false);

          $expires_at = $this->getCache('expires_at', 0);
          $token = $this->getCache('token', '');

          if (($userName === '') || ($password === '') || ($clientId === '') || ($codeChallenge === '')) {
              return null;
          }

          $params = [
          "clientId" => $clientId,
          "codeChallenge" => $codeChallenge,
          "user" => $userName,
          "pwd" => $password,
          "installationId" => $installationId,
          "serial" => $serial,
          "deviceId" => $deviceId,
          "circuitId" => $circuitId,
          "expires_at" => $expires_at,
          "token" => $token,
          "vicare" => $isVicare
        ];

          $viessmannApi = new ViessmannApi($params);
                        
          if ((empty($installationId)) || (empty($serial))) {
              $viessmannApi->getGateway();
              $viessmannApi->getFeatures();
            
              $installationId = $viessmannApi->getInstallationId();
              $serial = $viessmannApi->getSerial();

              $this->setConfiguration('installationId', $installationId);
              $this->setConfiguration('serial', $serial)->save();
            
//              $this->deleteAllCommands();
              $this->createCommands($viessmannApi);
          }

          if ($viessmannApi->isNewToken()) {
              $expires_at = time() + $viessmannApi->getExpiresIn() - 300;
              $this->setCache('expires_at', $expires_at);
              $this->setCache('token', $viessmannApi->getNewToken());
              log::add('viessmannIot', 'debug', 'Token expires at ' . date('d-m-Y H:i:s', $expires_at));
          }

          return $viessmannApi;
      }

      public function rafraichir($viessmannApi)
      {
          $circuitId = trim($this->getConfiguration('circuitId', '0'));

          $viessmannApi->getFeatures();
          $features = $viessmannApi->getArrayFeatures();
          $nbr = count($features["data"]);
          for ($i=0; $i<$nbr; $i++) {
              if ($features["data"][$i]["feature"] == self::OUTSIDE_TEMPERATURE && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["value"]["value"];
                  $obj = $this->getCmd(null, 'outsideTemperature');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::PUMP_STATUS) && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["status"]["value"];
                  $obj = $this->getCmd(null, 'pumpStatus');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              } elseif ($features["data"][$i]["feature"] == self::HOT_WATER_STORAGE_TEMPERATURE && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["value"]["value"];
                  $obj = $this->getCmd(null, 'hotWaterStorageTemperature');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              } elseif ($features["data"][$i]["feature"] == self::DHW_TEMPERATURE && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["value"]["value"];
                  $obj = $this->getCmd(null, 'dhwTemperature');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
                  $this->getCmd(null, 'dhwTemperature')->event($val);
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::ACTIVE_MODE) && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["value"]["value"];
                  $obj = $this->getCmd(null, 'activeMode');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::ACTIVE_PROGRAM) && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["value"]["value"];
                  $obj = $this->getCmd(null, 'activeProgram');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              } elseif ($features["data"][$i]["feature"] == self::HEATING_BURNER && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["active"]["value"];
                  $obj = $this->getCmd(null, 'isHeatingBurnerActive');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::STANDBY_MODE) && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["active"]["value"];
                  $obj = $this->getCmd(null, 'isStandbyModeActive');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::HEATING_MODE) && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["active"]["value"];
                  $obj = $this->getCmd(null, 'isHeatingModeActive');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::DHW_MODE) && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["active"]["value"];
                  $obj = $this->getCmd(null, 'isDhwModeActive');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::DHW_AND_HEATING_MODE) && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["active"]["value"];
                  $obj = $this->getCmd(null, 'isDhwAndHeatingModeActive');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::COOLING_MODE) && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["active"]["value"];
                  $obj = $this->getCmd(null, 'isCoolingModeActive');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::DHW_AND_HEATING_COOLING_MODE) && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["active"]["value"];
                  $obj = $this->getCmd(null, 'isDhwAndHeatingCoolingModeActive');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::HEATING_COOLING_MODE) && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["active"]["value"];
                  $obj = $this->getCmd(null, 'isHeatingCoolingModeActive');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::NORMAL_STANDBY_MODE) && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["active"]["value"];
                  $obj = $this->getCmd(null, 'isNormalStandbyModeActive');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::SENSORS_TEMPERATURE_SUPPLY) && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["value"]["value"];
                  $obj = $this->getCmd(null, 'supplyProgramTemperature');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::COMFORT_PROGRAM) && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["temperature"]["value"];
                  $obj = $this->getCmd(null, 'comfortProgramTemperature');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
                  $val = $features["data"][$i]["properties"]["active"]["value"];
                  $obj = $this->getCmd(null, 'isActivateComfortProgram');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::NORMAL_PROGRAM) && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["temperature"]["value"];
                  $obj = $this->getCmd(null, 'normalProgramTemperature');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::REDUCED_PROGRAM) && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["temperature"]["value"];
                  $obj = $this->getCmd(null, 'reducedProgramTemperature');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::ECO_PROGRAM) && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["active"]["value"];
                  $obj = $this->getCmd(null, 'isActivateEcoProgram');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::SENSORS_TEMPERATURE_ROOM) && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["value"]["value"];
                  $obj = $this->getCmd(null, 'roomTemperature');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              } elseif ($features["data"][$i]["feature"] == self::HEATING_DHW_ONETIMECHARGE && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["active"]["value"];
                  $obj = $this->getCmd(null, 'isOneTimeDhwCharge');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::HEATING_CURVE) && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["shift"]["value"];
                  $obj = $this->getCmd(null, 'shift');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
                  $val = $features["data"][$i]["properties"]["slope"]["value"];
                  $obj = $this->getCmd(null, 'slope');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              } elseif ($features["data"][$i]["feature"] == self::HEATING_DHW_SCHEDULE && $features["data"][$i]["isEnabled"] == true) {
                  $dhwSchedule = '';
        
                  $n = count($features["data"][$i]["properties"]['entries']['value']['mon']);
                  for ($j=0; $j<$n; $j++) {
                      $dhwSchedule .= 'n,';
                      $dhwSchedule .= $features["data"][$i]["properties"]['entries']['value']['mon'][$j]['start'] . ',';
                      $dhwSchedule .= $features["data"][$i]["properties"]['entries']['value']['mon'][$j]['end'];
                      if ($j < $n-1) {
                          $dhwSchedule .= ',';
                      }
                  }
                  $dhwSchedule .= ';';
        
                  $n = count($features["data"][$i]["properties"]['entries']['value']['tue']);
                  for ($j=0; $j<$n; $j++) {
                      $dhwSchedule .= 'n,';
                      $dhwSchedule .= $features["data"][$i]["properties"]['entries']['value']['tue'][$j]['start'] . ',';
                      $dhwSchedule .= $features["data"][$i]["properties"]['entries']['value']['tue'][$j]['end'];
                      if ($j < $n-1) {
                          $dhwSchedule .= ',';
                      }
                  }
                  $dhwSchedule .= ';';
        
                  $n = count($features["data"][$i]["properties"]['entries']['value']['wed']);
                  for ($j=0; $j<$n; $j++) {
                      $dhwSchedule .= 'n,';
                      $dhwSchedule .= $features["data"][$i]["properties"]['entries']['value']['wed'][$j]['start'] . ',';
                      $dhwSchedule .= $features["data"][$i]["properties"]['entries']['value']['wed'][$j]['end'];
                      if ($j < $n-1) {
                          $dhwSchedule .= ',';
                      }
                  }
                  $dhwSchedule .= ';';
        
                  $n = count($features["data"][$i]["properties"]['entries']['value']['thu']);
                  for ($j=0; $j<$n; $j++) {
                      $dhwSchedule .= 'n,';
                      $dhwSchedule .= $features["data"][$i]["properties"]['entries']['value']['thu'][$j]['start'] . ',';
                      $dhwSchedule .= $features["data"][$i]["properties"]['entries']['value']['thu'][$j]['end'];
                      if ($j < $n-1) {
                          $dhwSchedule .= ',';
                      }
                  }
                  $dhwSchedule .= ';';
        
                  $n = count($features["data"][$i]["properties"]['entries']['value']['fri']);
                  for ($j=0; $j<$n; $j++) {
                      $dhwSchedule .= 'n,';
                      $dhwSchedule .= $features["data"][$i]["properties"]['entries']['value']['fri'][$j]['start'] . ',';
                      $dhwSchedule .= $features["data"][$i]["properties"]['entries']['value']['fri'][$j]['end'];
                      if ($j < $n-1) {
                          $dhwSchedule .= ',';
                      }
                  }
                  $dhwSchedule .= ';';
        
                  $n = count($features["data"][$i]["properties"]['entries']['value']['sat']);
                  for ($j=0; $j<$n; $j++) {
                      $dhwSchedule .= 'n,';
                      $dhwSchedule .= $features["data"][$i]["properties"]['entries']['value']['sat'][$j]['start'] . ',';
                      $dhwSchedule .= $features["data"][$i]["properties"]['entries']['value']['sat'][$j]['end'];
                      if ($j < $n-1) {
                          $dhwSchedule .= ',';
                      }
                  }
                  $dhwSchedule .= ';';
        
                  $n = count($features["data"][$i]["properties"]['entries']['value']['sun']);
                  for ($j=0; $j<$n; $j++) {
                      $dhwSchedule .= 'n,';
                      $dhwSchedule .= $features["data"][$i]["properties"]['entries']['value']['sun'][$j]['start'] . ',';
                      $dhwSchedule .= $features["data"][$i]["properties"]['entries']['value']['sun'][$j]['end'];
                      if ($j < $n-1) {
                          $dhwSchedule .= ',';
                      }
                  }
                
                  $obj = $this->getCmd(null, 'dhwSchedule');
                  if (is_object($obj)) {
                      $obj->event($dhwSchedule);
                  }
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::HEATING_SCHEDULE) && $features["data"][$i]["isEnabled"] == true) {
                  $heatingSchedule = '';

                  $n = count($features["data"][$i]["properties"]['entries']['value']['mon']);
                  for ($j=0; $j<$n; $j++) {
                      $heatingSchedule .= substr($features["data"][$i]["properties"]['entries']['value']['mon'][$j]['mode'], 0, 1) . ',';
                      $heatingSchedule .= $features["data"][$i]["properties"]['entries']['value']['mon'][$j]['start'] . ',';
                      $heatingSchedule .= $features["data"][$i]["properties"]['entries']['value']['mon'][$j]['end'];
                      if ($j < $n-1) {
                          $heatingSchedule .= ',';
                      }
                  }
                  $heatingSchedule .= ';';

                  $n = count($features["data"][$i]["properties"]['entries']['value']['tue']);
                  for ($j=0; $j<$n; $j++) {
                      $heatingSchedule .= substr($features["data"][$i]["properties"]['entries']['value']['tue'][$j]['mode'], 0, 1) . ',';
                      $heatingSchedule .= $features["data"][$i]["properties"]['entries']['value']['tue'][$j]['start'] . ',';
                      $heatingSchedule .= $features["data"][$i]["properties"]['entries']['value']['tue'][$j]['end'];
                      if ($j < $n-1) {
                          $heatingSchedule .= ',';
                      }
                  }
                  $heatingSchedule .= ';';

                  $n = count($features["data"][$i]["properties"]['entries']['value']['wed']);
                  for ($j=0; $j<$n; $j++) {
                      $heatingSchedule .= substr($features["data"][$i]["properties"]['entries']['value']['wed'][$j]['mode'], 0, 1) . ',';
                      $heatingSchedule .= $features["data"][$i]["properties"]['entries']['value']['wed'][$j]['start'] . ',';
                      $heatingSchedule .= $features["data"][$i]["properties"]['entries']['value']['wed'][$j]['end'];
                      if ($j < $n-1) {
                          $heatingSchedule .= ',';
                      }
                  }
                  $heatingSchedule .= ';';

                  $n = count($features["data"][$i]["properties"]['entries']['value']['thu']);
                  for ($j=0; $j<$n; $j++) {
                      $heatingSchedule .= substr($features["data"][$i]["properties"]['entries']['value']['thu'][$j]['mode'], 0, 1) . ',';
                      $heatingSchedule .= $features["data"][$i]["properties"]['entries']['value']['thu'][$j]['start'] . ',';
                      $heatingSchedule .= $features["data"][$i]["properties"]['entries']['value']['thu'][$j]['end'];
                      if ($j < $n-1) {
                          $heatingSchedule .= ',';
                      }
                  }
                  $heatingSchedule .= ';';

                  $n = count($features["data"][$i]["properties"]['entries']['value']['fri']);
                  for ($j=0; $j<$n; $j++) {
                      $heatingSchedule .= substr($features["data"][$i]["properties"]['entries']['value']['fri'][$j]['mode'], 0, 1) . ',';
                      $heatingSchedule .= $features["data"][$i]["properties"]['entries']['value']['fri'][$j]['start'] . ',';
                      $heatingSchedule .= $features["data"][$i]["properties"]['entries']['value']['fri'][$j]['end'];
                      if ($j < $n-1) {
                          $heatingSchedule .= ',';
                      }
                  }
                  $heatingSchedule .= ';';

                  $n = count($features["data"][$i]["properties"]['entries']['value']['sat']);
                  for ($j=0; $j<$n; $j++) {
                      $heatingSchedule .= substr($features["data"][$i]["properties"]['entries']['value']['sat'][$j]['mode'], 0, 1) . ',';
                      $heatingSchedule .= $features["data"][$i]["properties"]['entries']['value']['sat'][$j]['start'] . ',';
                      $heatingSchedule .= $features["data"][$i]["properties"]['entries']['value']['sat'][$j]['end'];
                      if ($j < $n-1) {
                          $heatingSchedule .= ',';
                      }
                  }
                  $heatingSchedule .= ';';

                  $n = count($features["data"][$i]["properties"]['entries']['value']['sun']);
                  for ($j=0; $j<$n; $j++) {
                      $heatingSchedule .= substr($features["data"][$i]["properties"]['entries']['value']['sun'][$j]['mode'], 0, 1) . ',';
                      $heatingSchedule .= $features["data"][$i]["properties"]['entries']['value']['sun'][$j]['start'] . ',';
                      $heatingSchedule .= $features["data"][$i]["properties"]['entries']['value']['sun'][$j]['end'];
                      if ($j < $n-1) {
                          $heatingSchedule .= ',';
                      }
                  }
                  $obj = $this->getCmd(null, 'heatingSchedule');
                  if (is_object($obj)) {
                      $obj->event($heatingSchedule);
                  }
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::HEATING_FROSTPROTECTION) && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["status"]["value"];
                  $obj = $this->getCmd(null, 'frostProtection');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              } elseif ($features["data"][$i]["feature"] == self::HEATING_BOILER_SENSORS_TEMPERATURE && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["value"]["value"];
                  $obj = $this->getCmd(null, 'boilerTemperature');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              } elseif ($features["data"][$i]["feature"] == self::PRESSURE_SUPPLY && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["value"]["value"];
                  $obj = $this->getCmd(null, 'pressureSupply');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              }
          }
          $date = new DateTime();
          $date = $date->format('d-m-Y H:i:s');
          $this->getCmd(null, 'refreshDate')->event($date);

          return;
      }

      public static function periodique()
      {
          $oldUserName = '';
          $oldPassword = '';
  
          $first = true;
          $tousPareils = true;
          foreach (self::byType('viessmannIot') as $viessmann) {
              if ($viessmann->getIsEnable() == 1) {
                  $userName = trim($viessmann->getConfiguration('userName', ''));
                  $password = trim($viessmann->getConfiguration('password', ''));
                  if ($first == false) {
                      if (($userName != $oldUserName) || ($password != $oldPassword)) {
                          $tousPareils = false;
                      }
                  }
                  $oldUserName = $userName;
                  $oldPassword = $password;
                  $first = false;
              }
          }
  
          if ($tousPareils == true) {
              $viessmann = null;
              $first = true;
              foreach (self::byType('viessmannIot') as $viessmann) {
                  if ($viessmann->getIsEnable() == 1) {
                      if ($first == true) {
                          $viessmannApi = $viessmann->getViessmann();
                          $first = false;
                      }
  
                      if ($viessmannApi != null) {
                          $viessmann->rafraichir($viessmannApi);
                      }
                  }
              }
              unset($viessmannApi);
          } else {
              $viessmann = null;
              foreach (self::byType('viessmannIot') as $viessmann) {
                  if ($viessmann->getIsEnable() == 1) {
                      $viessmannApi = $viessmann->getViessmann();
                      if ($viessmannApi != null) {
                          $viessmann->rafraichir($viessmannApi);
                          unset($viessmannApi);
                      }
                  }
              }
          }
      }
  
      // Set Dhw Temperature
      //
      public function setDhwTemperature($temperature)
      {
          $viessmannApi = $this->getViessmann();
          if ($viessmannApi == null) {
              return;
          }

          $data = "{\"temperature\": $temperature}";
          $viessmannApi->setFeature(self::DHW_TEMPERATURE, "setTargetTemperature", $data);
          
          unset($viessmannApi);
      }

      // Set Mode
      //
      public function setMode($mode)
      {
          $circuitId = trim($this->getConfiguration('circuitId', '0'));

          $viessmannApi = $this->getViessmann();
          if ($viessmannApi == null) {
              return;
          }
        
          $data = "{\"mode\":\"" . $mode . "\"}";
          $viessmannApi->setFeature($this->buildFeature($circuitId, self::ACTIVE_MODE), "setMode", $data);
          unset($viessmannApi);

          $this->getCmd(null, 'activeMode')->event($mode);
      }

      // Set Comfort Program Temperature
      //
      public function setComfortProgramTemperature($temperature)
      {
          $circuitId = trim($this->getConfiguration('circuitId', '0'));

          $viessmannApi = $this->getViessmann();
          if ($viessmannApi == null) {
              return;
          }
        
          $data = "{\"targetTemperature\": $temperature}";
          $viessmannApi->setFeature($this->buildFeature($circuitId, self::COMFORT_PROGRAM), "setTemperature", $data);

          unset($viessmannApi);
      }

      // Set Normal Program Temperature
      //
      public function setNormalProgramTemperature($temperature)
      {
          $circuitId = trim($this->getConfiguration('circuitId', '0'));

          $viessmannApi = $this->getViessmann();
          if ($viessmannApi == null) {
              return;
          }
        
          $data = "{\"targetTemperature\": $temperature}";
          $viessmannApi->setFeature($this->buildFeature($circuitId, self::NORMAL_PROGRAM), "setTemperature", $data);

          unset($viessmannApi);
      }

      // Set Reduced Program Temperature
      //
      public function setReducedProgramTemperature($temperature)
      {
          $circuitId = trim($this->getConfiguration('circuitId', '0'));

          $viessmannApi = $this->getViessmann();
          if ($viessmannApi == null) {
              return;
          }
        
          $data = "{\"targetTemperature\": $temperature}";
          $viessmannApi->setFeature($this->buildFeature($circuitId, self::REDUCED_PROGRAM), "setTemperature", $data);

          unset($viessmannApi);
      }

      // Start One Time Dhw Charge
      //
      public function startOneTimeDhwCharge()
      {
          $viessmannApi = $this->getViessmann();
          if ($viessmannApi == null) {
              return;
          }
        
          $data = "{}";
          $viessmannApi->setFeature(self::HEATING_DHW_ONETIMECHARGE, "activate", $data);
          unset($viessmannApi);

          $this->getCmd(null, 'isOneTimeDhwCharge')->event(1);
      }

      // Stop One Time Dhw Charge
      //
      public function stopOneTimeDhwCharge()
      {
          $viessmannApi = $this->getViessmann();
          if ($viessmannApi == null) {
              return;
          }
        
          $data = "{}";
          $viessmannApi->setFeature(self::HEATING_DHW_ONETIMECHARGE, "deactivate", $data);
          unset($viessmannApi);

          $this->getCmd(null, 'isOneTimeDhwCharge')->event(0);
      }

      // Activate Comfort Program
      //
      public function activateComfortProgram()
      {
          $circuitId = trim($this->getConfiguration('circuitId', '0'));

          $viessmannApi = $this->getViessmann();
          if ($viessmannApi == null) {
              return;
          }
        
          $data = "{}";
          $viessmannApi->setFeature($this->buildFeature($circuitId, self::COMFORT_PROGRAM), "activate", $data);
          unset($viessmannApi);
 
          $this->getCmd(null, 'isActivateComfortProgram')->event(1);
      }

      // deActivate Comfort Program
      //
      public function deActivateComfortProgram()
      {
          $circuitId = trim($this->getConfiguration('circuitId', '0'));

          $viessmannApi = $this->getViessmann();
          if ($viessmannApi == null) {
              return;
          }
        
          $data = "{}";
          $viessmannApi->setFeature($this->buildFeature($circuitId, self::COMFORT_PROGRAM), "deactivate", $data);
          unset($viessmannApi);

          $this->getCmd(null, 'isActivateComfortProgram')->event(0);
      }

      // Activate Eco Program
      //
      public function activateEcoProgram()
      {
          $circuitId = trim($this->getConfiguration('circuitId', '0'));

          $viessmannApi = $this->getViessmann();
          if ($viessmannApi == null) {
              return;
          }
        
          $data = "{}";
          $viessmannApi->setFeature($this->buildFeature($circuitId, self::ECO_PROGRAM), "activate", $data);
          unset($viessmannApi);

          $this->getCmd(null, 'isActivateEcoProgram')->event(1);
      }

      // deActivate Eco Program
      //
      public function deActivateEcoProgram()
      {
          $circuitId = trim($this->getConfiguration('circuitId', '0'));

          $viessmannApi = $this->getViessmann();
          if ($viessmannApi == null) {
              return;
          }
        
          $data = "{}";
          $viessmannApi->setFeature($this->buildFeature($circuitId, self::ECO_PROGRAM), "deactivate", $data);
          unset($viessmannApi);

          $this->getCmd(null, 'isActivateEcoProgram')->event(0);
      }

      // Set Slope
      //
      public function setSlope($slope)
      {
          $circuitId = trim($this->getConfiguration('circuitId', '0'));

          $viessmannApi = $this->getViessmann();
          if ($viessmannApi == null) {
              return;
          }

          $obj = $this->getCmd(null, 'shift');
          $shift = $obj->execCmd();
        
          $data = "{\"shift\":" . $shift . ",\"slope\":" . round($slope, 1) . "}";
          $viessmannApi->setFeature($this->buildFeature($circuitId, self::HEATING_CURVE), "setCurve", $data);

          unset($viessmannApi);
      }

      // Set Shift
      //
      public function setShift($shift)
      {
          $circuitId = trim($this->getConfiguration('circuitId', '0'));

          $viessmannApi = $this->getViessmann();
          if ($viessmannApi == null) {
              return;
          }
        
          $obj = $this->getCmd(null, 'slope');
          $slope = $obj->execCmd();

          $data = "{\"shift\":" . $shift . ",\"slope\":" . round($slope, 1) . "}";
          $viessmannApi->setFeature($this->buildFeature($circuitId, self::HEATING_CURVE), "setCurve", $data);

          unset($viessmannApi);
      }

      public static function cron()
      {
          $maintenant = time();
          $minute = date("i", $maintenant);
          if (($minute % 2) == 0) {
              self::periodique();
          }
      }
      
      public static function cron5()
      {
          self::periodique();
      }
      
      public static function cron10()
      {
          self::periodique();
      }
      
      public static function cron15()
      {
          self::periodique();
      }
      
      public static function cron30()
      {
          self::periodique();
      }
      
      public static function cronHourly()
      {
          self::periodique();
      }
      
      // Fonction exécutée automatiquement avant la création de l'équipement
      //
      public function preInsert()
      {
      }

      // Fonction exécutée automatiquement après la création de l'équipement
      //
      public function postInsert()
      {
      }

      // Fonction exécutée automatiquement avant la mise à jour de l'équipement
      //
      public function preUpdate()
      {
      }

      // Fonction exécutée automatiquement après la mise à jour de l'équipement
      //
      public function postUpdate()
      {
      }

      // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
      //
      public function preSave()
      {
      }

      // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
      //
      public function postSave()
      {
          $obj = $this->getCmd(null, 'refresh');
          if (!is_object($obj)) {
              $obj = new viessmannIotCmd();
              $obj->setName(__('Rafraichir', __FILE__));
          }
          $obj->setEqLogic_id($this->getId());
          $obj->setLogicalId('refresh');
          $obj->setType('action');
          $obj->setSubType('other');
          $obj->save();

          $obj = $this->getCmd(null, 'refreshDate');
          if (!is_object($obj)) {
              $obj = new viessmannIotCmd();
              $obj->setName(__('Date rafraichissement', __FILE__));
              $obj->setIsVisible(1);
              $obj->setIsHistorized(0);
          }
          $obj->setEqLogic_id($this->getId());
          $obj->setType('info');
          $obj->setSubType('string');
          $obj->setLogicalId('refreshDate');
          $obj->save();
      }


      // Fonction exécutée automatiquement avant la suppression de l'équipement
      //
      public function preRemove()
      {
      }

      // Fonction exécutée automatiquement après la suppression de l'équipement
      //
      public function postRemove()
      {
      }

      private function buildFeature($circuitId, $feature)
      {
          return self::HEATING_CIRCUITS . "." . $circuitId . "." . $feature;
      }
  }
  
  class viessmannIotCmd extends cmd
  {
      // Exécution d'une commande
      //
      public function execute($_options = array())
      {
          $eqlogic = $this->getEqLogic();
          if ($this->getLogicalId() == 'refresh') {
              $viessmannApi = $eqlogic->getViessmann();
              if ($viessmannApi !== null) {
                  $eqlogic->rafraichir($viessmannApi);
                  unset($viessmannApi);
              }
          } elseif ($this->getLogicalId() == 'startOneTimeDhwCharge') {
              $eqlogic->startOneTimeDhwCharge();
          } elseif ($this->getLogicalId() == 'stopOneTimeDhwCharge') {
              $eqlogic->stopOneTimeDhwCharge();
          } elseif ($this->getLogicalId() == 'activateComfortProgram') {
              $eqlogic->activateComfortProgram();
          } elseif ($this->getLogicalId() == 'deActivateComfortProgram') {
              $eqlogic->deActivateComfortProgram();
          } elseif ($this->getLogicalId() == 'activateEcoProgram') {
              $eqlogic->activateEcoProgram();
          } elseif ($this->getLogicalId() == 'deActivateEcoProgram') {
              $eqlogic->deActivateEcoProgram();
          } elseif ($this->getLogicalId() == 'modeStandby') {
              $eqlogic->setMode('standby');
          } elseif ($this->getLogicalId() == 'modeDhw') {
              $eqlogic->setMode('dhw');
          } elseif ($this->getLogicalId() == 'modeHeating') {
              $eqlogic->setMode('heating');
          } elseif ($this->getLogicalId() == 'modeDhwAndHeating') {
              $eqlogic->setMode('dhwAndHeating');
          } elseif ($this->getLogicalId() == 'modeForcedReduced') {
              $eqlogic->setMode('forcedReduced');
          } elseif ($this->getLogicalId() == 'modeForcedNormal') {
              $eqlogic->setMode('forcedNormal');
          } elseif ($this->getLogicalId() == 'dhwSlider') {
              if (!isset($_options['slider']) || $_options['slider'] == '' || !is_numeric(intval($_options['slider']))) {
                  return;
              }
              $eqlogic->getCmd(null, 'dhwTemperature')->event($_options['slider']);
              $eqlogic->setDhwTemperature($_options['slider']);
          } elseif ($this->getLogicalId() == 'comfortProgramSlider') {
              if (!isset($_options['slider']) || $_options['slider'] == '' || !is_numeric(intval($_options['slider']))) {
                  return;
              }
              $eqlogic->getCmd(null, 'comfortProgramTemperature')->event($_options['slider']);
              $eqlogic->setComfortProgramTemperature($_options['slider']);
          } elseif ($this->getLogicalId() == 'normalProgramSlider') {
              if (!isset($_options['slider']) || $_options['slider'] == '' || !is_numeric(intval($_options['slider']))) {
                  return;
              }
              $eqlogic->getCmd(null, 'normalProgramTemperature')->event($_options['slider']);
              $eqlogic->setNormalProgramTemperature($_options['slider']);
          } elseif ($this->getLogicalId() == 'reducedProgramSlider') {
              if (!isset($_options['slider']) || $_options['slider'] == '' || !is_numeric(intval($_options['slider']))) {
                  return;
              }
              $eqlogic->getCmd(null, 'reducedProgramTemperature')->event($_options['slider']);
              $eqlogic->setReducedProgramTemperature($_options['slider']);
          } elseif ($this->getLogicalId() == 'shiftSlider') {
              if (!isset($_options['slider']) || $_options['slider'] == '' || !is_numeric(intval($_options['slider']))) {
                  return;
              }
              $eqlogic->getCmd(null, 'shift')->event($_options['slider']);
              $eqlogic->setShift($_options['slider']);
          } elseif ($this->getLogicalId() == 'slopeSlider') {
              if (!isset($_options['slider']) || $_options['slider'] == '' || !is_numeric(intval($_options['slider']))) {
                  return;
              }
              $eqlogic->getCmd(null, 'slope')->event($_options['slider']);
              $eqlogic->setSlope($_options['slider']);
          }
      }
  }
