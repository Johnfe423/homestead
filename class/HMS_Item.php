<?php

class HMS_Item {
    var $id         = 0;
    var $term       = null;

    var $added_on   = 0;
    var $added_by   = 0;

    var $updated_on = 0;
    var $updated_by = 0;
    var $_table     = null;
    
    public function construct($id=0, $table)
    {
        if (!$id) {
            return;
        }

        $this->_table = $table;

        $this->id = $id;
        $db = new PHPWS_DB($table);
        $db->addWhere('id', $this->id);
        $result = $db->loadObject($this);
        if (!$result || PHPWS_Error::logIfError($result)) {
            $this->id = 0;
        }
    }

    public function reset()
    {
        $this->id         = 0;
    }

    public function stamp()
    {
        $now = mktime();

        if (!$this->id) {
            $this->added_on = & $now;
            //$this->added_by = Current_User::getId();
        }
        $this->updated_on = & $now;
        //$this->updated_by = Current_User::getId();
    }

    public function delete()
    {
        $db = new PHPWS_DB($this->_table);
        $db->addWhere('id', $this->id);
        //$db->setTestMode();
        $result = $db->delete();
        if(!$result || PHPWS_Error::logIfError($result)){
            return $result;
        }
        return TRUE;
    }

    public function item_tags()
    {
        $tpl['ADDED_ON']     = strftime('%c', $this->added_on);
        $tpl['UPDATED_ON']   = strftime('%c', $this->updated_on);

        
        $adder = new PHPWS_User($this->added_by);
        $tpl['ADDED_BY']     = $adder->username;

        $updater = new PHPWS_User($this->updated_by);
        $tpl['UPDATED_BY']     = $updater->username;

        $tpl['TERM']         = Term::toString($this->term, true);

        return $tpl;
    }

    /*******************
     * Mutator Methods *
     ******************/
    public function set_id($id){
        $this->id = $id;
    }

    public function get_id(){
        return $this->id;
    }

}

?>
