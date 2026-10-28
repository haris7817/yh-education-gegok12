<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Http\Requests\UserProfileUpdateRequest;
use App\Http\Requests\UserProfileAddRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\StudentParentLink;
use App\Traits\MemberProcess;
use App\Traits\RegisterUser;
use App\Models\StudentAcademic;
use App\Models\Users\StudentUser;
use App\Models\StandardLink;
use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Helpers\SiteHelper;
use App\Traits\LogActivity;
use App\Models\ActivityLog;
use App\Models\Userprofile;
use App\Models\Standard;
use App\Traits\Common;
use App\Models\User;
use App\Models\Group;
use Carbon\Carbon;
use Exception;
use Hash;

/**
 * Class StudentController
 *
 * Handles student management including
 * listing, creation, update, deletion,
 * attendance, academic mapping, and
 * blocked student handling.
 *
 * @package App\Http\Controllers\Admin
 */
class StudentController extends Controller
{
    use RegisterUser; 
    use MemberProcess;
    use LogActivity;
    use Common;
    
    /**
     * Filter and fetch students list.
     *
     * Applies standard-based default filtering
     * and returns filtered student collection.
     *
     * @param Request $request
     * @return mixed
     */
    public function find(Request $request)
    {
        //
        $school_id = Auth::user()->school_id;
        $academic_year = SiteHelper::getAcademicYear($school_id);

        $lowest_standard = Standard::where('school_id',$school_id)->orderBy('order')->first();

        $standard = StandardLink::where([['school_id',$school_id],['academic_year_id',$academic_year->id],['standard_id',$lowest_standard->id]])->first();

        if(count((array)\Request::getQueryString()) == 0)
        {
            $request['standard'] = $standard->id;
        }

        return $this->MemberFilter($request,Auth::user()->school_id,6,'active');
    }
    /**
     * Display student listing page.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $school_id = Auth::user()->school_id;
        $academic_year = SiteHelper::getAcademicYear($school_id);
        $count    = StudentUser::ByRole(6)->where('school_id',$school_id)->where('deleted_at',NULL)->//count();dd($count);
        $alphabet = request('alphabet')?request('alphabet'):'';
        $query    = \Request::getQueryString();
        $standardLink = SiteHelper::getStandardLinkList($school_id);

        $lowest_standard = Standard::where('school_id',$school_id)->orderBy('order')->first();

        if(count((array)\Request::getQueryString()) == 0)
        {
            $standard = StandardLink::where([['school_id',$school_id],['academic_year_id',$academic_year->id],['standard_id',$lowest_standard->id]])->first();
        }
        if(request('date_of_birth') != null)
        {
            $birthday = 'true';
        }
        if(request('standard') != null)
        {
            $selected_standard = request('standard');
        }
        else
        {
            $selected_standard = $standard->id;
        }

        return view('/admin/member/index',[ 'alphabet' => $alphabet , 'query' => $query , 'count' => $count , 'standardLinks' => $standardLink , 'standard' => $standard->id , 'birthday' => $birthday , 'selected_standard' => $selected_standard ]);
    }

    /**
     * Show student creation form.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      //
      $count    = StudentUser::where('school_id',Auth::user()->school_id)->where('usergroup_id',6)->count();
      $subscription = Subscription::where('school_id',Auth::user()->school_id)->first();

      return view('/admin/member/create',['count'=>$count , 'subscription'=>$subscription]);
    }

    /**
     * Load supporting data for student creation.
     *
     * @return array
     */
    // public function member()
    // {
    //   $academic_year  = SiteHelper::getAcademicYear(Auth::user()->school_id);

    //   $array = [];
        
    //   $array['academic_year_id']  =   $academic_year->id;
    //   $array['countrylist']       =   SiteHelper::getCountries();
    //   $array['statelist']         =   SiteHelper::getStates();
    //   $array['citylist']          =   SiteHelper::getCities();
    //   $array['standardLinklist']  =   SiteHelper::getStandardLinkList(Auth::user()->school_id);
    //   $array['blood_groups']      =   SiteHelper::getBloodGroups();
    //   $array['castelist']         =   SiteHelper::getCasteList();
    //   $array['transportlist']     =   SiteHelper::getTransportList();
    //   $array['date_of_birth']     =   date('Y-m-d',strtotime('-4 years',strtotime(date('Y'))));
    //   $array['joining_date']      =   date('Y-m-d');
        
    //   return $array;
    // }
    public function member()
    {
        $academic_year = SiteHelper::getAcademicYear(Auth::user()->school_id);

        $standardlinks = collect(
            SiteHelper::getStandardLinkList(Auth::user()->school_id)
        )->map(function ($item) {

            $item['groups'] = Group::where('standardLink_id', $item['id'])
                ->select('id', 'group_name')
                ->get();

            return $item;
        });

        $array = [];

        $array['academic_year_id'] = $academic_year->id;
        $array['countrylist']      = SiteHelper::getCountries();
        $array['statelist']        = SiteHelper::getStates();
        $array['citylist']         = SiteHelper::getCities();

        // updated
        $array['standardLinklist'] = $standardlinks;

        $array['blood_groups']     = SiteHelper::getBloodGroups();
        $array['castelist']        = SiteHelper::getCasteList();
        $array['transportlist']    = SiteHelper::getTransportList();
        $array['date_of_birth']    = date('Y-m-d', strtotime('-4 years', strtotime(date('Y'))));
        $array['joining_date']     = date('Y-m-d');

        return $array;
    }

    /**
     * Validate student creation request.
     *
     * @param UserProfileAddRequest $request
     * @return void
     */
    public function validationUser(UserProfileAddRequest $request)
    {
      //
    }

    /**
     * Store newly created student.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
      //
      try
      {
        $school_id = Auth::user()->school_id;

        $academic_year = SiteHelper::getAcademicYear($school_id);

        $file = $request->file('avatar');
        if($file)
        {
          $folder=Auth::user()->school->slug.'/student/avatar';
          $path = $this->uploadFile($folder,$file); 
        }
        else
        {
          $path = '';
        }

        $user = $this->CreateUser($request , $school_id , $academic_year->id , $path , 6);

        // HK localization: optional guardian / emergency contact entered on the form
        $this->syncGuardian($user , $request , $school_id);

        $mes = trans('messages.add_success_msg',['module' => 'Student']);

        $ip= $this->getRequestIP();
        $this->doActivityLog(
          $user,
          Auth::user(),
          ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
          LOGNAME_ADD_STUDENT,
          $mes
        ); 

        return redirect()->back()->with('successmessage',$mes);
      }
      catch(Exception $e)
      {
        //dd($e->getMessage());
      } 
    }

  

    /**
     * Fetch student data for edit API.
     *
     * @param string $name
     * @return array
     */
    public function editStudent($name)
    {
      //
      $user             = StudentUser::where('name',$name)->first();
      $userprofile      = Userprofile::where('user_id',$user->id)->first();
      $studentAcademic  = $user->studentAcademicLatest;
        
      $array = [];

      $array['firstname']                 = $userprofile->firstname;
      $array['lastname']                  = $userprofile->lastname;
      $array['date_of_birth']             = date('Y-m-d',strtotime($userprofile->date_of_birth));
      $array['gender']                    = $userprofile->gender;
      $array['blood_group']               = $userprofile->blood_group;
      $array['aadhar_number']             = $userprofile->aadhar_number==NULL ? '':$userprofile->aadhar_number;
      $array['city_id']                   = $userprofile->city_id;
      $array['state_id']                  = $userprofile->state_id;
      $array['country_id']                = $userprofile->country_id;
      $array['pincode']                   = $userprofile->pincode==NULL ? '':$userprofile->pincode;
      $array['birth_place']               = $userprofile->birth_place;
      $array['native_place']              = $userprofile->native_place;
      $array['mother_tongue']             = $userprofile->mother_tongue;
      $array['caste']                     = $userprofile->caste;
      $array['sub_caste']                 = $userprofile->sub_caste;
      $array['avatar']                    = $userprofile->AvatarPath;
      $array['notes']                     = $userprofile->notes;
      $array['registration_number']       = $user->registration_number==NULL ? $userprofile->registration_number:$user->registration_number;
      $array['EMIS_number']               = $userprofile->EMIS_number==NULL ? '':$userprofile->EMIS_number;
      $array['joining_date']              = $userprofile->joining_date==NULL ? '':date('Y-m-d',strtotime($userprofile->joining_date));

      // HK localization fields
      $array['address']                   = $userprofile->address==NULL ? '':$userprofile->address;
      $array['alternate_no']              = $userprofile->alternate_no==NULL ? '':$userprofile->alternate_no;
      $array['school_name']               = $userprofile->school_name==NULL ? '':$userprofile->school_name;
      $array['grade']                     = $userprofile->grade==NULL ? '':$userprofile->grade;

      // Guardian / emergency contact (first linked parent, if any)
      $guardian = $user->parents()->first();
      if($guardian && $guardian->userParent)
      {
        $guardianProfile = Userprofile::where('user_id',$guardian->userParent->id)->first();
        $parentProfile   = \App\Models\ParentProfile::where('user_id',$guardian->userParent->id)->first();
        $array['guardian_name']     = trim(($guardianProfile->firstname ?? '').' '.($guardianProfile->lastname ?? ''));
        $array['guardian_relation'] = $parentProfile->relation ?? '';
        $array['guardian_mobile']   = $guardian->userParent->mobile_no ?? '';
      }
      else
      {
        $array['guardian_name']     = '';
        $array['guardian_relation'] = '';
        $array['guardian_mobile']   = '';
      }

      $array['standardLink_id']           = $studentAcademic->standardLink_id;
      $array['roll_number']               = $studentAcademic->roll_number==NULL ? '':$studentAcademic->roll_number;
      $array['id_card_number']            = $studentAcademic->id_card_number==NULL ? '':$studentAcademic->id_card_number;
      $array['board_registration_number'] = $studentAcademic->board_registration_number==NULL ? '':$studentAcademic->board_registration_number;
      $array['mode_of_transport']         = $studentAcademic->mode_of_transport;
      $array['driver_name']               = $studentAcademic->transport_details['driver_name'] ?? '';
      $array['driver_contact_number']     = $studentAcademic->transport_details['driver_contact_number'] ?? '';
      $array['siblings']                  = $studentAcademic->siblings;
      $array['siblings_count']            = $studentAcademic->siblings_count;

      for($i = 0 ; $i < $studentAcademic->siblings_count ; $i++)
      {
        $array['sibling_details'][$i]['sibling_relation']       = $studentAcademic->sibling_details[$i]['sibling_relation'];
        $array['sibling_details'][$i]['sibling_name']           = $studentAcademic->sibling_details[$i]['sibling_name'];
        // $array['sibling_details'][$i]['sibling_date_of_birth']  = date('Y-m-d',strtotime($studentAcademic->sibling_details[$i]['sibling_date_of_birth']));
        $dateValue = $studentAcademic->sibling_details[$i]['sibling_date_of_birth'];

        $array['sibling_details'][$i]['sibling_date_of_birth'] =
            \Carbon\Carbon::parse(is_array($dateValue) ? $dateValue['date'] : $dateValue)
                ->format('Y-m-d');
        
        $array['sibling_details'][$i]['sibling_standard']       = $studentAcademic->sibling_details[$i]['sibling_standard'];
      }

      $array['countrylist']       =   SiteHelper::getCountries();
      $array['statelist']         =   SiteHelper::getStates();
      $array['citylist']          =   SiteHelper::getCities();
      $array['standardLinklist']  =   SiteHelper::getStandardLinkList(Auth::user()->school_id);
      $array['blood_groups']      =   SiteHelper::getBloodGroups();
      $array['castelist']         =   SiteHelper::getCasteList();
      $array['transportlist']     =   SiteHelper::getTransportList();
      $array['today']             =   date('Y-m-d');

      return $array;
    }

    /**
     * Show student edit form.
     *
     * @param string $name
     * @return \Illuminate\Http\Response
     */
    public function edit($name)
    {
      //
      $user = StudentUser::where('name',$name)->first();
      $userprofile = Userprofile::where('user_id',$user->id)->first();
      if(Gate::allows('member',$user))
      {
        return view('/admin/member/edit',['user' => $user , 'userprofile' => $userprofile ]);
      }
      else
      {
        abort(403);
      } 
    }

    /**
     * Validate student update request.
     *
     * @param UserProfileUpdateRequest $request
     * @param string $name
     * @return void
     */
    public function editValidationUser(UserProfileUpdateRequest $request,$name)
    {
      //
    }

    /**
     * Update student details.
     *
     * @param Request $request
     * @param string $name
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,$name)
    {
      //
      try
      {
        $user = StudentUser::where('name',$name)->first();

        $userprofile = Userprofile::where('user_id',$user->id)->first();

        $school_id = Auth::user()->school_id;

        $academic_year = SiteHelper::getAcademicYear($school_id);
            
        if($request->hasFile('avatar'))
        { 
          $file = $request->file('avatar');
          $folder=Auth::user()->school->slug.'/member/avatar';
          $path = $this->uploadFile($folder,$file); 
        }
        else
        {
          $path= $userprofile->avatar;
        }

        $userprofile = $this->UpdateUser($request , $school_id , $academic_year->id ,$user->id , $path);

        // HK localization: optional guardian / emergency contact entered on the form
        $this->syncGuardian($user , $request , $school_id);

        $message=trans('messages.update_success_msg',['module' => 'Student']);

        $ip= $this->getRequestIP();
        $this->doActivityLog(
          $userprofile,
          Auth::user(),
          ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
          LOGNAME_EDIT_STUDENT,
          $message
        ); 
        \Session::put('successmessage',$message);
        return redirect()->back();
      }
      catch(Exception $e)
      {
        //dd($e->getMessage());
      } 
    }
    
    /**
     * Create, link or update a guardian / emergency contact for a student.
     *
     * HK localization (YH Education): the simplified student form carries three
     * optional fields — guardian_name, guardian_relation, guardian_mobile.
     * Behaviour mirrors the Excel importer: an existing parent account with the
     * same mobile number is linked (never duplicated); otherwise the student's
     * already-linked parent is updated; otherwise a new parent account is
     * created. Parent login/API accounts are preserved — nothing is replaced
     * by a plain-text field, and students without a guardian are untouched.
     *
     * @param \App\Models\User|null $student
     * @param \Illuminate\Http\Request $request
     * @param int $school_id
     * @return void
     */
    private function syncGuardian($student , $request , $school_id)
    {
        if( is_null($student) || !$request->filled('guardian_mobile') || !$request->filled('guardian_name') )
        {
            return;
        }

        try
        {
            $names     = preg_split('/\s+/', trim($request->guardian_name), 2);
            $firstname = $names[0];
            $lastname  = $names[1] ?? null;
            $relation  = $request->filled('guardian_relation') ? $request->guardian_relation : 'guardian';

            // 1. An existing parent account with this mobile number → link it.
            $parent = User::where([
                ['school_id',$school_id],
                ['usergroup_id',7],
                ['mobile_no',$request->guardian_mobile],
            ])->first();

            // 2. Otherwise, the student's already-linked parent → update it.
            if( is_null($parent) )
            {
                $link = StudentParentLink::where([
                    ['school_id',$school_id],
                    ['student_id',$student->id],
                ])->first();

                if( $link )
                {
                    $candidate = User::where('id',$link->parent_id)->first();

                    // Only take over the mobile number if no other account holds it.
                    $taken = $candidate ? User::where('mobile_no',$request->guardian_mobile)
                        ->where('id','!=',$candidate->id)->exists() : true;

                    if( $candidate && !$taken )
                    {
                        $parent = $candidate;
                        $parent->mobile_no = $request->guardian_mobile;
                        $parent->save();
                    }
                }
            }

            // 3. Otherwise create a new parent account (same shape as the importer).
            if( is_null($parent) )
            {
                $parent = new User;
                $parent->school_id    = $school_id;
                $parent->usergroup_id = 7;
                $parent->mobile_no    = $request->guardian_mobile;
                $parent->password     = bcrypt(\Illuminate\Support\Str::random(16));
                $parent->email_verification_code = \Illuminate\Support\Str::random(40);
                $parent->save();
            }

            // Keep the guardian's displayed details current.
            $profile = Userprofile::firstOrNew(['user_id' => $parent->id]);
            $profile->school_id    = $school_id;
            $profile->usergroup_id = 7;
            $profile->firstname    = $firstname;
            $profile->lastname     = $lastname;
            if( !$profile->exists || is_null($profile->status) )
            {
                $profile->status = 'active';
            }
            $profile->save();

            $parentProfile = \App\Models\ParentProfile::firstOrNew(['user_id' => $parent->id]);
            $parentProfile->school_id = $school_id;
            $parentProfile->relation  = $relation;
            $parentProfile->save();

            // Ensure the student ↔ parent link exists exactly once.
            StudentParentLink::firstOrCreate(
                [
                    'school_id'  => $school_id,
                    'student_id' => $student->id,
                    'parent_id'  => $parent->id,
                ],
                [ 'status' => 1 ]
            );
        }
        catch(Exception $e)
        {
            \Log::info('syncGuardian: '.$e->getMessage());
        }
    }

    /**
     * Delete student and related records.
     *
     * @param string $name
     * @return \Illuminate\Http\Response
     */
    public function destroy($name)
    {
        try
      {
        $user = StudentUser::with('userprofile')->where('name',$name)->first();

         $studentacademic = StudentAcademic::where('user_id',$user->id);
       if($studentacademic!=null){
         $studentacademic->delete();
         }
         $studentparentlink = StudentParentLink::where('student_id',$user->id);
         if($studentparentlink!=null){
         $studentparentlink->delete();
         }
         $userprofile = Userprofile::where('user_id',$user->id);
         $userprofile->delete();
         $user->delete();


        $message=trans('messages.delete_success_msg',['module' => 'Student']);

        $ip= $this->getRequestIP();
        $this->doActivityLog(
          $user,
          Auth::user(),
          ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
          LOGNAME_DELETE_STUDENT,
          $message
        ); 
        \Session::put('successmessage',$message);
        return redirect('/admin/students');
      }
      catch(Exception $e)
      {
        //dd($e->getMessage());
      } 
    }
    
    /**
     * Display blocked students list.
     *
     * @return \Illuminate\Http\Response
     */
    public function blockedstudents()
    {
        $school_id = Auth::user()->school_id;
        $academic_year = SiteHelper::getAcademicYear($school_id);
        $count    = StudentUser::ByRole(6)->where([['school_id',$school_id],['status','inactive']])->where('deleted_at',NULL)->count();
        $alphabet = request('alphabet')?request('alphabet'):'';
        $query    = \Request::getQueryString();
        $standardLink = SiteHelper::getStandardLinkList($school_id);

        $lowest_standard = Standard::where('school_id',$school_id)->orderBy('order')->first();

        if(count(\Request::getQueryString()) == 0)
        {
            $standard = StandardLink::where([['school_id',$school_id],['academic_year_id',$academic_year->id]])->first();
        }
        if(request('date_of_birth') != null)
        {
            $birthday = 'true';
        }
        if(request('standard') != null)
        {
            $selected_standard = request('standard');
        }
        else
        {
            $selected_standard = $standard->id;
        }

        return view('/admin/member/blockedstudents',[ 'alphabet' => $alphabet , 'query' => $query , 'count' => $count , 'standardLinks' => $standardLink , 'standard' => $standard->id , 'birthday' => $birthday , 'selected_standard' => $selected_standard ]);
    }
}
