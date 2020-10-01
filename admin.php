<?php
/*
Flare, a fully featured and easy to use crew centre, designed for Infinite Flight.
Copyright (C) 2020  Lucas Rebato

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

require_once './core/init.php';

$user = new User();

Page::setTitle('Admin Panel - '.Config::get('va/name'));

if (!$user->isLoggedIn()) {
    Redirect::to('index.php');
} elseif (!$user->hasPermission('admin')) {
    Session::flash('errormain', 'You don\'t have permission to access this!');
    Redirect::to('home.php');
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include './includes/header.php'; ?>
</head>
<body>
    <style>
        #loader {
        position: absolute;
        left: 50%;
        top: 50%;
        z-index: 1;
        width: 150px;
        height: 150px;
        margin: -75px 0 0 -75px;
        width: 120px;
        height: 120px;
        }
    </style>

    <nav class="navbar navbar-dark navbar-expand-lg bg-custom">
        <?php include './includes/navbar.php'; ?>
    </nav>

    <div class="container-fluid">
        <div class="container-fluid mt-4 text-center" style="overflow: auto;">
        <div class="row m-0 p-0">
            <?php include './includes/sidebar.php'; ?>
            <div class="col-lg-9 main-content">
                <div id="loader-wrapper"><div id="loader" class="spinner-border spinner-border-sm spinner-custom"></div></div>
                <div class="loaded">
                    <?php
                    if (file_exists('./install/install.php')) {
                        Session::flash('error', '<b>The Install Folder still exists! Please delete this <u>immediately</u>, as this poses a Severe Security Risk!</b>');
                    }
                    if (Session::exists('error')) {
                        echo '<div class="alert alert-danger text-center">Error: '.Session::flash('error').'</div>';
                    }
                    if (Session::exists('success')) {
                        echo '<div class="alert alert-success text-center">'.Session::flash('success').'</div>';
                    }

                    $ACTIVE_CATEGORY = null;
                    ?>
                    <?php if (Input::get('page') == ''): ?>
                        <h3>Admin Panel</h3>
                        <p>Welcome to the Admin Panel. Here you can find the administration tools required to manage <?= escape(Config::get('va/name')) ?></p>
                        <p>Looks like no page was specified. Make sure you use the buttons in the navbar/sidebar!</p>
                    <?php endif; ?>
                    <?php if (Input::get('page') === 'usermanage'): ?>
                        <?php $ACTIVE_CATEGORY = 'user-management'; ?>
                        <h3>Manage Users</h3>
                        <?php if (!$user->hasPermission('usermanage')): ?>
                            <div class="alert alert-danger text-center">Whoops! You don't have the necessary permissions to access this.</div>
                        <?php else: ?>
                            <p>Here you can view all users, active and inactive. Click on a user to view/edit the information.</p>
                            <table class="table table-striped datatable">
                                <thead class="bg-custom">
                                    <tr>
                                        <th>Callsign</th>
                                        <th class="mobile-hidden">Name</th>
                                        <th class="mobile-hidden">Email</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $users = $user->getAllUsers();
                                    $x = 0;
                                    foreach ($users as $user) {
                                        echo '<tr><td class="align-middle">';
                                        echo $user["callsign"];
                                        echo '</td><td class="mobile-hidden align-middle">';
                                        echo $user["name"];
                                        echo '</td><td class="mobile-hidden align-middle">';
                                        echo $user["email"];
                                        echo '</td><td class="align-middle">';
                                        echo $user["status"];
                                        echo '</td><td class="align-middle">';
                                        echo '<button class="btn btn-primary text-light userEdit" data-callsign="'.$user['callsign'].'" 
                                        data-name="'.$user['name'].'" data-email="'.$user['email'].'" data-ifc="'.$user['ifc'].'" 
                                        data-joined="'.date_format(date_create($user['joined']), 'Y-m-d').'" data-status="'.$user['status'].'" 
                                        data-id="'.$user['id'].'" data-thrs="'.Time::secsToString($user["transhours"]).'" 
                                        data-admin="'.$user['isAdmin'].'" data-tflts="'.$user["transflights"].'"><i class="fa fa-edit"></i>
                                        </button>';
                                        echo '&nbsp;<button id="delconfirmbtn" class="btn text-light btn-danger" 
                                        data-toggle="modal" data-target="#delconfirmmodal" 
                                        data-callsign="'.$user['callsign'].'" data-id="'.$user['id'].'">
                                        <i class="fa fa-trash"></i></button>';
                                        echo '</td>';
                                        $x++;
                                    }
                                    ?>
                                </tbody>
                            </table>
                            <div class="modal fade" id="delconfirmmodal" tabindex="-1" role="dialog" aria-labelledby="delconfirmmodallabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="exampleModalLabel">Confirm</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <p id="delconfirmmessage"></p>
                                            <form action="update.php" method="post">
                                                <input hidden name="action" value="deluser">
                                                <input hidden value="" name="id" id="delconfirmuserid">
                                                <input type="submit" class="btn bg-danger text-light" value="Mark as Inactive">
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal fade" id="usermodal" tabindex="-1" role="dialog" aria-labelledby="pirep'.$x.'label" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="usermodal-title"></h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <form action="update.php" method="post">
                                                <input hidden name="action" value="edituser">
                                                <input hidden name="id" id="usermodal-id" value="">
                                                <div class="form-group">
                                                    <label for="usermodal-callsign">Callsign</label>
                                                    <input required type="text" value="" class="form-control" name="callsign" id="usermodal-callsign">
                                                </div>
                                                <div class="form-group">
                                                    <label for="usermodal-name">Name</label>
                                                    <input required type="text" value="" class="form-control" name="name" id="usermodal-name">
                                                </div>
                                                <div class="form-group">
                                                    <label for="usermodal-email">Email</label>
                                                    <input required type="text" value="" class="form-control" name="email" id="usermodal-email">
                                                </div>
                                                <div class="form-group">
                                                    <label for="usermodal-ifc">IFC Profile URL</label>
                                                    <input required type="url" value="" class="form-control" name="ifc" id="usermodal-ifc">
                                                </div>
                                                <div class="form-group">
                                                    <label for="usermodal-thrs">Transfer Flight Time</label>
                                                    <div class="row">
                                                        <div class="col-sm-6">
                                                            <input required type="number" min="0" id="flightTimeHrs" class="form-control" placeholder="Hours" />
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <input required type="number" min="0" id="flightTimeMins" class="form-control" placeholder="Minutes" />
                                                        </div>
                                                    </div>
                                                    <input hidden name="transhours" id="usermodal-thrs" class="form-control" value="<?= escape(Input::get('ftime')) ?>" required />
                                                    <script>
                                                        function formatFlightTime() {
                                                            var hrs = $("#flightTimeHrs").val();
                                                            var mins = $("#flightTimeMins").val();
                                                            $("#usermodal-thrs").val(hrs + ":" + mins);
                                                        }

                                                        function reverseFormatFlightTime() {
                                                            var formatted = $("#usermodal-thrs").val();
                                                            if (formatted != '') {
                                                                var split = formatted.split(":");
                                                                var hrs = split[0];
                                                                var mins = split[1];
                                                                $("#flightTimeHrs").val(hrs);
                                                                $("#flightTimeMins").val(mins);
                                                            }
                                                        }

                                                        $(document).ready(function() {
                                                            $("#flightTimeHrs").keyup(function() {
                                                                formatFlightTime();
                                                            });
                                                            $("#flightTimeMins").keyup(function() {
                                                                formatFlightTime();
                                                            });
                                                            reverseFormatFlightTime();
                                                        });
                                                    </script>
                                                </div>
                                                <div class="form-group">
                                                    <label for="usermodal-tflts"># Transfer Flights</label>
                                                    <input required type="number" min="0" value="" class="form-control" name="transflights" id="usermodal-tflts">
                                                </div>
                                                <div class="form-group">
                                                    <label for="usermodal-joined">Join date</label>
                                                    <input readonly type="date" value="" class="form-control" name="joined" id="usermodal-joined">
                                                </div>
                                                <div class="form-group">
                                                    <label for="usermodal-status">Status</label>
                                                    <input readonly type="text" value="" class="form-control" name="status" id="usermodal-status">
                                                </div>
                                                <div class="form-group">
                                                    <label for="usermodal-admin">Admin Status</label>
                                                    <select required class="form-control" name="admin" id="usermodal-admin">
                                                        <option value>Select</option>
                                                        <option value="0" id="usermodal-admin-0">Pilot</option>
                                                        <option value="1" id="usermodal-admin-1">Staff Member</option>
                                                    </select>
                                                </div>
                                                <input type="submit" class="btn bg-custom" value="Save">
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Populate modal fields -->
                            <script>
                                $('.userEdit').click(function(e) {
                                    var userCallsign = $(this).data("callsign");
                                    var userName = $(this).data("name");
                                    var userEmail = $(this).data("email");
                                    var userIfc = $(this).data("ifc");
                                    var userJoined = $(this).data("joined");
                                    var userStatus = $(this).data("status");
                                    var userThrs = $(this).data("thrs");
                                    var userTflts = $(this).data("tflts");
                                    var userId = $(this).data("id");
                                    var userAdmin = $(this).data("admin");

                                    $("#usermodal-callsign").val(userCallsign);
                                    $("#usermodal-name").val(userName);
                                    $("#usermodal-email").val(userEmail);
                                    $("#usermodal-ifc").val(userIfc);
                                    $("#usermodal-joined").val(userJoined);
                                    $("#usermodal-status").val(userStatus);
                                    $("#usermodal-thrs").val(userThrs);
                                    $("#usermodal-tflts").val(userTflts);
                                    $("#usermodal-id").val(userId);
                                    $("#usermodal-admin-" + userAdmin).attr("selected", true);

                                    $("#usermodal-title").text("Edit User - " + userCallsign);
                                    reverseFormatFlightTime();

                                    $("#usermodal").modal("show");
                                });
                            </script>
                            <!-- Confirm delete modal -->
                            <script>
                                $('#delconfirmmodal').on('show.bs.modal', function(e) {
                                    var userCallsign = $(e.relatedTarget).data('callsign');
                                    var userId = $(e.relatedTarget).data('id');

                                    var message = 'Are you sure you want to mark the user ' + userCallsign + ' as inactive?'
                                    $("#delconfirmmessage").text(message);
                                    $("#delconfirmuserid").val(userId);
                                });
                            </script>
                        <?php endif; ?>
                    <?php elseif (Input::get('page') === 'staffmanage'): ?>
                        <?php $ACTIVE_CATEGORY = 'user-management'; ?>
                        <h3>Manage Staff</h3>
                        <?php if (!$user->hasPermission('staffmanage')): ?>
                            <div class="alert alert-danger text-center">Whoops! You don't have the necessary permissions to access this.</div>
                        <?php else: ?>
                            <p>Here you can manage staff members, and their permissions. Be sure to select the correct permissions, as setting the wrong permissions can give them access to sensitive information!</p>
                            <table class="table table-striped datatable">
                            <thead class="bg-custom">
                                <tr>
                                    <th>Callsign</th>
                                    <th class="mobile-hidden">Name</th>
                                    <th class="mobile-hidden">Email</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stafflist = $user->getAllStaff();
                                $x = 0;
                                foreach ($stafflist as $staff) {
                                    echo '<tr><td class="align-middle">';
                                    echo $staff["callsign"];
                                    echo '</td><td class="mobile-hidden align-middle">';
                                    echo $staff["name"];
                                    echo '</td><td class="mobile-hidden align-middle">';
                                    echo $staff["email"];
                                    echo '</td><td class="align-middle">';
                                    echo $staff["status"];
                                    echo '</td><td class="align-middle">';
                                    echo '<button class="btn text-light btn-primary" data-toggle="modal" data-target="#staff'.$x.'modal" data-callsign="'.$staff['callsign'].'" data-name="'.$staff['name'].'" data-email="'.$staff['email'].'" data-ifc="'.$staff['ifc'].'" data-joined="'.date_format(date_create($staff['joined']), 'Y-m-d').'" data-status="'.$staff['status'].'" data-id="'.$staff['id'].'"><i class="fa fa-edit"></i></button>';
                                    echo '&nbsp;<button id="delconfirmbtn" class="btn text-light btn-danger" data-toggle="modal" data-target="#delconfirmmodal" data-callsign="'.$staff['callsign'].'"><i class="fa fa-trash"></i></button>';
                                    echo '</td>';
                                    $x++;
                                }
                                ?>
                            </tbody>
                        </table>
                        <?php
                        $x = 0;
                        foreach ($stafflist as $staff) { ?>
                            <div class="modal fade" id="staff<?= $x ?>modal" tabindex="-1" role="dialog" aria-labelledby="staff<?= $x ?>label" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="staffmodaltitle">Edit Staff Member - <?= $staff['callsign'] ?></h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <form action="update.php" method="post">
                                                <input hidden name="action" value="editstaffmember">
                                                <input hidden name="id" value="<?= $staff['id'] ?>">
                                                <div class="form-group">
                                                    <label for="usermodal-callsign">Callsign</label>
                                                    <input required type="text" value="<?= $staff['callsign'] ?>" class="form-control" name="callsign" id="usermodal-callsign">
                                                </div>
                                                <div class="form-group">
                                                    <label for="usermodal-name">Name</label>
                                                    <input required type="text" value="<?= $staff['name'] ?>" class="form-control" name="name" id="usermodal-name">
                                                </div>
                                                <div class="form-group">
                                                    <label for="usermodal-email">Email</label>
                                                    <input required type="text" value="<?= $staff['email'] ?>" class="form-control" name="email" id="usermodal-email">
                                                </div>
                                                <div class="form-group">
                                                    <label for="usermodal-ifc">IFC Username</label>
                                                    <input required type="text" value="<?= $staff['ifc'] ?>" class="form-control" name="ifc" id="usermodal-ifc">
                                                </div>
                                                <div class="form-group">
                                                    <label for="usermodal-joined">Join Date</label>
                                                    <input readonly type="date" value="<?= date_format(date_create($staff['joined']), 'Y-m-d') ?>" class="form-control" name="joined" id="usermodal-joined">
                                                </div>
                                                <br>
                                                <h5>Permissions</h5>
                                                <?php
                                                    $allperms = Permissions::getAll();
                                                    $myperms = Permissions::forUser($staff['id']);
                                                    foreach ($allperms as $permission => $name) {
                                                        if ($user->hasPermission($permission, $staff['id'])): ?>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" value="on" id="permission-<?= $permission ?>" name="<?= $permission ?>" checked>
                                                                <label class="form-check-label" for="permission-<?= $permission ?>">
                                                                    <?= $name ?>
                                                                </label>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" value="on" id="permission-<?= $permission ?>" name="<?= $permission ?>">
                                                                <label class="form-check-label" for="permission-<?= $permission ?>">
                                                                    <?= $name ?>
                                                                </label>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php
                                                    } ?>
                                                <br />
                                                <input type="submit" class="btn bg-custom" value="Save">
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php $x++; ?>
                        <?php } ?>
                        <?php endif; ?>
                    <?php elseif (Input::get('page') === 'site'): ?>
                        <?php
                            if (!$user->hasPermission('opsmanage')) {
                                Redirect::to('home.php');
                                die();
                            }

                            $tab = "colors";
                            if (!empty(Input::get('tab'))) {
                                $tab = Input::get('tab');
                            }
                            $ACTIVE_CATEGORY = 'site-management';
                        ?>
                        <script>
                            $(document).ready(function() {
                                $("#<?= $tab; ?>link").click();
                            });
                        </script>
                        <h3>Flare Settings</h3>
                        <p>Here you may configure Flare to be your own.</p>
                        <ul class="nav nav-tabs nav-dark justify-content-center">
                            <li class="nav-item">
                                <a class="nav-link" id="colorslink" data-toggle="tab" href="#colors">Color Theme</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="settingslink" data-toggle="tab" href="#settings">VA Settings</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="vanetlink" data-toggle="tab" href="#vanet">VANet Settings</a>
                            </li>
                            <?php if (Updater::getVersion()["prerelease"]) { ?>
                                <li class="nav-item">
                                    <a class="nav-link" id="debuglink" data-toggle="tab" href="#debug">Debugging Info</a>
                                </li>
                            <?php } ?>
                            <li class="nav-item">
                                <a class="nav-link" id="updateslink" data-toggle="tab" href="#updates">Updates</a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div id="colors" class="tab-pane container-fluid p-3 fade">
                                <h4>Colour Theme</h4>
                                <form action="update.php" method="post">
                                    <input hidden name="action" value="setcolour">
                                    <div class="form-group">
                                        <label for="">Main Colour (hex)</label>
                                        <input required type="text" class="form-control" name="hexcol" value="<?= Config::get('site/colour_main_hex') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="">Text Colour (hex)</label>
                                        <input required type="text" class="form-control" name="textcol" value="<?= Config::get('TEXT_COLOUR') ?>">
                                    </div>
                                    <input type="submit" class="btn bg-custom" value="Save">
                                </form>
                            </div>
                            <div id="settings" class="tab-pane container-fluid p-3 fade">
                                <h4>VA Settings</h4>
                                <form action="update.php" method="post">
                                    <input hidden name="action" value="vasettingsupdate">
                                    <div class="form-group">
                                        <label for="">VA Full Name</label>
                                        <input required type="text" class="form-control" name="vaname" value="<?= Config::get('va/name') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="">VA Callsign Identifier</label>
                                        <input required type="text" class="form-control" name="vaident" value="<?= Config::get('va/identifier') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="">Force Live Server</label>
                                        <select required class="form-control" name="forceserv" id="forceserv">
                                            <option value="0">Don't Force Server</option>
                                            <option value="casual">Force Casual Server</option>
                                            <option value="training">Force Training Server</option>
                                            <option value="expert">Force Expert Server</option>
                                        </select>
                                        <script>
                                            $(document).ready(function() {
                                                $("#forceserv").val("<?= Config::get('FORCE_SERVER'); ?>")
                                            });
                                        </script>
                                        <small class="text-muted">This will force all operations (PIREP lookups, ACARS, etc) to be on this server. If turned off, pilots will be able to choose.</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="">Check for Beta Updates?</label>
                                        <select requried class="form-control" name="checkpre" id="check-prerelease">
                                            <option value="0">No (Recommended for Production Sites)</option>
                                            <option value="1">Yes</option>
                                        </select>
                                        <script>
                                            $("#check-prerelease").val('<?= Config::get("CHECK_PRERELEASE"); ?>');
                                        </script>
                                        <small class="text-muted">Beta Pushes are often unstable and may break your site.</small>
                                    </div>
                                    <input type="submit" class="btn bg-custom" value="Save">
                                </form>
                            </div>
                            <div id="vanet" class="tab-pane container-fluid p-3 fade">
                                <h4>VANet Settings</h4>
                                <form action="update.php" method="post">
                                    <input hidden name="action" value="vanetupdate">
                                    <div class="form-group">
                                        <label for="">VANet API Key</label>
                                        <input required type="text" class="form-control" name="vanetkey" value="<?= Config::get('vanet/api_key') ?>">
                                    </div>
                                    <input type="submit" class="btn bg-custom" value="Save">
                                </form>
                            </div>
                            <div id="debug" class="tab-pane container-fluid p-3 fade">
                                <h4>Debugging Information</h4>
                                <p>
                                    This screen is shown to VAs running a pre-release version of Flare only. It contains information to help the
                                    Flare developers reproduce any issues you may have.
                                </p>
                                <table class="table">
                                    <tr>
                                        <th>DB Host</th>
                                        <td><?= Config::get('mysql/host'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>DB Port</th>
                                        <td><?= Config::get('mysql/port'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>VANet API Key</th>
                                        <td><?= Config::get('vanet/api_key'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Is VANet Gold?</th>
                                        <td><?= VANet::isGold(); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Force Server</th>
                                        <td><?= Config::get('FORCE_SERVER'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Version</th>
                                        <td><?= Json::decode(file_get_contents("./version.json"))["tag"]; ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div id="updates" class="tab-pane container-fluid p-3 fade">
                                <h4>Flare Updates</h4>
                                <p>
                                    <?php $ver = Updater::getVersion(); ?>
                                    <b>You are on Flare <?php echo $ver["tag"]; ?></b>
                                    <br />
                                    <?php
                                        $update = Updater::checkUpdate(Config::get('CHECK_PRERELEASE') == 1);
                                        if (!$update) {
                                            echo "Flare is Up-to-Date!";
                                        } else {
                                            echo "<span id=\"updateAvail\">An update to Flare ".$update["tag"]." is available<br /></span>";
                                            echo '<button class="btn bg-custom" id="updateNow">Update Now</button>';
                                            echo '<p id="updateResult"></p>';
                                        }
                                    ?>
                                </p>
                                <script>
                                    $(document).ready(function() {
                                        $("#updateNow").click(function() {
                                            $(this).hide();
                                            $("#updateAvail").hide();
                                            $(".loaded").hide();
                                            $("#loader-wrapper").show();
                                            $("#updateResult").html('<div class="spinner-grow spinner-custom"></div>');
                                            $.get("updater.php", function(data, status) {
                                                $("#updateResult").html(data);
                                                $(".loaded").show();
                                                $("#loader-wrapper").hide();
                                            });
                                        });
                                    });
                                </script>
                            </div>
                        </div>

                        <style>
                            .nav-tabs .nav-link {
                                color: #000!important;
                            }
                        </style>
                    <?php elseif (Input::get('page') === 'recruitment'): ?>
                        <?php $ACTIVE_CATEGORY = 'user-management'; ?>
                        <h3>Recruitment</h3>
                        <?php if (!$user->hasPermission('usermanage')): ?>
                            <div class="alert alert-danger text-center">Whoops! You don't have the necessary permissions to access this.</div>
                        <?php else: ?>
                            <p>Here you can manage any Pending Applications</p>
                            <form id="accept" action="update.php" method="post">
                                <input hidden name="action" value="acceptapplication">
                            </form>
                            <table class="table table-striped datatable">
                            <thead class="bg-custom">
                                <tr>
                                    <th>Name</th>
                                    <th class="mobile-hidden">Email</th>
                                    <th class="mobile-hidden">IFC</th>
                                    <th>Flags</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $lists = Json::decode(file_get_contents("https://ifvarb.com/watchlist_api.php?apikey=a5f2963d-29b1-40e4-8867-a4fbb384002c"));
                                $watchlist = array();
                                $blacklist = array();
                                foreach ($lists as $l) {
                                    if ($l["type"] == "Watchlist") {
                                        $watchlist[strtolower($l["ifc"])] = $l["notes"];
                                    } else {
                                        $blacklist[strtolower($l["ifc"])] = $l["notes"];
                                    }
                                }

                                $users = $user->getAllPendingUsers();
                                $x = 0;
                                foreach ($users as $user) {
                                    echo '<tr><td class="mobile-hidden align-middle">';
                                    echo $user["name"];
                                    echo '</td><td class="mobile-hidden align-middle">';
                                    echo $user["email"];
                                    echo '</td><td class="mobile-hidden align-middle">';
                                    $username = explode('/', $user['ifc'])[4];
                                    echo '<a href="'.$user['ifc'].'" target="_blank">'.$username.'</a>';
                                    echo '</td><td class="align-middle">';
                                    if (array_key_exists(strtolower($username), $blacklist)) {
                                        echo '<span class="badge badge-danger" data-toggle="tooltip" title="'.$blacklist[strtolower($username)].'">Blacklisted</span>';
                                    } elseif (array_key_exists(strtolower($username), $watchlist)) {
                                        echo '<span class="badge badge-warning" data-toggle="tooltip" title="'.$watchlist[strtolower($username)].'">Watchlisted</span>';
                                    } else {
                                        echo '<span class="badge badge-success">None</span>';
                                    }
                                    echo '</td><td class="align-middle">&nbsp;';
                                    if (!array_key_exists(strtolower($username), $blacklist)) {
                                        echo '<button class="btn btn-success text-light" value="'.$user['id'].'" form="accept" type="submit" name="accept"><i class="fa fa-check"></i></button>&nbsp;';
                                    }
                                    echo '<button value="'.$user['id'].'" id="delconfirmbtn" data-toggle="modal" data-target="#user'.$x.'declinemodal" class="btn btn-danger text-light" name="decline"><i class="fa fa-times"></i></button>&nbsp;';
                                    echo '<button id="delconfirmbtn" class="btn btn-primary text-light" data-toggle="modal" data-target="#user'.$x.'modal"><i class="fa fa-plus"></i></button>';
                                    echo '</td>';
                                    $x++;
                                }
                                ?>
                            </tbody>
                        </table>
                        <?php
                        $x = 0;
                        foreach ($users as $user) {
                            echo
                            '
                            <div class="modal fade" id="user'.$x.'modal" tabindex="-1" role="dialog" aria-labelledby="user'.$x.'label" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="usermodal-title"></h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <form action="update.php" method="post">
                                                <div class="form-group">
                                                    <label for="usermodal-callsign">Callsign</label>
                                                    <input readonly type="text" value="'.$user["callsign"].'" class="form-control" name="callsign">
                                                </div>
                                                <div class="form-group">
                                                    <label for="usermodal-name">Name</label>
                                                    <input readonly type="text" value="'.$user["name"].'" class="form-control" name="name">
                                                </div>
                                                <div class="form-group">
                                                    <label for="usermodal-email">Email</label>
                                                    <input readonly type="text" value="'.$user["email"].'" class="form-control" name="email">
                                                </div>
                                                <div class="form-group">
                                                    <label for="usermodal-ifc">IFC Username</label>
                                                    <a href="'.$user['ifc'].'" target="_blank"><input readonly type="text" style="cursor:pointer" value="'.$username.'" class="form-control" name="ifc"></a>
                                                </div>
                                                <div class="form-group">
                                                    <label for="usermodal-joined">Grade</label>
                                                    <input readonly type="text" value="'.$user["grade"].'" class="form-control" name="grade">
                                                </div>
                                                <div class="form-group">
                                                    <label for="usermodal-status">Violations to landings</label>
                                                    <input readonly type="text" value="'.$user["viol"].'" class="form-control" name="viol">
                                                </div>
                                            </form>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn bg-custom" data-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal fade" id="user'.$x.'declinemodal" tabindex="-1" role="dialog" aria-labelledby="user'.$x.'label" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="usermodal-title"></h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <form action="update.php" method="post" id="declinemodal">
                                                <input hidden class="form-control" name="action" value="declineapplication">
                                                <input hidden class="form-control" name="id" value="'.$user['id'].'">
                                                <div class="form-group">
                                                    <label for="usermodal-status">Reason for decline of application</label>
                                                    <input required type="text" class="form-control" name="declinereason">
                                                </div>
                                            </form>
                                        </div>
                                        <div class="modal-footer">
                                            <button class="btn bg-custom" form="declinemodal" type="submit">Decline</button>
                                            <button type="button" class="btn bg-secondary" data-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            ';
                            $x++;
                        }

                        ?>
                        <?php endif; ?>
                    <?php elseif (Input::get('page') === 'pirepmanage'): ?>
                        <h3>Manage PIREPs</h3>
                        <?php $ACTIVE_CATEGORY = 'pirep-management'; ?>
                        <?php if (!$user->hasPermission('pirepmanage')): ?>
                            <div class="alert alert-danger text-center">Whoops! You don't have the necessary permissions to access this.</div>
                        <?php else: ?>
                            <form id="acceptpirep" action="update.php" method="post">
                                <input hidden name="action" value="acceptpirep">
                            </form>
                            <form id="declinepirep" action="update.php" method="post">
                                <input hidden name="action" value="declinepirep">
                            </form>
                            <table class="table table-striped">
                                <thead class="bg-custom">
                                    <tr>
                                        <th class="mobile-hidden">Callsign</th>
                                        <th class="mobile-hidden">Flight Number</th>
                                        <th>Dep<span class="mobile-hidden">arture</span></th>
                                        <th>Arr<span class="mobile-hidden">ival</span></th>
                                        <th>Flight Time</th>
                                        <th class="mobile-hidden">Multiplier</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $x = 0;
                                    $pireps = Pirep::fetchPending();
                                    foreach ($pireps as $pirep) {
                                        echo '<tr><td class="align-middle mobile-hidden">';
                                        $callsign = $user->idToCallsign($pirep['pilotid']);
                                        echo $callsign;
                                        echo '</td><td class="align-middle mobile-hidden">';
                                        echo $pirep['flightnum'];
                                        echo '</td><td class="align-middle">';
                                        echo $pirep['departure'];
                                        echo '</td><td class="align-middle">';
                                        echo $pirep['arrival'];
                                        echo '</td><td class="align-middle">';
                                        echo Time::secsToString($pirep["flighttime"]);
                                        echo '</td><td class="align-middle mobile-hidden">';
                                        echo $pirep["multi"];
                                        echo '</td><td class="align-middle">';
                                        echo '<button class="btn btn-success text-light" value="'.$pirep['id'].'" form="acceptpirep" type="submit" name="accept"><i class="fa fa-check"></i></button>';
                                        echo '&nbsp;<button value="'.$pirep['id'].'" form="declinepirep" type="submit" class="btn btn-danger text-light" name="decline"><i class="fa fa-times"></i></button>';
                                        echo '</td>';
                                        $x++;
                                    }
                                    ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    <?php elseif (Input::get('page') === 'multimanage'): ?>
                        <h3>Manage Multipliers</h3>
                        <?php $ACTIVE_CATEGORY = 'pirep-management'; ?>
                        <?php if (!$user->hasPermission('pirepmanage')): ?>
                            <div class="alert alert-danger text-center">Whoops! You don't have the necessary permissions to access this.</div>
                        <?php else: ?>
                            <p>
                                Multiplier codes allow your pilots to gain multiplied flight time easily. They can simply enter their real flight time
                                when filing their PIREP, enter a multiplier code, and their flight time multiplier will be applied automatically.
                            </p>
                            <h4>Active Multipliers</h4>
                            <form id="multiarticle" action="update.php" method="post">
                                <input hidden name="action" value="deletemulti">
                            </form>
                            <table class="table table-striped">
                                <thead class="bg-custom">
                                    <tr>
                                        <th>Code</th>
                                        <th>Name</th>
                                        <th>Multiplication</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        $multis = Pirep::fetchMultipliers();
                                        foreach ($multis as $m) {
                                            echo '<tr><td class="align-middle">';
                                            echo $m->code;
                                            echo '</td><td class="align-middle">';
                                            echo $m->name;
                                            echo '</td><td class="align-middle">';
                                            echo $m->multiplier.'x';
                                            echo '</td><td class="align-middle">';
                                            echo '<button value="'.$m->id.'" form="multiarticle" type="submit" class="btn btn-danger text-light" name="delete"><i class="fa fa-trash"></i></button>';
                                            echo '</td></tr>';
                                        }
                                    ?>
                                </tbody>
                            </table>
                            <br />
                            <h4>Add Multiplier</h4>
                            <form action="update.php" method="post">
                                <input hidden name="action" value="addmulti" />
                                <div class="form-group">
                                    <label for="multi-name">Name</label>
                                    <input required type="text" maxlength="120" class="form-control" name="name" id="multi-name" />
                                </div>
                                <div class="form-group">
                                    <label for="multi-multi">Multiplication</label>
                                    <input required type="number" step="0.1" class="form-control" name="multi" id="multi-multi" />
                                </div>
                                <input type="submit" class="btn bg-custom" value="Save" />
                            </form>
                        <?php endif; ?>
                    <?php elseif (Input::get('page') === 'newsmanage'): ?>
                        <h3>Manage News</h3>
                        <?php $ACTIVE_CATEGORY = 'site-management'; ?>
                        <?php if (!$user->hasPermission('usermanage')): ?>
                            <div class="alert alert-danger text-center">Whoops! You don't have the necessary permissions to access this.</div>
                        <?php else: ?>
                            <h4>Active News Articles</h4>
                            <div class="modal fade" id="confirmNewsDelete">
                            <div class="modal-dialog modal-sm">
                                <div class="modal-content">

                                <div class="modal-header">
                                    <h4 class="modal-title">Are You Sure?</h4>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>

                                <div class="modal-body">
                                    Are you sure you want to delete this News Item?
                                    <form id="deletearticle" action="update.php" method="post">
                                        <input hidden name="action" value="deletearticle" />
                                        <input hidden name="delete" id="confirmNewsDelete-id" />
                                        <input type="submit" class="btn btn-danger" value="Delete" />
                                    </form>
                                </div>

                                <div class="modal-footer text-center justify-content-center">
                                    <button type="button" class="btn bg-custom" data-dismiss="modal">Cancel</button>
                                </div>

                                </div>
                            </div>
                            </div>
                            <table class="table table-striped datatable">
                                <thead class="bg-custom">
                                    <tr>
                                        <th>Title</th>
                                        <th class="mobile-hidden">Date Posted</th>
                                        <th class="mobile-hidden">Author</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $x = 0;
                                    $news = News::get();
                                    foreach ($news as $article) {
                                        echo '<tr><td class="align-middle">';
                                        echo $article['title'];
                                        echo '</td><td class="align-middle mobile-hidden">';
                                        echo $article['dateposted'];
                                        echo '</td><td class="align-middle mobile-hidden">';
                                        echo $article['author'];
                                        echo '</td><td class="align-middle">';
                                        echo '&nbsp;<button value="'.$article['id'].'" id="articleedit" data-toggle="modal" data-target="#article'.$x.'editmodal" class="btn btn-primary text-light" name="edit"><i class="fa fa-edit"></i></button>';
                                        echo '&nbsp;<button data-id="'.$article['id'].'" class="btn btn-danger text-light deleteArticle"><i class="fa fa-trash"></i></button>';
                                        echo '</td>';
                                        $x++;
                                    }
                                    ?>
                                </tbody>
                            </table>
                            <?php
                            $x = 0;
                            foreach ($news as $article) {
                                echo
                                '
                                <div class="modal fade" id="article'.$x.'editmodal" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit News Article</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <form action="update.php" method="post">
                                                    <input hidden name="action" value="editarticle">
                                                    <input hidden name="id" value="'.$article['id'].'">
                                                    <div class="form-group">
                                                        <label>Title</label>
                                                        <input type="text" value="'.$article["title"].'" class="form-control" name="title">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Content</label>
                                                        <textarea class="form-control" name="content">'.$article["content"].'</textarea>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Author</label>
                                                        <input readonly type="text" value="'.$article["author"].'" class="form-control" name="author">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="usermodal-ifc">Date Posted</label>
                                                        <input readonly type="text" value="'.$article["dateposted"].'" class="form-control" name="dateposted">
                                                    </div>
                                                    <input type="submit" class="btn bg-success" value="Save">
                                                </form>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn bg-danger" data-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                ';
                                $x++;
                            }
                            ?>
                            <br />
                            <h4>New Article</h4>
                            <form action="update.php" method="post">
                                <input hidden name="action" value="newarticle">
                                <div class="form-group">
                                    <label>Title</label>
                                    <input type="text" class="form-control" name="title">
                                </div>
                                <div class="form-group">
                                    <label>Content</label>
                                    <textarea class="form-control" name="content"></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Author</label>
                                    <input readonly type="text" value="<?= escape($user->data()->name) ?>" class="form-control" name="author">
                                </div>
                                <input type="submit" class="btn bg-custom" value="Save">
                            </form>

                            <script>
                                $(".deleteArticle").click(function() {
                                    var id = $(this).data('id');
                                    $("#confirmNewsDelete-id").val(id);
                                    $("#confirmNewsDelete").modal('show');
                                });
                            </script>
                        <?php endif; ?>
                    <?php elseif (Input::get('page') === 'events'): ?>
                        <?php $ACTIVE_CATEGORY = 'operations-management'; ?>
                        <?php if (!$user->hasPermission('opsmanage')): ?>
                            <div class="alert alert-danger text-center">Whoops! You don't have the necessary permissions to access this.</div>
                        <?php else: ?>
                            <h3>Manage Events</h3>
                            <button type="button" class="btn bg-custom mb-2" data-toggle="modal" data-target="#newevent">New Event</button>

                            <div class="modal fade" id="confirmEventDelete">
                            <div class="modal-dialog modal-sm">
                                <div class="modal-content">

                                <div class="modal-header">
                                    <h4 class="modal-title">Are You Sure?</h4>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>

                                <div class="modal-body">
                                    Are you sure you want to delete this Event?
                                    <form id="deleteevent" action="update.php" method="post">
                                        <input hidden name="action" value="deleteevent" />
                                        <input hidden name="delete" id="confirmEventDelete-id" />
                                        <input type="submit" class="btn btn-danger" value="Delete" />
                                    </form>
                                </div>

                                <div class="modal-footer text-center justify-content-center">
                                    <button type="button" class="btn bg-custom" data-dismiss="modal">Cancel</button>
                                </div>

                                </div>
                            </div>
                            </div>

                            <!-- Add Event Modal -->
                            <div class="modal fade" id="newevent">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title">Add Event</h4>
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    </div>

                                    <div class="modal-body">
                                        <form action="update.php" method="post">
                                            <input hidden name="action" value="addevent" />
                                            <div class="form-group">
                                                <label for="event-name">Event Name</label>
                                                <input required type="text" class="form-control" name="name" id="event-name" />
                                            </div>
                                            <div class="form-group">
                                                <label for="event-description">Event Description</label>
                                                <textarea required class="form-control" name="description" id="event-description"></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label for="event-date">Event Date</label>
                                                <input required type="date" class="form-control" name="date" id="event-date" min="<?= date("Y-m-d"); ?>" />
                                            </div>
                                            <div class="form-group">
                                                <label for="event-time">Event Time</label>
                                                <select required class="form-control" name="time" id="event-time">
                                                    <option value>Select</option>
                                                    <?php
                                                        $times = ["00", "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17",
                                                        "18", "19", "20", "21", "22", "23"];
                                                        foreach ($times as $t) {
                                                            echo '<option value="'.$t.'00'.'">'.$t.'00Z</option>';
                                                            echo '<option value="'.$t.'30'.'">'.$t.'30Z</option>';
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="event-dep">Departure ICAO</label>
                                                <input required type="text" class="form-control" name="dep" id="event-dep" />
                                            </div>
                                            <div class="form-group">
                                                <label for="event-arr">Arrival ICAO</label>
                                                <input required type="text" class="form-control" name="arr" id="event-arr" />
                                            </div>
                                            <div class="form-group">
                                                <label for="event-aircraft">Aircraft</label>
                                                <select required class="form-control" name="aircraft" id="event-aircraft">
                                                    <option value>Select</option>
                                                    <?php
                                                        $activeAircraft = Aircraft::fetchActiveAircraft()->results();
                                                        foreach ($activeAircraft as $aircraft) {
                                                            echo '<option value="'.$aircraft->ifliveryid.'">'.$aircraft->name.' ('.$aircraft->liveryname.')</option>';
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="event-vis">Visible to Pilots?</label>
                                                <select required class="form-control" name="visible" id="event-vis">
                                                    <option value="1">Yes</option>
                                                    <option value="0">No</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="event-serv">Event Server</label>
                                                <select required class="form-control" name="server" id="event-serv">
                                                    <option value>Select</option>
                                                    <option value="casual">Casual Server</option>
                                                    <option value="training">Training Server</option>
                                                    <option value="expert">Expert Server</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="event-gates">Gate Names</label>
                                                <input required type="text" class="form-control" name="gates" id="event-gates" />
                                                <small class="text-muted">Comma-Separated List of Gate Names</small>
                                            </div>
                                            
                                            <input type="submit" class="btn bg-custom" value="Add Event" />
                                        </form>
                                    </div>
                                </div>
                            </div>
                            </div>

                            <table class="table table-striped">
                                <thead class="bg-custom text-center">
                                    <tr>
                                        <th>Name</th>
                                        <th>Airport</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="events-table">
                                    <tr><td colspan="3">Loading...</td></tr>
                                </tbody>
                            </table>
                            <script>
                                $.post("vanet.php", {
                                    "method": "events-admin"
                                }, function (data, status) {
                                    $("#events-table").html(data);
                                    $(".deleteEvent").click(function() {
                                        var id = $(this).data('id');
                                        $("#confirmEventDelete-id").val(id);
                                        $("#confirmEventDelete").modal('show');
                                    });
                                    $(".editEvent").click(function() {
                                        var eventName = $(this).data('name');
                                        var eventDesc = $(this).data('desc');
                                        var eventDep = $(this).data('dep');
                                        var eventArr = $(this).data('arr');
                                        var eventAircraft = $(this).data('aircraft');
                                        var eventVis = $(this).data('vis');
                                        var eventServer = $(this).data('server');
                                        var eventId = $(this).data('id');

                                        $("#editevent-name").val(eventName);
                                        $("#editevent-description").val(eventDesc);
                                        $("#editevent-dep").val(eventDep);
                                        $("#editevent-arr").val(eventArr);
                                        $("#editevent-aircraft").val(eventAircraft);
                                        $("#editevent-vis").val(eventVis);
                                        $("#editevent-serv").val(eventServer);
                                        $("#editevent-id").val(eventId);

                                        $("#editevent").modal('show');
                                    });
                                });
                            </script>

                            <!-- Edit Event Modal -->
                            <div class="modal fade" id="editevent">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title" id="editevent-title">Edit Event</h4>
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    </div>

                                    <div class="modal-body">
                                        <form action="update.php" method="post">
                                            <input hidden name="action" value="editevent" />
                                            <input hidden name="id" id="editevent-id" />
                                            <div class="form-group">
                                                <label for="editevent-name">Event Name</label>
                                                <input required type="text" class="form-control" name="name" id="editevent-name" />
                                            </div>
                                            <div class="form-group">
                                                <label for="editevent-description">Event Description</label>
                                                <textarea required class="form-control" name="description" id="editevent-description"></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label for="editevent-dep">Departure ICAO</label>
                                                <input required type="text" class="form-control" name="dep" id="editevent-dep" />
                                            </div>
                                            <div class="form-group">
                                                <label for="editevent-arr">Arrival ICAO</label>
                                                <input required type="text" class="form-control" name="arr" id="editevent-arr" />
                                            </div>
                                            <div class="form-group">
                                                <label for="editevent-aircraft">Aircraft</label>
                                                <select required class="form-control" name="aircraft" id="editevent-aircraft">
                                                    <option value>Select</option>
                                                    <?php
                                                        $activeAircraft = Aircraft::fetchActiveAircraft()->results();
                                                        foreach ($activeAircraft as $aircraft) {
                                                            echo '<option value="'.$aircraft->ifliveryid.'">'.$aircraft->name.' ('.$aircraft->liveryname.')</option>';
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="editevent-vis">Visible to Pilots?</label>
                                                <select required class="form-control" name="visible" id="editevent-vis">
                                                    <option value="1">Yes</option>
                                                    <option value="0">No</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="editevent-serv">Event Server</label>
                                                <select required class="form-control" name="server" id="editevent-serv">
                                                    <option value>Select</option>
                                                    <option value="casual">Casual Server</option>
                                                    <option value="training">Training Server</option>
                                                    <option value="expert">Expert Server</option>
                                                </select>
                                            </div>
                                            
                                            <input type="submit" class="btn bg-custom" value="Save" />
                                        </form>
                                    </div>
                                </div>
                            </div>
                            </div>
                        <?php endif; ?>
                    <?php elseif (Input::get('page') === 'statsviewing'): ?>
                        <?php $ACTIVE_CATEGORY = 'pirep-management'; ?>
                        <?php if (!$user->hasPermission('statsviewing')): ?>
                            <div class="alert alert-danger text-center">Whoops! You don't have the necessary permissions to access this.</div>
                        <?php else: ?>
                            <h3>VA Statistics</h3>
                            <table class="table">
                                <tr><td><b>Total Hours</b></td><td><?php echo Time::secsToString(Stats::totalHours()); ?></td></tr>
                                <tr><td><b>Total Flights</b></td><td><?php echo Stats::totalFlights(); ?></td></tr>
                                <tr><td><b>Total Pilots</b></td><td><?php echo Stats::numPilots(); ?></td></tr>
                                <tr><td><b>Total Routes</b></td><td><?php echo Stats::numRoutes(); ?></td></tr>
                            </table>
                            <hr />
                            <?php if (!$IS_GOLD): ?>
                                <p>
                                    View vFinance Stats on <a href="https://vanet.app/airline/finance/">VANet</a>. 
                                    Sign Up to VANet Gold in order to get access to VANet Stats right here.
                                </p>
                            <?php else: ?>
                                <h4>VANet Statistics</h4>
                                <?php $stats = VANet::getStats(); ?>
                                <table class="table">
                                    <tr><td><b>Total Distance</b></td><td><?php echo $stats["totalDistance"]; ?>NM</td></tr>
                                    <tr><td><b>Total Revenue</b></td><td>$<?php echo $stats["totalRevenue"]; ?></td></tr>
                                </table>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php elseif (Input::get('page') === 'opsmanage'): ?>
                        <?php $ACTIVE_CATEGORY = 'operations-management'; ?>
                        <?php if (!$user->hasPermission('opsmanage')): ?>
                            <div class="alert alert-danger text-center">Whoops! You don't have the necessary permissions to access this.</div>
                        <?php else: ?>
                            <?php if (Input::get('section') === 'fleet'): ?>
                                <h3>Fleet</h3>
                                <button type="button" class="btn bg-custom mb-2" data-toggle="modal" data-target="#addAircraft">Add Aircraft</button>
                                <div id="addAircraft" class="modal fade" role="dialog">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h4 class="modal-title">Add Aircraft</h4>
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body">
                                                <form action="update.php" method="post">
                                                    <input hidden name="action" value="addaircraft">
                                                    <div class="form-group">
                                                        <label for="aircraft">Type</label>
                                                        <select class="form-control" name="aircraftselect" id="aircraftselect" required>
                                                            <option value>Select</option>
                                                            <?php
                                                            $allac = Aircraft::fetchAllAircraftFromVANet();
                                                            foreach ($allac as $id => $name) {
                                                                echo '<option value="'.$id.'">'.$name.'</option>';
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="rank">Livery</label>
                                                        <select class="form-control" name="livery" id="liveriesselect" required>
                                                            <option disabled>Loading...</option>
                                                        </select>
                                                    </div>
                                                    <script>
                                                        $("#aircraftselect").change(function() {
                                                            $.ajax({
                                                                url: "vanet.php",
                                                                type: "POST",
                                                                data: { method: "liveriesforaircraft", data: $(this).val() },
                                                                success: function(html){
                                                                    $("#liveriesselect").empty();
                                                                    $("#liveriesselect").append("<option>Select</option>");
                                                                    $("#liveriesselect").append(html);
                                                                }
                                                                });
                                                            });
                                                    </script>
                                                    <div class="form-group">
                                                        <label for="rank">Rank required</label>
                                                        <select class="form-control" name="rank" required>
                                                            <option value>Select</option>
                                                            <?php
                                                            $ranks = Rank::fetchAllNames()->results();

                                                            foreach ($ranks as $rank) {
                                                                echo '<option value="'.$rank->id.'">'.$rank->name.'</option>';
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                    <input type="submit" class="btn bg-custom" value="Add Aircraft">
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal fade" id="confirmFleetDelete">
                                <div class="modal-dialog modal-sm">
                                    <div class="modal-content">

                                    <div class="modal-header">
                                        <h4 class="modal-title">Are You Sure?</h4>
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    </div>

                                    <div class="modal-body">
                                        Are you sure you want to delete this Aircraft?
                                        <form id="deleteaircraft" action="update.php" method="post">
                                            <input hidden name="action" value="deleteaircraft" />
                                            <input hidden name="delete" id="confirmFleetDelete-id" />
                                            <input type="submit" class="btn btn-danger" value="Delete" />
                                        </form>
                                    </div>

                                    <div class="modal-footer text-center justify-content-center">
                                        <button type="button" class="btn bg-custom" data-dismiss="modal">Cancel</button>
                                    </div>

                                    </div>
                                </div>
                                </div>
                                <form id="deleteaircraft" method="post" action="update.php">
                                    <input hidden value="deleteaircraft" name="action">
                                </form>
                                <table class="table table-striped datatable">
                                    <thead class="bg-custom">
                                        <tr>
                                            <th>Name</th>
                                            <th class="mobile-hidden">Livery</th>
                                            <th class="mobile-hidden">Min. Rank</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $all = Aircraft::fetchActiveAircraft()->results();
                                        foreach ($all as $aircraft) {
                                            echo '<tr><td class="align-middle">';
                                            echo $aircraft->name;
                                            echo '</td><td class="align-middle mobile-hidden">';
                                            echo $aircraft->liveryname;
                                            echo '</td><td class="align-middle mobile-hidden">';
                                            echo $aircraft->rank;
                                            echo '</td><td class="align-middle">';
                                            echo '&nbsp;<button data-id="'.$aircraft->id.'" class="btn btn-danger text-light deleteFleet"><i class="fa fa-trash"></i></button>';
                                            echo '&nbsp;<button class="btn btn-primary editFleet" data-acName="'.$aircraft->name.' ('.$aircraft->liveryname.')'.'" 
                                            data-rankReq="'.$aircraft->rankreq.'" data-id="'.$aircraft->id.'"><i class="fa fa-edit"></i></button>';
                                            echo '</td>';
                                        }
                                        ?>
                                    </tbody>
                                </table>

                                <script>
                                    $(".deleteFleet").click(function() {
                                        var id = $(this).data('id');
                                        $("#confirmFleetDelete-id").val(id);
                                        $("#confirmFleetDelete").modal('show');
                                    });
                                </script>

                                <a href="?page=opsmanage&section=export">Export Aircraft</a> | <a href="?page=opsmanage&section=import">Import Aircraft</a>

                                <div class="modal fade" id="fleetedit">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                        <div class="modal-header">
                                            <h4 class="modal-title" id="fleetedit-title"></h4>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <form action="update.php" method="post">
                                                <input hidden name="action" value="editfleet" />
                                                <input hidden name="id" id="fleetedit-id" />
                                                <div class="form-group">
                                                    <label for="fleetedit-rank">Minimum Rank</label>
                                                    <select class="form-control" name="rank" id="fleetedit-rank">
                                                        <?php
                                                            $ranks = Rank::fetchAllNames()->results();
                                                            foreach ($ranks as $r) {
                                                                echo '<option id="fleetedot-rank-'.$r->id.'" value="'.$r->id.'">'.$r->name.'</option>';
                                                            }
                                                        ?>
                                                    </select>
                                                </div>
                                                <input type="submit" class="btn bg-custom" value="Save" />
                                            </form>
                                        </div>
                                        </div>
                                    </div>
                                </div>

                                <script>
                                    $(".editFleet").click(function() {
                                        var acName = $(this).data('acname');
                                        var acRank = $(this).data('rankreq');
                                        var acId = $(this).data('id');
                                        
                                        $("#fleetedit-title").text("Edit Aircraft: " + acName);
                                        $("#fleetedit-id").val(acId);
                                        $("#fleetedit-rank-" + acRank).attr('selected', true);

                                        $("#fleetedit").modal('show');
                                    });
                                </script>
                            <?php elseif (Input::get('section') === 'routes'): ?>
                                <h3>Route Management</h3>
                                <p>Here you can Manage your VA's Routes.</p>
                                <button type="button" class="btn bg-custom mb-2" data-toggle="modal" data-target="#addRoute">Add Route</button>
                                <div id="addRoute" class="modal fade" role="dialog">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h4 class="modal-title">Add Route</h4>
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body">
                                                <form action="update.php" method="post">
                                                    <input hidden name="action" value="addroute">
                                                    <div class="form-group">
                                                        <label for="">Departure Airport</label>
                                                        <input type="text" name="dep" class="form-control" placeholder="ICAO" required />
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="">Arrival Airport</label>
                                                        <input type="text" name="arr" class="form-control" placeholder="ICAO" required />
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="">Flight Number</label>
                                                        <input maxlength="10" type="text" name="fltnum" class="form-control" required />
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="">Flight Duration</label>
                                                        <div class="row">
                                                            <div class="col-sm-6">
                                                                <input required type="number" min="0" id="flightTimeHrs" class="form-control" placeholder="Hours" />
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <input required type="number" min="0" id="flightTimeMins" class="form-control" placeholder="Minutes" />
                                                            </div>
                                                        </div>
                                                        <input hidden name="duration" id="flightTimeFormatted" class="form-control" required />
                                                        <script>
                                                            function formatFlightTime() {
                                                                var hrs = $("#flightTimeHrs").val();
                                                                var mins = $("#flightTimeMins").val();
                                                                $("#flightTimeFormatted").val(hrs + ":" + mins);
                                                            }

                                                            function reverseFormatFlightTime() {
                                                                var formatted = $("#flightTimeFormatted").val();
                                                                if (formatted != '') {
                                                                    var split = formatted.split(":");
                                                                    var hrs = split[0];
                                                                    var mins = split[1];
                                                                    $("#flightTimeHrs").val(hrs);
                                                                    $("#flightTimeMins").val(mins);
                                                                }
                                                            }

                                                            $(document).ready(function() {
                                                                $("#flightTimeHrs").keyup(function() {
                                                                    formatFlightTime();
                                                                });
                                                                $("#flightTimeMins").keyup(function() {
                                                                    formatFlightTime();
                                                                });
                                                                reverseFormatFlightTime();
                                                            });
                                                        </script>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="">Aircraft</label>
                                                        <select class="form-control" name="aircraft" required>
                                                            <option value>Select</option>
                                                            <?php
                                                            $all = Aircraft::fetchActiveAircraft()->results();

                                                            foreach ($all as $aircraft) {
                                                                echo '<option value="'.$aircraft->id.'">'.$aircraft->name.' ('.$aircraft->liveryname.')</option>';
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                    <input type="submit" class="btn bg-custom" value="Add Route" />
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <table class="table table-striped datatable">
                                    <thead class="bg-custom">
                                        <tr>
                                            <th class="mobile-hidden">Flight Number</th>
                                            <th>Departure</th>
                                            <th>Arrival</th>
                                            <th class="mobile-hidden">Aircraft</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $all = Route::fetchAll()->results();

                                        foreach ($all as $route) {
                                            echo '<tr><td class="align-middle mobile-hidden">';
                                            echo $route->fltnum;
                                            echo '</td><td class="align-middle">';
                                            echo $route->dep;
                                            echo '</td><td class="align-middle">';
                                            echo $route->arr;
                                            echo '</td><td class="align-middle mobile-hidden">';
                                            echo $route->aircraft.'<span class="mobile-hidden"> ('.$route->livery.')</span>';
                                            echo '</td><td class="align-middle">';
                                            echo '<button class="btn bg-custom editRoute" 
                                            data-id="'.$route->id.'" data-fltnum="'.$route->fltnum.'" 
                                            data-dep="'.$route->dep.'" data-arr="'.$route->arr.'" 
                                            data-duration="'.Time::secsToString($route->duration).'" data-aircraft="'.$route->aircraftid.'" 
                                            ><i class="fa fa-edit"></i></button>';
                                            echo '&nbsp;<button value="'.$route->id.'" form="deleteroute" type="submit" class="btn btn-danger text-light" name="delete"><i class="fa fa-trash"></i></button>';
                                            echo '</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                                <div id="routeedit" class="modal fade" role="dialog">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h4 class="modal-title">Edit Route</h4>
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body">
                                                <form action="update.php" method="post">
                                                    <input hidden name="action" value="editroute">
                                                    <input hidden name="id" id="routeedit-id" />
                                                    <div class="form-group">
                                                        <label for="routeedit-dep">Departure Airport</label>
                                                        <input type="text" name="dep" id="routeedit-dep" class="form-control" placeholder="ICAO" required />
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="aircraft">Arrival Airport</label>
                                                        <input type="text" name="arr" id="routeedit-arr" class="form-control" placeholder="ICAO" required />
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="aircraft">Flight Number</label>
                                                        <input maxlength="10" type="text" name="fltnum" id="routeedit-fltnum" class="form-control" required />
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="aircraft">Flight Duration</label>
                                                        <div class="row">
                                                            <div class="col-sm-6">
                                                                <input required type="number" min="0" id="routeedit-hrs" class="form-control" placeholder="Hours" />
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <input required type="number" min="0" id="routeedit-mins" class="form-control" placeholder="Minutes" />
                                                            </div>
                                                        </div>
                                                        <input hidden name="duration" id="routeedit-duration" class="form-control" required />
                                                        <script>
                                                            function formatEditFlightTime() {
                                                                var hrs = $("#routeedit-hrs").val();
                                                                var mins = $("#routeedit-mins").val();
                                                                $("#routeedit-duration").val(hrs + ":" + mins);
                                                            }

                                                            function reverseFormatEditFlightTime() {
                                                                var formatted = $("#routeedit-duration").val();
                                                                if (formatted != '') {
                                                                    var split = formatted.split(":");
                                                                    var hrs = split[0];
                                                                    var mins = split[1];
                                                                    $("#routeedit-hrs").val(hrs);
                                                                    $("#routeedit-mins").val(mins);
                                                                }
                                                            }

                                                            $(document).ready(function() {
                                                                $("#routeedit-hrs").keyup(function() {
                                                                    formatEditFlightTime();
                                                                });
                                                                $("#routeedit-mins").keyup(function() {
                                                                    formatEditFlightTime();
                                                                });
                                                                reverseFormatEditFlightTime();
                                                            });
                                                        </script>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="aircraft">Aircraft</label>
                                                        <select class="form-control" id="routeedit-aircraft" name="aircraft" required>
                                                            <option value>Select</option>
                                                            <?php
                                                            $aircraft = Aircraft::fetchAllAircraft()->results();

                                                            foreach ($aircraft as $a) {
                                                                echo '<option value="'.$a->id.'">'.$a->name.' ('.$a->liveryname.')</option>';
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                    <input type="submit" class="btn bg-custom" value="Save" />
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <a href="?page=opsmanage&section=export">Export Routes</a> | <a href="?page=opsmanage&section=import">Import Routes</a>
                                <form id="deleteroute" method="post" action="update.php">
                                    <input hidden value="deleteroute" name="action">
                                </form>
                                <script>
                                    $(".editRoute").click(function() {
                                        var id = $(this).data('id');
                                        var fltnum = $(this).data('fltnum');
                                        var dep = $(this).data('dep');
                                        var arr = $(this).data('arr');
                                        var duration = $(this).data('duration');
                                        var aircraft = $(this).data('aircraft');

                                        $("#routeedit-id").val(id);
                                        $("#routeedit-fltnum").val(fltnum);
                                        $("#routeedit-dep").val(dep);
                                        $("#routeedit-arr").val(arr);
                                        $("#routeedit-duration").val(duration);
                                        reverseFormatEditFlightTime();
                                        $("#routeedit-aircraft").val(aircraft);

                                        $("#routeedit").modal('show');
                                    });
                                </script>
                            <?php elseif (Input::get('section') === 'ranks'): ?>
                                <h3>Manage Ranks</h3>
                                <p>Here you can Manage the Ranks that your pilots can be Awarded.</p>
                                <button type="button" class="btn bg-custom mb-2" data-toggle="modal" data-target="#addRank">Add Rank</button>
                                <div id="addRank" class="modal fade" role="dialog">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h4 class="modal-title">Add Rank</h4>
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body">
                                                <form action="update.php" method="post">
                                                    <input hidden name="action" value="addrank">
                                                    <div class="form-group">
                                                        <label for="name">Name</label>
                                                        <input type="text" name="name" class="form-control" placeholder="Second Officer" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="time">Flight time required (in hours)</label>
                                                        <input type="number" name="time" class="form-control" placeholder="50" required>
                                                    </div>
                                                    <input type="submit" class="btn bg-custom" value="Add Rank">
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <form id="delrank" action="update.php" method="post">
                                    <input hidden name="action" value="delrank" />
                                </form>

                                <table class="table table-striped datatable">
                                    <thead class="bg-custom">
                                        <tr>
                                            <th>Name</th>
                                            <th>Min. Hours</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $all = Rank::fetchAllNames()->results();
                                        foreach ($all as $rank) {
                                            echo '<tr><td class="align-middle">';
                                            echo $rank->name;
                                            echo '</td><td class="align-middle">';
                                            echo Time::secsToString($rank->timereq);
                                            echo '</td><td class="align-middle">';
                                            echo '<button class="btn btn-primary text-light editRank" 
                                            data-id="'.$rank->id.'" data-name="'.$rank->name.'" 
                                            data-minhrs="'.($rank->timereq / 3600).'">
                                            <i class="fa fa-edit"></i></button>';
                                            echo '&nbsp;<button class="btn btn-danger text-light" 
                                            value="'.$rank->id.'" form="delrank" name="delete">
                                            <i class="fa fa-trash"></i></button>';
                                            echo '</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                                <div id="rankmodal" class="modal fade" role="dialog">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h4 class="modal-title" id="rankmodal-title"></h4>
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body">
                                                <form action="update.php" method="post">
                                                    <input hidden name="action" value="editrank">
                                                    <input hidden name="id" id="rankmodal-id">
                                                    <div class="form-group">
                                                        <label for="name">Name</label>
                                                        <input type="text" name="name" class="form-control" id="rankmodal-name" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="time">Flight Time Required (in hours)</label>
                                                        <input type="number" min="0" name="time" class="form-control" id="rankmodal-hours" required>
                                                    </div>
                                                    <input type="submit" class="btn bg-custom" value="Save">
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <script>
                                    $('.editRank').click(function(e) {
                                        var rankId = $(this).data("id");
                                        var rankName = $(this).data("name");
                                        var rankHrs = $(this).data("minhrs");

                                        $("#rankmodal-id").val(rankId);
                                        $("#rankmodal-name").val(rankName);
                                        $("#rankmodal-hours").val(rankHrs);
                                        $("#rankmodal-title").text("Edit Rank - " + rankName);

                                        $("#rankmodal").modal("show");
                                    });
                                </script>
                            <?php elseif (Input::get('section') === 'import'): ?>
                                <h3>Import Operations Files</h3>
                                <p>
                                    Here, you can import Flare JSON Files containg routes and aircraft into your database.
                                    Please note when you are importing aircraft, they will all be set to the default rank.<br /><br />

                                    Alternatively, you can import your routes from the phpVMS 
                                    format <a href="admin.php?page=opsmanage&section=phpvms">here</a>.
                                </p>

                                <ul class="nav nav-tabs nav-dark justify-content-center">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="importrouteslink" data-toggle="tab" href="#routes">Import Routes</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="importaircraftlink" data-toggle="tab" href="#aircraft">Import Aircraft</a>
                                    </li>
                                </ul>

                                <div class="tab-content">
                                    <div id="routes" class="tab-pane container-fluid p-3 active">
                                        <h4>Import Routes</h4>
                                        <form action="update.php" method="post" enctype="multipart/form-data">
                                            <input hidden name="action" value="importroutes" />
                                            <div class="custom-file mb-2">
                                                <input required type="file" class="custom-file-input" name="upload" accept=".json" id="importroutes-upload">
                                                <label class="custom-file-label" id="importroutes-upload-label" for="importroutes-upload">Choose File</label>
                                            </div>
                                            <input type="submit" class="btn bg-custom" value="Import" />
                                        </form>
                                    </div>
                                    <div id="aircraft" class="tab-pane container-fluid p-3 fade">
                                        <h4>Import Aircraft</h4>
                                        <form action="update.php" method="post" enctype="multipart/form-data">
                                            <input hidden name="action" value="importaircraft" />
                                            <div class="custom-file mb-2">
                                                <input required type="file" class="custom-file-input" name="upload" accept=".json" id="importaircraft-upload">
                                                <label class="custom-file-label" id="importaircraft-upload-label" for="importaircraft-upload">Choose File</label>
                                            </div>
                                            <input type="submit" class="btn bg-custom" value="Import" />
                                        </form>
                                    </div>
                                </div>

                                <style>
                                    .nav-tabs .nav-link {
                                        color: #000!important;
                                    }
                                </style>
                                <script>
                                    $("#importroutes-upload").on("change", function() {
                                        var fileName = $(this).val().split("\\").pop();
                                        $(this).siblings("#importroutes-upload-label").addClass("selected").html(fileName);
                                    });

                                    $("#importaircraft-upload").on("change", function() {
                                        var fileName = $(this).val().split("\\").pop();
                                        $(this).siblings("#importaircraft-upload-label").addClass("selected").html(fileName);
                                    });
                                </script>
                            <?php elseif (Input::get('section') === 'export'): ?>
                                <h3>Export Operations Files</h3>
                                <p>
                                    Here, you can export your aircraft and routes to Flare JSON files.
                                    These are useful for backups.
                                </p>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <a href="update.php?action=exportroutes" download="routes.json" class="btn bg-custom">Export Routes</a>
                                    </div>
                                    <div class="col-lg-6">
                                        <a href="update.php?action=exportaircraft" download="aircraft.json" class="btn bg-custom">Export Aircraft</a>
                                    </div>
                                </div>
                            <?php elseif (Input::get('section') === 'phpvms'): ?>
                                <h3>phpVMS Importer</h3>
                                <p>
                                    Here, you can import your routes from phpVMS. 
                                </p>
                                <?php if (empty(Input::get('action'))): ?>
                                    <form method="post" enctype="multipart/form-data">
                                        <input hidden name="action" value="phpvms" />
                                        <div class="custom-file mb-2">
                                            <input required type="file" class="custom-file-input" name="routes-upload" accept=".csv" id="routes-upload">
                                            <label class="custom-file-label" id="routes-upload-label" for="routes-upload">Routes File</label>
                                        </div>
                                        <input type="submit" class="btn bg-custom" value="Process" />
                                    </form>
                                    <script>
                                        $("#routes-upload").on("change", function() {
                                            var fileName = $(this).val().split("\\").pop();
                                            $(this).siblings("#routes-upload-label").addClass("selected").html(fileName);
                                        });

                                        $("#aircraft-upload").on("change", function() {
                                            var fileName = $(this).val().split("\\").pop();
                                            $(this).siblings("#aircraft-upload-label").addClass("selected").html(fileName);
                                        });
                                    </script>
                                <?php else: ?>
                                    <p>
                                        So we can import everything correctly, please select the aircraft type and livery for each registration.
                                        These aircraft will be added with the lowest rank if they do not already exist in your VA's database.
                                    </p>
                                    <?php
                                        $file = Input::getFile('routes-upload');
                                        if ($file["error"] == 1 || $file["error"] === true) {
                                            Session::flash('error', 'Upload failed. Maybe your file is too big?');
                                            echo '<script>window.location.href= "admin.php?page=opsmanage&section=phpvms";</script>';
                                            die();
                                        }
                                        $routes = file_get_contents($file["tmp_name"]);
                                        preg_match_all('/.*\n|\r\n/m', $routes, $routelines);

                                        $i = 0;
                                        $valid = false;
                                        $routesArray = [];
                                        foreach ($routelines[0] as $l) {
                                            if ($i == 0) {
                                                $l = trim(preg_replace('/"|\'| /', '', $l));
                                                if ($l != 'code,flightnum,depicao,arricao,route,aircraft,flightlevel,distance,deptime,arrtime,flighttime,notes,price,flighttype,daysofweek,enabled,week1,week2,week3,week4') {
                                                    Session::flash('error', 'Your Routes Import seems to be in the incorrect format');
                                                    echo '<script>window.location.href= "admin.php?page=opsmanage&section=phpvms";</script>';
                                                    die();
                                                } else {
                                                    $valid = true;
                                                }
                                            } elseif ($valid) {
                                                $segments = preg_split('/, ?/', $l);

                                                array_push($routesArray, array(
                                                    "fltnum" => $segments[1],
                                                    "dep" => $segments[2],
                                                    "arr" => $segments[3],
                                                    "duration" => Time::strToSecs(str_replace('.', ':', $segments[10])),
                                                    "aircraftid" => $segments[5]
                                                ));
                                            }
                                            $i++;
                                        }

                                        $routesJson = Json::encode($routesArray);

                                        $allAircraft = Aircraft::fetchAllAircraftFromVANet();
                                        $aircraftOptions = "";
                                        foreach ($allAircraft as $id => $name) {
                                            $aircraftOptions .= '<option value="'.$id.'">'.$name.'</option>';
                                        }

                                        echo '<form action="update.php" method="post">';
                                        echo '<input hidden name="action" value="phpvms" />';
                                        echo "<input hidden name='rJson' value='$routesJson' />";
                                        $j = 0;
                                        $doneAircraft = [];
                                        echo '<table class="w-100 mb-2">';
                                        for ($j=0; $j<$i-1; $j++) {
                                            $r = $routesArray[$j];
                                            if (!in_array($r['aircraftid'], $doneAircraft)) {
                                                echo '<tr class="border-bottom border-top"><td class="align-middle p-2"><b>';
                                                echo $r['aircraftid'];
                                                echo '</b></td><td class="align-middle py-2">';
                                                echo '<input hidden name="rego'.$j.'" value="'.$r["aircraftid"].'" />';
                                                echo '<select required class="form-control mb-2 aircraftSelect" name="aircraft'.$j.'" id="'.$j.'">';
                                                echo '<option value>Aircraft Type</option>';
                                                echo $aircraftOptions;
                                                echo '</select>';
                                                echo '<select required class="form-control" name="livery'.$j.'" id="livery'.$j.'">';
                                                echo '<option value>Select an Aircraft to Get Liveries</option>';
                                                echo '</select>';
                                                echo '</td></tr>';
                                                array_push($doneAircraft, $r['aircraftid']);
                                            }
                                        }
                                        echo '</table>';
                                        echo '<input type="submit" class="btn bg-custom" value="Import Now" />';
                                        echo '</form>';

                                        echo '<script>
                                            $(document).ready(function() {
                                                $(".aircraftSelect").change(function() {
                                                    var id = $(this).attr("id");
                                                    $("#livery" + id).html("<option value>Loading...</option>");
                                                    $.ajax({
                                                        url: "vanet.php",
                                                        type: "POST",
                                                        data: { method: "liveriesforaircraft", data: $(this).val() },
                                                        success: function(html){
                                                            $("#livery" + id).empty();
                                                            $("#livery" + id).append("<option>Select</option>");
                                                            $("#livery" + id).append(html);
                                                        }
                                                    });
                                                });
                                            });
                                        </script>';
                                    ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php elseif (Input::get('page') === 'codeshares'): ?>
                        <?php $ACTIVE_CATEGORY = 'operations-management'; ?>
                        <?php if (!$user->hasPermission('opsmanage')): ?>
                            <div class="alert alert-danger text-center">Whoops! You don't have the necessary permissions to access this.</div>
                        <?php else: ?>
                            <h3>Codeshares Dashboard</h3>
                            <p>
                                Here you can see active codeshare requests from other VAs. 
                                You can also make codeshare requests to share routes with other VAs.
                            </p>
                            <!-- Delete Codeshare Confirmation Modal -->
                            <div class="modal fade" id="confirmShareDelete">
                            <div class="modal-dialog modal-sm">
                                <div class="modal-content">

                                <div class="modal-header">
                                    <h4 class="modal-title">Are You Sure?</h4>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>

                                <div class="modal-body">
                                    Are you sure you want to delete (and hence deny) this Codeshare Request?
                                    <form id="confirmShareDelete" action="update.php" method="post">
                                        <input hidden name="action" value="deletecodeshare" />
                                        <input hidden name="delete" id="confirmShareDelete-id" />
                                        <input type="submit" class="btn btn-danger" value="Delete" />
                                    </form>
                                </div>

                                <div class="modal-footer text-center justify-content-center">
                                    <button type="button" class="btn bg-custom" data-dismiss="modal">Cancel</button>
                                </div>

                                </div>
                            </div>
                            </div>

                            <form id="importcodeshare" action="update.php" method="post">
                                <input hidden name="action" value="importcodeshare" />
                            </form>

                            <h4>Pending Codeshare Requests</h4>
                            <table class="table table-striped">
                                <thead class="bg-custom">
                                    <tr>
                                        <th>Sender</th>
                                        <th class="mobile-hidden">Message</th>
                                        <th># Routes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="codeshares-table">
                                    <tr><td colspan="4">Loading...</td></tr>
                                </tbody>
                            </table>
                            <script>
                                $.post("vanet.php", {
                                    "method": "codeshares"
                                }, function(data, status) {
                                    $("#codeshares-table").html(data);
                                    $(".deleteCodeshare").click(function() {
                                        var id = $(this).data('id');
                                        $("#confirmShareDelete-id").val(id);
                                        $("#confirmShareDelete").modal('show');
                                    })
                                });
                            </script>
                            <hr />
                            <h4>Make Codeshare Request</h4>
                            <form action="update.php" method="post">
                                <input hidden name="action" value="newcodeshare" />
                                <div class="form-group">
                                    <label for="codeshare-recipid">Recipient Codeshare ID</label>
                                    <input required type="number" class="form-control" min="1" name="recipient" id="codeshare-recipid" />
                                </div>
                                <div class="form-group">
                                    <label for="codeshare-routes">Routes</label>
                                    <input required type="text" class="form-control" name="routes" id="codeshare-routes" />
                                    <small class="text-muted">Comma-Separated List of Flight Numbers</small>
                                </div>
                                <div class="form-group">
                                    <label for="codeshare-msg">Optional Message</label>
                                    <input type="text" class="form-control" name="message" id="codeshare-msg" />
                                </div>
                                <input type="submit" class="btn bg-custom" value="Send Request" />
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            </div>
            <script>
                $(document).ready(function() {
                    $(".<?= $ACTIVE_CATEGORY ?>").collapse('show');
                });
            </script>
            <footer class="container-fluid text-center">
                <?php include './includes/footer.php'; ?>
            </footer>
        </div>
    </div>
</body>
</html>