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

require_once 'SimpleDropbox.php';
require_once 'config.php';
require_once 'Zend/Loader/Autoloader.php';
Zend_Loader_Autoloader::getInstance();

$transport = new Zend_Mail_Transport_Smtp($config_smtp_server, $config_smtp);
Zend_Mail::setDefaultTransport($transport);

$email = (isset($_REQUEST['email'])) ? $_REQUEST['email'] : null;
$oauth_token = (isset($_REQUEST['oauth_token'])) ? $_REQUEST['oauth_token'] : null;
if (!empty($email)) {
    $dropbox = new SimpleDropbox(
        'SendToMyKindle', $config_consumerKey, $config_consumerSecret,
        array('redirectUrl' => $config_scripturl . '?email=' . urlencode($email)));
    if ($dropbox->authorize($email, $oauth_token)) {
        $metadata = $dropbox->getMetaData('/');
        if (count($metadata['contents']) > 0) {
            $message = "<ul>\n";
            foreach ($metadata['contents'] as $fileData) {
                $blob = $dropbox->getFile($fileData['path']);
        
                $mail = new Zend_Mail();
                $mail->setType(Zend_Mime::MULTIPART_MIXED);
                $attachment = new Zend_Mime_Part($blob);
                $attachment->type        = 'application/octet-stream';
                $attachment->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
                $attachment->encoding    = Zend_Mime::ENCODING_BASE64;
                $attachment->filename    = substr($fileData['path'], 1);
                $mail->addAttachment($attachment);
                $mail->setBodyText($fileData['path']);
                $mail->setBodyHtml($fileData['path']);
                $mail->setFrom($config_smtp_sender, 'SendToMyKindle');
                $mail->addTo($email);
                $mail->setSubject('SendToMyKindle');
                $mail->send();
        
                $message .= "<li>" . $attachment->filename . "</li>\n";
                $dropbox->delete($fileData['path']);
            }
            out("Delivered documents", $message . "</ul>\n");
        } else {
            out("No documents delivered", "The <em>app folder</em> is empty!");
        }
    } else {
        out("Error", "Reload the page to retry...");
    }
}
out("Error", "No email specified.");

function out($title, $message) {
?>
<html>
    <head>
        <title>SendToMyKindle - App</title>
        <link rel="stylesheet" type="text/css" href="projectsite.css" media="all"/>        
    </head>
    <body>
        <div id="wrapper">
            <h1>SendToMyKindle</h1>
            <ul class="tabset_tabs">
                <li>
                    <a href="app.php" class="active">App Result</a>
                </li>
            </ul>
            <div id="tab1" class="tabset_content">
                <h3><?php echo $title ?></h3>
                <p><?php echo $message ?></p>
                <p><a href="/">Back to Home</a></p>
            </div>
            Copyright &copy; 2012-2013 <a href="http://www.casarini.org" target="_new">Paolo Casarini</a>
        </div>
    </body>
</html>
<?php
    die;
}
