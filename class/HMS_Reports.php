<?php

class HMS_Reports{

    function display_reports()
    {
        PHPWS_Core::initModClass('hms', 'HMS_Forms.php');
        return HMS_Form::display_reports();
    }
    
    function run_report()
	{
	    switch($_REQUEST['reports'])
	    {
            case 'housing_apps':
                return HMS_Reports::run_applicant_demographics_report();
                break;
            case 'housing_asss':
                return HMS_Reports::run_housing_demographics_report();
                break;
            case 'unassd_rooms':
                return HMS_Reports::run_unassigned_rooms_report();
                break;
            case 'unassd_beds':
                return HMS_Reports::run_unassigned_beds_report();
                break;
            case 'reqd_roommate':
                return HMS_Reports::run_unconfirmed_roommates_report();
                break;
            case 'assd_alpha':
                return HMS_Reports::run_assigned_students_report();
                break;
            case 'special':
                return HMS_Reports::run_special_circumstances_report();
                break;
            case 'hall_structs':
                return HMS_Reports::display_hall_structures();
                break;
            case 'unassd_apps':
                return HMS_Reports::run_unassigned_applicants_report();
                break;
            case 'no_ban_data':
                return HMS_Reports::run_no_banner_data_report();
                break;
            default:
                return "ugh";
                break;
        }
    }
    
    function run_housing_demographics_report()
	{
	    PHPWS_Core::initModClass('hms', 'HMS_SOAP.php');
	    
        $db = &new PHPWS_DB('hms_residence_hall');
        $db->addColumn('id');
        $db->addColumn('hall_name');
        $db->addWhere('deleted', '0');
        $db->addOrder('hall_name', 'asc');
        $result = $db->select();

        if(PEAR::isError($result)) {
            PHPWS_Error::log($result);
            return '<font color="red"><b>A database error occurred running this report.  Please contact Electronic Student Services immediately.</b></font>';
        }
        
        foreach($result as $line) {
            $db = &new PHPWS_DB('hms_assignment');
            $db->addColumn('hms_assignment.asu_username');
            $db->addWhere('bed_id', 'hms_beds.id');
            $db->addWhere('hms_beds.bedroom_id', 'hms_bedrooms.id');
            $db->addWhere('hms_bedrooms.room_id', 'hms_room.id');
            $db->addWhere('hms_room.floor_id', 'hms_floor.id');
            $db->addWhere('hms_floor.building', 'hms_residence_hall.id');
            $db->addWhere('hms_residence_hall.id', $line['id']);

            $db->addWhere('hms_assignment.deleted', 0);
            $db->addWhere('hms_beds.deleted', 0);
            $db->addWhere('hms_bedrooms.deleted', 0);
            $db->addWhere('hms_room.deleted', 0);
            $db->addWhere('hms_floor.deleted', 0);
            $db->addWhere('hms_residence_hall.deleted', 0);

            $db->addWhere('hms_bedrooms.is_online', 1);
            $db->addWhere('hms_room.is_online', 1);
            $db->addWhere('hms_floor.is_online', 1);
            $db->addWhere('hms_residence_hall.is_online', 1);

            $stuffs = $db->select();
         
            if(PEAR::isError($stuffs)) {
                PHPWS_Error::log($stuffs);   
                return '<font color="red"><b>A database error occurred running this report.  Please contact Electronic Student Services immediately.</b></font>';
            }

            foreach($stuffs as $stuff) {
                $person = HMS_SOAP::get_gender_class($stuff['asu_username']);
                if(isset($person) && $person != NULL) {
                    if(!isset($person['gender']) || $person['gender'] == NULL ||
                            ($person['gender'] != 'M' && $person['gender'] != 'F')) {
                        $problems[] = $stuff['asu_username'] .
                            ': Gender is unrecognized ('.$person['gender'].')';
                    }
                    if(!isset($person['class']) || $person['class'] == NULL ||
                            ($person['class'] != 'NFR' && $person['class'] != 'FR' &&
                             $person['class'] != 'SO' && $person['class'] != 'JR' &&
                             $person['class'] != 'SR')) {
                        $problems[] = $stuff['asu_username'] .
                            ': Class is unrecognized ('.$person['class'].')';
                    }
                    if(!isset($person['type']) || $person['type'] == NULL ||
                            ($person['type'] != 'C' && $person['type'] != 'T' &&
                             $person['type'] != 'F')) {
                        $problems[] = $stuff['asu_username'] .
                            ': Type is unrecognized ('.$person['type'].')';
                    }

                    if(    ($person['type'] == 'F' && $person['class'] != 'NFR' && 
                            $person['class'] != 'FR') ||
                           ($person['type'] != 'F' && $person['class'] == 'NFR')) {
                        $problems[] = $stuff['asu_username'] .
                        ': Type is '.$person['type'].' but Class is '.$person['class'];
                    }
                } else {
                    $problems[] = $stuff['asu_username'] .
                        ': PERSON is unset or is null';
                }
                
                $t = $person['type'];
                $g = $person['gender'];
                $c = $person['class'];

                if(isset($building[$line['hall_name']][$t][$c][$g])) {
                    $building[$line['hall_name']][$t][$c][$g]++;
                } else {
                    $building[$line['hall_name']][$t][$c][$g] = 1;
                }
            }
        }

        $total['F']['NFR']['M'] = 0;
        $total['F']['FR']['M']  = 0;
        $total['C']['FR']['M']  = 0;
        $total['C']['SO']['M']  = 0;
        $total['C']['JR']['M']  = 0;
        $total['C']['SR']['M']  = 0;
        $total['T']['FR']['M']  = 0;
        $total['T']['SO']['M']  = 0;
        $total['T']['JR']['M']  = 0;
        $total['T']['SR']['M']  = 0;
        $total['F']['NFR']['F'] = 0;
        $total['F']['FR']['F']  = 0;
        $total['C']['FR']['F']  = 0;
        $total['C']['SO']['F']  = 0;
        $total['C']['JR']['F']  = 0;
        $total['C']['SR']['F']  = 0;
        $total['T']['FR']['F']  = 0;
        $total['T']['SO']['F']  = 0;
        $total['T']['JR']['F']  = 0;
        $total['T']['SR']['F']  = 0;

        $content = '';

        if(isset($problems) && count($problems) > 0) {
            $content .= '<font color="red"><b>Some problems were found while retrieving data from Banner:</b></font><br />';
            foreach($problems as $problem) {
                $content .= $problem . '<br />';
            }
            $content .= '<br /><br />';
        }

        foreach($building as $hall) {
            ksort($hall);
            $name = key($building);
            $content .= '<table border="1">';
            $content .= '<tr><th colspan="11"><h2 style="text-align: center">' . $name . '</h2></th></tr>';
            $content .= '<tr>';
            $content .= '<td rowspan="2"></td>';
            $content .= '<th colspan="2">Freshmen (F)</th>';
            $content .= '<th colspan="4">Continuing (C)</th>';
            $content .= '<th colspan="4">Transfer (T)</th>';
            $content .= '</tr><tr>';
            $content .= '<th>0 HRS</th><th>1+ HRS</th>';
            $content .= '<th>FR</th><th>SO</th><th>JR</th><th>SR</th>';
            $content .= '<th>FR</th><th>SO</th><th>JR</th><th>SR</th>';
            $content .= '</tr><tr>';
            $content .= '<th>Male</th>';
            $content .= '<td>' . $building[$name]['F']['NFR']['M']  . '</td>';
            $content .= '<td>' . $building[$name]['F']['FR']['M']   . '</td>';
            $content .= '<td>' . $building[$name]['C']['FR']['M']   . '</td>';
            $content .= '<td>' . $building[$name]['C']['SO']['M']   . '</td>';
            $content .= '<td>' . $building[$name]['C']['JR']['M']   . '</td>';
            $content .= '<td>' . $building[$name]['C']['SR']['M']   . '</td>';
            $content .= '<td>' . $building[$name]['T']['FR']['M']   . '</td>';
            $content .= '<td>' . $building[$name]['T']['SO']['M']   . '</td>';
            $content .= '<td>' . $building[$name]['T']['JR']['M']   . '</td>';
            $content .= '<td>' . $building[$name]['T']['SR']['M']   . '</td>';
            $content .= '</tr><tr>';
            $content .= '<th>Female</th>';
            $content .= '<td>' . $building[$name]['F']['NFR']['F']  . '</td>';
            $content .= '<td>' . $building[$name]['F']['FR']['F']   . '</td>';
            $content .= '<td>' . $building[$name]['C']['FR']['F']   . '</td>';
            $content .= '<td>' . $building[$name]['C']['SO']['F']   . '</td>';
            $content .= '<td>' . $building[$name]['C']['JR']['F']   . '</td>';
            $content .= '<td>' . $building[$name]['C']['SR']['F']   . '</td>';
            $content .= '<td>' . $building[$name]['T']['FR']['F']   . '</td>';
            $content .= '<td>' . $building[$name]['T']['SO']['F']   . '</td>';
            $content .= '<td>' . $building[$name]['T']['JR']['F']   . '</td>';
            $content .= '<td>' . $building[$name]['T']['SR']['F']   . '</td>';
            $content .= '</tr></table><br /><br />';

            $total['F']['NFR']['M'] += $building[$name]['F']['NFR']['M'];
            $total['F']['FR']['M']  += $building[$name]['F']['FR']['M'];
            $total['C']['FR']['M']  += $building[$name]['C']['FR']['M'];
            $total['C']['SO']['M']  += $building[$name]['C']['SO']['M'];
            $total['C']['JR']['M']  += $building[$name]['C']['JR']['M'];
            $total['C']['SR']['M']  += $building[$name]['C']['SR']['M'];
            $total['T']['FR']['M']  += $building[$name]['T']['FR']['M'];
            $total['T']['SO']['M']  += $building[$name]['T']['SO']['M'];
            $total['T']['JR']['M']  += $building[$name]['T']['JR']['M'];
            $total['T']['SR']['M']  += $building[$name]['T']['SR']['M'];
            $total['F']['NFR']['F'] += $building[$name]['F']['NFR']['F'];
            $total['F']['FR']['F']  += $building[$name]['F']['FR']['F'];
            $total['C']['FR']['F']  += $building[$name]['C']['FR']['F'];
            $total['C']['SO']['F']  += $building[$name]['C']['SO']['F'];
            $total['C']['JR']['F']  += $building[$name]['C']['JR']['F'];
            $total['C']['SR']['F']  += $building[$name]['C']['SR']['F'];
            $total['T']['FR']['F']  += $building[$name]['T']['FR']['F'];
            $total['T']['SO']['F']  += $building[$name]['T']['SO']['F'];
            $total['T']['JR']['F']  += $building[$name]['T']['JR']['F'];
            $total['T']['SR']['F']  += $building[$name]['T']['SR']['F'];
            
            next($building);
        }
        $content .= '======================================================';

        $content .= '<table border="1">';
        $content .= '<tr><th colspan="11" style="text-align: center"><h2>TOTALS</h2></th></tr>';
        $content .= '<tr>';
        $content .= '<td rowspan="2"></td>';
        $content .= '<th colspan="2">Freshmen (F)</th>';
        $content .= '<th colspan="4">Continuing (C)</th>';
        $content .= '<th colspan="4">Transfer (T)</th>';
        $content .= '</tr><tr>';
        $content .= '<th>0 HRS</th><th>1+ HRS</th>';
        $content .= '<th>FR</th><th>SO</th><th>JR</th><th>SR</th>';
        $content .= '<th>FR</th><th>SO</th><th>JR</th><th>SR</th>';
        $content .= '</tr><tr>';
        $content .= '<th>Male</th>';
        $content .= '<td>' . $total['F']['NFR']['M']  . '</td>';
        $content .= '<td>' . $total['F']['FR']['M']   . '</td>';
        $content .= '<td>' . $total['C']['FR']['M']   . '</td>';
        $content .= '<td>' . $total['C']['SO']['M']   . '</td>';
        $content .= '<td>' . $total['C']['JR']['M']   . '</td>';
        $content .= '<td>' . $total['C']['SR']['M']   . '</td>';
        $content .= '<td>' . $total['T']['FR']['M']   . '</td>';
        $content .= '<td>' . $total['T']['SO']['M']   . '</td>';
        $content .= '<td>' . $total['T']['JR']['M']   . '</td>';
        $content .= '<td>' . $total['T']['SR']['M']   . '</td>';
        $content .= '</tr><tr>';
        $content .= '<th>Female</th>';
        $content .= '<td>' . $total['F']['NFR']['F']  . '</td>';
        $content .= '<td>' . $total['F']['FR']['F']   . '</td>';
        $content .= '<td>' . $total['C']['FR']['F']   . '</td>';
        $content .= '<td>' . $total['C']['SO']['F']   . '</td>';
        $content .= '<td>' . $total['C']['JR']['F']   . '</td>';
        $content .= '<td>' . $total['C']['SR']['F']   . '</td>';
        $content .= '<td>' . $total['T']['FR']['F']   . '</td>';
        $content .= '<td>' . $total['T']['SO']['F']   . '</td>';
        $content .= '<td>' . $total['T']['JR']['F']   . '</td>';
        $content .= '<td>' . $total['T']['SR']['F']   . '</td>';
        $content .= '</tr></table><br /><br />';
        $content .=  "<br /> ";
        if(isset($problems) && count($problems) > 0) {
            $content .= '<h2 style="color: red;">Errors:</h2>';
            $content .=  '<span style="color: red; font-weight: bold;">Unknown Gender, Type, or Class: ' . count($problems) . '</span><br /> ';
        }
        $content .=  "<br /><br /> ";

        return $content;
    }

    function run_applicant_demographics_report()
    {
        PHPWS_Core::initModClass('hms', 'HMS_SOAP.php');

        $db = &new PHPWS_DB('hms_application');
        $db->addColumn('hms_student_id');
        $db->addWhere('deleted', '0');
        $db->addOrder('hms_student_id', 'ASC');
        $results = $db->select();

        if(PEAR::isError($results)) {
            PHPWS_Error::log($results);
            return '<font color="red"><b>A database error occurred running this report.  Please contact Electronic Student Services immediately.</b></font>';
        }
        
        $content = '';

        foreach($results as $line) {
            $person = HMS_SOAP::get_gender_class($line['hms_student_id']);

            if(!$person['gender'] && !$person['class']) {
                if(isset($application['null'])) {
                    $application['null']++;
                } else {
                    $application['null'] = 1;
                }
                continue;
            }

            $g = $person['gender'];
            $c = $person['class'];

            if(isset($application[$c][$g])) {
                $application[$c][$g]++;
            } else {
                $application[$c][$g] = 1;
            }
        }

        $content .= "Housing Applications received by class and gender:<br /><br />";
        $content .= "New Freshman <br />";
        $content .= "Male: " . $application["NFR"]["M"] . "<br />";
        $content .= "Female: " . $application["NFR"]["F"] . "<br />";
        $content .= "<br />**Note: New Freshmen are classified as any freshman with 0 completed credit hours at Appalachian State University**<br />\n";
        $content .= "<br />";
        $content .= "Freshmen <br />";
        $content .= "Male: " . $application["FR"]["M"] . "<br />";
        $content .= "Female: " . $application["FR"]["M"] . "<br />";
        $content .= "<br />";
        $content .= "Sophomore <br />";
        $content .= "Male: " . $application["SO"]["M"] . "<br />";
        $content .= "Female: " . $application["SO"]["F"] . "<br />";
        $content .= "<br />";
        $content .= "Junior <br />";
        $content .= "Male: " . $application["JR"]["M"] . "<br />";
        $content .= "Female: " . $application["JR"]["F"] . "<br />";
        $content .= "<br />";
        $content .= "Senior <br />";
        $content .= "Male: " . $application["SR"]["M"] . "<br />";
        $content .= "Female: " . $application["SR"]["F"] . "<br />";
        $content .= "<br />";
        $content .= "No Class or Gender Data Available<br />";
        $content .= "Total: " . $application["null"] . "<br />";
        $content .= "<br />";
        $content .= "<br />";
    
        return $content;
    }

    function run_unassigned_rooms_report()
    {
        $db = &new PHPWS_DB('hms_residence_hall');
        $db->addColumn('hms_residence_hall.hall_name');
        $db->addColumn('hms_floor.floor_number');
        $db->addColumn('hms_room.id');
        $db->setDistinct();
        $db->addWhere('hms_assignment.bed_id',      'hms_beds.id');
        $db->addWhere('hms_beds.bedroom_id',        'hms_bedrooms.id');
        $db->addWhere('hms_bedrooms.room_id',       'hms_room.id');
        $db->addWhere('hms_room.floor_id',          'hms_floor.id');
        $db->addWhere('hms_floor.building',         'hms_residence_hall.id');
        $db->addWhere('hms_assignment.deleted',     0);
        $db->addWhere('hms_beds.deleted',           0);
        $db->addWhere('hms_bedrooms.deleted',       0);
        $db->addWhere('hms_room.deleted',           0);
        $db->addWhere('hms_floor.deleted',          0);
        $db->addWhere('hms_residence_hall.deleted', 0);
        $db->addOrder('hms_residence_hall.hall_name');
        $db->addOrder('hms_floor.floor_number');
        $db->addOrder('hms_room.id');

        $result = $db->select();

        if(PEAR::isError($result)) {
            PHPWS_Error::log($result);
            return '<font color="red"><b>A database error occurred running this report.  Please contact Electronic Student Services immediately.</b></font>';
        }

        foreach ($result as $room) {
            $ids[] = $room['id'];
        }

        $db = &new PHPWS_DB('hms_residence_hall');
        $db->addColumn('hms_residence_hall.hall_name');
        $db->addColumn('hms_floor.floor_number');
        $db->addColumn('hms_room.gender_type');
        $db->addColumn('hms_room.id','count');
        $db->addWhere('hms_room.floor_id',            'hms_floor.id');
        $db->addWhere('hms_floor.building',           'hms_residence_hall.id');
        $db->addWhere('hms_room.deleted',             0);
        $db->addWhere('hms_floor.deleted',            0);
        $db->addWhere('hms_residence_hall.deleted',   0);
        $db->addWhere('hms_room.is_online',           1);
        $db->addWhere('hms_floor.is_online',          1);
        $db->addWhere('hms_residence_hall.is_online', 1);
	    $db->addWhere('hms_room.id',                  $ids, 'not in');
        $db->addOrder('hms_residence_hall.hall_name');
        $db->addOrder('hms_floor.floor_number');
        $db->addGroupBy('hms_residence_hall.hall_name');
        $db->addGroupBy('hms_floor.floor_number');
        $db->addGroupBy('hms_room.gender_type');

        $result = $db->select();

        if(PEAR::isError($result)) {
            PHPWS_Error::log($result);
            return '<font color="red"><b>A database error occurred running this report.  Please contact Electronic Student Services immediately.</b></font>';
        }

        $content  = "<h2>Unassigned Rooms</h2>";
        $content .= '<p>Please note that this report only shows rooms that '.
                    'are <b>completely empty</b>; this means that any rooms '.
                    'that have an assignment but also have empty beds are '.
                    '<b>not</b> counted in this report.</p>';

        $hall = 'none';
        $floor = -1;
        $totalf = -1;
        $totalm = -1;
        $totalc = -1;
        $currentf = 0;
        $currentm = 0;
        $currentc = 0;
        foreach($result as $row) {
            if($floor == -1) $floor = $row['floor_number'];

            if(($floor != $row['floor_number'] || $hall != $row['hall_name']) && $hall != 'none') {
                $content .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Floor '.$floor.': (F) '.$currentf.', (M) '.$currentm.', (C) '.$currentc.'<br />';
                $floor = $row['floor_number'];
                $currentf = 0;
                $currentm = 0;
                $currentc = 0;
            }

            if($hall != $row['hall_name']) {
                $hall = $row['hall_name'];

                if($totalf > -1 && $totalm > -1 && $totalc > -1) {
                    $content .= '<b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Total: (F) '.$totalf.', (M) '.$totalm.', (C) '.$totalc.'</b><br />';
                }
                $totalf = $totalm = $totalc = 0;
                $content .= '<br /><b>'.$row['hall_name'].'</b><br />';
            }
            
//            $content .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Floor '.$row['floor_number'].': ('.$gender.') '.$row['count'].'<br />';
            
            if($row['gender_type'] == 0) {        // Female 0
                $currentf = $row['count'];
                $totalf  += $row['count'];
            } else if($row['gender_type'] == 1) { // Male 1
                $currentm = $row['count'];
                $totalm  += $row['count'];
            } else if($row['gender_type'] == 2) { // Coed 2
                $currentc = $row['count'];
                $totalc  += $row['count'];
            } else {
                // Unknown
            }
        }
        $content .= '<b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Total: (F) '.$totalf.', (M) '.$totalm.', (C) '.$totalc.'</b><br />';

        return $content;
    }

    function run_unassigned_beds_report()
    {
        $sql = "select hall.hall_name,
               floor.floor_number,
               room.gender_type,
               count(beds.id)

        from   hms_residence_hall as hall,
               hms_room as room,
               hms_floor as floor,
               hms_bedrooms as br,
               hms_beds as beds

        left outer join hms_assignment as assign
        on     beds.id = assign.bed_id

        where (beds.bedroom_id = br.id  AND
               br.room_id = room.id     AND
               room.floor_id = floor.id AND
               floor.building = hall.id AND

               beds.deleted = 0         AND
               br.deleted = 0           AND
               room.deleted = 0         AND
               floor.deleted = 0        AND
               hall.deleted  = 0        AND

               br.is_online = 1         AND
               room.is_online = 1       AND
               floor.is_online = 1      AND
               hall.is_online = 1)      AND

              (assign.bed_id is null    OR
               0 not in (select deleted from hms_assignment where bed_id = assign.bed_id))

        group by hall.hall_name, floor.floor_number, room.gender_type

        order by hall.hall_name, floor.floor_number, room.gender_type";
                
        $result = PHPWS_DB::getAll($sql);

        if(PEAR::isError($result)) {
            PHPWS_Error::log($result);
            return '<font color="red"><b>A database error occurred running this report.  Please contact Electronic Student Services immediately.</b></font>';
        }

        $content  = "<h2>Unassigned Beds</h2>";
        $content .= '<p>This report shows individual beds that have not '.
                    'been assigned.  Please note that one room may contain '.
                    'several beds.</p>';

        $hall = 'none';
        $floor = -1;
        $totalf = -1;
        $totalm = -1;
        $totalc = -1;
        $currentf = 0;
        $currentm = 0;
        $currentc = 0;
        foreach($result as $row) {
            if($floor == -1) $floor = $row['floor_number'];

            if(($floor != $row['floor_number'] || $hall != $row['hall_name']) && $hall != 'none') {
                $content .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Floor '.$floor.': (F) '.$currentf.', (M) '.$currentm.', (C) '.$currentc.'<br />';
                $floor = $row['floor_number'];
                $currentf = 0;
                $currentm = 0;
                $currentc = 0;
            }

            if($hall != $row['hall_name']) {
                $hall = $row['hall_name'];

                if($totalf > -1 && $totalm > -1 && $totalc > -1) {
                    $content .= '<b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Total: (F) '.$totalf.', (M) '.$totalm.', (C) '.$totalc.'</b><br />';
                }
                $totalf = $totalm = $totalc = 0;
                $content .= '<br /><b>'.$row['hall_name'].'</b><br />';
            }
            
//            $content .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Floor '.$row['floor_number'].': ('.$gender.') '.$row['count'].'<br />';
            
            if($row['gender_type'] == 0) {        // Female 0
                $currentf = $row['count'];
                $totalf  += $row['count'];
            } else if($row['gender_type'] == 1) { // Male 1
                $currentm = $row['count'];
                $totalm  += $row['count'];
            } else if($row['gender_type'] == 2) { // Coed 2
                $currentc = $row['count'];
                $totalc  += $row['count'];
            } else {
                // Unknown
            }
        }
        $content .= '<b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Total: (F) '.$totalf.', (M) '.$totalm.', (C) '.$totalc.'</b><br />';

        return $content;
    }
    
    function run_unconfirmed_roommates_report()
    {
        $db = &new PHPWS_DB('hms_roommate_approval');
        $db->addColumn('number_roommates');
        $db->addColumn('roommate_zero');
        $db->addColumn('roommate_one');
        $db->addColumn('roommate_two');
        $db->addColumn('roommate_three');
        $db->addOrder('roommate_zero');
        $results = $db->select();

        $content  = '<h2>Unapproved Requested Roommates</h2><br /><br />';

        PHPWS_Core::initModClass('hms', 'HMS_SOAP.php');
        $count = 0;
        foreach($results as $row) {
            $zero  = $row['roommate_zero'];
            $one   = $row['roommate_one'];
            $two   = $row['roommate_two'];
            $three = $row['roommate_three'];

            $content .= "($zero) " . HMS_SOAP::get_name($zero) . '<br />';
            $content .= "($one) "  . HMS_SOAP::get_name($one)  . '<br />';
            
            if($row['number_roommates'] > 2) {
                $content .= "($two) " . HMS_SOAP::get_name($two) . '<br />';
            }

            if($row['number_roommates'] > 3) {
                $content .= "($three) " . HMS_SOAP::get_name($three) . '<br />';
            }

            $content .= '<br />';
            
            $count++;
        }

        $content .= '<strong>Total Pairs: ' . $count . '</strong>';

        return $content;
    }

    function run_assigned_students_report()
    {
        $content = '<h2>Assigned Students Report</h2>';
        if(!isset($_REQUEST['action'])) {
            $content .= 'If anyone has created any assignments since the last time New Data was Generated, then the report will be out of date.  If in doubt, generate new data, although this will take a few minutes.<br /><br />';
        }

        switch($_REQUEST['action']) {
            case 'generate':
                PHPWS_Core::initModClass('hms', 'HMS_Assignment.php');
                HMS_Assignment::generate_student_assignment_data();
                $content .= '<font color="blue">New banner data has been generated.</font><br /><br />';
                break;
            case 'run':
                return HMS_Reports::do_assigned_students_report();
                break;
            case 'pdf':
                return HMS_Reports::create_pdf_letters();
                break;
        }
        
        $link['type']    = 'reports';
        $link['op']      = 'run_report';
        $link['reports'] = 'assd_alpha';
        $link['action']  = 'generate';
        $content .= PHPWS_Text::secureLink('Generate New Data', 'hms', $link);
        $content .= '<br /><br />';

        $link['type']    = 'reports';
        $link['op']      = 'run_report';
        $link['reports'] = 'assd_alpha';
        $link['action']  = 'run';
        $content .= PHPWS_Text::secureLink('Run Report', 'hms', $link);

        return $content;
    }

    function do_assigned_students_report()
    {
        $db = &new PHPWS_DB('hms_cached_student_info');
        $db->addOrder('last_name');
        $db->addOrder('first_name');
        $db->addOrder('middle_name');

        $results = $db->select();

        if(PHPWS_Error::isError($results)) {
            test($results,1);
        }

        $content  = '<h2>Assigned Students</h2><br />';
        $content .= '<table border="1"><tr>';
        $content .= '<th>Username</th>';
        $content .= '<th>Student</th>';
        $content .= '<th>Banner</th>';
        $content .= '<th>Assignment</th>';
        $content .= '</tr>';

        foreach($results as $row) {
            $content .= '<tr><td>';
            $content .= $row['asu_username'] . '<br />';
            $content .= '</td><td><strong>';
            $content .= $row['last_name']   . ', ';
            $content .= $row['first_name']  . ' ';
            $content .= $row['middle_name'] . '</strong><br />';
            $content .= $row['address1'] . '<br />';
            if(isset($row['address2']) && !empty($row['address2'])) {
                $content .= $row['address2'] . '<br />';
            }
            if(isset($row['address3']) && !empty($row['address3'])) {
                $content .= $row['address3'] . '<br />';
            }

            $content .= $row['city']  . ', ';
            $content .= $row['state'] . ' ';
            $content .= $row['zip']   . '<br />';
            $content .= $row['phone_number'] . '<br />';
            
            $content .= '</td><td>';
            $content .= '<b>Gender:</b> ' . $row['gender'] . '<br />';
            $content .= '<b>Type:</b> ' . $row['student_type'] . '<br />';
            $content .= '<b>Class:</b> ' . $row['class'] . '<br />';
            $content .= '<b>Credits:</b> ' . $row['credit_hours'] . '<br />';
            
            $content .= '</td><td>';
            $content .= $row['hall_name'] . ' ' . $row['room_number'];
            $content .= '</td></tr>';
        }
        $content .= '</table>';
        return $content;
    }

    function create_pdf_letters()
    {
        // TODO: Implement This
        return "Not Implemented";
    }

    function list_generated_student_assignment_data()
    {
        
    }

    function run_special_circumstances_report()
    {
        $db = &new PHPWS_DB('hms_assignment');
        $db->addWhere('hms_assignment.asu_username', 'hms_application.hms_student_id');
        $db->addWhere('hms_assignment.deleted', 0);

        $db->addColumn('hms_assignment.asu_username');

        $results = $db->select();

        $content = '<h2>Continuing Students who Filled Out Online Application</h2>';

        foreach($results as $row) {
	        PHPWS_Core::initModClass('hms', 'HMS_SOAP.php');
            $person = HMS_SOAP::get_gender_class($row['asu_username']);
            $type   = HMS_SOAP::get_student_type($row['asu_username']);

            if($person['class'] != 'NFR' && $type != 'T') {
                $content .= '<li>('.$row['asu_username'].') '.HMS_SOAP::get_full_name($row['asu_username']).'</li>';
            }
        }

        return $content;
    }

    function display_hall_structures()
    {
        $sql = "
            SELECT hms_residence_hall.id AS hall_id,
                   hms_residence_hall.banner_building_code,
                   hms_residence_hall.hall_name,
                   hms_residence_hall.is_online AS hall_online,
                   hms_floor.id AS floor_id,
                   hms_floor.floor_number,
                   hms_floor.is_online AS floor_online,
                   hms_room.id AS room_id,
                   hms_room.room_number,
                   hms_room.displayed_room_number,
                   hms_room.bedrooms_per_room,
                   hms_room.is_online AS room_online,
                   hms_bedrooms.id AS bedroom_id,
                   hms_bedrooms.bedroom_letter,
                   hms_bedrooms.number_beds,
                   hms_bedrooms.is_online AS bedroom_online,
                   hms_beds.id AS bed_id,
                   hms_beds.bed_letter
            FROM hms_residence_hall,
                 hms_floor,
                 hms_room,
                 hms_bedrooms,
                 hms_beds
            WHERE hms_beds.bedroom_id  = hms_bedrooms.id       AND
                  hms_bedrooms.room_id = hms_room.id           AND
                  hms_room.floor_id    = hms_floor.id          AND
                  hms_floor.building   = hms_residence_hall.id AND
                  hms_beds.deleted           = 0 AND
                  hms_bedrooms.deleted       = 0 AND
                  hms_room.deleted           = 0 AND
                  hms_floor.deleted          = 0 AND
                  hms_residence_hall.deleted = 0
            ORDER BY hms_residence_hall.hall_name,
                     hms_floor.floor_number,
                     hms_room.room_number,
                     hms_bedrooms.bedroom_letter,
                     hms_beds.bed_letter
        ";

        $results = PHPWS_DB::getAll($sql);

        if(PHPWS_Error::isError($results)) {
            test($results,1);
        }

        $content = '<h2>Appalachian State University Residence Halls</h2>';

        $hall_id     = -1;
        $floor_id    = -1;
        $room_id     = -1;
        $bedrooms_id = -1;
        $beds_id     = -1;

        $first_hall  = 1;
        $first_floor = 1;

        $count = 0;
        
        foreach($results as $result) {
            if($result['hall_id'] != $hall_id) {
                $count = 0;
                
                if(!$first_hall) $content .= '</table><br />';
                else $first_hall = 0;
                
                $hall_id = $result['hall_id'];
                $content .= '<table border="1">';
                $content .= '<tr><th colspan="22">(' .
                            $result['hall_id'] . ') ' .
                            $result['hall_name'] . ' (' .
                            $result['banner_building_code'];
                if($result['hall_online'] == 0) {
                    $content .= ' - OFFLINE';
                }
                $content .= ')</th></tr>';
            }
        
            if(++$count == 21)
            {
                $count = 0;
                $floor_id = -1;
            }

            if($result['floor_id'] != $floor_id) {
                $count = 0;

                if(!$first_floor) $content .= '</tr>';
                else $first_floor = 0;

                $floor_id = $result['floor_id'];
                $content .= '<tr>';
                $content .= '<th>' . $result['floor_number'] . '<br />';
                if($result['floor_online'] == 0) {
                    $content .= 'OFF<br />';
                }
                $content .= '<span style="font-size: .3em">' . 
                            '(' . $result['floor_id'] . ') ' .
                            '</span></th>';
            }

            $content .= '<td>' . $result['room_number'] .
                        $result['bedroom_letter'] .
                        $result['bed_letter'] . '<br />';
            if($result['room_online'] == 0 ||
               $result['bedroom_online'] == 0) {
                $content .= 'OFF<br />';
            }
            $content .= '<span style="font-size: .3em">' .
                        '(' . $result['room_id'] . ',' .
                        $result['bedroom_id'] . ',' .
                        $result['bed_id'] . ')</span>';
            $content .= '</td>';
        }

        $content .= '</tr>';
        $content .= '</table>';

        return $content;
    }

    function run_unassigned_applicants_report()
    {
        $sql = "
            SELECT hms_student_id AS user,
                   student_status AS status,
                   gender         AS gender
            FROM hms_application
            LEFT OUTER JOIN hms_assignment
            ON hms_assignment.asu_username = hms_application.hms_student_id
            WHERE hms_assignment.asu_username IS NULL
            ORDER BY hms_student_id
        ";
        $results = PHPWS_DB::getAll($sql);
        if(PHPWS_Error::isError($results)) {
            test($results,1);
        }

        $content = "<h2>Unassigned Applicants</h2><br />";

        PHPWS_Core::initModClass('hms','HMS_SOAP.php');
        foreach($results as $row) {
            $student = HMS_SOAP::get_student_info($row['user']);
            $app = PHPWS_Text::secureLink($row['user'], 'hms',
                array('type'    => 'student',
                      'op'      => 'view_housing_application',
                      'student' => $row['user']));
            $content .= "($app) " . $student->last_name . ", " .
                        $student->first_name . " " .
                        $student->middle_name . " [" .
                        ($row['gender'] == 0 ? "Female, " : "Male, ") .
                        ($row['status'] == 1 ? "Freshman" : "Transfer") .
                        "]<br />";
        }

        return $content;
    }

    function run_no_banner_data_report()
    {
        $db = new PHPWS_DB('hms_application');
        $db->addColumn('hms_student_id');
        $db->addOrder('hms_student_id');
        $results = $db->select();
        if(PHPWS_Error::isError($results)) {
            test($results,1);
        }

        $content = "<h2>Students With No Banner Data</h2><br />";

        PHPWS_Core::initModClass('hms','HMS_SOAP.php');
        foreach($results as $row) {
            if(!HMS_SOAP::is_valid_student($row['hms_student_id'])) {
                $content .= $row['hms_student_id'] . '<br />';
            }
        }

        return $content;
    }

    function main()
    {
        $op = $_REQUEST['op'];
        switch($op){
            case 'display_reports':
                return HMS_Reports::display_reports();
                break;
            case 'run_report':
                return HMS_Reports::run_report();
                break;
            default:
                # No such 'op', or no 'op' specified
                # TODO: Find a way to throw an error here
                return $op;
                break;
        }
    }
}

?>
