<script type="text/javascript">
//<![CDATA[

var res_hall_drop   = 'phpws_form_residence_hall';
var floor_drop      = 'phpws_form_floor';
var room_drop       = 'phpws_form_room';

var xmlHttp;

function handle_hall_change()
{
    // Reset and disable all the lower-order drop downs
    resetDrop(floor_drop);
    resetDrop(room_drop);

    disableDrop(floor_drop);
    disableDrop(room_drop);

    // Get the selected value
    var hallId = document.getElementById(res_hall_drop).options[document.getElementById(res_hall_drop).selectedIndex].value
    
    // the default value is selected
    if(hallId == 0){
        //alert('default selected');
        return;
    }
    
    // Set the floor drop down to "loading"
    setSingleOption(floor_drop, "Loading...");
    
    // Assemble the necessary URL
    var requestURL = document.location + '?mod=hms&type=xml&op=get_floors_with_vacancies&hall_id=' + hallId;

    //alert('request URL: ' + requestURL);

    xmlHttp = createXMLHttp();
    xmlHttp.open("GET", requestURL, true);
    xmlHttp.onreadystatechange = function () {
        if(xmlHttp.readyState == 4){
            handle_hall_response();
        }
    };
    xmlHttp.send(null);
    //alert('Query sent');
}

function handle_hall_response()
{
    if(xmlHttp.status != 200){
        //alert('An error occurred. HTTP status code: ' + xmlHttp.status); 
        return;
    }else{
        //alert('Received response!');
    }

    setSingleOption(floor_drop, 'Select...');

    var response = xmlHttp.responseXML;

    var floors = response.firstChild;

    for(i = 0; i < floors.childNodes.length; i++){
	    floor = floors.childNodes[i];
	    if (floor.nodeType == 3) {
	        continue;
        }
	    for (j = 0; j < floor.childNodes.length; j++) {
	        sub = floor.childNodes[j];
            if (sub.nodeType == 3) {
	            continue;
            }
	        if (sub.nodeName == 'id') {
                id = sub.firstChild.nodeValue;
                //alert('id is ' + id);
            } 
	        if (sub.nodeName == 'floor_num') {
                floor_num = sub.firstChild.nodeValue;
                //alert('floor_num is ' + floor_num);
                var drop = document.getElementById(floor_drop);
                drop.options[drop.options.length] = new Option(floor_num, id, false, false);
            } 
        }
    }

    enableDrop(floor_drop);
}

function handle_floor_change()
{
    // Reset and disable all the lower-order drop downs
    resetDrop(room_drop);
    disableDrop(room_drop);

    // Get the selected value
    var floorId = document.getElementById(floor_drop).options[document.getElementById(floor_drop).selectedIndex].value
    
    // the default value is selected
    if(floorId == 0){
        //alert('default selected');
        return;
    }
    
    // Set the floor drop down to "loading"
    setSingleOption(room_drop, "Loading...");
    
    // Assemble the necessary URL
    var requestURL = document.location + '?mod=hms&type=xml&op=get_rooms_with_vacancies&floor_id=' + floorId;
    
    //alert('request URL: ' + requestURL);
    
    xmlHttp = createXMLHttp();
    xmlHttp.open("GET", requestURL, true);
    xmlHttp.onreadystatechange = function () {
        if(xmlHttp.readyState == 4){
            handle_floor_response();
        }
    };
    xmlHttp.send(null);
    //alert('Query sent');
}

function handle_floor_response()
{
    if(xmlHttp.status != 200){
        alert('An error occurred.. HTTP status code: ' + xmlHttp.status);
    }else{
        //alert('Received response!');
    }

    setSingleOption(room_drop, 'Select...');

    var response = xmlHttp.responseXML;

    //alert(response);

    var rooms = response.firstChild;

    for(var i = 0; i < rooms.childNodes.length; i++){
        var room = rooms.childNodes[i];
        if(room.nodeType == 3){
            continue;
        }
        for (j = 0; j < room.childNodes.length; j++){
            sub = room.childNodes[j];
            if(sub.nodeType == 3){
                continue;
            }
            if(sub.nodeName == 'id'){
                id = sub.firstChild.nodeValue;
                //alert('id is ' + id);
            }
            if(sub.nodeName == 'room_num') {
                room_num = sub.firstChild.nodeValue;
                //alert('room_num is ' + room_num);
                var drop = document.getElementById(room_drop);
                drop.options[drop.options.length] = new Option(room_num, id, false, false);
            }
        }
    }

    enableDrop(room_drop);
}

function handle_room_change()
{
    // Get the selected value
    var roomId = document.getElementById(room_drop).options[document.getElementById(room_drop).selectedIndex].value
    
    // the default value is selected
    if(roomId == 0){
        //alert('default selected');
        document.getElementById('phpws_form_submit').disabled = true;
        return;
    }
    
    document.getElementById('phpws_form_submit').disabled = false;
}

// Clears a drop down's options
function resetDrop(dropDownId)
{
    document.getElementById(dropDownId).options.length = 0;
}

// Disables a drop down
function disableDrop(dropDownId)
{
    document.getElementById(dropDownId).disabled = true;
}

// Enables a drop down
function enableDrop(dropDownId)
{
    document.getElementById(dropDownId).disabled = false;
}

// Clears a drop down's options and creates a single "default" option
function setSingleOption(dropDownId, text)
{
    // Clear the drop down first
    resetDrop(dropDownId);
    
    // Create the option
    document.getElementById(dropDownId).options[0] = new Option(text, 0, true, true);
}

function createXMLHttp()
{
    if (typeof XMLHttpRequest != "undefined") {
        return new XMLHttpRequest();
    } else if(window.ActiveXObject) {
        var aVersions = ["MSXML2.XMLHttp.5.0",
                            "MSXML2.XMLHttp.4.0",
                            "MSXML2.XMLHttp.3.0",
                            "MSXML2.XMLHttp",
                            "Microsoft.XMLHttp"];
        for (var i = 0; i < aVersions.length; i++) {
            try{
                var oXMLHttp = new ActiveXObject(aVersions[i]);
                return xXMLHttp;
            } catch (oError) {
                // Do nothing
            }
        }
    }

    throw new Error("XMLHttp object could not be created.");
}

//]]>
</script>