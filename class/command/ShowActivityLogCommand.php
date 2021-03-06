<?php

class ShowActivityLogCommand extends Command {

    private $acteeUsername;
    private $actorUsername;
    private $activity;

    public function setActeeUsername($username){
        $this->acteeUsername = $username;
    }

    public function setActorUsername($username){
        $this->actorUsername = $username;
    }

    public function setActivity(Array $activity){
        $this->activity = $activity;
    }

    function getRequestVars(){
        $vars = array('action'=>'ShowActivityLog');

        if(isset($this->acteeUsername)){
            $vars['actee'] = $this->acteeUsername;
        }

        if(isset($this->actorUsername)){
            $vars['actor'] = $this->actorUsername;
        }

        if(isset($this->activity) && !empty($this->activity)){
            foreach($this->activity as $act){
                $vars["a$act"] = 1;
            }
        }

        return $vars;
    }

    function execute(CommandContext $context)
    {
        if(!UserStatus::isAdmin() || !Current_User::allow('hms', 'view_activity_log')){
            PHPWS_Core::initModClass('hms', 'exception/PermissionException.php');
            throw new PermissionException('You do not have permission to view the activity log.');
        }
        
        PHPWS_Core::initModClass('hms', 'HMS_Activity_Log.php');
        PHPWS_Core::initModClass('hms', 'ActivityLogView.php');

        $actee = $context->get('actee');
        $actor = $context->get('actor');
        $notes = $context->get('notes');
        $exact = $context->get('exact');
        $begin = $context->get('begin');
        $end   = $context->get('end');

        if(!is_null($begin) && !is_null($end) && $end <= $begin) {
            unset($_REQUEST['begin_year'],
            $_REQUEST['begin_month'],
            $_REQUEST['begin_day'],
            $_REQUEST['end_year'],
            $_REQUEST['end_month'],
            $_REQUEST['end_day']);
            $begin = null;
            $end = null;

            NQ::simple('hms', hms\NotificationView::WARNING, 'Invalid date range. The search results will not be filtered by date.');
        }

        $activityMap = HMS_Activity_Log::getActivityMapping();
        $activities = array();

        foreach($activityMap as $i => $t){
            $act = $context->get("a$i");
            if(!is_null($act)){
                $activities[] = $i;
            }
        }

        $activityLogView = new ActivityLogView($actee, $actor, $notes, $exact, $begin, $end, $activities);
        $context->setContent($activityLogView->show());
    }
}

?>
