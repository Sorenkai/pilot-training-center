<?php

namespace App\Http\Controllers;

use anlutro\LaravelSettings\Facade as Setting;
use App\Helpers\Vatsim;
use App\Models\Area;
use App\Models\Group;
use App\Models\PilotTrainingReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Controller to handle user views
 */
class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index()
    {
        $this->authorize('index', \Auth::user());

        $users = [];

        $apiUsers = [];
        $users = User::with(['pilotTrainings'])->get();

        return view('user.index', compact('users'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function indexOther()
    {
        $this->authorize('index', \Auth::user());

        $users = User::with('endorsements')->get();

        return view('user.other', compact('users'));
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View|void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(User $user)
    {
        $this->authorize('view', $user);

        $groups = Group::all();
        $areas = Area::all();

        if ($user == null) {
            return abort(404);
        }

        $trainings = $user->pilotTrainings;
        $statuses = PilotTrainingController::$statuses;

        $exams = $user->exams;

        return view('user.show', compact('user', 'groups', 'areas', 'trainings', 'statuses', 'exams'));
    }

    /**
     * AJAX: Search for the user by name or ID
     *
     * @return array
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function search(Request $request)
    {
        $output = [];

        $query = $request->get('query');

        if (strlen($query) >= 2) {
            $data = User::query()
                ->select(['id', 'first_name', 'last_name'])
                ->where(DB::raw('LOWER(id)'), 'like', '%' . strtolower($query) . '%')
                ->orWhere(DB::raw('LOWER(CONCAT(first_name, " ", last_name))'), 'like', '%' . strtolower($query) . '%')
                ->orderByDesc('last_login')
                ->get();

            if ($data->count() <= 0) {
                return;
            }

            $authUser = Auth::user();

            $count = 0;
            foreach ($data as $user) {
                if ($count >= 10) {
                    break;
                }

                if ($authUser->can('view', $user)) {
                    $output[] = ['id' => $user->id, 'name' => $user->name];
                    $count++;
                }
            }

            return json_encode($output);
        }
    }

    /**
     * AJAX: Return ATC hours from VATSIM for user
     *
     * @return array
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function fetchVatsimHours(Request $request)
    {
        $cid = $request['cid'];

        $vatsimStats = [];
        try {
            $client = new \GuzzleHttp\Client();
            $res = $client->request('GET', 'https://api.vatsim.net/v2/members/' . $cid . '/stats');
            if ($res->getStatusCode() == 200) {
                $vatsimStats = json_decode($res->getBody(), false);
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return response()->json(['data' => null], 404);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return response()->json(['data' => null], 404);
        }

        return response()->json(['data' => $vatsimStats], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);
        $permissions = [];
        // Generate a list of possible validations
        foreach (Area::all() as $area) {
            foreach (Group::all() as $group) {
                // Don't list or allow admin rank to be set through this interface
                if ($group->id == 1) {
                    continue;
                }

                // Only process ranks the user is allowed to change
                if (! \Illuminate\Support\Facades\Gate::inspect('updateGroup', [$user, $group, $area])->allowed()) {
                    continue;
                }

                $key = $area->id . '_' . $group->name;
                $permissions[$key] = '';
            }
        }
        $permissions = array_combine(
            array_map(fn ($key) => str_replace(' ', '_', $key), array_keys($permissions)),
            $permissions
        );

        // Valiate and allow these fields, then loop through permissions to set the final data set
        $data = $request->validate($permissions);

        foreach ($permissions as $key => $value) {
            isset($data[$key]) ? $permissions[$key] = true : $permissions[$key] = false;
        }

        // Check and update the permissions
        foreach ($permissions as $key => $value) {
            $str = explode('_', $key, 2);
            // Replace the Group Name underscores with spaces so it can find the group id properly
            if (isset($str[1])) {
                $str[1] = str_replace('_', ' ', $str[1]);
            }

            $area = Area::where('id', $str[0])->get()->first();
            $group = Group::where('name', $str[1])->get()->first();

            // Check if permission is not set, and set it or other way around.
            if ($user->groups()->where('area_id', $area->id)->where('group_id', $group->id)->get()->count() == 0) {
                if ($value == true) {
                    $this->authorize('updateGroup', [$user, $group, $area]);

                    // Attach the new permission
                    $user->groups()->attach($group, ['area_id' => $area->id, 'inserted_by' => Auth::id()]);
                }
            } else {
                if ($value == false) {
                    $this->authorize('updateGroup', [$user, $group, $area]);

                    // Detach the permission
                    $user->groups()->wherePivot('area_id', $area->id)->wherePivot('group_id', $group->id)->detach();
                }
            }

        }

        return redirect(route('user.show', $user))->with('success', 'User access settings successfully updated.');
    }

    /**
     * Display a listing of user's settings
     *
     * @return \Illuminate\Http\Response
     */
    public function settings()
    {
        $user = Auth::user();

        return view('usersettings', compact('user'));
    }

    /**
     * Update the user's settings to storage
     *
     * @return \Illuminate\Http\Response
     */
    public function settings_update(Request $request, User $user)
    {
        $user = Auth::user();

        $data = $request->validate([
            'setting_notify_newreport' => '',
            'setting_notify_newreq' => '',
            'setting_notify_closedreq' => '',
            'setting_notify_newexamreport' => '',
            'setting_notify_tasks' => '',
            'setting_workmail_address' => 'nullable|email|max:64|regex:/(.*)' . Setting::get('linkDomain') . '$/i',
        ]);

        isset($data['setting_notify_newreport']) ? $setting_notify_newreport = true : $setting_notify_newreport = false;
        isset($data['setting_notify_newreq']) ? $setting_notify_newreq = true : $setting_notify_newreq = false;
        isset($data['setting_notify_closedreq']) ? $setting_notify_closedreq = true : $setting_notify_closedreq = false;
        isset($data['setting_notify_newexamreport']) ? $setting_notify_newexamreport = true : $setting_notify_newexamreport = false;
        isset($data['setting_notify_tasks']) ? $setting_notify_tasks = true : $setting_notify_tasks = false;

        $user->setting_notify_newreport = $setting_notify_newreport;
        $user->setting_notify_newreq = $setting_notify_newreq;
        $user->setting_notify_closedreq = $setting_notify_closedreq;
        $user->setting_notify_newexamreport = $setting_notify_newexamreport;
        $user->setting_notify_tasks = $setting_notify_tasks;

        if (! $user->setting_workmail_address && isset($data['setting_workmail_address'])) {
            $user->setting_workmail_address = $data['setting_workmail_address'];
            $user->setting_workmail_expire = Carbon::now()->addDays(60);
        } elseif ($user->setting_workmail_address && ! isset($data['setting_workmail_address'])) {
            $user->setting_workmail_address = null;
            $user->setting_workmail_expire = null;
        }

        $user->save();

        return redirect()->intended(route('user.settings'))->withSuccess('Settings successfully changed');
    }

    /**
     * Display a listing of user's settings
     *
     * @return \Illuminate\Http\Response
     */
    public function reports(Request $request, User $user)
    {
        $this->authorize('viewReports', $user);

        $reports = PilotTrainingReport::where('written_by_id', $user->id)->with('lesson')->get();

        return view('user.reports', compact('user', 'reports'));
    }

    /**
     * Renew 30 days on the workmail address
     *
     * @return \Illuminate\Http\Response
     */
    public function extendWorkmail()
    {
        $user = Auth::user();

        if (Carbon::parse($user->setting_workmail_expire)->diffInDays(Carbon::now(), false) > -7) {
            $user->setting_workmail_expire = Carbon::now()->addDays(60);
            $user->save();

            return redirect()->intended(route('user.settings'))->withSuccess('Workmail successfully extended');
        } else {
            return redirect()->intended(route('user.settings'))->withErrors('Workmail is not due to expire');
        }
    }

    /**
     * Fetch users from VATSIM Core API
     *
     * @return \Illuminate\Http\Response|bool
     */
    private function fetchUsersFromVatsimCoreApi()
    {
        $url = sprintf('https://api.vatsim.net/v2/orgs/subdivision/%s', config('app.owner_code'));
        $headers = [
            'X-API-Key' => config('vatsim.core_api_token'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $users = [];
        $usersCount = 0;

        $limit = 1000;
        $count = -1;

        do {
            $response = Http::withHeaders($headers)->get(sprintf('%s?include_inactive=1&limit=%s&offset=%s', $url, $limit, $usersCount));

            if (! $response->successful()) {
                return false;
            }

            $jsonResponse = $response->json();

            if ($count == -1) {
                $count = $jsonResponse['count'];
            }

            $users = array_merge($users, $jsonResponse['items']);
            $usersCount = count($users);
        } while ($usersCount < $count);

        return $users;
    }
}
