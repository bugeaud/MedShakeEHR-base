/*
 * This file is part of MedShakeEHR.
 *
 * Copyright (c) 2017
 * Bertrand Boutillier <b.boutillier@gmail.com>
 * http://www.medshake.net
 *
 * MedShakeEHR is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * MedShakeEHR is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MedShakeEHR.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Fonctions JS pour les relations patient <-> patient et patient <-> praticien
 *
 * @author Bertrand Boutillier <b.boutillier@gmail.com>
 * @contrib fr33z00 <https://www.github.com/fr33z00>
 */


$(document).ready(function() {

  // reset recherche patient
  $('body').on("click", "#searchPatientID", function(e) {
    $('#searchPatientID').val('');
    $('#searchPatientID').attr('data-id', '');
  });

  //autocomplete pour la relation patient <-> patient
  $('body').delegate('#searchPatientID', 'focusin', function() {
    if ($(this).is(':data(autocomplete)')) return;
    $(this).autocomplete({
      source: urlBase + '/people/ajax/getRelationsPatients/',
      select: function(event, ui) {
        $('#searchPatientID').val(ui.item.label);
        $('#searchPatientID').attr('data-id', ui.item.id);
      }
    });
  });

  //ajouter une relation patient <-> patient
  $('body').on("click", "#addRelationPatientPatients", function(e) {
    e.preventDefault();
    patient2ID = $('#searchPatientID').attr('data-id');
    patientID = $('#identitePatient').attr("data-patientID");
    preRelationPatientPatient = $('#preRelationPatientPatientID').val();

    if (patient2ID > 0) {
      $.ajax({
        url: urlBase + '/people/ajax/addRelationPatientPatient/',
        type: 'post',
        data: {
          patientID: patientID,
          patient2ID: patient2ID,
          preRelationPatientPatient: preRelationPatientPatient
        },
        dataType: "html",
        success: function(data) {
          getRelationsPatientPatientsTab($('#identitePatient').attr("data-patientID"));
        },
        error: function() {
          alert_popup("danger", 'Problème, rechargez la page !');

        }
      });
    } else {
      alert_popup("danger", "Le patient n'est pas correctement sélectionné");

    }

  });

  // reset recherche praticien
  $('body').on("click", "#searchPratID", function(e) {
    $('#searchPratID').val('');
    $('#searchPratID').attr('data-id', '');
  });

  //autocomplete pour la relation patient <-> praticien
  $('body').delegate('#searchPratID', 'focusin', function() {
    if ($(this).is(':data(autocomplete)')) return;
    $(this).autocomplete({
      source: urlBase + '/people/ajax/getRelationsPraticiens/',
      select: function(event, ui) {
        $('#searchPratID').val(ui.item.label);
        $('#searchPratID').attr('data-id', ui.item.id);
      }
    });
  });

  //ajouter une relation patient <-> praticien
  $('body').on("click", "#addRelationPatientPrat", function(e) {
    e.preventDefault();
    praticienID = $('#searchPratID').attr('data-id');
    patientID = $('#identitePatient').attr("data-patientID");
    preRelationPatientPrat = $('#preRelationPatientPratID').val();
    setRelationPatientPrat(patientID, praticienID, preRelationPatientPrat);
  });

  //retirer une relation patient <-> praticien/patient
  $('body').on("click", ".removeRelationPatient", function(e) {
    e.preventDefault();
    e.stopPropagation();
    deleteRelationPatient($(this).attr("data-patientID"), $(this).attr('data-peopleID'));
  });

  //ajax save form in modal
  $('body').on("click", "#newPro button.modal-save", function(e) {
    var modal = '#' + $(this).attr("data-modal");
    var form = '#' + $(this).attr("data-form");
    ajaxModalSave(form, modal, function() {
      $(modal).modal('hide');
    });
  });

  $('body').on('click', '.voirDossier', function() {
    window.location = $(this).find('a.btn').attr('href');
  });

  setTimeout(getRelationsPatientPraticiensTab($('#identitePatient').attr("data-patientID")), 500);
  setTimeout(getRelationsPatientPatientsTab($('#identitePatient').attr("data-patientID")), 500);

});

/**
 * Obtenir et afficher le tableau de relations patient / patient
 * @param  {int} patientID patientID
 * @return {void}
 */
function getRelationsPatientPatientsTab(patientID) {
  if (!patientID) return;
  $.ajax({
    url: urlBase + '/people/ajax/getRelationsPatientPatientsTab/',
    type: 'post',
    data: {
      patientID: patientID,
    },
    dataType: "json",
    success: function(data) {
      $('#bodyTabRelationPatientPatients').html('');
      if (data.length > 0) {
        $.each(data, function(index, value) {
          if(value.ageAnnees > 1) {
            strAge = value.ageAnnees + ' ans';
          } else if( value.ageJours < 30 ) {
            strAge = value.ageJours + ' jours';
          } else {
            strAge = value.ageMois + ' mois';
          }

          $('#bodyTabRelationPatientPatients').append('\
          <tr class="voirDossier" style="cursor:pointer">\
            <td>\
              <a class="btn btn-light btn-sm" role="button" href="' + urlBase + '/patient/' + value.patientID + '/">\
                <i class="fas fa-folder-open fa-fw"></i>\
              </a>\
            </td>\
            <td>' + value.prenom + ' ' + value.nom + '</td>\
            <td>' + value.ddn + ' - ' + strAge +'</td><td>' + value.typeRelation + '</td>\
            <td class="text-right">\
              <div class="btn-group">\
                <button class="btn btn-light btn-sm removeRelationPatient" style="cursor:pointer" type="button" data-patientID="' + patientID + '" data-peopleID="' + value.patientID + '"><i class="fas fa-times"></i>\
                </button>\
              </div>\
            </td>\
          </tr>');
        });
      } else {
        $('#bodyTabRelationPatientPatients').append('\
        <tr class="bg-transparent text-muted">\
          <td></td>\
          <td colspan="3">\
            Pas de lien familial connu\
          </td>\
        </tr>');
      }

    },
    error: function() {
      alert_popup("danger", 'Problème, rechargez la page !');

    }
  });
}

/**
 * Obtenir et afficher le tableau de relations patient / prat d'un patient
 * @param  {int} patientID patientID
 * @return {void}
 */
function getRelationsPatientPraticiensTab(patientID) {
  if (!patientID) return;
  $.ajax({
    url: urlBase + '/people/ajax/getRelationsPatientPraticiensTab/',
    type: 'post',
    data: {
      patientID: patientID,
    },
    dataType: "json",
    success: function(data) {
      $('#bodyTabRelationPatientPrat').html('');
      if (data.length > 0) {
        $.each(data, function(index, value) {
          $('#bodyTabRelationPatientPrat').append('\
            <tr class="voirDossier" style="cursor:pointer">\
              <td>\
                <a class="btn btn-light btn-sm" role="button" href="' + urlBase + '/pro/' + value.pratID + '/">\
                  <i class="fas fa-user-md fa-fw"></i>\
                </a>\
              </td>\
              <td>' + (value.prenom ? value.prenom : '') + ' ' + value.nom + '</td><td>' + value.typeRelationTxt + '</td>\
              <td class="text-right">\
                <button class="btn btn-light btn-sm removeRelationPatient" style="cursor:pointer" type="button" data-patientID="' + patientID + '" data-peopleID="' + value.pratID + '">\
                    <i class="fas fa-times"></i>\
                </button>\
              </td>\
            </tr>');
        });
      } else {
        $('#bodyTabRelationPatientPrat').append('\
          <tr class="bg-transparent text-muted">\
            <td></td>\
            <td colspan="3">\
              Pas de correspondant connu\
            </td>\
          </tr>');
      }
    },
    error: function() {
      alert_popup("danger", 'Problème, rechargez la page !');
    }
  });
}

/**
 * Définir une relation entre un patient et un praticien
 * @param {int} patientID              patientID
 * @param {int} praticienID            praticienID
 * @param {string} preRelationPatientPrat code relation
 */
function setRelationPatientPrat(patientID, praticienID, preRelationPatientPrat) {
  if (praticienID > 0) {
    $.ajax({
      url: urlBase + '/people/ajax/addRelationPatientPraticien/',
      type: 'post',
      data: {
        patientID: patientID,
        praticienID: praticienID,
        preRelationPatientPrat: preRelationPatientPrat
      },
      dataType: "html",
      success: function(data) {
        getRelationsPatientPraticiensTab(patientID);
        if (typeof ajaxModalPatientAdminCloseAndRefreshHeader === "function") ajaxModalPatientAdminCloseAndRefreshHeader();
      },
      error: function() {
        alert_popup("danger", 'Problème, rechargez la page !');
      }
    });
  } else {
    alert_popup("danger", "Le praticien n'est pas correctement sélectionné");
  }
}

/**
 * Effacer une relation entre 2 individus
 * @param  {int} patientID patientID
 * @param  {int} peopleID  peopleID
 * @return {void}
 */
function deleteRelationPatient(patientID, peopleID) {
  if (patientID > 0 && peopleID > 0) {
    $.ajax({
      url: urlBase + '/people/ajax/removeRelationPatient/',
      type: 'post',
      data: {
        ID1: patientID,
        ID2: peopleID,
      },
      dataType: "html",
      success: function(data) {
        getRelationsPatientPraticiensTab(patientID);
        getRelationsPatientPatientsTab(patientID);
        if (typeof ajaxModalPatientAdminCloseAndRefreshHeader === "function") ajaxModalPatientAdminCloseAndRefreshHeader();
      },
      error: function() {
        alert_popup("danger", 'Problème, rechargez la page !');
      }
    });
  } else {
    alert_popup("danger", "Le praticien n'est pas correctement sélectionné");
  }
}
