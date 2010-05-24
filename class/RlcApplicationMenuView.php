<?php

class RlcApplicationMenuView extends View {

    private $term;
    private $student;
    private $startDate;
    private $editDate;
    private $endDate;
    private $application;

    public function __construct($term, Student $student, $startDate, $editDate, $endDate, HMS_RLC_Application $application = NULL)
    {
        $this->term         = $term;
        $this->student      = $student;
        $this->startDate    = $startDate;
        $this->editDate     = $editDate;
        $this->endDate      = $endDate;
        $this->application  = $application;
    }

    public function show()
    {
        $tpl = array();

        if(isset($this->application) && !is_null($this->application->id)) {
            $viewCmd = CommandFactory::getCommand('ShowRlcApplicationReView');
            $viewCmd->setAppId($this->application->getId());
            $tpl['VIEW_APP'] = $viewCmd->getLink('view your application');

            if(time() < $this->editDate){
                $newCmd = CommandFactory::getCommand('ShowRlcApplicationView');
                $newCmd->setTerm($this->term);
                $tpl['NEW_APP'] = $newCmd->getLink('submit a new application');
            }
        }else if(time() < $this->startDate){
            $tpl['BEGIN_DEADLINE'] = HMS_Util::getFriendlyDate($this->startDate);
        }else if (time() > $this->endDate){
            $tpl['END_DEADLINE'] = HMS_Util::getFriendlyDate($this->endDate);
        }else{
            $applyCmd = CommandFactory::getCommand('ShowRlcApplicationView');
            $applyCmd->setTerm($this->term);
            $tpl['APP_NOW'] = $applyCmd->getLink('Apply for a Residential Learning Community now.');
        }

        Layout::addPageTitle("RLC Application Menu");

        return PHPWS_Template::process($tpl, 'hms', 'student/menuBlocks/RlcApplicationMenuBlock.tpl');
    }
}

?>
