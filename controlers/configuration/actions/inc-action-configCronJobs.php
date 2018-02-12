<?php
/*
 * This file is part of MedShakeEHR.
 *
 * Copyright (c) 2017
 * fr33z00 <https://github.com/fr33z00>
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
 * Config > action : sauver la configuration des crons
 *
 * @author fr33z00 <https://github.com/fr33z00>
 */
$crons=array();
foreach ($_POST as $k=>$v) {
    if (preg_match('/p_(a|m|h|M|dom|dow)+(.*)/', $k, $matches)) {
        if (!array_key_exists($matches[2], $crons)) {
            $crons[$matches[2]]=array();
        }
        $crons[$matches[2]][$matches[1]]=preg_replace('/ +/','', $v);
    }
}

exec("crontab -l", $cronFileLines);

$gotbegin=false;
$gotend=false;
foreach ($cronFileLines as $line) {
    if (!$gotbegin and strpos($line, '#MedShake')===0) {
        $gotbegin=true;
    } elseif (!$gotend and strpos($line,'#/MedShake')===0) {
        $gotend=true;
    } elseif (!$gotbegin or $gotend) {
        $newCronFileLines[]=$line;
    }
}

$newCronFileLines[]='#MedShake (Ne pas modifier ou supprimer ce commentaire!)';
foreach ($crons as $k=>$cron) {
    if (!isset($cron['a']) or $cron['a'] == 'false') {
        continue;
    }
    $newCronFileLines[]=$cron['m'].' '.$cron['h'].' '.$cron['M'].' '.$cron['dom'].' '.$cron['dow'].' cd '.$p['config']['homeDirectory'].' && php -f cron/'.$k.'.php';
}
$newCronFileLines[]='#/MedShake (Ne pas modifier ou supprimer ce commentaire!)';

$file=tempnam(sys_get_temp_dir(), 'ct_');
file_put_contents($file, implode("\n", $newCronFileLines)."\n");
exec('/usr/bin/crontab '.$file);
unlink($file);

msTools::redirection('/configuration/');
