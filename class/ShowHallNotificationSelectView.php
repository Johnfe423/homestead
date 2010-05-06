<?php
/**
 * ShowHallNotificationSelectView
 *
 *  Creates the interface for showing hall selection for notification.
 *
 * @author Daniel West <lw77517 at appstate dot edu>
 * @package mod
 * @subpackage hms
 */

PHPWS_Core::initModClass('hms', 'View.php');

class ShowHallNotificationSelectView extends View {

    public function show(){
        if(!Current_User::allow('hms', 'email_hall')){
             return PHPWS_Template::process($tpl, 'hms', 'admin/permission_denied.tpl');
        }
        
        PHPWS_Core::initModClass('hms', 'HMS_Residence_Hall.php');
        
        $halls = HMS_Residence_Hall::get_halls(Term::getSelectedTerm());

        $submitCmd = CommandFactory::getCommand('ShowHallNotificationEdit');
        $form = new PHPWS_Form('select_halls_to_email');
        $submitCmd->initForm($form);

        $tpl=array();
        
        /*
        if(Current_User::allow('hms', 'email_all')){
            
            foreach($halls as $hall){
                if($hall->is_online != 1){
                    continue;
                } else {
                    $form->addCheck('hall['.$hall->id.']', $hall->id);
                    $form->setLabel('hall['.$hall->id.']', $hall->hall_name);
                }
            }
            
            $form->addSubmit('submit', 'Continue');
            
            $i=0;
            $elements = $form->getTemplate();
            foreach($elements as $row){
                //put the first and last elements directly into the template, not the row repeat because they are form tags
                if($i == 0){ 
                    $tpl['START_FORM'] = $row;
                    $i++;
                    continue;
                } elseif($i == sizeof($elements)-1){
                    $tpl['END_FORM'] = $row;
                    break;
                }
               
                //even numbered rows are checkboxes, odd are labels
                if($i % 2 == 1)
                    $tpl['halls_list'][$i+1]['LABEL'] = $row; //group the label with the checkbox
                else
                    $tpl['halls_list'][$i]['SELECT'] = $row;

                $i++;
            }
        } else {
            //TODO use the SelectResidenceHall command here
            $halls_array = array();
            foreach($halls as $hall){
                $halls_array[$hall->id] = $hall->hall_name;
            }
            
            $form->addDropBox('hall', $halls_array);
            $form->setLabel('hall', 'Choose a hall:');
            $form->addSubmit('submit', 'Continue');
            $form->mergeTemplate($tpl);
            $tpl = $form->getTemplate();
        }
        */
        javascript('jquery_ui');

        $cmd = CommandFactory::getCommand('ShowHallNotificationEdit');
        $form = new PHPWS_Form("select_halls");
        $cmd->initForm($form);
        $form->addSubmit('submit', 'Submit');
        $form->setExtra('submit', 'onclick="submitHallList();"');
        $tpl = $form->getTemplate();
        
        return PHPWS_Template::process($tpl, 'hms', 'admin/messages.tpl').Layout::getJavascript("modules/hms/hall_expander", array("DIV"=>"hall_list", "FORM"=>"select_halls"));
    }
}
?>
