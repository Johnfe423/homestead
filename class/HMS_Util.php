<?php

/**
 * HMS Utility class for various functions that don't fit anywhere else
 * @author Jeremy Booker <jbooker at tux dot appstate dot edu>
 */

/************************
* Date & Time Functions *
************************/

class HMS_Util{

    /**
     * Returns an array where the keys are numeric 1-12, values are text month names
     */
    function get_months()
    {
        $months = array('1'=>'January',
                        '2'=>'February',
                        '3'=>'March',
                        '4'=>'April',
                        '5'=>'May',
                        '6'=>'June',
                        '7'=>'July',
                        '8'=>'August',
                        '9'=>'September',
                        '10'=>'October',
                        '11'=>'November',
                        '12'=>'December');

        return $months;
    }

    /**
     * Returns an array of days of of the month (1-31), keys and values match.
     */
    function get_days()
    {
        for($d = 1; $d <= 31; $d++) {
            $days[$d] = $d;
        }
        
        return $days;
    }

    /**
     * Returns an array of the current year and the next year. Keys and values match.
     */
    function get_years_2yr(){
        return array(date('Y')=>date('Y'), date('Y') + 1=>date('Y') + 1);
    }

    /**
     * Returns an array of hours 12 hour format, indexed in 24 hour
     */
    function get_hours(){
        $hours = array();

        $hours[0] = '12 AM';

        for($i=1; $i < 24; $i++){            
            $hours[$i] = $i;

            if($i == 12){
                $hours[12] = "12 PM";
                continue;
            }

            if($i >= 12){
                $hours[$i] = $i-12 . ' PM';
            }else{
                $hours[$i] = "$i AM";
            }
        }

        return $hours;
    }

    /**
     * Return a date in the format dd-mm-yy given a timestamp
     *
     * @param int $timestamp
     */
    function get_short_date($timestamp) {
        if(!isset($timestamp))
            $timestamp = mktime();
     
        return date('j-n-y', $timestamp);
    }

    /**
     * Return a date in long format dd-mm-yyyy given a timestamp
     *
     * @param int $timestamp
     */
    function get_long_date($timestamp) {
        if(!isset($timestamp))
            $timestamp = mktime();

        return date('n-j-Y', $timestamp);
    }

    /**
     * Return a date in super long format eg. 7th-November-2007
     *
     * @param int $timestamp
     */
    function get_super_long_date($timestamp) {
        if(!isset($timestamp))
            $timestamp = mktime();

        return date('jS-M-Y', $timestamp);
    }

    /**
     * Returns a date and time in the long format eg. November 7th, 2007 3:00 PM
     *
     * @param int $timestamp
     */
    function get_long_date_time($timestamp)
    {
        if(!isset($timestamp)){
            $timestamp = mktime();
        }

        return date('M jS, Y g:i A', $timestamp);
    }
    
    /**
     * Determines which color the title bar should be based on
     * the selected and current terms.
     */
    function get_title_class(){
        PHPWS_Core::initModClass('hms', 'HMS_Term.php');

        $selected_term = HMS_Term::get_selected_term();
        $current_term = HMS_Term::get_current_term();
        
        if($selected_term < $current_term){
            return "box-title-red";
        }else if($selected_term == $current_term){
            return "box-title-green";
        }else if($selected_term > $current_term){
            return "box-title-blue";
        }else{
            return "box-title";
        }
    }

    function formatGender($gender)
    {
        switch ($gender) {
        case FEMALE:
            return FEMALE_DESC;
           
        case MALE:
            return MALE_DESC;

        case COED:
            return COED_DESC;

        default:
            return 'Error: Unknown gender';
        }
    }

    function formatClass($class)
    {
        switch($class){
            case CLASS_FRESHMEN:
                return 'Freshmen';
            case CLASS_SOPHOMORE:
                return 'Sophomore';
            case CLASS_JUNIOR:
                return 'Junior';
            case CLASS_SENIOR:
                return 'Senior';
            default:
                return 'Unknown';
        }
    }

    function formatType($type)
    {
        switch($type){
            case TYPE_FRESHMEN:
                return 'New freshmen';
            case TYPE_TRANSFER:
                return 'Transfer';
            default:
                return 'Unknown';
        }
    }

    function formatMealOption($meal)
    {
        switch($meal){
            case BANNER_MEAL_NONE:
                return 'None';
            case BANNER_MEAL_LOW:
                return 'Low';
            case BANNER_MEAL_STD:
                return 'Standard';
            case BANNER_MEAL_HIGH:
                return 'High';
            case BANNER_MEAL_SUPER:
                return 'Super';
            default:
                return 'Unknown';
        }
    }


    // when fed a number, adds the English ordinal suffix. Works for any
    // number, even negatives
    function ordinal($number) {
        if ($number % 100 > 10 && $number %100 < 14){
            $suffix = "th";
        }else{
            switch($number % 10) {

                case 0:
                    $suffix = "th";
                    break;

                case 1:
                    $suffix = "st";
                    break;

                case 2:
                    $suffix = "nd";
                    break;

                case 3:
                    $suffix = "rd";
                    break;

                default:
                    $suffix = "th";
                    break;
            }
        }

        return "${number}<SUP>$suffix</SUP>";
    }
}
?>
