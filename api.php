<?php
/*
Flare, a fully featured and easy to use crew centre, designed for Infinite Flight.
Copyright (C) 2020  Lucas Rebato

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

require_once './core/init.php';
header('Content-Type: application/json');

abstract class AuthType {
    const NoAuth = 0;
    const Session = 1; // All scopes
    const Cookie = 2; // Reserved for Future Use, All Scopes
    const ApiKey = 3; // Read Only
    const BasicHttp = 4; // All Scopes
}

abstract class ErrorCode {
    const NoError = 0;
    const Unauthorized = 1;
    const NotFound = 2;
    const BadAuthMethod = 3;
    const InternalServerError = 4;
    const CallsignNotValid = 5;
    const CallsignTaken = 6;
}

function unauthorized() {
    http_response_code(401);
    echo Json::encode([
        "status" => ErrorCode::Unauthorized,
        "result" => null
    ]);
    die();
}

function internalError() {
    http_response_code(500);
    echo Json::encode([
        "status" => ErrorCode::InternalServerError,
        "result" => null
    ]);
    die();
}

function badReq($status) {
    http_response_code(400);
    echo Json::encode([
        "status" => $status,
        "result" => null
    ]);
    die();
}

function notFound() {
    http_response_code(404);
    echo Json::encode([
        "status" => ErrorCode::NotFound,
        "result" => null
    ]);
    die();
}

$user = new User();

$_authType = AuthType::NoAuth;
$_apiUser = new stdClass;
$_headers = getallheaders();

if ($user->isLoggedIn()) {
    // Session Auth
    $_authType = AuthType::Session;
    $_apiUser = $user->data();
} elseif (array_key_exists('Authorization', $_headers) && explode(' ', $_headers['Authorization'])[0] == 'Bearer') {
    // Bearer Auth (Header)
    $check = Api::processKey(explode(' ', $_headers['Authorization'])[1]);
    if ($check === FALSE) {
        unauthorized();
    }

    $_authType = AuthType::ApiKey;
    $_apiUser = $check;
} elseif (array_key_exists('apikey', $_GET)) {
    // Bearer Auth (Query String)
    $check = Api::processKey($_GET['apikey']);
    if ($check === FALSE) {
        unauthorized();
    }

    $_authType = AuthType::ApiKey;
    $_apiUser = $check;
} elseif (array_key_exists('Authorization', $_headers) && explode(' ', $_headers['Authorization'])[0] == 'Basic') {
    // Basic HTTP Auth
    $check = Api::processBasic($_headers['Authorization']);
    if ($check === FALSE) {
        unauthorized();
    }

    $_authType = AuthType::BasicHttp;
    $_apiUser = $check;
    $pass = explode(':', base64_decode(explode(' ', $_headers['Authorization'])[1]))[1];
    // If for some reason this fails, return a 500
    if (!$user->login($_apiUser->email, $pass)) {
        internalError();
    }
} else {
    unauthorized();
}

Router::add('/pireps', function() {
    global $user, $_apiUser;
    $res = [
        "status" => ErrorCode::NoError,
        "result" => $user->fetchPireps($_apiUser->id)->results(),
    ];

    $i = 0;
    foreach ($res["result"] as $p) {
        $p = (array)$p;
        foreach ($p as $key => $val) {
            if (is_numeric($val) && $key != 'flightnum') {
                $res["result"][$i]->$key = intval($val);
            }
        }
        unset($res["result"][$i]->pilotid);
        $i++;
    }

    echo Json::encode($res);
});

Router::add("/pireps/([0-9]*)", function($pirepId) {
    global $_apiUser;
    
    $pirep = Pirep::find($pirepId, $_apiUser->id);
    if ($pirep === FALSE) {
        notFound();
    }

    $pirep = (array)$pirep;

    unset($pirep['pilotid']);
    $pirep['id'] = intval($pirep['id']);
    $pirep['flighttime'] = intval($pirep['flighttime']);
    $pirep['aircraftid'] = intval($pirep['aircraftid']);
    $pirep['status'] = intval($pirep['status']);

    echo Json::encode([
        "status" => ErrorCode::NoError,
        "result" => $pirep,
    ]);
});

Router::add('/about', function() {
    global $_apiUser, $_authType;
    echo Json::encode([
        "status" => ErrorCode::NoError,
        "result" => [
            "id" => intval($_apiUser->id),
            "callsign" => $_apiUser->callsign,
            "name" => $_apiUser->name,
            "email" => $_authType != AuthType::ApiKey ? $_apiUser->email : null,
            "ifc" => $_apiUser->ifc,
            "transfer_hours" => intval($_apiUser->transhours),
            "transfer_flights" => intval($_apiUser->transflights),
            "violation_landing" => $_apiUser->violand,
            "grade" => $_apiUser->grade,
            "joined" => date_format(date_create($_apiUser->joined), 'c'),
        ],
    ]);
});

Router::add('/about', function() {
    global $_authType, $user, $_apiUser;
    if ($_authType == AuthType::ApiKey) {
        badReq(ErrorCode::BadAuthMethod);
    }

    $csPattern = Config::get('VA_CALLSIGN_FORMAT');
    if (!Regex::match($csPattern, Input::get('callsign'))) {
        badReq(ErrorCode::CallsignNotValid);
    }

    if (!Callsign::assigned(Input::get('callsign'), $_apiUser->id)) {
        badReq(ErrorCode::CallsignTaken);
    }
    
    try {
        $user->update([
            "callsign" => Input::get('callsign'),
            "name" => Input::get('name'),
            "email" => Input::get('email'),
            "ifc" => Input::get('ifc'),
        ]);
        echo Json::encode([
            "status" => ErrorCode::NoError,
            "result" => "Profile Updated",
        ]);
    } catch (Exception $e) {
        internalError();
    }
}, 'put');

Router::run('/api.php');