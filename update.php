<?php
/*
Flare, a fully featured and easy to use crew centre, designed for Infinite Flight.
Copyright (C) 2020  Lucas Rebato

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

require_once './core/init.php';

$user = new User();
if (!$user->isLoggedIn()) {
    Redirect::to('index.php');
}

if (Input::get('action') === 'editprofile') {
    if (!Callsign::assigned(Input::get('callsign'), $user->data()->id)) {
        Session::flash('error', 'Callsign is Already Taken!');
    } else {
        try {
            $user->update(array(
                'name' => Input::get('name'),
                'callsign' => Input::get('callsign'),
                'email' => Input::get('email'),
                'ifc' => Input::get('ifc')
            ));
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
            Redirect::to('home.php');
        }
        Session::flash('success', 'Profile updated successfully!');
        Redirect::to('home.php');
    }
} elseif (Input::get('action') === 'changepass') {
    if (Hash::check(Input::get('oldpass'), $user->data()->password)) {
        try {
            $user->update(array(
                'password' => Hash::make(Input::get('newpass'))
            ));
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
            Redirect::to('home.php');
        }
        Session::flash('success', 'Password Changed Successfully!');
        Redirect::to('home.php');
    } else {
        Session::flash('error', 'Your Current Password was Incorrect!');
        Redirect::to('home.php');
    }
} elseif (Input::get('action') === 'filepirep') {
    $multi = "None";
    $finalFTime = Time::strToSecs(Input::get('ftime'));

    if (!empty(Input::get('multi'))) {
        $multiplier = Pirep::findMultiplier(Input::get('multi'));
        if (!$multiplier) {
            Session::flash('error', 'Invalid Multiplier Code');
            Redirect::to('pireps.php?page=new');
            die();
        }

        $multi = $multiplier->name;
        $finalFTime *= $multiplier->multiplier;
    }

    $user = new User();
    $allowedaircraft = $user->getAvailableAircraft();
    $allowed = false;
    foreach ($allowedaircraft as $a) {
        if ($a["id"] == Input::get('aircraft')) {
            $allowed = true;
        }
    }
    if (!$allowed) {
        Session::flash('error', 'You are not of a high enough rank to fly that aircraft. Your PIREP has not been filed.');
        Redirect::to('pireps.php?page=new');
    }

    $curl = new Curl;

    $response = VANet::sendPirep(array(
        'AircraftID' => Aircraft::idToLiveryId(Input::get('aircraft')),
        'Arrival' => Input::get('arr'),
        'DateTime' => Input::get('date'),
        'Departure' => Input::get('dep'),
        'FlightTime' => Time::strToSecs(Input::get('ftime')),
        'FuelUsed' => Input::get('fuel'),
        'PilotId' => $user->data()->ifuserid
    ));

    $response = Json::decode($response->body);
    if ($response['success'] != true) {
        Session::flash('error', 'There was an Error Connecting to VANet.');
        Redirect::to('pireps.php?page=new');
        die();
    }

    if (!Pirep::file(array(
        'flightnum' => Input::get('fnum'),
        'departure' => Input::get('dep'),
        'arrival' => Input::get('arr'),
        'flighttime' => $finalFTime,
        'pilotid' => $user->data()->id,
        'date' => Input::get('date'),
        'aircraftid' => Input::get('aircraft'),
        'multi' => $multi
    ))) {
        Session::flash('error', 'There was an Error Filing the PIREP.');
        Redirect::to('pireps.php?page=recents');
    } else {
        Session::flash('success', 'PIREP Filed Successfully!');
        Redirect::to('pireps.php?page=recents');
    }
} elseif (Input::get('action') === 'editpirep') {
    if (!Pirep::update(Input::get('id'), array(
        'flightnum' => Input::get('fnum'),
        'departure' => Input::get('dep'),
        'arrival' => Input::get('arr'),
        'pilotid' => $user->data()->id,
        'date' => Input::get('date'),
        'aircraftid' => Input::get('aircraft'),
        'multi' => Input::get('multi')
    ))) {
        Session::flash('errorrecent', 'There was an Error Editing the PIREP.');
        Redirect::to('pireps.php');
    } else {
        Session::flash('successrecent', 'PIREP Edited successfully!');
        Redirect::to('pireps.php');
    }
} elseif (Input::get('action') === 'edituser') {
    if (!$user->hasPermission('usermanage')) {
        Redirect::to('home.php');
        die();
    }

    $perms = Json::decode($user->getUser(Input::get('id'))->permissions);
    $perms["admin"] = Input::get("admin");

    $user->update(array(
        'callsign' => Input::get('callsign'),
        'name' => Input::get('name'),
        'email' => Input::get('email'),
        'ifc' => Input::get('ifc'),
        'transhours' => Time::strToSecs(Input::get('transhours')),
        'transflights' => Input::get('transflights'),
        'permissions' => Json::encode($perms),
    ), Input::get('id'));
    Session::flash('success', 'User Edited Successfully!');
    Redirect::to('admin.php?page=usermanage');
} elseif (Input::get('action') === 'deluser') {
    if (!$user->hasPermission('usermanage')) {
        Redirect::to('home.php');
        die();
    }

    try {
        $user->update(array(
            'status' => 2
        ), Input::get('id'));
    } catch (Exception $e) {
        Session::flash('error', 'There was an Error Deleting the User.');
        Redirect::to('admin.php?page=usermanage');
    }
    Session::flash('success', 'User deleted successfully!');
    Redirect::to('admin.php?page=usermanage');
} elseif (Input::get('action') === 'editstaffmember') {
    if (!$user->hasPermission('staffmanage')) {
        Redirect::to('home.php');
        die();
    }

    $myperms = Permissions::forUser(Input::get('id'));
    $permissions = Permissions::getAll();
    foreach ($permissions as $permission => $name) {
        if (Input::get($permission) == 'on' && !in_array($permission, $myperms)) {
            Permissions::give(Input::get('id'), $permission);
        } elseif (Input::get($permission) != 'on' && in_array($permission, $myperms)) {
            Permissions::revoke(Input::get('id'), $permission);
        }
    }

    try {
        $user->update(array(
            'callsign' => Input::get('callsign'),
            'name' => Input::get('name'),
            'email' => Input::get('email'),
            'ifc' => Input::get('ifc')
        ), Input::get('id'));
    } catch (Exception $e) {
        Session::flash('error', 'There was an Error Editing the Staff Member.');
        Redirect::to('admin.php?page=staffmanage');
    }
    Session::flash('success', 'Staff Member Edited Successfully!');
    Redirect::to('admin.php?page=staffmanage');
} elseif (Input::get('action') === 'declineapplication') {
    if (!$user->hasPermission('recruitment')) {
        Redirect::to('home.php');
        die();
    }

    try {
        $user->update(array(
            'status' => 3,
            'declinereason' => Input::get('declinereason')
        ), Input::get('id'));
    } catch (Exception $e) {
        Session::flash('error', 'There was an error Declining the Application.');
        Redirect::to('admin.php?page=recruitment');
    }
    Session::flash('success', 'Application Declined Successfully');
    Redirect::to('admin.php?page=recruitment');
} elseif (Input::get('action') === 'acceptapplication') {
    if (!$user->hasPermission('recruitment')) {
        Redirect::to('home.php');
        die();
    }

    try {
        $user->update(array(
            'status' => 1
        ), Input::get('accept'));
    } catch (Exception $e) {
        Session::flash('error', 'There was an Error Accepting the Application.');
        Redirect::to('admin.php?page=recruitment');
    }
    Session::flash('success', 'Application Accepted Successfully!');
    Redirect::to('admin.php?page=recruitment');
} elseif (Input::get('action') === 'acceptpirep') {
    if (!$user->hasPermission('pirepmanage')) {
        Redirect::to('home.php');
        die();
    }

    Pirep::accept(Input::get('accept'));
    Session::flash('success', 'PIREP Accepted Successfully!');
    Redirect::to('admin.php?page=pirepmanage');
} elseif (Input::get('action') === 'declinepirep') {
    if (!$user->hasPermission('pirepmanage')) {
        Redirect::to('home.php');
        die();
    }

    Pirep::decline(Input::get('decline'));
    Session::flash('success', 'PIREP Declined Successfully');
    Redirect::to('admin.php?page=pirepmanage');
} elseif (Input::get('action') === 'deletemulti') {
    if (!$user->hasPermission('pirepmanage')) {
        Redirect::to('home.php');
        die();
    }

    Pirep::deleteMultiplier(Input::get('delete'));
    Session::flash('success', 'Multiplier Deleted Successfully!');
    Redirect::to('admin.php?page=multimanage');
} elseif (Input::get('action') === 'addmulti') {
    if (!$user->hasPermission('pirepmanage')) {
        Redirect::to('home.php');
        die();
    }

    Pirep::addMultiplier(array(
        "code" => Pirep::generateMultiCode(),
        "name" => Input::get("name"),
        "multiplier" => Input::get("multi")
    ));
    Session::flash('success', 'Multiplier Added Successfully!');
    Redirect::to('admin.php?page=multimanage');
} elseif (Input::get('action') === 'deletearticle') {
    if (!$user->hasPermission('newsmanage')) {
        Redirect::to('home.php');
        die();
    }

    News::archive(Input::get('delete'));
    Session::flash('success', 'News Item Archived Successfully! ');
    Redirect::to('admin.php?page=newsmanage');
} elseif (Input::get('action') === 'editarticle') {
    if (!$user->hasPermission('newsmanage')) {
        Redirect::to('home.php');
        die();
    }

    News::edit(Input::get('id'), array(
        'subject' => Input::get('title'),
        'content' => Input::get('content')
    ));
    Session::flash('success', 'News Article Edited Successfully! ');
    Redirect::to('admin.php?page=newsmanage');
} elseif (Input::get('action') === 'newarticle') {
    if (!$user->hasPermission('newsmanage')) {
        Redirect::to('home.php');
        die();
    }

    News::add(array(
        'subject' => Input::get('title'),
        'content' => Input::get('content'),
        'author' => Input::get('author')
    ));
    Session::flash('success', 'News Article Added Successfully! ');
    Redirect::to('admin.php?page=newsmanage');
} elseif (Input::get('action') === 'deleteaircraft') {
    if (!$user->hasPermission('opsmanage')) {
        Redirect::to('home.php');
        die();
    }

    Aircraft::archive(Input::get('delete'));
    Session::flash('success', 'Aircraft Archived Successfully! ');
    Redirect::to('admin.php?page=opsmanage&section=fleet');
} elseif (Input::get('action') === 'addaircraft') {
    if (!$user->hasPermission('opsmanage')) {
        Redirect::to('home.php');
        die();
    }

    Aircraft::add(Input::get('livery'), Input::get('rank'));
    Session::flash('success', 'Aircraft Added Successfully! ');
    Redirect::to('admin.php?page=opsmanage&section=fleet');
} elseif (Input::get('action') === 'editfleet') {
    if (!$user->hasPermission('opsmanage')) {
        Redirect::to('home.php');
        die();
    }
    
    Aircraft::updateRank(Input::get('rank'), Input::get('id'));
    Session::flash('success', 'Aircraft Updated Successfully!');
    Redirect::to('admin.php?page=opsmanage&section=fleet');
} elseif (Input::get('action') === 'setuppireps') {
    if (!Pirep::setup(Input::get('callsign'), $user->data()->id)) {
        $server = 'casual';
        $force = Config::get('FORCE_SERVER');
        if ($force !== 0 && $force !== 'casual') {
            $server = $force;
        }
        Session::flash('errorrecent', 'There was an Error Connecting to Infinite Flight. Ensure you are spawned in on the <b>'.ucfirst($server).' Server, and have set your callsign to \''.$user->data()->callsign.'\'</b>!');
        Redirect::to('pireps.php?page=new');
    }
    Session::flash('successrecent', 'PIREPs Setup Successfully! You can now File PIREPs.');
    Redirect::to('pireps.php?page=new');
} elseif (Input::get('action') === 'addroute') {
    if (!$user->hasPermission('opsmanage')) {
        Redirect::to('home.php');
        die();
    }

    Route::add(array(Input::get('fltnum'), Input::get('dep'), Input::get('arr'), Time::strToSecs(Input::get('duration')), Input::get('aircraft')));
    Session::flash('success', 'Route Added Successfully!');
    Redirect::to('admin.php?page=opsmanage&section=routes');
} elseif (Input::get('action') === 'deleteroute') {
    if (!$user->hasPermission('opsmanage')) {
        Redirect::to('home.php');
        die();
    }

    Route::delete(Input::get('delete'));
    Session::flash('success', 'Route Removed Successfully!');
    Redirect::to('admin.php?page=opsmanage&section=routes');
} elseif (Input::get('action') === 'editroute') {
    if (!$user->hasPermission('opsmanage')) {
        Redirect::to('home.php');
        die();
    }
    
    $ret = Route::update(Input::get('id'), array(
        "fltnum" => Input::get('fltnum'),
        "dep" => Input::get('dep'),
        "arr" => Input::get('arr'),
        "aircraftid" => Input::get('aircraft'),
        "duration" => Time::strToSecs(Input::get('duration'))
    ));

    if ($ret === false) {
        Session::flash('error', 'Error Updating Route');
        Redirect::to('admin.php?page=opsmanage&section=routes');
        die();
    }

    Session::flash('success', 'Route Updated Successfully!');
    Redirect::to('admin.php?page=opsmanage&section=routes');
} elseif (Input::get('action') === 'addrank') {
    if (!$user->hasPermission('opsmanage')) {
        Redirect::to('home.php');
        die();
    }

    Rank::add(Input::get('name'), Time::hrsToSecs(Input::get('time')));
    Session::flash('success', 'Rank Added Successfully!');
    Redirect::to('admin.php?page=opsmanage&section=ranks');
} elseif (Input::get('action') === 'editrank') {
    if (!$user->hasPermission('opsmanage')) {
        Redirect::to('home.php');
        die();
    }

    try {
        Rank::update(Input::get('id'), array(
            'name' => Input::get('name'),
            'timereq' => Time::hrsToSecs(Input::get('time'))
        ));
    } catch (Exception $e) {
        Session::flash('error', 'There was an Error Editing the Rank.');
        Redirect::to('admin.php?page=opsmanage&section=ranks');
    }
    Session::flash('success', 'Rank Edited Successfully!');
    Redirect::to('admin.php?page=opsmanage&section=ranks');
} elseif (Input::get('action') === 'delrank') {
    if (!$user->hasPermission('opsmanage')) {
        Redirect::to('home.php');
        die();
    }

    $ret = Rank::delete(Input::get('delete'));
    if (!$ret) {
        Session::flash('error', 'There was an Error Deleting the Rank.');
        Redirect::to('admin.php?page=opsmanage&section=ranks');
    } else {
        Session::flash('success', 'Rank Deleted Successfully!');
        Redirect::to('admin.php?page=opsmanage&section=ranks');
    }
} elseif (Input::get('action') === 'setcolour') {
    if (!$user->hasPermission('opsmanage')) {
        Redirect::to('home.php');
        die();
    }

    if (!Config::replaceColour(trim(Input::get('hexcol'), "#"), trim(Input::get('textcol'), "#"))) {
        Session::flash('error', 'There was an Error Updating the Colour Theme!');
        Redirect::to('admin.php?page=site&tab=colors');
        die();
    }
    Session::flash('success', 'Colour Theme Updated Successfully! You may need to reload the page or clear your cache in order for it to show.');
    Redirect::to('admin.php?page=site&tab=colors');
} elseif (Input::get('action') === 'vasettingsupdate') {
    if (!$user->hasPermission('opsmanage')) {
        Redirect::to('home.php');
        die();
    }

    if (!Config::replace('name', Input::get('vaname'))
        || !Config::replace('identifier', Input::get('vaident'))
        || !Config::replace("FORCE_SERVER", Input::get('forceserv'))
        || !Config::replace("CHECK_PRERELEASE", Input::get('checkpre'))
        ) {
        Session::flash('error', 'There was an error updating the Settings');
        Redirect::to('admin.php?page=site&tab=settings');
        die();
    }
    Session::flash('success', 'VA Settings Changed Successfully!');
    Redirect::to('admin.php?page=site&tab=settings');
} elseif (Input::get('action') === 'vanetupdate') {
    if (!$user->hasPermission('opsmanage')) {
        Redirect::to('home.php');
        die();
    }
    
    if (!Config::replace('api_key', trim(Input::get('vanetkey')))) {
        Session::flash('error', 'There was an error updating the config file!');
        Redirect::to('admin.php?page=site&tab=vanet');
        die();
    }
    Session::flash('success', 'VANet API Key changed Successfully.');
    Redirect::to('admin.php?page=site&tab=vanet');
} elseif (Input::get('action') === 'addevent') {
    if (!$user->hasPermission('opsmanage')) {
        Redirect::to('home.php');
        die();
    }

    $sentGates = explode(",", Input::get('gates'));
    $gates = array();
    foreach ($sentGates as $g) {
        array_push($gates, trim($g));
    }

    $vis = 'true';
    if (Input::get('visible') == 0) {
        $vis = 'false';
    }

    $datetime = Input::get('date').' '.substr(Input::get('time'), 0, 2).':'.substr(Input::get('time'), 2, 2);

    try {
        VANet::createEvent(array(
            "Name" => Input::get('name'),
            "Description" => Input::get('description'),
            "EventTypeID" => "1",
            "DateTime" => $datetime,
            "DepartureAirport" => Input::get('dep'),
            "ArrivalAirport" => Input::get('arr'),
            "Visible" => $vis,
            "Aircraft" => Input::get('aircraft'),
            "Server" => Input::get('server'),
            "Gates" => $gates
        ));
        Session::flash('success', 'Event Added Successfully!');
    } catch (Exception $e) {
        Session::flash('error', 'Error Creating Event');
    } finally {
        Redirect::to('admin.php?page=events');
    }
} elseif (Input::get('action') === 'eventsignup') {
    $uData = $user->data();
    if (VANet::isSignedUp($uData->ifuserid, Input::get('event')) != false) {
        Redirect::to('events.php?page=view&event='.urlencode(Input::get('event')));
        die();
    }

    $ret = VANet::eventSignUp($uData->ifuserid, Input::get('gate'));
    if ($ret === 400) {
        Session::flash("error", "Event is Corrupted. Please contact your VA.");
        Redirect::to('events.php?page=view&event='.urlencode(Input::get('event')));
        die();
    } elseif ($ret === 404) {
        Session::flash('error', 'Slot Not Found. Are you messing with us? :/');
        Redirect::to('events.php?page=view&event='.urlencode(Input::get('event')));
        die();
    } elseif ($ret === 409) {
        Session::flash("error", "Rats! Someone got to that gate before you. Please try again.");
        Redirect::to('events.php');
        die();
    } elseif ($ret === true) {
        Session::flash('success', 'Gate Reserved Successfully!');
        Redirect::to('events.php?page=view&event='.urlencode(Input::get('event')));
        die();
    }
} elseif (Input::get('action') === 'vacateslot') {
    $uData = $user->data();

    $ret = VANet::eventPullOut(Input::get('gate'), Input::get('event'), $uData->ifuserid);

    if ($ret === 400) {
        Redirect::to('events.php?page=view&event='.urlencode(Input::get('event')));
        die();
    } elseif ($ret === 404) {
        Session::flash('error', 'Slot Not Found. Are you messing with us? :/');
        Redirect::to('events.php?page=view&event='.urlencode(Input::get('event')));
        die();
    } elseif ($ret === 409) {
        Session::flash("error", "Event is Corrupted. Please contact your VA.");
        Redirect::to('events.php');
        die();
    } elseif ($ret === true) {
        Session::flash('success', 'Slot Vacated Successfully!');
        Redirect::to('events.php?page=view&event='.urlencode(Input::get('event')));
        die();
    }
} elseif (Input::get('action') === 'deleteevent') {
    if (!$user->hasPermission('opsmanage')) {
        Redirect::to('home.php');
        die();
    }

    VANet::deleteEvent(Input::get('delete'));
    Session::flash('success', 'Event Deleted Successfully');
    Redirect::to('admin.php?page=events');
} elseif (Input::get('action') === 'editevent') {
    if (!$user->hasPermission('opsmanage')) {
        Redirect::to('home.php');
        die();
    }

    $vis = 'true';
    if (Input::get('visible') == 0) {
        $vis = 'false';
    }
    $ret = VANet::editEvent(Input::get('id'), array(
        "Name" => Input::get('name'),
        "Description" => Input::get('description'),
        "EventTypeID" => 1,
        "DepartureAirport" => Input::get('dep'),
        "ArrivalAirport" => Input::get('arr'),
        "Visible" => $vis,
        "AircraftID" => Input::get('aircraft'),
        "Server" => Input::get('server')
    ));

    if (!$ret) {
        Session::flash('error', "Error Updating Event");
        Redirect::to('admin.php?page=events');
    } else {
        Session::flash('success', "Event Updated Successfully");
        Redirect::to('admin.php?page=events');
    }
} elseif (Input::get('action') === 'importroutes') {
    if (!$user->hasPermission('opsmanage')) {
        Redirect::to('home.php');
        die();
    }
    $file = Input::getFile('upload');

    $fileName = explode(".", $file["name"]);
    if ($fileName[count($fileName) - 1] != "json") {
        Session::flash('error', 'You Uploaded an Invalid File Type');
        Redirect::to('admin.php?page=opsmanage&section=import');
        die();
    }

    $data = Json::decode(file_get_contents($file["tmp_name"]));
    $db = DB::getInstance();

    $sql = "INSERT INTO routes (fltnum, dep, arr, duration, aircraftid) VALUES\n";
    $params = array();
    $i = 0;

    $allaircraft = Aircraft::fetchAllLiveriesFromVANet();

    foreach ($data as $item) {
        if ($i % 50 == 0 && $i != 0) {
            $sql = trim($sql, ',');
            $ret = $db->query($sql, $params);
            if ($ret->error()) {
                Session::flash('error', "Error Importing Routes");
                Redirect::to('admin.php?page=opsmanage&section=import');
                die();
            }
            $sql = "INSERT INTO routes (fltnum, dep, arr, duration, aircraftid) VALUES";
            $params = array();
        }

        $aircraft = null;
        foreach ($allaircraft as $ac) {
            if ($ac["liveryID"] == $item["aircraftid"]) {
                $aircraft = $ac;
            }
        }

        $acId = $db->query("SELECT * FROM aircraft WHERE ifliveryid= ?", array($aircraft["liveryID"]));
        if ($acId->count() === 0) {
            $rank = $db->query("SELECT * FROM ranks ORDER BY timereq ASC")->first();
            Aircraft::add($aircraft["liveryID"], $rank->id);
            $acId = $db->query("SELECT * FROM aircraft WHERE ifliveryid= ?", array($aircraft["liveryID"]))->first()->id;
        } else {
            $acId = $acId->first()->id;
        }

        $sql .= "\n(?, ?, ?, ?, ?),";
        array_push($params, $item["fltnum"]);
        array_push($params, $item["dep"]);
        array_push($params, $item["arr"]);
        array_push($params, $item["duration"]);
        array_push($params, $acId);
        
        $i++;
    }

    $sql = trim($sql, ',');
    $ret = $db->query($sql, $params);
    if ($ret->error()) {
        Session::flash('error', "Error Importing Routes");
        Redirect::to('admin.php?page=opsmanage&section=import');
        die();
    }
    Session::flash('success', "Routes Imported Successfully!");
    Redirect::to('admin.php?page=opsmanage&section=routes');
} elseif (Input::get('action') === 'importaircraft') {
    if (!$user->hasPermission('opsmanage')) {
        Redirect::to('home.php');
        die();
    }
    $file = Input::getFile('upload');

    $fileName = explode(".", $file["name"]);
    if ($fileName[count($fileName) - 1] != "json") {
        Session::flash('error', 'You Uploaded an Invalid File Type');
        Redirect::to('admin.php?page=opsmanage&section=import');
        die();
    }

    $data = Json::decode(file_get_contents($file["tmp_name"]));
    $db = DB::getInstance();

    $minrank = $db->query("SELECT * FROM ranks ORDER BY timereq ASC")->first()->id;

    $sql = "INSERT INTO aircraft (name, ifaircraftid, liveryname, ifliveryid, rankreq, status) VALUES\n";
    $params = array();
    $i = 0;

    $allaircraft = Aircraft::fetchAllLiveriesFromVANet();

    foreach ($data as $item) {
        if ($i % 20 == 0 && $i != 0) {
            $sql = trim($sql, ',');
            $ret = $db->query($sql, $params);
            if ($ret->error()) {
                Session::flash('error', "Error Importing Aircraft");
                throw new Exception($sql);
                Redirect::to('admin.php?page=opsmanage&section=import');
                die();
            }
            $sql = "INSERT INTO aircraft (name, ifaircraftid, liveryname, ifliveryid, rankreq, status) VALUES\n";
            $params = array();
        }

        $aircraft = null;
        foreach ($allaircraft as $ac) {
            if ($ac["liveryID"] == $item) {
                $aircraft = $ac;
            }
        }

        if ($aircraft == null) {
            Session::flash('error', "Could Not Find Aircraft with ID ".$item);
            Redirect::to('admin.php?page=opsmanage&section=import');
            die();
        }

        $sql .= "\n(?, ?, ?, ?, ?, ?),";
        array_push($params, $aircraft["aircraftName"]);
        array_push($params, $aircraft["aircraftID"]);
        array_push($params, $aircraft["liveryName"]);
        array_push($params, $aircraft["liveryID"]);
        array_push($params, $minrank);
        array_push($params, 1);
        
        $i++;
    }

    $sql = trim($sql, ',');
    $ret = $db->query($sql, $params);
    if ($ret->error()) {
        Session::flash('error', "Error Importing Aircraft");
        throw new Exception($sql);
        Redirect::to('admin.php?page=opsmanage&section=import');
        die();
    }
    Session::flash('success', "Aircraft Imported Successfully!");
    Redirect::to('admin.php?page=opsmanage&section=fleet');
} elseif (Input::get('action') === 'exportroutes') {
    header('Content-Type: application/json');

    $routes = Route::fetchAll()->results();
    $ret = array();
    foreach ($routes as $r) {
        array_push($ret, array(
            "fltnum" => $r->fltnum,
            "dep" => $r->dep,
            "arr" => $r->arr,
            "duration" => $r->duration,
            "aircraftid" => $r->liveryid
        ));
    }

    echo Json::encode($ret);
} elseif (Input::get('action') === 'exportaircraft') {
    header('Content-Type: application/json');

    $aircraft = Aircraft::fetchActiveAircraft()->results();
    $ret = array();
    foreach ($aircraft as $a) {
        array_push($ret, $a->ifliveryid);
    }

    echo Json::encode($ret);
} elseif (Input::get('action') === 'newcodeshare') {
    if (!$user->hasPermission('opsmanage')) {
        Redirect::to('home.php');
        die();
    }

    $routes = array();
    $inputRoutes = explode(",", Input::get('routes'));

    $db = DB::getInstance();
    foreach ($inputRoutes as $input) {
        $input = trim($input);
        $query = $db->query('SELECT routes.*, aircraft.ifliveryid AS liveryid FROM routes INNER JOIN aircraft ON routes.aircraftid=aircraft.id WHERE routes.fltnum=?', array($input));
        if ($query->count() === 0) {
            Session::flash('error', 'Could not Find Route '.$input);
            Redirect::to('admin.php?page=codeshares');
            die();
        } elseif ($query->count() > 1) {
            Session::flash('error', 'There\'s More than One Route with the Flight Number '.$input);
            Redirect::to('admin.php?page=codeshares');
            die();
        }

        $route = $query->first();
        array_push($routes, array(
            "flightNum" => $route->fltnum,
            "departure" => $route->dep,
            "arrival" => $route->arr,
            "aircraftID" => $route->liveryid,
            "flightTime" => $route->duration
        ));
    }

    $ret = VANet::sendCodeshare(array(
        "VEToID" => Input::get('recipient'),
        "Message" => Input::get('message'),
        "Routes" => $routes
    ));
    if (!$ret) {
        Session::flash('error', "Error Connnecting to VANet");
        Redirect::to('admin.php?page=codeshares');
        die();
    } else {
        Session::flash('success', "Codeshare Sent Successfully!");
        Redirect::to('admin.php?page=codeshares');
    }
} elseif (Input::get('action') === 'deletecodeshare') {
    if (!$user->hasPermission('opsmanage')) {
        Redirect::to('home.php');
        die();
    }

    $ret = VANet::deleteCodeshare(Input::get('delete'));
    if (!$ret) {
        Session::flash('error', "Error Connnecting to VANet");
        Redirect::to('admin.php?page=codeshares');
        die();
    } else {
        Session::flash('success', "Codeshare Deleted Successfully!");
        Redirect::to('admin.php?page=codeshares');
    }
} elseif (Input::get('action') === 'importcodeshare') {
    if (!$user->hasPermission('opsmanage')) {
        Redirect::to('home.php');
        die();
    }

    $codeshare = VANet::findCodeshare(Input::get('id'));
    if ($codeshare === false) {
        Session::flash('error', "Codeshare Not Found");
        //Redirect::to('admin.php?page=codeshares');
        die();
    }

    $db = DB::getInstance();
    $allaircraft = Aircraft::fetchAllLiveriesFromVANet();

    $sql = "INSERT INTO routes (fltnum, dep, arr, duration, aircraftid) VALUES\n";
    $params = array();
    $i = 0;

    foreach ($codeshare["routes"] as $route) {
        if ($i % 50 == 0 && $i != 0) {
            $sql = trim($sql, ',');
            $ret = $db->query($sql, $params);
            if ($ret->error()) {
                Session::flash('error', "Error Importing Codeshare Routes");
                //Redirect::to('admin.php?page=codeshares');
                die();
            }
            $sql = "INSERT INTO routes (fltnum, dep, arr, duration, aircraftid) VALUES";
            $params = array();
        }

        $aircraft = null;
        foreach ($allaircraft as $ac) {
            if ($ac["liveryID"] == $route["aircraft"]["liveryID"]) {
                $aircraft = $ac;
            }
        }

        $acId = $db->query("SELECT * FROM aircraft WHERE ifliveryid= ?", array($aircraft["liveryID"]));
        if ($acId->count() === 0) {
            $rank = $db->query("SELECT * FROM ranks ORDER BY timereq ASC")->first();
            Aircraft::add($aircraft["liveryID"], $rank->id);
            $acId = $db->query("SELECT * FROM aircraft WHERE ifliveryid= ?", array($aircraft["liveryID"]))->first()->id;
        } else {
            $acId = $acId->first()->id;
        }

        

        $sql .= "\n(?, ?, ?, ?, ?),";
        array_push($params, $codeshare["veFrom"]["code"].$route["flightNum"]);
        array_push($params, $route["departure"]);
        array_push($params, $route["arrival"]);
        array_push($params, $route["flightTime"]);
        array_push($params, $acId);
        $i++;
    }

    $sql = trim($sql, ',');
    $ret = $db->query($sql, $params);
    if ($ret->error()) {
        Session::flash('error', "Error Importing Codeshare Routes");
        //Redirect::to('admin.php?page=codeshares');
        die();
    }
    //VANet::deleteCodeshare($codeshare["id"]);
    Session::flash('success', "Codeshare Routes Imported Successfully!");
//Redirect::to('admin.php?page=opsmanage&section=routes');
} elseif (Input::get('action') === 'phpvms') {
    $routes = Input::get('rJson');
    $count = count(Json::decode($routes));
    $db = DB::getInstance();

    $allaircraft = Aircraft::fetchActiveAircraft()->results();
    $firstRank = $db->query("SELECT * FROM ranks ORDER BY timereq ASC LIMIT 1")->first()->id;

    for ($i=0; $i<$count; $i++) {
        $item = Input::get('livery'.$i);
        $aircraft = false;
        foreach ($allaircraft as $a) {
            if ($a->ifliveryid == $item) {
                $aircraft = $a;
            }
        }

        if ($aircraft === false) {
            Aircraft::add($item, $firstRank);
            $aircraft = Aircraft::findAircraft($item);
        }

        $routes = str_replace(Input::get('rego'.$i), $aircraft->id, $routes);
    }

    $routes = Json::decode($routes);

    $sql = "INSERT INTO routes (fltnum, dep, arr, duration, aircraftid) VALUES\n";
    $params = array();
    $j = 0;
    foreach ($routes as $item) {
        if ($j % 50 == 0 && $j != 0) {
            $sql = trim($sql, ',');
            $ret = $db->query($sql, $params);
            if ($ret->error()) {
                Session::flash('error', "Error Importing Routes");
                Redirect::to('admin.php?page=opsmanage&section=phpvms');
                die();
            }
            $sql = "INSERT INTO routes (fltnum, dep, arr, duration, aircraftid) VALUES";
            $params = array();
        }

        $sql .= "\n(?, ?, ?, ?, ?),";
        array_push($params, $item["fltnum"]);
        array_push($params, $item["dep"]);
        array_push($params, $item["arr"]);
        array_push($params, $item["duration"]);
        array_push($params, $item["aircraftid"]);
        
        $j++;
    }

    $sql = trim($sql, ',');
    $ret = $db->query($sql, $params);
    if ($ret->error()) {
        Session::flash('error', "Error Importing Routes");
        Redirect::to('admin.php?page=opsmanage&section=import');
        die();
    }

    Session::flash('success', "Routes Imported Successfully!");
    Redirect::to('admin.php?page=opsmanage&section=routes');
}
