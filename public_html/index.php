<?php
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
 * Pivot central des pages loguées
 *
 * @author Bertrand Boutillier <b.boutillier@gmail.com>
 * @contrib fr33z00 <https://www.github.com/fr33z00>
 */

ini_set('display_errors', 1);
setlocale(LC_ALL, "fr_FR.UTF-8");


if(($homepath=getenv("MEDSHAKEEHRPATH"))===false) {
    die("La variable d'environnement MEDSHAKEEHRPATH n'a pas été fixée.<br>Veuillez insérer 'SetEnv MEDSHAKEEHRPATH /chemin/vers/MedShakeEHR' dans votre .htaccess ou la configuration du serveur");
}
$homepath.=$homepath[strlen($homepath)-1]=='/'?'':'/';

session_start();

/////////// Composer class auto-upload
require $homepath.'vendor/autoload.php';

/////////// Class medshakeEHR auto-upload
spl_autoload_register(function ($class) {
    if (is_file(getenv("MEDSHAKEEHRPATH").'/class/' . $class . '.php')) {
        include getenv("MEDSHAKEEHRPATH").'/class/' . $class . '.php';
    }
});

/////////// Vérification de l'état d'installation
if (!is_file($homepath.'config/config.yml')) {
    msTools::redirection('/install.php');
}
/////////// Config loader
$p['config']=Spyc::YAMLLoad($homepath.'config/config.yml');
/////////// correction pour host non présent (IP qui change)
if ($p['config']['host']=='') {
    $p['config']['host']=$_SERVER['SERVER_ADDR'];
    $p['config']['cookieDomain']=$_SERVER['SERVER_ADDR'];
}
$p['config']['homeDirectory']=$homepath;
$p['configDefaut']=$p['config'];

/////////// SQL connexion
$mysqli=msSQL::sqlConnect();

/////////// Vérification de l'état de la base
if (!count(msSQL::sql2tabSimple("SHOW TABLES"))) {
    msTools::redirection('/install.php');
}
if ('maintenance'==msSQL::sqlUniqueChamp("SELECT value FROM system WHERE name='state' and groupe='system'")) {
    msTools::redirection('/maintenance.html');
}

/////////// Validators loader
require $homepath.'fonctions/validators.php';

/////////// Router
$router = new AltoRouter();
$routes=Spyc::YAMLLoad($homepath.'config/routes.yml');
$router->addRoutes($routes);
$router->setBasePath($p['config']['urlHostSuffixe']);
$match = $router->match();

///////// user
$p['user']=null;
$p['user']['id']=null;
$p['user']['module']='base';
if (msSQL::sqlUniqueChamp("SELECT COUNT(*) FROM people WHERE type='pro' AND name!=''") == "0") {
    if ($match['target']!='login/logInFirst' and $match['target']!='login/logInFirstDo') {
        msTools::redirRoute('userLogInFirst');
    }
} elseif (isset($_COOKIE['userName'])) {
    $p['user']=msUser::userIdentification();
    if (is_file($homepath.'config/config-'.$p['user']['module'].'.yml') and $p['user']['module']) {
        $p['config']=array_merge($p['config'], Spyc::YAMLLoad($homepath.'config/config-'.$p['user']['module'].'.yml'));
    }
    if (isset($p['user']['id'])) {
        msUser::applySpecificConfig($p['config'], $p['user']['id']);
    }
} else {
    if ($match['target']!='login/logIn' and $match['target']!='login/logInDo') {
        msTools::redirRoute('userLogIn');
    }
}

///////// Controler
if ($match and is_file($homepath.'controlers/'.$match['target'].'.php')) {
    include $homepath.'controlers/'.$match['target'].'.php';

    // complément lié au module installé
    if (is_file($homepath.'controlers/module/'.$p['user']['module'].'/'.$match['target'].'.php')) {
        include $homepath.'controlers/module/'.$p['user']['module'].'/'.$match['target'].'.php';
    }
} elseif ($match and is_file($homepath.'controlers/module/'.$p['user']['module'].'/'.$match['target'].'.php')) {
    include $homepath.'controlers/module/'.$p['user']['module'].'/'.$match['target'].'.php';
}


//////// View if defined
if (isset($template)) {
    if (isset($_SESSION)) {
        $p['session']=$_SESSION;
    }

    if (isset($p['user']['id'])) {
      //inbox number of messages
      $p['page']['inbox']['numberOfMsg']=msSQL::sqlUniqueChamp("select count(txtFileName) from inbox where archived='n' and mailForUserID = '".$p['config']['apicryptInboxMailForUserID']."' ");

      // patients of the day
      if ($p['config']['agendaNumberForPatientsOfTheDay'] > 0) {
        $events = new msAgenda();
        $events->set_userID($p['config']['agendaNumberForPatientsOfTheDay']);
        $p['page']['patientsOfTheDay']=$events->getPatientsOfTheDay();
      }
      elseif($p['config']['administratifPeutAvoirAgenda']=='true') {
        $events = new msAgenda();
        $events->set_userID($p['user']['id']);
        $p['page']['patientsOfTheDay']=$events->getPatientsOfTheDay();
      }
      elseif (trim($p['config']['agendaLocalPatientsOfTheDay']) !=='') {
          $p['page']['patientsOfTheDay']=msExternalData::jsonFileToPhpArray($p['config']['workingDirectory'].$p['config']['agendaLocalPatientsOfTheDay']);
      }
    }

    // crédits SMS
    if (is_file($p['config']['workingDirectory'].$p['config']['smsCreditsFile'])) {
        $p['page']['creditsSMS']=file_get_contents($p['config']['workingDirectory'].$p['config']['smsCreditsFile']);
    }

    //utilisateurs pouvant avoir un agenda
    $agendaUsers= new msPeople();
    $p['page']['agendaUsers']=$agendaUsers->getUsersListForService('administratifPeutAvoirAgenda');

    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");


    //générer et sortir le html
    $getHtml = new msGetHtml();
    $getHtml->set_template($template);
    if(isset($forceAllTemplates)) {
      $getHtml->set_templatesDirectories(msTools::getAllSubDirectories($p['config']['templatesFolder'],'/'));
    }
    echo $getHtml->genererHtml();
}

//////// Debug
if (!isset($debug)) {
    $debug=null;
}

//and $p['user']['id']=='1'

if ($debug=='y' and $p['user']['id']=='1') {
    echo '<pre style="margin-top : 50px;">';
    //echo '$p[\'config\'] :';
    //print_r($p['config']);
    echo '$p[\'page\'] :';
    print_r($p['page']);
    echo '$p[\'user\'] :';
    print_r($p['user']);
    echo '$MATCH :';
    print_r($match);
    echo '$_COOKIE :';
    print_r($_COOKIE);
    echo '$_SESSION :';
    print_r($_SESSION);
}
