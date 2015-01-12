<?php
/**
 * Created by PhpStorm.
 * User: dmitrijdorozkin
 * Date: 22.10.14
 * Time: 7:12
 */

class model {
    private $_db;

    function __construct() {
        try {
            include 'db.config.php';
            $this->_db->exec('SET NAMES utf8mb4');
        } catch (PDOException $e) {

        }
    }

    function __destruct() {
        $this->_db = NULL;
    }

    protected function catch_db_error($query) {
        $this->_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        try {
            $dbh = $this->_db->query($query);
        } catch (Exception $e) {
            echo $e->getMessage();die();
        }
        if(!$dbh){
            print $query;
            die(json_encode(array("Error" => "Mysql syntax error.")));
        }
        return $dbh;
    }

    protected function select_one($query) {
        return $this->catch_db_error($query)->fetch(PDO::FETCH_ASSOC);
    }

    private function select($query) {
        return $this->catch_db_error($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    private function replace($query) {
        $this->catch_db_error($query);
        return $this->_db->lastInsertId();
    }

    private function insert($query) {
        $this->catch_db_error($query);
        return $this->_db->lastInsertId();
    }

    private function delete($query) {
        return $this->catch_db_error($query);
    }

    private function update($query) {
        return $this->catch_db_error($query);
    }

    public function getToken_vk($id) {
        $data = $this->select_one('SELECT `UserID`,`Token` FROM `UserPrivate` WHERE `VkID` = "'.$id.'"');
        if(!empty($data)) {
            return $data;
        } else
            return false;
    }

    public function register_vk($id) {
        $token = md5(uniqid(rand(),1));
        $userID = $this->insert('INSERT INTO `UserPrivate` (`Token`,`VkID`) VALUES ("'.$token.'","'.$id.'")');
        return array(
            'Token'  => $token,
            'UserID' => $userID
        );
    }

    public function update_profile($data,$userID) {
        $profile = $this->select_one('SELECT `UserID` FROM `UserProfile` WHERE `UserID` = "'.$userID.'"');

        $keys = array();
        $values = array();

        foreach ($data as $arg => $value) {
            if (($arg=='City') or ($arg=='favorite_place') or ($arg=='Study') or ($arg=='favorite_place') or ($arg=='Career')) {
                if(!empty($value)) ${$arg} = $value;
                unset($data[$arg]);
            }
        }

        if(empty($profile)) {

            $keys[] = '`UserID`';
            $values[] = '"'.$userID.'"';

            foreach ($data as $arg => $value) {
                $keys[] = '`'.$arg.'`';
                $values[] = '"'.$value.'"';
            }

            $result = $this->insert('INSERT INTO `UserProfile` ('.implode(',',$keys).') VALUES ('.implode(',',$values).')');
        } else {
            $part = array();

            foreach ($data as $arg => $value) {
                $part[] = '`'.$arg.'` = "'.$value.'"';
            }


            $this->update('UPDATE `UserProfile` SET '.implode(', ',$part).' WHERE `UserID` = "'.$userID.'"');
            $result = true;
        }

        if(isset($City)) {
            //$this->insert('INSERT INTO `UserCity` (`City`,`UserID`) VALUES ("'.$City.'","'.$userID.'") ON DUPLICATE KEY UPDATE `City` = "'.$City.'"');
        }

        if(isset($favorite_place)) {
            $this->insert('INSERT INTO `UserFavoritePlace` (`Data`,`UserID`) VALUES ("'.$favorite_place.'","'.$userID.'")  ON DUPLICATE KEY UPDATE `Data` = "'.$favorite_place.'"');
        }

        if(isset($Study)) {
            $this->insert('INSERT INTO `UserStudy` (`Data`,`UserID`) VALUES ("'.$Study.'","'.$userID.'")  ON DUPLICATE KEY UPDATE `Data` = "'.$Study.'"');
        }

        if(isset($Career)) {
            $this->insert('INSERT INTO `UserCareer` (`Data`,`UserID`) VALUES ("'.$Career.'","'.$userID.'")  ON DUPLICATE KEY UPDATE `Data` = "'.$Career.'"');
        }

        return $result?true:false;
    }

    public function checkUserToken($token) {
        $profile = $this->select_one('SELECT `UserID` FROM `UserPrivate` WHERE `Token` = "'.$token.'"');
        return empty($profile)?false:$profile['UserID'];
    }

    public function getUserProfile($userID) {
        $profile = $this->select_one('SELECT `SearchType`,`FirstName`,`LastName`,`Bio`,`Virgin`,`Sex`,`WorldView`,`Alcohol`,`Smoking`,`Ideal`
                                      FROM `UserProfile` WHERE `UserID` = "'.$userID.'"');

        if(!empty($profile)) {

            unset($profile['id']);

            $favoriteText = $this->select_one('SELECT `Data` FROM `UserFavoritePlace` WHERE `UserID` = "'.$userID.'"');
            $userStudy = $this->select_one('SELECT `Data` FROM `UserStudy` WHERE `UserID` = "'.$userID.'"');
            $userCareer = $this->select_one('SELECT `Data` FROM `UserCareer` WHERE `UserID` = "'.$userID.'"');

            $result['UserID'] = $userID;
            $result['FirstName'] = $profile['FirstName'];
            $result['LastName'] = $profile['LastName'];

            $result['Photos'] = $this->select('SELECT `URL`,`Role` FROM `UserPhotos` WHERE `UserID` = "'.$userID.'"');

            $result['Favorite_Text'] = $favoriteText['Data'];
            $result['Study'] = $userStudy['Data'];
            $result['Career'] = $userCareer['Data'];
            $result['Bio'] = $profile['Bio'];
            $result['Ideal'] = $profile['Ideal'];
            $result['SearchType'] = $profile['SearchType'];
            $result['Virgin'] = $profile['Virgin'];
            $result['Alcohol'] = $profile['Alcohol'];
            $result['Smoking'] = $profile['Smoking'];
            $result['WorldView'] = $profile['WorldView'];

            return $result;
        } else
            return false;
    }

    public function addUserImage($url,$role,$userID) {
        $result = $this->insert('INSERT INTO `UserPhotos` (`URL`,`Role`,`UserID`) VALUES ('.$url.','.$role.','.$userID.')');
        return empty($result)?false:true;
    }

    public function checkImageRole($role,$userID) {
        $img = $this->select_one('SELECT `id` FROM `UserPhotos` WHERE `Role` = "'.$role.'" AND `UserID` = "'.$userID.'"');
        return empty($img)?false:$img['id'];
    }

    public function changeImageRole($role,$imgID) {
        $this->update('UPDATE `UserPhotos` SET `Role` = "'.$role.'" WHERE `id` = "'.$imgID.'"');
        return true;
    }

    public function get_user_stage($userID) {
        $profile = $this->select_one('SELECT `Stage`,`Data`,`ChatWithID` FROM `UserStage` WHERE `UserID` = "'.$userID.'"');
        return empty($profile)?0:$profile;
    }

    public function update_stage($stage, $userID, $chatWithID = null, $data = null) {
        $this->update('INSERT INTO `UserStage` (`Stage`,`UserID`,`Data`,`ChatWithID`)
                       VALUES ("'.$stage.'","'.$userID.'","'.$data.'","'.$chatWithID.'")
                       ON DUPLICATE KEY UPDATE
                       `Stage` = "'.$stage.'",
                       `Data` = "'.$data.'",
                       `ChatWithID` = "'.$chatWithID.'"');
        return true;
    }

    public function get_touch_slides() {
        return $this->select('SELECT * FROM `Characters`');
    }

    public function set_user_characters($group,$characters,$userID) {

        $this->delete('DELETE FROM `UserGroup` WHERE `UserID` = "'.$userID.'"');
        foreach($group as $value) {
            $result = $this->insert('INSERT INTO `UserGroup` (`GroupID`,`UserID`) VALUES ('.$value.','.$userID.')');
        }

        $this->delete('DELETE FROM `UserCharacters` WHERE `UserID` = "'.$userID.'"');
        foreach($characters as $value) {
            $result = $this->insert('INSERT INTO `UserCharacters` (`CharacterID`,`UserID`) VALUES ('.$value.','.$userID.')');
        }

        return empty($result)?false:true;
    }

    public function get_users_random($userID) {
        /* scaling advice: store user group in session */
        $userGroups = $this->select('SELECT `GroupID` FROM `UserGroup` WHERE `UserID` = "'.$userID.'"');

        $associations = array(
            1 => array(4),
            2 => array(3),
            3 => array(2,3),
            4 => array(1,4),
            5 => array(5)
        );

        $offered = array();

        foreach($userGroups as $userGroup) {
            $offered[] = $associations[intval($userGroup['GroupID'])];
        }

        $offered_groups = array();

        foreach($offered as $groups) {
            foreach($groups as $group) {
                $offered_groups[] = '`GroupID` = "'.$group.'"';
            }
        }

        $offered_groups = implode(' OR ', $offered_groups);

        $userIDs = $this->select('SELECT `UserID` FROM `UserGroup` WHERE ('.$offered_groups.') AND `UserID` <> "'.$userID.'" ORDER BY RAND() LIMIT 8');

        $result = array();

        foreach($userIDs as $offeredID) {
            $result[] = $this->getUserProfile($offeredID['UserID']);
        }

        return empty($result)?false:$result;
    }

    public function get_favorites($userID) {
        $users = $this->select('SELECT `FavoriteID` FROM `UserFavorite` WHERE `UserID` = "'.$userID.'"');

        $result = array();

        foreach ($users as $user) {
            $result[] = $this->getUserProfile($user['FavoriteID']);
        }

        return $result;
    }
}