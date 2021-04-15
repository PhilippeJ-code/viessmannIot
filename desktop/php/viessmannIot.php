<?php
  if (!isConnect('admin')) {
      throw new Exception('{{401 - Accès non autorisé}}');
  }

  $plugin = plugin::byId('viessmannIot');
  sendVarToJS('eqType', $plugin->getId());
  $eqLogics = eqLogic::byType($plugin->getId());

?>

<div class="row row-overflow">
  <div class="col-xs-12 eqLogicThumbnailDisplay">
    <legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
    <div class="eqLogicThumbnailContainer">
      <div class="cursor eqLogicAction logoPrimary" data-action="add">
        <i class="fas fa-plus-circle"></i>
        <br>
        <span>{{Ajouter}}</span>
      </div>
      <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
        <i class="fas fa-wrench"></i>
        <br>
        <span>{{Configuration}}</span>
      </div>
    </div>
    <legend><i class="fas fa-table"></i> {{Mes équipements}}</legend>
    <input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
    <div class="eqLogicThumbnailContainer">
      <?php

        // Affiche la liste des équipements
        //
        foreach ($eqLogics as $eqLogic) {
            $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
            echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
            echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
            echo '<br>';
            echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
            echo '</div>';
        }
      ?>
    </div>
  </div>

  <div class="col-xs-12 eqLogic" style="display: none;">
    <div class="input-group pull-right" style="display:inline-flex">
      <span class="input-group-btn">
        <a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure"><i class="fa fa-cogs"></i>
          {{Configuration avancée}}</a><a class="btn btn-default btn-sm eqLogicAction" data-action="copy"><i
            class="fas fa-copy"></i> {{Dupliquer}}</a><a class="btn btn-sm btn-success eqLogicAction"
          data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a><a
          class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i
            class="fas fa-minus-circle"></i> {{Supprimer}}</a>
      </span>
    </div>
    <ul class="nav nav-tabs" role="tablist">
      <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab"
          data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
      <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i
            class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
      <li role="presentation"><a href="#widgettab" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i>
          {{Widget}}</a></li>
      <li role="presentation"><a href="#donneestab" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i>
          {{Données supplémentaires}}</a></li>
      <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i
            class="fa fa-list-alt"></i> {{Commandes}}</a></li>
    </ul>
    <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
      <div role="tabpanel" class="tab-pane active" id="eqlogictab">
        <br />
        <form class="form-horizontal">
          <fieldset>
            <legend><i class="fas fa-wrench"></i> {{Général}}</legend>
            <div class="form-group">
              <label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
              <div class="col-sm-3">
                <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                <input type="text" class="eqLogicAttr form-control" data-l1key="name"
                  placeholder="{{Nom de l'équipement}}" />
              </div>
            </div>
            <div class="form-group">
              <label class="col-sm-3 control-label">{{Objet parent}}</label>
              <div class="col-sm-3">
                <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                  <option value="">{{Aucun}}</option>
                  <?php
                    foreach (jeeObject::all() as $object) {
                        echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                    }
                  ?>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label class="col-sm-3 control-label">{{Catégorie}}</label>
              <div class="col-sm-9">
                <?php
                  foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                      echo '<label class="checkbox-inline">';
                      echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                      echo '</label>';
                  }
                ?>
              </div>
            </div>
            <div class="form-group">
              <label class="col-sm-3 control-label"></label>
              <div class="col-sm-9">
                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable"
                    checked />{{Activer}}</label>
                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible"
                    checked />{{Visible}}</label>
              </div>
            </div>

            <legend><i class="fas fa-cogs"></i> {{Paramètres}}</legend>

            <div class="form-group">
              <label class="col-sm-3 control-label">{{Id Client}}</label>
              <div class="col-sm-3">
                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="clientId"
                  placeholder="Id Client" />
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label">{{Code Challenge}}</label>
              <div class="col-sm-3">
                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration"
                  data-l2key="codeChallenge" placeholder="Code Challenge" />
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label">{{Nom d'utilisateur}}</label>
              <div class="col-sm-3">
                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="userName"
                  placeholder="Nom d'utilisateur Vicare" />
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label">{{Mot de passe}}</label>
              <div class="col-sm-3">
                <input type="password" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="password"
                  placeholder="Mot de passe Vicare" />
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label">{{Id de l'installation}}</label>
              <div class="col-sm-3">
                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration"
                  data-l2key="installationId" placeholder="Id de l'installation" />
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label">{{Serial}}</label>
              <div class="col-sm-3">
                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="serial"
                  placeholder="Serial" />
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label">{{Id du device}}</label>
              <div class="col-sm-3">
                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="deviceId"
                  placeholder="Id du device" />
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label">{{Id du circuit}}</label>
              <div class="col-sm-3">
                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="circuitId"
                  placeholder="Id du circuit" />
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label">{{Log Features}}
                <sup><i class="fas fa-question-circle tooltips"
                    title="{{Le json est à récupérer dans le répertoire data du plugin}}"></i></sup>
              </label>
              <div class="col-sm-3">
                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="logFeatures"
                  placeholder="Mettre Oui" />
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label">{{Utiliser les identifiants Vicare}}</label>
              <div class="col-sm-3 form-check-input">
                <input type="checkbox" required class="eqLogicAttr" data-l1key="configuration" data-l2key="isVicare"
                  unchecked /></label>
              </div>
            </div>

          </fieldset>
        </form>
      </div>

      <div role="tabpanel" class="tab-pane" id="widgettab">
        <form class="form-horizontal">
          <fieldset>
            <br /><br />

            <div class="form-group">
              <label class="col-sm-3 control-label">{{Nom du circuit}}</label>
              <div class="col-sm-3">
                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="circuitName"
                  placeholder="Nom du circuit" />
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label">{{Unité Gaz}}</label>
              <div class="col-sm-3">
                <select required class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="uniteGaz">
                  <option value="" disabled selected>{{Sélectionnez l'unité}}</option>
                  <option value="m3">m3( défaut )</option>
                  <option value="kWh">kWh</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label">{{Facteur de conversion}}</label>
              <div class="col-sm-3">
                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration"
                  data-l2key="facteurConversionGaz" placeholder="m3 -> kWh ou kWh -> m3" />
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label">{{Utiliser le widget du plugin}}</label>
              <div class="col-sm-3 form-check-input">
                <input type="checkbox" required class="eqLogicAttr" data-l1key="configuration"
                  data-l2key="isWidgetPlugin" checked /></label>
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label">{{Afficher la tuile eau chaude}}</label>
              <div class="col-sm-3 form-check-input">
                <input type="checkbox" required class="eqLogicAttr" data-l1key="configuration" data-l2key="displayWater"
                  checked /></label>
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label">{{Afficher la tuile gaz}}</label>
              <div class="col-sm-3 form-check-input">
                <input type="checkbox" required class="eqLogicAttr" data-l1key="configuration" data-l2key="displayGas"
                  checked /></label>
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label">{{Afficher la tuile électricité}}</label>
              <div class="col-sm-3 form-check-input">
                <input type="checkbox" required class="eqLogicAttr" data-l1key="configuration" data-l2key="displayPower"
                  checked /></label>
              </div>
            </div>

          </fieldset>
        </form>
      </div>

      <div role="tabpanel" class="tab-pane" id="donneestab">
        <form class="form-horizontal">
          <fieldset>
            <br /><br />
            <div class="form-group">
              <label class="col-sm-2 control-label">{{Température intérieure}}</label>
              <div class="col-sm-4">
                <div class="input-group">
                  <input type="text" class="eqLogicAttr form-control tooltips roundedLeft" data-l1key="configuration"
                    data-l2key="temperature_interieure" data-concat="1" />
                  <span class="input-group-btn">
                    <a class="btn btn-default listCmdInfo roundedRight"><i class="fas fa-list-alt"></i></a>
                  </span>
                </div>
              </div>
            </div>
            <div class="form-group">
              <label class="col-sm-2 control-label">{{Température extérieure}}</label>
              <div class="col-sm-4">
                <div class="input-group">
                  <input type="text" class="eqLogicAttr form-control tooltips roundedLeft" data-l1key="configuration"
                    data-l2key="temperature_exterieure" data-concat="1" />
                  <span class="input-group-btn">
                    <a class="btn btn-default listCmdInfo roundedRight"><i class="fas fa-list-alt"></i></a>
                  </span>
                </div>
              </div>
            </div>
          </fieldset>
        </form>
      </div>

      <div role="tabpanel" class="tab-pane" id="commandtab">
        <a class="btn btn-success btn-sm cmdAction pull-right" data-action="add" style="margin-top:5px;"><i
            class="fa fa-plus-circle"></i> {{Commandes}}</a><br /><br />
        <table id="table_cmd" class="table table-bordered table-condensed">
          <thead>
            <tr>
              <th>{{Nom}}</th>
              <th>{{Type}}</th>
              <th>{{Action}}</th>
            </tr>
          </thead>
          <tbody>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Inclusion du fichier javascript du plugin (dossier, nom_du_fichier, extension_du_fichier, nom_du_plugin) -->
<?php include_file('desktop', 'viessmannIot', 'js', 'viessmannIot');?>
<!-- Inclusion du fichier javascript du core - NE PAS MODIFIER NI SUPPRIMER -->
<?php include_file('core', 'plugin.template', 'js');
