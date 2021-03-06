<?php
/*
Flare, a fully featured and easy to use crew centre, designed for Infinite Flight.
Copyright (C) 2020  Lucas Rebato

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

class UsersController extends Controller
{
    public function get()
    {
        $user = new User;
        $this->authenticate($user, true, 'usermanage');
        $data = new stdClass;
        $data->user = $user;
        $data->va_name = Config::get('va/name');
        $data->is_gold = VANet::isGold();
        $data->users = $user->getAllUsers();
        $this->render('admin/users', $data);
    }

    public function post()
    {
        $user = new User;
        $this->authenticate($user, true, 'usermanage');
        switch (Input::get('action')) {
            case 'deluser':
                $this->delete();
            case 'edituser':
                $this->edit();
            case 'announce':
                $this->announce();
            default:
                $this->get();
        }
    }

    private function delete()
    {
        $user = new User;
        try {
            $user->update(array(
                'status' => 2
            ), Input::get('id'));
            Session::flash('success', 'User deleted successfully!');
        } catch (Exception $e) {
            Session::flash('error', 'There was an Error Deleting the User.');
        } finally {
            $this->redirect('/admin/users');
        }
    }

    private function edit()
    {
        $user = new User;
        $isAdmin = $user->hasPermission('admin', Input::get('id'));
        if (!$isAdmin && Input::get('admin') == 1) {
            Permissions::give(Input::get('id'), 'admin');
        } elseif ($isAdmin && Input::get('admin') == 0) {
            Permissions::revokeAll(Input::get('id'));
        }

        $statuses = [
            "Pending" => 0,
            "Active" => 1,
            "Inactive" => 2,
            "Declined" => 3,
        ];

        $user->update([
            'callsign' => Input::get('callsign'),
            'name' => Input::get('name'),
            'email' => Input::get('email'),
            'ifc' => Input::get('ifc'),
            'transhours' => Time::strToSecs(Input::get('transhours')),
            'transflights' => Input::get('transflights'),
            'status' => $statuses[Input::get('status')]
        ], Input::get('id'));
        Session::flash('success', 'User Edited Successfully');
        $this->redirect('/admin/users');
    }

    private function announce()
    {
        $title = escape(Input::get('title'));
        $content = escape(Input::get('content'));
        Notifications::notify(0, "fa-bullhorn", $title, $content);
        Session::flash('success', 'Announcement Created');
        $this->redirect('/admin/users');
    }

    public function get_pending()
    {
        $user = new User;
        $this->authenticate($user, true, 'recruitment');
        $data = new stdClass;
        $data->user = $user;
        $data->va_name = Config::get('va/name');
        $data->is_gold = VANet::isGold();
        $data->users = $user->getAllPendingUsers();
        $this->render('admin/recruitment', $data);
    }

    public function post_pending()
    {
        $user = new User;
        $this->authenticate($user, true, 'recruitment');
        switch (Input::get('action')) {
            case 'acceptapplication':
                $this->accept();
            case 'declineapplication':
                $this->decline();
            default:
                $this->get_pending();
        }
    }

    private function accept()
    {
        $user = new User;
        try {
            $user->update(array(
                'status' => 1
            ), Input::get('accept'));
        } catch (Exception $e) {
            Session::flash('error', 'There was an Error Accepting the Application.');
            $this->redirect('/admin/users/pending');
        }

        Events::trigger('user/accepted', [Input::get('accept')]);

        Cache::delete('badge_recruitment');
        Session::flash('success', 'Application Accepted Successfully!');
        $this->redirect('/admin/users/pending');
    }

    private function decline()
    {
        $user = new User;
        try {
            $user->update(array(
                'status' => 3
            ), Input::get('id'));
        } catch (Exception $e) {
            Session::flash('error', 'There was an error Declining the Application.');
            $this->redirect('/admin/users/pending');
        }

        Events::trigger('user/declined', ['id' => Input::get('id'), 'reason' => Input::get('declinereason')]);

        Cache::delete('badge_recruitment');
        Session::flash('success', 'Application Declined Successfully');
        $this->redirect('/admin/users/pending');
    }

    public function get_staff()
    {
        $user = new User;
        $this->authenticate($user, true, 'staffmanage');
        $data = new stdClass;
        $data->user = $user;
        $data->va_name = Config::get('va/name');
        $data->is_gold = VANet::isGold();
        $data->staff = $user->getAllStaff();
        $this->render('admin/staff', $data);
    }

    public function post_staff()
    {
        $user = new User;
        $this->authenticate($user, true, 'staffmanage');
        $myperms = Permissions::forUser(Input::get('id'));
        $permissions = array_keys(Permissions::getAll());
        foreach ($permissions as $permission) {
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
            $this->redirect('/admin/users/staff');
        }
        Session::flash('success', 'Staff Member Edited Successfully!');
        $this->redirect('/admin/users/staff');
    }
}
