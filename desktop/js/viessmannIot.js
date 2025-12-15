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

// Event Select Info
//
function eventSelectInfo() {
  var el = this.closest(".form-group").querySelector(".eqLogicAttr");
  jeedom.cmd.getSelectModal({ cmd: { type: "info" } }, function (result) {
    el.value = result.human;
  });
}

els = document.querySelectorAll(".listCmdInfo");
els.forEach(function (el) {
  el.removeEventListener("click", eventSelectInfo);
  el.addEventListener("click", eventSelectInfo);
});

// Trier les commandes
//
$("#bt_sort")
  .off("click")
  .on("click", function () {
          domUtils.ajax({
            type: "POST",
            url: "plugins/viessmannIot/core/ajax/viessmannIot.ajax.php",
            data: {
              action: "sortCmds",
              id: document.querySelector('.eqLogicAttr[data-l1key="id"]').jeeValue(),
            },
            dataType: "json",
            global: false,
            async: false,
            error: function (error) {
              jeedomUtils.showAlert({
                message: error.message,
                level: "danger"
              });
            },
            success: function (data) {
              if (data.state != "ok") {
                jeedomUtils.showAlert({
                  message: data.result,
                  level: "danger"
                });
              } else {
                url = "index.php?v=d&p=viessmannIot&m=viessmannIot&id=" + document.querySelector('.eqLogicAttr[data-l1key="id"]').jeeValue()+"&saveSuccessFull=1#commandtab";
                jeedomUtils.loadPage(url)              }
            },
          });
        }
      
    );

// Add command
//
function addCmdToTable(_cmd) {
  if (document.getElementById("table_cmd") == null) return;
  if (document.querySelector("#table_cmd thead") == null) {
    table = "<thead>";
    table += "<tr>";
    table += "<th>{{Id}}</th>";
    table += "<th>{{Nom}}</th>";
    table += "<th>{{Type}}</th>";
    table += "<th>{{Paramètres}}</th>";
    table += "<th>{{Etat}}</th>";
    table += "<th>{{Action}}</th>";
    table += "</tr>";
    table += "</thead>";
    table += "<tbody>";
    table += "</tbody>";
    document.getElementById("table_cmd").insertAdjacentHTML("beforeend", table);
  }
  if (!isset(_cmd)) {
    var _cmd = { configuration: {} };
  }
  if (!isset(_cmd.configuration)) {
    _cmd.configuration = {};
  }
  var tr = "";
  tr += '<td style="min-width:50px;width:70px;">';
  tr += '<span class="cmdAttr" data-l1key="id"></span>';
  tr += "</td>";
  tr += "<td>";
  tr += '<div class="row">';
  tr += '<div class="col-sm-6">';
  tr +=
    '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fa fa-flag"></i> Icône</a>';
  tr +=
    '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
  tr += "</div>";
  tr += '<div class="col-sm-6">';
  tr += '<input class="cmdAttr form-control input-sm" data-l1key="name">';
  tr += "</div>";
  tr += "</div>";
  tr +=
    '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display : none;margin-top : 5px;" title="{{La valeur de la commande vaut par défaut la commande}}">';
  tr += '<option value="">Aucune</option>';
  tr += "</select>";
  tr += "</td>";
  tr += "<td>";
  tr +=
    '<span class="type" type="' +
    init(_cmd.type) +
    '">' +
    jeedom.cmd.availableType() +
    "</span>";
  tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
  tr += "</td>";
  tr += "<td>";
  tr +=
    '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;display:inline-block;">';
  tr +=
    '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;display:inline-block;">';
  tr +=
    '<input class="cmdAttr form-control input-sm" data-l1key="unite" placeholder="{{Unité}}" title="{{Unité}}" style="width:30%;display:inline-block;margin-left:2px;">';
  tr +=
    '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="listValue" placeholder="{{Liste de valeur|texte séparé par ;}}" title="{{Liste}}">';
  tr +=
    '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
  tr +=
    '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
  tr +=
    '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label></span> ';
  tr += "</td>";
  tr += "<td>";
  tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>';
  tr += "</td>";
  tr += "<td>";
  if (is_numeric(_cmd.id)) {
    tr +=
      '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
    tr +=
      '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
  }
  tr +=
    '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i>';
  tr += "</td>";

  let newRow = document.createElement("tr");
  newRow.innerHTML = tr;
  newRow.addClass("cmd");
  newRow.setAttribute("data-cmd_id", init(_cmd.id));
  document
    .getElementById("table_cmd")
    .querySelector("tbody")
    .appendChild(newRow);

  jeedom.eqLogic.buildSelectCmd({
    id: document.querySelector('.eqLogicAttr[data-l1key="id"]').jeeValue(),
    filter: { type: "info" },
    error: function (error) {
      jeedomUtils.showAlert({ message: error.message, level: "danger" });
    },
    success: function (result) {
      newRow
        .querySelector('.cmdAttr[data-l1key="value"]')
        .insertAdjacentHTML("beforeend", result);
      newRow.setJeeValues(_cmd, ".cmdAttr");
      jeedom.cmd.changeType(newRow, init(_cmd.subType));
    },
  });
}
