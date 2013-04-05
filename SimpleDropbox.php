<?php
/**
 *  This file is part of SendToMyKindle
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

include('Dropbox/autoload.php');

/**
 * Class to persist dropbox tokens
 */
class SimpleDropbox extends Dropbox_API {
    // CREATE TABLE tokens (app STRING, uid STRING, token STRING, secret STRING, state STRING, PRIMARY KEY (app, uid));
    const STATE_INIT = 'init';
    const STATE_REQUEST = 'request';
    const STATE_ACCESS = 'access';

    private $_db;
    
    private $_appName;
    
    private $_oauth;
    
    private $_api;
    
    private $_redirectUrl;

    public function __construct($appName, $consumerKey, $consumerSecret, $options, $root = self::ROOT_SANDBOX) {
        $this->_appName = $appName;
        $this->_redirectUrl = $options['redirectUrl'];
        
        $this->oauth = new Dropbox_OAuth_PHP($consumerKey,$consumerSecret);
        parent::__construct($this->oauth, $root);
        
        $this->_db = new PDO('sqlite:' . dirname('__FILE__') . '/dropbox.db');
        $this->_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    public function authorize($uid, $oauth_token) {
        $tokens = $this->get($this->_appName, $uid);
        if ($tokens === null) {
            $tokens = $this->oauth->getRequestToken();
            $this->set($this->_appName, $uid, $tokens['token'], $tokens['token_secret'], self::STATE_REQUEST);
            $url = $this->oauth->getAuthorizeUrl($this->_redirectUrl);
            echo '<script type="text/javascript">window.location="' . $url . '";</script>';
            die;
        } else {
            $state = $tokens['state'];
            unset($tokens['state']);
            if ($state === self::STATE_REQUEST
                    && !empty($oauth_token) 
                    && $oauth_token == $tokens['token']) {
                $this->oauth->setToken($tokens);
                $tokens = $this->oauth->getAccessToken();
                $this->set($this->_appName, $uid, $tokens['token'], $tokens['token_secret'], self::STATE_ACCESS);
                echo '<script type="text/javascript">window.location="' . $this->_redirectUrl . '";</script>';
                die;
            } else if ($state === self::STATE_ACCESS) {
                $this->oauth->setToken($tokens);
                return true;
            }
        }
        $this->del($this->_appName, $uid);
        return false;
    }

    public function get($appName, $uid) {
        $stmt = $this->_db->prepare('SELECT token, secret, state FROM tokens WHERE app = :app AND uid = :uid');
        $stmt->bindValue(':app', $appName, PDO::PARAM_STR);
        $stmt->bindValue(':uid', $uid, PDO::PARAM_STR);
        $stmt->execute();

        if (($row = $stmt->fetch()) !== false) {
            $result = array();
            $result['token'] = $row['token'];
            $result['token_secret'] = $row['secret'];
            $result['state'] = $row['state'];
            return $result;
        }

        return null;
    }

    public function set($appName, $uid, $token, $token_secret, $state) {
        try {
            $stmt = $this->_db->prepare('INSERT INTO tokens (app, uid, token, secret, state) VALUES (:app, :uid, :token, :secret, :state)');
            $stmt->bindValue(':app', $appName, PDO::PARAM_STR);
            $stmt->bindValue(':uid', $uid, PDO::PARAM_STR);
            $stmt->bindValue(':token', $token, PDO::PARAM_STR);
            $stmt->bindValue(':secret', $token_secret, PDO::PARAM_STR);
            $stmt->bindValue(':state', $state, PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000 || 23505 == $e->getCode()) {
                $stmt = $this->_db->prepare('UPDATE tokens SET token = :token, secret = :secret, state = :state WHERE app = :app AND uid = :uid');
                $stmt->bindValue(':app', $appName, PDO::PARAM_STR);
                $stmt->bindValue(':uid', $uid, PDO::PARAM_STR);
                $stmt->bindValue(':token', $token, PDO::PARAM_STR);
                $stmt->bindValue(':secret', $token_secret, PDO::PARAM_STR);
                $stmt->bindValue(':state', $state, PDO::PARAM_STR);
                $stmt->execute();
            } else {
                throw $e;
            }
        }
    }
    
    public function del($appName, $uid) {
        $stmt = $this->_db->prepare('DELETE FROM tokens WHERE app = :app AND uid = :uid');
        $stmt->bindValue(':app', $appName, PDO::PARAM_STR);
        $stmt->bindValue(':uid', $uid, PDO::PARAM_STR);
        $stmt->execute();
    }
}
