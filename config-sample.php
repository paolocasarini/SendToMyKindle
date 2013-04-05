<?php
/**
 *  This file is part of SendToMyKindle.
 *
 *  Copyright © 2012-2013 Paolo Casarini
 *
 *  SendToMyKindle is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  SendToMyKindle is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with SendToMyKindle.  If not, see <http://www.gnu.org/licenses/>.
 */

// site parameters
$config_scripturl = 'http://host.mydomain.com/app.php';

// Zend_Mail_Transport_Smtp config
$config_smtp_sender = 'youraddress@gmail.com';
$config_smtp_server = 'smtp.gmail.com';
$config_smtp = array(
    'ssl' => 'tls',
    'port' => 587,
    'auth' => 'login',
    'username' => 'youraddress@gmail.com',
    'password' => 'secret'
);

// DropBox App parameters: replace the following with real values
$config_consumerKey = '__dropbox_consumerKey__';
$config_consumerSecret = '__dropbox_consumerSecret__';
