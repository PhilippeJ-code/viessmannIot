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

  require_once __DIR__  . '/../../../../core/php/core.inc.php';
  include __DIR__ . '/../php/viessmannApi.php';

  class viessmannIot extends eqLogic
  {
      const HEATING_CIRCUITS = "heating.circuits";

      const OUTSIDE_TEMPERATURE = "heating.sensors.temperature.outside";
      const HOT_WATER_STORAGE_TEMPERATURE = "heating.dhw.sensors.temperature.hotWaterStorage";
      const DHW_TEMPERATURE = "heating.dhw.temperature.main";

      const ACTIVE_MODE = "operating.modes.active";
      const ACTIVE_PROGRAM = "operating.programs.active";
      const PUMP_STATUS = "circulation.pump";

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
          for ($i=0; $i<$n; $i++) {
              if ($features["data"][$i]["feature"] == self::OUTSIDE_TEMPERATURE) {
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
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::PUMP_STATUS)) {
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
              } elseif ($features["data"][$i]["feature"] == self::HOT_WATER_STORAGE_TEMPERATURE) {
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
              } elseif ($features["data"][$i]["feature"] == self::DHW_TEMPERATURE) {
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
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::ACTIVE_MODE)) {
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
                              $obj->setName(__('Mode arrêt', __FILE__));
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
                } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::ACTIVE_PROGRAM)) {
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
          "token" => $token
        ];

          $viessmannApi = new ViessmannApi($params);
                        
          if ((empty($installationId)) || (empty($serial))) {
              $viessmannApi->getGateway();
              $viessmannApi->getFeatures();
            
              $installationId = $viessmannApi->getInstallationId();
              $serial = $viessmannApi->getSerial();

              $this->setConfiguration('installationId', $installationId);
              $this->setConfiguration('serial', $serial)->save();
            
              $this->deleteAllCommands();
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
          $n = count($features["data"]);
          for ($i=0; $i<$n; $i++) {
              if ($features["data"][$i]["feature"] == self::OUTSIDE_TEMPERATURE) {
                  $val = $features["data"][$i]["properties"]["value"]["value"];
                  $this->getCmd(null, 'outsideTemperature')->event($val);
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::PUMP_STATUS)) {
                  $val = $features["data"][$i]["properties"]["status"]["value"];
                  $this->getCmd(null, 'pumpStatus')->event($val);
              } elseif ($features["data"][$i]["feature"] == self::HOT_WATER_STORAGE_TEMPERATURE) {
                  $val = $features["data"][$i]["properties"]["value"]["value"];
                  $this->getCmd(null, 'hotWaterStorageTemperature')->event($val);
              } elseif ($features["data"][$i]["feature"] == self::DHW_TEMPERATURE) {
                  $val = $features["data"][$i]["properties"]["value"]["value"];
                  $this->getCmd(null, 'dhwTemperature')->event($val);
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::ACTIVE_MODE)) {
                  $val = $features["data"][$i]["properties"]["value"]["value"];
                  $this->getCmd(null, 'activeMode')->event($val);
              } elseif ($features["data"][$i]["feature"] == $this->buildFeature($circuitId, self::ACTIVE_PROGRAM)) {
                  $val = $features["data"][$i]["properties"]["value"]["value"];
                  $this->getCmd(null, 'activeProgram')->event($val);
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
          }
      }
  }
