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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) 
{
  include_file('desktop', '404', 'php');
  die();
}
?>
<form class="form-horizontal">
  <fieldset>
    <div class="form-group">
      <label class="col-lg-2 control-label">{{Avertissement}}</label>
      <div class="col-lg-6">
        Pour information, le nombre d'appels au serveur Viessmann est limité à 1450 par jour</br>                
        Le dépassement de cette limite conduit à un bannissement d'une journée</br>
        Pour cette raison le cron ( minute ) n'effectuera le rafraichissement que sur la minute paire</br>
      </div>
    </div>
  </fieldset>
</form>

<script>

// Cron
//
function eventCron() {
  if (this.checked) {

    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron5::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron10::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron15::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron30::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cronHourly::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cronDaily::enable"]'))) cmd.checked = false;

  }
}

if (is_object(el = document.querySelector('input[data-l1key="functionality::cron::enable"]'))) {
  el.removeEventListener('change', eventCron);
  el.addEventListener('change', eventCron);
}

// Cron 5
//
function eventCron5() {
  if (this.checked) {

    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron10::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron15::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron30::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cronHourly::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cronDaily::enable"]'))) cmd.checked = false;

  }
}

if (is_object(el = document.querySelector('input[data-l1key="functionality::cron5::enable"]'))) {
  el.removeEventListener('change', eventCron5);
  el.addEventListener('change', eventCron5);
}

// Cron 10
//
function eventCron10() {
  if (this.checked) {

    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron5::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron15::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron30::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cronHourly::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cronDaily::enable"]'))) cmd.checked = false;

  }
}

if (is_object(el = document.querySelector('input[data-l1key="functionality::cron10::enable"]'))) {
  el.removeEventListener('change', eventCron10);
  el.addEventListener('change', eventCron10);
}

// Cron 15
//
function eventCron15() {
  if (this.checked) {

    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron5::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron10::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron30::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cronHourly::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cronDaily::enable"]'))) cmd.checked = false;

  }
}

if (is_object(el = document.querySelector('input[data-l1key="functionality::cron15::enable"]'))) {
  el.removeEventListener('change', eventCron15);
  el.addEventListener('change', eventCron15);
}

// Cron 30
//
function eventCron30() {
  if (this.checked) {

    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron5::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron10::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron15::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cronHourly::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cronDaily::enable"]'))) cmd.checked = false;

  }
}

if (is_object(el = document.querySelector('input[data-l1key="functionality::cron30::enable"]'))) {
  el.removeEventListener('change', eventCron30);
  el.addEventListener('change', eventCron30);
}

// Cron Hourly
//
function eventCronHourly() {
  if (this.checked) {

    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron5::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron10::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron15::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron30::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cronDaily::enable"]'))) cmd.checked = false;

  }
}

if (is_object(el = document.querySelector('input[data-l1key="functionality::cronHourly::enable"]'))) {
  el.removeEventListener('change', eventCronHourly);
  el.addEventListener('change', eventCronHourly);
}

// Cron Daily
//
function eventCronDaily() {
  if (this.checked) {

    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron5::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron10::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron15::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cron30::enable"]'))) cmd.checked = false;
    if (is_object(cmd = document.querySelector('input[data-l1key="functionality::cronHourly::enable"]'))) cmd.checked = false;

  }
}

if (is_object(el = document.querySelector('input[data-l1key="functionality::cronDaily::enable"]'))) {
  el.removeEventListener('change', eventCronDaily);
  el.addEventListener('change', eventCronDaily);
}
</script>
