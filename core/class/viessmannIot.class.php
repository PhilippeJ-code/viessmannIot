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
      const HEATING_GAS_CONSUMPTION_DHW = "heating.gas.consumption.dhw";
      const HEATING_GAS_CONSUMPTION_HEATING = "heating.gas.consumption.heating";
      const HEATING_GAS_CONSUMPTION_TOTAL = "heating.gas.consumption.total";
      const HEATING_POWER_CONSUMPTION_TOTAL = "heating.power.consumption.total";
      const HEATING_ERRORS_ACTIVE = "heating.errors.active";
      const HEATING_ERRORS = "heating.errors";
      const HEATING_ERRORS_HISTORY = "heating.errors.history";
      const HEATING_SERVICE_TIMEBASED = "heating.service.timeBased";
      const HEATING_BURNER_STATISTICS = "heating.burner.statistics";
      const HEATING_BURNER_MODULATION = "heating.burner.modulation";
      
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
              if ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::PUMP_STATUS) && $features["data"][$i]["isEnabled"] == true) {
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
                      $obj = new viessmannIotCmd();
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
              } elseif ($features["data"][$i]["feature"] == self::HEATING_GAS_CONSUMPTION_TOTAL && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'totalGazConsumptionDay');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Consommation journalière gaz', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('string');
                  $obj->setLogicalId('totalGazConsumptionDay');
                  $obj->save();
            
                  $obj = $this->getCmd(null, 'totalGazConsumptionWeek');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Consommation hebdomadaire gaz', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('string');
                  $obj->setLogicalId('totalGazConsumptionWeek');
                  $obj->save();
            
                  $obj = $this->getCmd(null, 'totalGazConsumptionMonth');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Consommation mensuelle gaz', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('string');
                  $obj->setLogicalId('totalGazConsumptionMonth');
                  $obj->save();
            
                  $obj = $this->getCmd(null, 'totalGazConsumptionYear');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Consommation annuelle gaz', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('string');
                  $obj->setLogicalId('totalGazConsumptionYear');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == self::HEATING_GAS_CONSUMPTION_DHW && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'dhwGazConsumptionDay');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Consommation journalière gaz eau chaude', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('string');
                  $obj->setLogicalId('dhwGazConsumptionDay');
                  $obj->save();
                  
                  $obj = $this->getCmd(null, 'dhwGazConsumptionWeek');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Consommation hebdomadaire gaz eau chaude', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('string');
                  $obj->setLogicalId('dhwGazConsumptionWeek');
                  $obj->save();
                  
                  $obj = $this->getCmd(null, 'dhwGazConsumptionMonth');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Consommation mensuelle gaz eau chaude', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('string');
                  $obj->setLogicalId('dhwGazConsumptionMonth');
                  $obj->save();
                  
                  $obj = $this->getCmd(null, 'dhwGazConsumptionYear');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Consommation annuelle gaz eau chaude', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('string');
                  $obj->setLogicalId('dhwGazConsumptionYear');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == self::HEATING_GAS_CONSUMPTION_HEATING && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'heatingGazConsumptionDay');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Consommation journalière gaz chauffage', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('string');
                  $obj->setLogicalId('heatingGazConsumptionDay');
                  $obj->save();
                
                  $obj = $this->getCmd(null, 'heatingGazConsumptionWeek');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Consommation hebdomadaire gaz chauffage', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('string');
                  $obj->setLogicalId('heatingGazConsumptionWeek');
                  $obj->save();
                
                  $obj = $this->getCmd(null, 'heatingGazConsumptionMonth');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Consommation mensuelle gaz chauffage', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('string');
                  $obj->setLogicalId('heatingGazConsumptionMonth');
                  $obj->save();
                
                  $obj = $this->getCmd(null, 'heatingGazConsumptionYear');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Consommation annuelle gaz chauffage', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('string');
                  $obj->setLogicalId('heatingGazConsumptionYear');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == self::HEATING_POWER_CONSUMPTION_TOTAL && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'heatingPowerConsumptionDay');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Consommation journalière électrique', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('string');
                  $obj->setLogicalId('heatingPowerConsumptionDay');
                  $obj->save();
        
                  $obj = $this->getCmd(null, 'heatingPowerConsumptionWeek');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Consommation hebdomadaire électrique', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('string');
                  $obj->setLogicalId('heatingPowerConsumptionWeek');
                  $obj->save();
        
                  $obj = $this->getCmd(null, 'heatingPowerConsumptionMonth');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Consommation mensuelle électrique', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('string');
                  $obj->setLogicalId('heatingPowerConsumptionMonth');
                  $obj->save();
        
                  $obj = $this->getCmd(null, 'heatingPowerConsumptionYear');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Consommation annuelle électrique', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('string');
                  $obj->setLogicalId('heatingPowerConsumptionYear');
                  $obj->save();
              } elseif (($features["data"][$i]["feature"] == self::HEATING_ERRORS_ACTIVE && $features["data"][$i]["isEnabled"] == true) ||
                    ($features["data"][$i]["feature"] == self::HEATING_ERRORS_HISTORY && $features["data"][$i]["isEnabled"] == true)) {
                  $obj = $this->getCmd(null, 'errors');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Erreurs', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('string');
                  $obj->setLogicalId('errors');
                  $obj->save();
                
                  $obj = $this->getCmd(null, 'currentError');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Erreur courante', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('string');
                  $obj->setLogicalId('currentError');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == self::HEATING_SERVICE_TIMEBASED && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'lastServiceDate');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Date dernier entretien', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('string');
                  $obj->setLogicalId('lastServiceDate');
                  $obj->save();
            
                  $obj = $this->getCmd(null, 'serviceInterval');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Intervalle entretien', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('numeric');
                  $obj->setLogicalId('serviceInterval');
                  $obj->save();
            
                  $obj = $this->getCmd(null, 'monthSinceService');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Mois entretien', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('numeric');
                  $obj->setLogicalId('monthSinceService');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == self::HEATING_BURNER_STATISTICS && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'heatingBurnerHoursPerDay');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Heures fonctionnement brûleur par jour', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(1);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('numeric');
                  $obj->setLogicalId('heatingBurnerHoursPerDay');
                  $obj->save();
            
                  $obj = $this->getCmd(null, 'heatingBurnerHours');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Heures fonctionnement brûleur', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('numeric');
                  $obj->setLogicalId('heatingBurnerHours');
                  $obj->save();
            
                  $obj = $this->getCmd(null, 'heatingBurnerStarts');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Démarrages du brûleur', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('numeric');
                  $obj->setLogicalId('heatingBurnerStarts');
                  $obj->save();
              } elseif ($features["data"][$i]["feature"] == self::HEATING_BURNER_MODULATION && $features["data"][$i]["isEnabled"] == true) {
                  $obj = $this->getCmd(null, 'heatingBurnerModulation');
                  if (!is_object($obj)) {
                      $obj = new viessmannIotCmd();
                      $obj->setName(__('Modulation de puissance', __FILE__));
                      $obj->setIsVisible(1);
                      $obj->setIsHistorized(0);
                  }
                  $obj->setEqLogic_id($this->getId());
                  $obj->setType('info');
                  $obj->setSubType('numeric');
                  $obj->setLogicalId('heatingBurnerModulation');
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
          $facteurConversionGaz = floatval($this->getConfiguration('facteurConversionGaz', 1));
          if ($facteurConversionGaz == 0) {
              $facteurConversionGaz = 1;
          }
  
          $nbr = 0;
          $erreurs = '';
          $erreurCourante = '';

          $outsideTemperature = 99;
          $roomTemperature = 99;

          $comfortProgramTemperature = 99;
          $normalProgramTemperature = 99;
          $reducedProgramTemperature = 99;
          $activeProgram = '';

          $heatingBurnerHours = -1;

          $viessmannApi->getFeatures();
          $features = $viessmannApi->getArrayFeatures();
          $nbrFeatures = count($features["data"]);
          for ($i=0; $i<$nbrFeatures; $i++) {
              if ($features["data"][$i]["feature"] == self::OUTSIDE_TEMPERATURE && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["value"]["value"];
                  $outsideTemperature = $val;
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
                  $activeProgram = $val;
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
                  $comfortProgramTemperature = $val;
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
                  $normalProgramTemperature = $val;
                  $obj = $this->getCmd(null, 'normalProgramTemperature');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::REDUCED_PROGRAM) && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["temperature"]["value"];
                  $reducedProgramTemperature = $val;
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
                  $roomTemperature = $val;
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
              } elseif ($features["data"][$i]["feature"] == self::HEATING_GAS_CONSUMPTION_TOTAL && $features["data"][$i]["isEnabled"] == true) {
                  $heatingGazConsumptions = array();
                  $n = count($features["data"][$i]["properties"]['day']['value']);
                  for ($j=0; $j<$n; $j++) {
                      $heatingGazConsumptions[$j] = $features["data"][$i]["properties"]['day']['value'][$j]*$facteurConversionGaz;
                  }

                  $day = '';
                  $n = 0;
                  foreach ($heatingGazConsumptions as $heatingGazConsumption) {
                      if ($day !== '') {
                          $day = ',' . $day;
                      }
                      $day = $heatingGazConsumption*$facteurConversionGaz . $day;
                      $n++;
                  }
                  $obj = $this->getCmd(null, 'totalGazConsumptionDay');
                  if (is_object($obj)) {
                      $obj->event($day);
                  }

                  $heatingGazConsumptions = array();
                  $n = count($features["data"][$i]["properties"]['week']['value']);
                  for ($j=0; $j<$n; $j++) {
                      $heatingGazConsumptions[$j] = $features["data"][$i]["properties"]['week']['value'][$j]*$facteurConversionGaz;
                  }

                  $week = '';
                  $n = 0;
                  foreach ($heatingGazConsumptions as $heatingGazConsumption) {
                      if ($week !== '') {
                          $week = ',' . $week;
                      }
                      $week = $heatingGazConsumption*$facteurConversionGaz . $week;
                      $n++;
                  }
                  $obj = $this->getCmd(null, 'totalGazConsumptionWeek');
                  if (is_object($obj)) {
                      $obj->event($week);
                  }

                  $heatingGazConsumptions = array();
                  $n = count($features["data"][$i]["properties"]['month']['value']);
                  for ($j=0; $j<$n; $j++) {
                      $heatingGazConsumptions[$j] = $features["data"][$i]["properties"]['month']['value'][$j]*$facteurConversionGaz;
                  }

                  $month = '';
                  $n = 0;
                  foreach ($heatingGazConsumptions as $heatingGazConsumption) {
                      if ($month !== '') {
                          $month = ',' . $month;
                      }
                      $month = $heatingGazConsumption*$facteurConversionGaz . $month;
                      $totalMonth[$n] += $heatingGazConsumption*$facteurConversionGaz;
                      $n++;
                  }
                  $obj = $this->getCmd(null, 'totalGazConsumptionMonth');
                  if (is_object($obj)) {
                      $obj->event($month);
                  }

                  $heatingGazConsumptions = array();
                  $n = count($features["data"][$i]["properties"]['year']['value']);
                  for ($j=0; $j<$n; $j++) {
                      $heatingGazConsumptions[$j] = $features["data"][$i]["properties"]['year']['value'][$j]*$facteurConversionGaz;
                  }

                  $year = '';
                  $n = 0;
                  foreach ($heatingGazConsumptions as $heatingGazConsumption) {
                      if ($year !== '') {
                          $year = ',' . $year;
                      }
                      $year = $heatingGazConsumption*$facteurConversionGaz . $year;
                      $totalYear[$n] += $heatingGazConsumption*$facteurConversionGaz;
                      $n++;
                  }
                  $obj = $this->getCmd(null, 'totalGazConsumptionYear');
                  if (is_object($obj)) {
                      $obj->event($year);
                  }
              } elseif ($features["data"][$i]["feature"] == self::HEATING_GAS_CONSUMPTION_DHW && $features["data"][$i]["isEnabled"] == true) {
                  $dhwGazConsumtions = array();
                  $n = count($features["data"][$i]["properties"]['day']['value']);
                  for ($j=0; $j<$n; $j++) {
                      $dhwGazConsumtions[$j] = $features["data"][$i]["properties"]['day']['value'][$j]*$facteurConversionGaz;
                  }
                  $this->getCmd(null, 'dhwGazConsumption')->event($dhwGazConsumtions[0]);
  
                  $day = '';
                  $n = 0;
                  foreach ($dhwGazConsumtions as $dhwGazConsumtion) {
                      if ($day !== '') {
                          $day = ',' . $day;
                      }
                      $day = $dhwGazConsumtion*$facteurConversionGaz . $day;
                      $n++;
                  }
                  $obj = $this->getCmd(null, 'dhwGazConsumptionDay');
                  if (is_object($obj)) {
                      $obj->event($day);
                  }
  
                  $dhwGazConsumtions = array();
                  $n = count($features["data"][$i]["properties"]['week']['value']);
                  for ($j=0; $j<$n; $j++) {
                      $dhwGazConsumtions[$j] = $features["data"][$i]["properties"]['week']['value'][$j]*$facteurConversionGaz;
                  }
  
                  $week = '';
                  $n = 0;
                  foreach ($dhwGazConsumtions as $dhwGazConsumtion) {
                      if ($week !== '') {
                          $week = ',' . $week;
                      }
                      $week = $dhwGazConsumtion*$facteurConversionGaz . $week;
                      $n++;
                  }
                  $obj = $this->getCmd(null, 'dhwGazConsumptionWeek');
                  if (is_object($obj)) {
                      $obj->event($week);
                  }
  
                  $dhwGazConsumtions = array();
                  $n = count($features["data"][$i]["properties"]['month']['value']);
                  for ($j=0; $j<$n; $j++) {
                      $dhwGazConsumtions[$j] = $features["data"][$i]["properties"]['month']['value'][$j]*$facteurConversionGaz;
                  }
  
                  $month = '';
                  $n = 0;
                  foreach ($dhwGazConsumtions as $dhwGazConsumtion) {
                      if ($month !== '') {
                          $month = ',' . $month;
                      }
                      $month = $dhwGazConsumtion*$facteurConversionGaz . $month;
                      $totalMonth[$n] += $dhwGazConsumtion*$facteurConversionGaz;
                      $n++;
                  }
                  $obj = $this->getCmd(null, 'dhwGazConsumptionMonth');
                  if (is_object($obj)) {
                      $obj->event($month);
                  }
  
                  $dhwGazConsumtions = array();
                  $n = count($features["data"][$i]["properties"]['year']['value']);
                  for ($j=0; $j<$n; $j++) {
                      $dhwGazConsumtions[$j] = $features["data"][$i]["properties"]['year']['value'][$j]*$facteurConversionGaz;
                  }
  
                  $year = '';
                  $n = 0;
                  foreach ($dhwGazConsumtions as $dhwGazConsumtion) {
                      if ($year !== '') {
                          $year = ',' . $year;
                      }
                      $year = $dhwGazConsumtion*$facteurConversionGaz . $year;
                      $totalYear[$n] += $dhwGazConsumtion*$facteurConversionGaz;
                      $n++;
                  }
                  $obj = $this->getCmd(null, 'dhwGazConsumptionYear');
                  if (is_object($obj)) {
                      $obj->event($year);
                  }
              } elseif ($features["data"][$i]["feature"] == self::HEATING_GAS_CONSUMPTION_HEATING && $features["data"][$i]["isEnabled"] == true) {
                  $heatingGazConsumptions = array();
                  $n = count($features["data"][$i]["properties"]['day']['value']);
                  for ($j=0; $j<$n; $j++) {
                      $heatingGazConsumptions[$j] = $features["data"][$i]["properties"]['day']['value'][$j]*$facteurConversionGaz;
                  }
                  $this->getCmd(null, 'heatingGazConsumption')->event($heatingGazConsumptions[0]);

                  $day = '';
                  $n = 0;
                  foreach ($heatingGazConsumptions as $heatingGazConsumption) {
                      if ($day !== '') {
                          $day = ',' . $day;
                      }
                      $day = $heatingGazConsumption*$facteurConversionGaz . $day;
                      $n++;
                  }
                  $obj = $this->getCmd(null, 'heatingGazConsumptionDay');
                  if (is_object($obj)) {
                      $obj->event($day);
                  }
  
                  $heatingGazConsumptions = array();
                  $n = count($features["data"][$i]["properties"]['week']['value']);
                  for ($j=0; $j<$n; $j++) {
                      $heatingGazConsumptions[$j] = $features["data"][$i]["properties"]['week']['value'][$j]*$facteurConversionGaz;
                  }
  
                  $week = '';
                  $n = 0;
                  foreach ($heatingGazConsumptions as $heatingGazConsumption) {
                      if ($week !== '') {
                          $week = ',' . $week;
                      }
                      $week = $heatingGazConsumption*$facteurConversionGaz . $week;
                      $n++;
                  }
                  $obj = $this->getCmd(null, 'heatingGazConsumptionMWeek');
                  if (is_object($obj)) {
                      $obj->event($week);
                  }
  
                  $heatingGazConsumptions = array();
                  $n = count($features["data"][$i]["properties"]['month']['value']);
                  for ($j=0; $j<$n; $j++) {
                      $heatingGazConsumptions[$j] = $features["data"][$i]["properties"]['month']['value'][$j]*$facteurConversionGaz;
                  }
  
                  $month = '';
                  $n = 0;
                  foreach ($heatingGazConsumptions as $heatingGazConsumption) {
                      if ($month !== '') {
                          $month = ',' . $month;
                      }
                      $month = $heatingGazConsumption*$facteurConversionGaz . $month;
                      $totalMonth[$n] += $heatingGazConsumption*$facteurConversionGaz;
                      $n++;
                  }
                  $obj = $this->getCmd(null, 'heatingGazConsumptionMonth');
                  if (is_object($obj)) {
                      $obj->event($month);
                  }
   
                  $heatingGazConsumptions = array();
                  $n = count($features["data"][$i]["properties"]['year']['value']);
                  for ($j=0; $j<$n; $j++) {
                      $heatingGazConsumptions[$j] = $features["data"][$i]["properties"]['year']['value'][$j]*$facteurConversionGaz;
                  }
  
                  $year = '';
                  $n = 0;
                  foreach ($heatingGazConsumptions as $heatingGazConsumption) {
                      if ($year !== '') {
                          $year = ',' . $year;
                      }
                      $year = $heatingGazConsumption*$facteurConversionGaz . $year;
                      $totalYear[$n] += $heatingGazConsumption*$facteurConversionGaz;
                      $n++;
                  }
                  $obj = $this->getCmd(null, 'heatingGazConsumptionYear');
                  if (is_object($obj)) {
                      $obj->event($year);
                  }
              } elseif ($features["data"][$i]["feature"] == self::HEATING_POWER_CONSUMPTION_TOTAL && $features["data"][$i]["isEnabled"] == true) {
                  $heatingPowerConsumptions = array();
                  $n = count($features["data"][$i]["properties"]['day']['value']);
                  for ($j=0; $j<$n; $j++) {
                      $heatingPowerConsumptions[$j] = $features["data"][$i]["properties"]['day']['value'][$j];
                  }
                  $this->getCmd(null, 'heatingPowerConsumption')->event($heatingPowerConsumptions[0]);
                  
                  $day = '';
                  foreach ($heatingPowerConsumptions as $heatingPowerConsumption) {
                      if ($day !== '') {
                          $day = ',' . $day;
                      }
                      $day = $heatingPowerConsumption . $day;
                  }
                  $obj = $this->getCmd(null, 'heatingPowerConsumptionDay');
                  if (is_object($obj)) {
                      $obj->event($day);
                  }
        
                  $heatingPowerConsumptions = array();
                  $n = count($features["data"][$i]["properties"]['week']['value']);
                  for ($j=0; $j<$n; $j++) {
                      $heatingPowerConsumptions[$j] = $features["data"][$i]["properties"]['week']['value'][$j];
                  }
                  $week = '';
                  foreach ($heatingPowerConsumptions as $heatingPowerConsumption) {
                      if ($week !== '') {
                          $week = ',' . $week;
                      }
                      $week = $heatingPowerConsumption . $week;
                  }
                  $obj = $this->getCmd(null, 'heatingPowerConsumptionWeek');
                  if (is_object($obj)) {
                      $obj->event($week);
                  }
        
                  $heatingPowerConsumptions = array();
                  $n = count($features["data"][$i]["properties"]['month']['value']);
                  for ($j=0; $j<$n; $j++) {
                      $heatingPowerConsumptions[$j] = $features["data"][$i]["properties"]['month']['value'][$j];
                  }
                  $month = '';
                  foreach ($heatingPowerConsumptions as $heatingPowerConsumption) {
                      if ($month !== '') {
                          $month = ',' . $month;
                      }
                      $month = $heatingPowerConsumption . $month;
                  }
                  $obj = $this->getCmd(null, 'heatingPowerConsumptionMonth');
                  if (is_object($obj)) {
                      $obj->event($month);
                  }
        
                  $heatingPowerConsumptions = array();
                  $n = count($features["data"][$i]["properties"]['year']['value']);
                  for ($j=0; $j<$n; $j++) {
                      $heatingPowerConsumptions[$j] = $features["data"][$i]["properties"]['year']['value'][$j];
                  }
                  $year = '';
                  foreach ($heatingPowerConsumptions as $heatingPowerConsumption) {
                      if ($year !== '') {
                          $year = ',' . $year;
                      }
                      $year = $heatingPowerConsumption . $year;
                  }
                  $obj = $this->getCmd(null, 'heatingPowerConsumptionYear');
                  if (is_object($obj)) {
                      $obj->event($year);
                  }
              } elseif ($features["data"][$i]["feature"] == self::HEATING_ERRORS_ACTIVE && $features["data"][$i]["isEnabled"] == true) {
                  $n = count($features["data"][$i]['entries']['value']['new']);
                  for ($j=0; $j<$n; $j++) {
                      $timeStamp = substr($features["data"][$i]['entries']['value']['new'][$j]['timestamp'], 0, 19);
                      $timeStamp = str_replace('T', ' ', $timeStamp);
                      $errorCode = $features["data"][$i]['entries']['value']['new'][$j]['errorCode'];
                      if ($nbr < 10) {
                          if ($nbr > 0) {
                              $erreurs .= ';';
                          }
                          $erreurs .= 'AN;' . $timeStamp . ';' . $errorCode;
                          if ($erreurCourante == '') {
                              $erreurCourante = $errorCode;
                          }
                          $nbr++;
                      }
                  }
                  $n = count($features["data"][$i]['entries']['value']['current']);
                  for ($j=0; $j<$n; $j++) {
                      $timeStamp = substr($features["data"][$i]['entries']['value']['current'][$j]['timestamp'], 0, 19);
                      $timeStamp = str_replace('T', ' ', $timeStamp);
                      $errorCode = $features["data"][$i]['entries']['value']['current'][$j]['errorCode'];
                      if ($nbr < 10) {
                          if ($nbr > 0) {
                              $erreurs .= ';';
                          }
                          $erreurs .= 'AC;' . $timeStamp . ';' . $errorCode;
                          $nbr++;
                      }
                  }
                  $n = count($features["data"][$i]['entries']['value']['gone']);
                  for ($j=0; $j<$n; $j++) {
                      $timeStamp = substr($features["data"][$i]['entries']['value']['gone'][$j]['timestamp'], 0, 19);
                      $timeStamp = str_replace('T', ' ', $timeStamp);
                      $errorCode = $features["data"][$i]['entries']['value']['gone'][$j]['errorCode'];
                      if ($nbr < 10) {
                          if ($nbr > 0) {
                              $erreurs .= ';';
                          }
                          $erreurs .= 'AG;' . $timeStamp . ';' . $errorCode;
                          $nbr++;
                      }
                  }
              } elseif ($features["data"][$i]["feature"] == self::HEATING_ERRORS_HISTORY && $features["data"][$i]["isEnabled"] == true) {
                  $n = count($features["data"][$i]['entries']['value']['new']);
                  for ($j=0; $j<$n; $j++) {
                      $timeStamp = substr($features["data"][$i]['entries']['value']['new'][$j]['timestamp'], 0, 19);
                      $timeStamp = str_replace('T', ' ', $timeStamp);
                      $errorCode = $features["data"][$i]['entries']['value']['new'][$j]['errorCode'];
                      if ($nbr < 10) {
                          if ($nbr > 0) {
                              $erreurs .= ';';
                          }
                          $erreurs .= 'HN;' . $timeStamp . ';' . $errorCode;
                          if ($erreurCourante == '') {
                              $erreurCourante = $errorCode;
                          }
                          $nbr++;
                      }
                  }
                  $n = count($features["data"][$i]['entries']['value']['current']);
                  for ($j=0; $j<$n; $j++) {
                      $timeStamp = substr($features["data"][$i]['entries']['value']['current'][$j]['timestamp'], 0, 19);
                      $timeStamp = str_replace('T', ' ', $timeStamp);
                      $errorCode = $features["data"][$i]['entries']['value']['current'][$j]['errorCode'];
                      if ($nbr < 10) {
                          if ($nbr > 0) {
                              $erreurs .= ';';
                          }
                          $erreurs .= 'HC;' . $timeStamp . ';' . $errorCode;
                          $nbr++;
                      }
                  }
                  $n = count($features["data"][$i]['entries']['value']['gone']);
                  for ($j=0; $j<$n; $j++) {
                      $timeStamp = substr($features["data"][$i]['entries']['value']['gone'][$j]['timestamp'], 0, 19);
                      $timeStamp = str_replace('T', ' ', $timeStamp);
                      $errorCode = $features["data"][$i]['entries']['value']['gone'][$j]['errorCode'];
                      if ($nbr < 10) {
                          if ($nbr > 0) {
                              $erreurs .= ';';
                          }
                          $erreurs .= 'HG;' . $timeStamp . ';' . $errorCode;
                          $nbr++;
                      }
                  }
              } elseif ($features["data"][$i]["feature"] == self::HEATING_SERVICE_TIMEBASED && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["lastService"]["value"];
                  $val = substr($val, 0, 19);
                  $val = str_replace('T', ' ', $val);
                  $obj = $this->getCmd(null, 'lastService');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
                  $val = $features["data"][$i]["properties"]["serviceIntervalMonths"]["value"];
                  $obj = $this->getCmd(null, 'serviceInterval');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
                  $val = $features["data"][$i]["properties"]["activeMonthSinceLastService"]["value"];
                  $obj = $this->getCmd(null, 'monthSinceService');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              } elseif ($features["data"][$i]["feature"] == self::HEATING_BURNER_STATISTICS && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["hours"]["value"];
                  $heatingBurnerHours = $val;
                  $obj = $this->getCmd(null, 'heatingBurnerHours');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
                  $val = $features["data"][$i]["properties"]["starts"]["value"];
                  $obj = $this->getCmd(null, 'heatingBurnerStarts');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              } elseif ($features["data"][$i]["feature"] == self::HEATING_BURNER_MODULATION && $features["data"][$i]["isEnabled"] == true) {
                  $val = $features["data"][$i]["properties"]["value"]["value"];
                  $heatingBurnerHours = $val;
                  $obj = $this->getCmd(null, 'heatingBurnerModulation');
                  if (is_object($obj)) {
                      $obj->event($val);
                  }
              }
          }

          $obj = $this->getCmd(null, 'errors');
          if (is_object($obj)) {
              $obj->event($erreurs);
          }
          $obj = $this->getCmd(null, 'currentError');
          if (is_object($obj)) {
              $obj->event($erreurCourante);
          }

          if ($outsideTemperature == 99) {
              $outsideTemperature = jeedom::evaluateExpression($this->getConfiguration('temperature_exterieure'));
              if (!is_numeric($outsideTemperature)) {
                  $outsideTemperature = 99;
              } else {
                  $outsideTemperature = round($outsideTemperature, 1);
              }
          }
          $obj = $this->getCmd(null, 'outsideTemperature');
          if (is_object($obj)) {
              $obj->event($outsideTemperature);
          }

          if ($roomTemperature == 99) {
              $roomTemperature = jeedom::evaluateExpression($this->getConfiguration('temperature_interieure'));
              if (!is_numeric($roomTemperature)) {
                  $roomTemperature = 99;
              } else {
                  $roomTemperature = round($roomTemperature, 1);
              }
          }
          $obj = $this->getCmd(null, 'roomTemperature');
          if (is_object($obj)) {
              $obj->event($roomTemperature);
          }

          if ($activeProgram === 'comfort') {
              $this->getCmd(null, 'programTemperature')->event($comfortProgramTemperature);
          } elseif ($activeProgram === 'normal') {
              $this->getCmd(null, 'programTemperature')->event($normalProgramTemperature);
          } else {
              $this->getCmd(null, 'programTemperature')->event($reducedProgramTemperature);
          }
        
          if ($heatingBurnerHours != -1) {
              $jour = date("d", $now);
              $oldJour = $this->getCache('oldJour', -1);
              $oldHours = $this->getCache('oldHours', -1);
              if ($oldJour != $jour) {
                  if ($oldHours != -1) {
                      $dateVeille = time()-24*60*60;
                      $dateVeille = date('Y-m-d 00:00:00', $dateVeille);
                      $obj = $this->getCmd(null, 'heatingBurnerHoursPerDay');
                      if (is_object($obj)) {
                          $obj->event($heatingBurnerHours-$oldHours, $dateVeille);
                      }
                  }
                  $this->setCache('oldHours', $heatingBurnerHours);
                  $this->setCache('oldJour', $jour);
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

          $obj = $this->getCmd(null, 'programTemperature');
          if (!is_object($obj)) {
              $obj = new viessmannIotCmd();
              $obj->setName(__('Consigne radiateurs', __FILE__));
              $obj->setUnite('°C');
              $obj->setIsVisible(1);
              $obj->setIsHistorized(0);
          }
          $obj->setEqLogic_id($this->getId());
          $obj->setType('info');
          $obj->setSubType('numeric');
          $obj->setLogicalId('programTemperature');
          $obj->save();

          $obj = $this->getCmd(null, 'dhwGazConsumption');
          if (!is_object($obj)) {
              $obj = new viessmannIotCmd();
              $obj->setName(__('Consommation gaz eau chaude', __FILE__));
              $obj->setIsVisible(1);
              $obj->setIsHistorized(0);
          }
          $obj->setEqLogic_id($this->getId());
          $obj->setType('info');
          $obj->setSubType('numeric');
          $obj->setLogicalId('dhwGazConsumption');
          $obj->save();
  
          $obj = $this->getCmd(null, 'heatingGazConsumption');
          if (!is_object($obj)) {
              $obj = new viessmannIotCmd();
              $obj->setName(__('Consommation gaz chauffage', __FILE__));
              $obj->setIsVisible(1);
              $obj->setIsHistorized(0);
          }
          $obj->setEqLogic_id($this->getId());
          $obj->setType('info');
          $obj->setSubType('numeric');
          $obj->setLogicalId('heatingGazConsumption');
          $obj->save();

          $obj = $this->getCmd(null, 'heatingPowerConsumption');
          if (!is_object($obj)) {
              $obj = new viessmannIotCmd();
              $obj->setName(__('Consommation électrique', __FILE__));
              $obj->setIsVisible(1);
              $obj->setIsHistorized(0);
          }
          $obj->setEqLogic_id($this->getId());
          $obj->setType('info');
          $obj->setSubType('numeric');
          $obj->setLogicalId('heatingPowerConsumption');
          $obj->save();

          $obj = $this->getCmd(null, 'startHoliday');
          if (!is_object($obj)) {
              $obj = new viessmannIotCmd();
              $obj->setName(__('Date début', __FILE__));
              $obj->setIsVisible(1);
              $obj->setIsHistorized(0);
          }
          $obj->setEqLogic_id($this->getId());
          $obj->setType('info');
          $obj->setSubType('string');
          $obj->setLogicalId('startHoliday');
          $obj->save();
  
          $obj = $this->getCmd(null, 'endHoliday');
          if (!is_object($obj)) {
              $obj = new viessmannIotCmd();
              $obj->setName(__('Date fin', __FILE__));
              $obj->setIsVisible(1);
              $obj->setIsHistorized(0);
          }
          $obj->setEqLogic_id($this->getId());
          $obj->setType('info');
          $obj->setSubType('string');
          $obj->setLogicalId('endHoliday');
          $obj->save();
  
          $obj = $this->getCmd(null, 'startHolidayText');
          if (!is_object($obj)) {
              $obj = new viessmannIotCmd();
              $obj->setName(__('Date Début texte', __FILE__));
          }
          $obj->setEqLogic_id($this->getId());
          $obj->setLogicalId('startHolidayText');
          $obj->setType('action');
          $obj->setSubType('other');
          $obj->save();
  
          $obj = $this->getCmd(null, 'endHolidayText');
          if (!is_object($obj)) {
              $obj = new viessmannIotCmd();
              $obj->setName(__('Date Fin texte', __FILE__));
          }
          $obj->setEqLogic_id($this->getId());
          $obj->setLogicalId('endHolidayText');
          $obj->setType('action');
          $obj->setSubType('other');
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

      // Permet de modifier l'affichage du widget (également utilisable par les commandes)
      //
      public function toHtml($_version = 'dashboard')
      {
          $isWidgetPlugin = $this->getConfiguration('isWidgetPlugin');
          $displayWater = $this->getConfiguration('displayWater', '1');
          $displayGas = $this->getConfiguration('displayGas', '1');
          $displayPower = $this->getConfiguration('displayPower', '1');
          $circuitName = $this->getConfiguration('circuitName', 'Radiateurs');
          $uniteGaz = $this->getConfiguration('uniteGaz', 'm3');

          if (!$isWidgetPlugin) {
              return eqLogic::toHtml($_version);
          }

          $replace = $this->preToHtml($_version);
          if (!is_array($replace)) {
              return $replace;
          }
          $version = jeedom::versionAlias($_version);

          $obj = $this->getCmd(null, 'refresh');
          $replace["#idRefresh#"] = $obj->getId();

          $obj = $this->getCmd(null, 'isHeatingBurnerActive');
          if (is_object($obj)) {
              $replace["#isHeatingBurnerActive#"] = $obj->execCmd();
              $replace["#idIsHeatingBurnerActive#"] = $obj->getId();
          } else {
              $replace["#isHeatingBurnerActive#"] = -1;
              $replace["#idIsHeatingBurnerActive#"] = "#idIsHeatingBurnerActive#";
          }

          $obj = $this->getCmd(null, 'currentError');
          if (is_object($obj)) {
              $replace["#currentError#"] = $obj->execCmd();
              $replace["#idCurrentError#"] = $obj->getId();
          } else {
              $replace["#currentError#"] = '';
              $replace["#idCurrentError#"] = "#idCurrentError#";
          }

          $obj = $this->getCmd(null, 'outsideTemperature');
          $replace["#outsideTemperature#"] = $obj->execCmd();
          $replace["#idOutsideTemperature#"] = $obj->getId();

          $obj = $this->getCmd(null, 'activeProgram');
          if (is_object($obj)) {
              $replace["#activeProgram#"] = $obj->execCmd();
              $replace["#idActiveProgram#"] = $obj->getId();
          } else {
              $replace["#activeProgram#"] = '';
              $replace["#idActiveProgram#"] = "#idActiveProgram#";
          }

          $replace["#circuitName#"] = $circuitName;

          $obj = $this->getCmd(null, 'roomTemperature');
          $replace["#roomTemperature#"] = $obj->execCmd();
          $replace["#idRoomTemperature#"] = $obj->getId();

          $obj = $this->getCmd(null, 'programTemperature');
          $replace["#programTemperature#"] = $obj->execCmd();
          $replace["#idProgramTemperature#"] = $obj->getId();

          $obj = $this->getCmd(null, 'hotWaterStorageTemperature');
          if (is_object($obj)) {
              $replace["#hotWaterStorageTemperature#"] = $obj->execCmd();
              $replace["#idHotWaterStorageTemperature#"] = $obj->getId();
          } else {
              $replace["#hotWaterStorageTemperature#"] = 99;
              $replace["#idHotWaterStorageTemperature#"] = "#idHotWaterStorageTemperature#";
          }

          $obj = $this->getCmd(null, 'dhwTemperature');
          if (is_object($obj)) {
              $replace["#dhwTemperature#"] = $obj->execCmd();
              $replace["#idDhwTemperature#"] = $obj->getId();
              $replace["#minDhw#"] = $obj->getConfiguration('minValue');
              $replace["#maxDhw#"] = $obj->getConfiguration('maxValue');
              $replace["#stepDhw#"] = 1;
          } else {
              $replace["#dhwTemperature#"] = 99;
              $replace["#idDhwTemperature#"] = "#idDhwTemperature#";
          }

          $obj = $this->getCmd(null, 'dhwGazConsumption');
          $replace["#dhwGazConsumption#"] = $obj->execCmd();
          $replace["#idDhwGazConsumption#"] = $obj->getId();

          $obj = $this->getCmd(null, 'heatingGazConsumption');
          $replace["#heatingGazConsumption#"] = $obj->execCmd();
          $replace["#idHeatingGazConsumption#"] = $obj->getId();
                      
          $obj = $this->getCmd(null, 'heatingPowerConsumption');
          $replace["#heatingPowerConsumption#"] = $obj->execCmd();
          $replace["#idHeatingPowerConsumption#"] = $obj->getId();
            
          $obj = $this->getCmd(null, 'refreshDate');
          $replace["#refreshDate#"] = $obj->execCmd();
          $replace["#idRefreshDate#"] = $obj->getId();
            
          $obj = $this->getCmd(null, 'heatingBurnerHours');
          if (is_object($obj)) {
              $replace["#heatingBurnerHours#"] = $obj->execCmd();
              $replace["#idHeatingBurnerHours#"] = $obj->getId();
          } else {
              $replace["#heatingBurnerHours#"] = -1;
              $replace["#idHeatingBurnerHours#"] = "#idHeatingBurnerHours#";
          }
            
          $obj = $this->getCmd(null, 'heatingBurnerHoursPerDay');
          if (is_object($obj)) {
              $replace["#heatingBurnerHoursPerDay#"] = $obj->execCmd();
              $replace["#idHeatingBurnerHoursPerDay#"] = $obj->getId();
          } else {
              $replace["#heatingBurnerHoursPerDay#"] = -1;
              $replace["#idHeatingBurnerHoursPerDay#"] = "#idHeatingBurnerHoursPerDay#";
          }
            
          $obj = $this->getCmd(null, 'heatingBurnerStarts');
          if (is_object($obj)) {
              $replace["#heatingBurnerStarts#"] = $obj->execCmd();
              $replace["#idHeatingBurnerStarts#"] = $obj->getId();
          } else {
              $replace["#heatingBurnerStarts#"] = -1;
              $replace["#idHeatingBurnerStarts#"] = "#idHeatingBurnerStarts#";
          }
        
          $obj = $this->getCmd(null, 'heatingBurnerModulation');
          if (is_object($obj)) {
              $replace["#heatingBurnerModulation#"] = $obj->execCmd();
              $replace["#idHeatingBurnerModulation#"] = $obj->getId();
          } else {
              $replace["#heatingBurnerModulation#"] = -1;
              $replace["#idHeatingBurnerModulation#"] = "#idHeatingBurnerModulation#";
          }
  
          $obj = $this->getCmd(null, 'slope');
          if (is_object($obj)) {
              $replace["#slope#"] = $obj->execCmd();
              $replace["#idSlope#"] = $obj->getId();
              $replace["#minSlope#"] = $obj->getConfiguration('minValue');
              $replace["#maxSlope#"] = $obj->getConfiguration('maxValue');
              $replace["#stepSlope#"] = 0.1;
          } else {
              $replace["#slope#"] = 99;
              $replace["#idSlope#"] = "#idSlope#";
          }
  
          $obj = $this->getCmd(null, 'shift');
          if (is_object($obj)) {
              $replace["#shift#"] = $obj->execCmd();
              $replace["#idShift#"] = $obj->getId();
              $replace["#minShift#"] = $obj->getConfiguration('minValue');
              $replace["#maxShift#"] = $obj->getConfiguration('maxValue');
              $replace["#stepShift#"] = 1;
          } else {
              $replace["#shift#"] = 99;
              $replace["#idShift#"] = "#idShift#";
          }
  
          $obj = $this->getCmd(null, 'pressureSupply');
          if (is_object($obj)) {
              $replace["#pressureSupply#"] = $obj->execCmd();
              $replace["#idPressureSupply#"] = $obj->getId();
          } else {
              $replace["#pressureSupply#"] = 99;
              $replace["#idPressureSupply#"] = "#idPressureSupply#";
          }
  
          $obj = $this->getCmd(null, 'lastServiceDate');
          if (is_object($obj)) {
              $replace["#lastServiceDate#"] = $obj->execCmd();
              $replace["#idLastServiceDate#"] = $obj->getId();
          } else {
              $replace["#lastServiceDate#"] = '';
              $replace["#idLastServiceDate#"] = "#idLastServiceDate#";
          }
            
          $obj = $this->getCmd(null, 'serviceInterval');
          if (is_object($obj)) {
              $replace["#serviceInterval#"] = $obj->execCmd();
              $replace["#idServiceInterval#"] = $obj->getId();
          } else {
              $replace["#serviceInterval#"] = 99;
              $replace["#idServiceInterval#"] = "#idServiceInterval#";
          }
            
          $obj = $this->getCmd(null, 'monthSinceService');
          if (is_object($obj)) {
              $replace["#monthSinceService#"] = $obj->execCmd();
              $replace["#idMonthSinceService#"] = $obj->getId();
          } else {
              $replace["#monthSinceService#"] = 99;
              $replace["#idMonthSinceService#"] = "#idMonthSinceService#";
          }
            
          $obj = $this->getCmd(null, 'errors');
          if (is_object($obj)) {
              $replace["#errors#"] = $obj->execCmd();
              $replace["#idErrors#"] = $obj->getId();
          } else {
              $replace["#errors#"] = '';
              $replace["#idErrors#"] = "#idErrors#";
          }
          
          $obj = $this->getCmd(null, 'activeMode');
          if (is_object($obj)) {
              $replace["#activeMode#"] = $obj->execCmd();
              $replace["#idActiveMode#"] = $obj->getId();
          } else {
              $replace["#activeMode#"] = '??';
              $replace["#idActiveMode#"] = "#idActiveMode#";
          }

          $obj = $this->getCmd(null, 'comfortProgramTemperature');
          if (is_object($obj)) {
              $replace["#comfortProgramTemperature#"] = $obj->execCmd();
              $replace["#idComfortProgramTemperature#"] = $obj->getId();
              $replace["#minComfort#"] = $obj->getConfiguration('minValue');
              $replace["#maxComfort#"] = $obj->getConfiguration('maxValue');
              $replace["#stepComfort#"] = 1;
          } else {
              $replace["#comfortProgramTemperature#"] = 99;
              $replace["#idComfortProgramTemperature#"] = "#idComfortProgramTemperature#";
          }
          $obj = $this->getCmd(null, 'normalProgramTemperature');
          if (is_object($obj)) {
              $replace["#normalProgramTemperature#"] = $obj->execCmd();
              $replace["#idNormalProgramTemperature#"] = $obj->getId();
              $replace["#minNormal#"] = $obj->getConfiguration('minValue');
              $replace["#maxNormal#"] = $obj->getConfiguration('maxValue');
              $replace["#stepNormal#"] = 1;
          } else {
              $replace["#normalProgramTemperature#"] = 99;
              $replace["#idNormalProgramTemperature#"] = "#idNormalProgramTemperature#";
          }
          $obj = $this->getCmd(null, 'reducedProgramTemperature');
          if (is_object($obj)) {
              $replace["#reducedProgramTemperature#"] = $obj->execCmd();
              $replace["#idReducedProgramTemperature#"] = $obj->getId();
              $replace["#minReduced#"] = $obj->getConfiguration('minValue');
              $replace["#maxReduced#"] = $obj->getConfiguration('maxValue');
              $replace["#stepReduced#"] = 1;
          } else {
              $replace["#reducedProgramTemperature#"] = 99;
              $replace["#idReducedProgramTemperature#"] = "#idReducedProgramTemperature#";
          }

          $obj = $this->getCmd(null, 'supplyProgramTemperature');
          if (is_object($obj)) {
              $replace["#supplyProgramTemperature#"] = $obj->execCmd();
              $replace["#idSupplyProgramTemperature#"] = $obj->getId();
          } else {
              $replace["#supplyProgramTemperature#"] = 99;
              $replace["#idSupplyProgramTemperature#"] = "#idSupplyProgramTemperature#";
          }
                    
          $obj = $this->getCmd(null, 'boilerTemperature');
          if (is_object($obj)) {
              $replace["#boilerTemperature#"] = $obj->execCmd();
              $replace["#idBoilerTemperature#"] = $obj->getId();
          } else {
              $replace["#boilerTemperature#"] = 99;
              $replace["#idBoilerTemperature#"] = "#idBoilerTemperature#";
          }

          $obj = $this->getCmd(null, 'frostProtection');
          if (is_object($obj)) {
              $replace["#frostProtection#"] = $obj->execCmd();
              $replace["#idFrostProtection#"] = $obj->getId();
          } else {
              $replace["#frostProtection#"] = '??';
              $replace["#idFrostProtection#"] = "#idFrostProtection#";
          }
  
          $obj = $this->getCmd(null, 'pumpStatus');
          if (is_object($obj)) {
              $replace["#pumpStatus#"] = $obj->execCmd();
              $replace["#idPumpStatus#"] = $obj->getId();
          } else {
              $replace["#pumpStatus#"] = '??';
              $replace["#idPumpStatus#"] = "#idPumpStatus#";
          }
  
          $obj = $this->getCmd(null, 'heatingSchedule');
          if (is_object($obj)) {
              $str = $obj->execCmd();
              $schedules = explode(";", $str);
            
              if (count($schedules) == 7) {
                  $replace["#heaSchLun#"] = $schedules[0];
                  $replace["#heaSchMar#"] = $schedules[1];
                  $replace["#heaSchMer#"] = $schedules[2];
                  $replace["#heaSchJeu#"] = $schedules[3];
                  $replace["#heaSchVen#"] = $schedules[4];
                  $replace["#heaSchSam#"] = $schedules[5];
                  $replace["#heaSchDim#"] = $schedules[6];
              } else {
                  $replace["#heaSchLunSta#"] = '';
                  $replace["#heaSchMarSta#"] = '';
                  $replace["#heaSchMerSta#"] = '';
                  $replace["#heaSchJeuSta#"] = '';
                  $replace["#heaSchVenSta#"] = '';
                  $replace["#heaSchSamSta#"] = '';
                  $replace["#heaSchDimSta#"] = '';
              }
          } else {
              $replace["#heaSchLunSta#"] = '';
              $replace["#heaSchMarSta#"] = '';
              $replace["#heaSchMerSta#"] = '';
              $replace["#heaSchJeuSta#"] = '';
              $replace["#heaSchVenSta#"] = '';
              $replace["#heaSchSamSta#"] = '';
              $replace["#heaSchDimSta#"] = '';
          }
  
          $obj = $this->getCmd(null, 'dhwSchedule');
          if (is_object($obj)) {
              $str = $obj->execCmd();
              $schedules = explode(";", $str);
              if (count($schedules) == 7) {
                  $replace["#dhwSchLun#"] = $schedules[0];
                  $replace["#dhwSchMar#"] = $schedules[1];
                  $replace["#dhwSchMer#"] = $schedules[2];
                  $replace["#dhwSchJeu#"] = $schedules[3];
                  $replace["#dhwSchVen#"] = $schedules[4];
                  $replace["#dhwSchSam#"] = $schedules[5];
                  $replace["#dhwSchDim#"] = $schedules[6];
              } else {
                  $replace["#dhwSchLun#"] = '';
                  $replace["#dhwSchMar#"] = '';
                  $replace["#dhwSchMer#"] = '';
                  $replace["#dhwSchJeu#"] = '';
                  $replace["#dhwSchVen#"] = '';
                  $replace["#dhwSchSam#"] = '';
                  $replace["#dhwSchDim#"] = '';
              }
          } else {
              $replace["#dhwSchLun#"] = '';
              $replace["#dhwSchMar#"] = '';
              $replace["#dhwSchMer#"] = '';
              $replace["#dhwSchJeu#"] = '';
              $replace["#dhwSchVen#"] = '';
              $replace["#dhwSchSam#"] = '';
              $replace["#dhwSchDim#"] = '';
          }
  
          $obj = $this->getCmd(null, 'heatingGazConsumptionDay');
          $str = $obj->execCmd();
          $replace["#heatingGazConsumptionDay#"] = $str;
          $replace["#idHeatingGazConsumptionDay#"] = $obj->getId();
   
          $obj = $this->getCmd(null, 'dhwGazConsumptionDay');
          $replace["#dhwGazConsumptionDay#"] = $obj->execCmd();
          $replace["#idDhwGazConsumptionDay#"] = $obj->getId();
  
          $obj = $this->getCmd(null, 'totalGazConsumptionDay');
          $replace["#totalGazConsumptionDay#"] = $obj->execCmd();
          $replace["#idTotalGazConsumptionDay#"] = $obj->getId();
  
          $jours = array("Lun", "Mar", "Mer", "Jeu", "Ven", "Sam", "Dim");
            
          $maintenant = time();
          $jour = date("N", $maintenant) - 1;
          $joursSemaine = '';
          $n = substr_count($str, ",") + 1;
  
          for ($i=0; $i<$n; $i++) {
              if ($joursSemaine !== '') {
                  $joursSemaine = ',' . $joursSemaine;
              }
              $joursSemaine = "'" . $jours[$jour] . "'" . $joursSemaine;
              $jour--;
              if ($jour < 0) {
                  $jour = 6;
              }
          }
          $replace["#joursSemaine#"] = $joursSemaine;
                     
          $obj = $this->getCmd(null, 'heatingGazConsumptionWeek');
          $str = $obj->execCmd();
          $replace["#heatingGazConsumptionWeek#"] = $str;
          $replace["#idHeatingGazConsumptionWeek#"] = $obj->getId();
  
          $obj = $this->getCmd(null, 'dhwGazConsumptionWeek');
          $replace["#dhwGazConsumptionWeek#"] = $obj->execCmd();
          $replace["#idDhwGazConsumptionWeek#"] = $obj->getId();
  
          $obj = $this->getCmd(null, 'totalGazConsumptionWeek');
          $replace["#totalGazConsumptionWeek#"] = $obj->execCmd();
          $replace["#idTotalGazConsumptionWeek#"] = $obj->getId();
  
          $maintenant = time();
          $semaine = date("W", $maintenant);
          $semaines = '';
          $n = substr_count($str, ",") + 1;
  
          for ($i=0; $i<$n; $i++) {
              if ($semaines !== '') {
                  $semaines = ',' . $semaines;
              }
              $semaines = "'" . $semaine . "'" . $semaines;
              $maintenant -= 7*24*60*60;
              $semaine = date("W", $maintenant);
          }
          $replace["#semaines#"] = $semaines;
  
          $obj = $this->getCmd(null, 'heatingGazConsumptionMonth');
          $str = $obj->execCmd();
          $replace["#heatingGazConsumptionMonth#"] = $str;
          $replace["#idHeatingGazConsumptionMonth#"] = $obj->getId();
  
          $obj = $this->getCmd(null, 'dhwGazConsumptionMonth');
          $replace["#dhwGazConsumptionMonth#"] = $obj->execCmd();
          $replace["#idDhwGazConsumptionMonth#"] = $obj->getId();
  
          $obj = $this->getCmd(null, 'totalGazConsumptionMonth');
          $replace["#totalGazConsumptionMonth#"] = $obj->execCmd();
          $replace["#idTotalGazConsumptionMonth#"] = $obj->getId();
  
          $libMois = array("Janv", "Févr", "Mars", "Avr", "Mai", "Juin", "Juil", "Août", "Sept", "Oct", "Nov", "Déc");
            
          $maintenant = time();
          $mois = date("m", $maintenant)-1;
          $moisS = '';
          $n = substr_count($str, ",") + 1;
  
          for ($i=0; $i<$n; $i++) {
              if ($moisS !== '') {
                  $moisS = ',' . $moisS;
              }
              $moisS = "'" . $libMois[$mois] . "'" . $moisS;
              $mois--;
              if ($mois < 0) {
                  $mois = 11;
              }
          }
          $replace["#moisS#"] = $moisS;
  
          $obj = $this->getCmd(null, 'heatingGazConsumptionYear');
          $str = $obj->execCmd();
          $replace["#heatingGazConsumptionYear#"] = $str;
          $replace["#idHeatingGazConsumptionYear#"] = $obj->getId();
  
          $obj = $this->getCmd(null, 'dhwGazConsumptionYear');
          $replace["#dhwGazConsumptionYear#"] = $obj->execCmd();
          $replace["#idDhwGazConsumptionYear#"] = $obj->getId();
  
          $obj = $this->getCmd(null, 'totalGazConsumptionYear');
          $replace["#totalGazConsumptionYear#"] = $obj->execCmd();
          $replace["#idTotalGazConsumptionYear#"] = $obj->getId();
  
          $maintenant = time();
          $annee = date("Y", $maintenant);
          $annees = '';
          $n = substr_count($str, ",") + 1;
  
          for ($i=0; $i<$n; $i++) {
              if ($annees !== '') {
                  $annees = ',' . $annees;
              }
              $annees = "'" . $annee . "'" . $annees;
              $annee--;
          }
          $replace["#annees#"] = $annees;
            
          $obj = $this->getCmd(null, 'heatingPowerConsumptionDay');
          $str = $obj->execCmd();
          $replace["#heatingPowerConsumptionDay#"] = $str;
          $replace["#idHeatingPowerConsumptionDay#"] = $obj->getId();
          
          $maintenant = time();
          $jour = date("N", $maintenant) - 1;
          $joursSemaine = '';
          $n = substr_count($str, ",") + 1;
  
          for ($i=0; $i<$n; $i++) {
              if ($joursSemaine !== '') {
                  $joursSemaine = ',' . $joursSemaine;
              }
              $joursSemaine = "'" . $jours[$jour] . "'" . $joursSemaine;
              $jour--;
              if ($jour < 0) {
                  $jour = 6;
              }
          }
          $replace["#elec_joursSemaine#"] = $joursSemaine;
           
          $obj = $this->getCmd(null, 'heatingPowerConsumptionWeek');
          $str = $obj->execCmd();
          $replace["#heatingPowerConsumptionWeek#"] = $str;
          $replace["#idHeatingPowerConsumptionWeek#"] = $obj->getId();
  
          $maintenant = time();
          $semaine = date("W", $maintenant);
          $semaines = '';
          $n = substr_count($str, ",") + 1;
  
          for ($i=0; $i<$n; $i++) {
              if ($semaines !== '') {
                  $semaines = ',' . $semaines;
              }
              $semaines = "'" . $semaine . "'" . $semaines;
              $maintenant -= 7*24*60*60;
              $semaine = date("W", $maintenant);
          }
          $replace["#elec_semaines#"] = $semaines;
  
          $obj = $this->getCmd(null, 'heatingPowerConsumptionMonth');
          $str = $obj->execCmd();
          $replace["#heatingPowerConsumptionMonth#"] = $str;
          $replace["#idHeatingPowerConsumptionMonth#"] = $obj->getId();
  
          $maintenant = time();
          $mois = date("m", $maintenant)-1;
          $moisS = '';
          $n = substr_count($str, ",") + 1;
  
          for ($i=0; $i<$n; $i++) {
              if ($moisS !== '') {
                  $moisS = ',' . $moisS;
              }
              $moisS = "'" . $libMois[$mois] . "'" . $moisS;
              $mois--;
              if ($mois < 0) {
                  $mois = 11;
              }
          }
          $replace["#elec_moisS#"] = $moisS;
  
          $obj = $this->getCmd(null, 'heatingPowerConsumptionYear');
          $str = $obj->execCmd();
          $replace["#heatingPowerConsumptionYear#"] = $str;
          $replace["#idHeatingPowerConsumptionYear#"] = $obj->getId();
  
          $maintenant = time();
          $annee = date("Y", $maintenant);
          $annees = '';
          $n = substr_count($str, ",") + 1;
  
          for ($i=0; $i<$n; $i++) {
              if ($annees !== '') {
                  $annees = ',' . $annees;
              }
              $annees = "'" . $annee . "'" . $annees;
              $annee--;
          }
          $replace["#elec_annees#"] = $annees;
  
          $obj = $this->getCmd(null, 'isOneTimeDhwCharge');
          if (is_object($obj)) {
              $replace["#isOneTimeDhwCharge#"] = $obj->execCmd();
              $replace["#idIsOneTimeDhwCharge#"] = $obj->getId();
            
              $obj = $this->getCmd(null, 'startOneTimeDhwCharge');
              $replace["#idStartOneTimeDhwCharge#"] = $obj->getId();
  
              $obj = $this->getCmd(null, 'stopOneTimeDhwCharge');
              $replace["#idStopOneTimeDhwCharge#"] = $obj->getId();
          } else {
              $replace["#isOneTimeDhwCharge#"] = -1;
              $replace["#idIsOneTimeDhwCharge#"] = "#idIsOneTimeDhwCharge#";
          }
  
          $obj = $this->getCmd(null, 'isActivateComfortProgram');
          if (is_object($obj)) {
              $replace["#isActivateComfortProgram#"] = $obj->execCmd();
              $replace["#idIsActivateComfortProgram#"] = $obj->getId();

              $obj = $this->getCmd(null, 'activateComfortProgram');
              $replace["#idActivateComfortProgram#"] = $obj->getId();
      
              $obj = $this->getCmd(null, 'deActivateComfortProgram');
              $replace["#idDeActivateComfortProgram#"] = $obj->getId();
          } else {
              $replace["#isActivateComfortProgram#"] = -1;
              $replace["#idIsActivateComfortProgram#"] = "#idIsActivateComfortProgram#";
          }

          $obj = $this->getCmd(null, 'isActivateEcoProgram');
          if (is_object($obj)) {
              $replace["#isActivateEcoProgram#"] = $obj->execCmd();
              $replace["#idIsActivateEcoProgram#"] = $obj->getId();

              $obj = $this->getCmd(null, 'activateEcoProgram');
              $replace["#idActivateEcoProgram#"] = $obj->getId();
      
              $obj = $this->getCmd(null, 'deActivateEcoProgram');
              $replace["#idDeActivateEcoProgram#"] = $obj->getId();
          } else {
              $replace["#isActivateEcoProgram#"] = -1;
              $replace["#idIsActivateEcoProgram#"] = "#idIsActivateEcoProgram#";
          }
   
          $obj = $this->getCmd(null, 'startHoliday');
          $replace["#startHoliday#"] = $obj->execCmd();
          $replace["#idStartHoliday#"] = $obj->getId();
   
          $obj = $this->getCmd(null, 'endHoliday');
          $replace["#endHoliday#"] = $obj->execCmd();
          $replace["#idEndHoliday#"] = $obj->getId();
   
          $obj = $this->getCmd(null, 'startHolidayText');
          $replace["#idStartHolidayText#"] = $obj->getId();
   
          $obj = $this->getCmd(null, 'endHolidayText');
          $replace["#idEndHolidayText#"] = $obj->getId();
      
          return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'viessmannIot_view', 'viessmannIot')));
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
          } elseif ($this->getLogicalId() == 'startHolidayText') {
              if (!isset($_options['text']) || $_options['text'] == '') {
                  return;
              }
              $eqlogic->getCmd(null, 'startHoliday')->event($_options['text']);
          } elseif ($this->getLogicalId() == 'endHolidayText') {
              if (!isset($_options['text']) || $_options['text'] == '') {
                  return;
              }
              $eqlogic->getCmd(null, 'endHoliday')->event($_options['text']);
          }
      }
  }
