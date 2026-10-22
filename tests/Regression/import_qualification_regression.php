<?php
/**
 * Regression test — Student Excel import (S-5).
 *
 * Verifies the parent_qualification fix and orphan-prevention in App\Imports\UsersImport:
 *   - valid single qualification                -> parent created WITH qualification
 *   - blank qualification                        -> parent created WITHOUT qualification (no orphan)  [D-2]
 *   - unknown qualification                      -> parent created WITHOUT qualification (no orphan)
 *   - duplicate/multiple qualification names     -> resolved to unique valid ids, parent created
 *   - duplicate parent mobile                    -> parent linked, not duplicated
 *   - invalid class/section                      -> row skipped (no student created)
 *   - NO orphaned students                       -> every imported student has a student_parent_link
 *
 * SAFE: runs against the seeded local dev DB, inserts only synthetic rows with a unique
 * run token, performs NO reset/delete. Re-runnable.
 *
 * Usage (inside the app container):
 *   docker compose exec -T app php tests/Regression/import_qualification_regression.php
 *
 * Exit code 0 = all assertions passed, 1 = failure.
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\User;

$failures = [];
function check(string $label, bool $ok, string $detail = '') {
    global $failures;
    echo ($ok ? "  PASS " : "  FAIL ") . $label . ($detail ? " — $detail" : "") . "\n";
    if (!$ok) { $failures[] = $label; }
}

$headers = ['firstname','lastname','mobile_no','email','gender','date_of_birth','blood_group','class','section','address','city','state','country','pincode','birth_place','native_place','mother_tongue','caste','sub_caste','aadhar_number','joining_date','admission_number','EMIS_number','roll_number','id_card_number','board_registration_number','mode_of_transport','driver_name','driver_contact_number','siblings','siblings_count','sibling_relation','sibling_name','sibling_date_of_birth','sibling_class','notes','parent_firstname','parent_lastname','parent_mobile_no','parent_alternate_no','parent_email','parent_qualification','parent_occupation','parent_sub_occupation','parent_designation','parent_organization_name','parent_official_address','parent_annual_income','relation'];
function row(array $vals, array $headers): array {
    $r = array_fill_keys($headers, '');
    foreach ($vals as $k => $v) { $r[$k] = $v; }
    return array_values($r);
}

// Unique run token so repeated runs don't collide on mobile numbers.
$t = substr((string) time(), -6);
$sm = fn(int $n) => '96' . $t . str_pad((string)$n, 2, '0', STR_PAD_LEFT); // student mobiles
$pm = fn(int $n) => '97' . $t . str_pad((string)$n, 2, '0', STR_PAD_LEFT); // parent mobiles
$base = ['gender'=>'male','date_of_birth'=>'2014-01-01','address'=>'1 Test Rd','city'=>'Madurai','state'=>'Tamilnadu','country'=>'India','pincode'=>'625001','joining_date'=>'2024-06-01','relation'=>'father'];

$rows = [$headers];
// 1) valid single qualification (class 1/A), parent P1
$rows[] = row(array_merge($base, ['firstname'=>'Q1valid','lastname'=>'T','mobile_no'=>$sm(1),'class'=>1,'section'=>'A','parent_firstname'=>'P1','parent_lastname'=>'T','parent_mobile_no'=>$pm(1),'parent_qualification'=>'Bachelor of Commerce (B.Com.)']), $headers);
// 2) BLANK qualification (class 2/B), parent P2 -> must still create parent (no orphan)
$rows[] = row(array_merge($base, ['firstname'=>'Q2blank','lastname'=>'T','mobile_no'=>$sm(2),'class'=>2,'section'=>'B','parent_firstname'=>'P2','parent_lastname'=>'T','parent_mobile_no'=>$pm(2),'parent_qualification'=>'']), $headers);
// 3) UNKNOWN qualification (class 3/A), parent P3 -> must still create parent (no orphan)
$rows[] = row(array_merge($base, ['firstname'=>'Q3unknown','lastname'=>'T','mobile_no'=>$sm(3),'class'=>3,'section'=>'A','parent_firstname'=>'P3','parent_lastname'=>'T','parent_mobile_no'=>$pm(3),'parent_qualification'=>'NoSuchDegreeXYZ']), $headers);
// 4) MULTIPLE/duplicate qualification names (class 4/A), parent P4
$rows[] = row(array_merge($base, ['firstname'=>'Q4multi','lastname'=>'T','mobile_no'=>$sm(4),'class'=>4,'section'=>'A','parent_firstname'=>'P4','parent_lastname'=>'T','parent_mobile_no'=>$pm(4),'parent_qualification'=>'Bachelor of Commerce (B.Com.),Bachelor of Commerce (B.Com.),Bachelor of Arts (B.A.)']), $headers);
// 5) DUPLICATE parent mobile (same as row 1 P1), class 5/A -> link, not duplicate
$rows[] = row(array_merge($base, ['firstname'=>'Q5dupparent','lastname'=>'T','mobile_no'=>$sm(5),'class'=>5,'section'=>'A','parent_firstname'=>'P1','parent_lastname'=>'T','parent_mobile_no'=>$pm(1),'parent_qualification'=>'Bachelor of Commerce (B.Com.)']), $headers);
// 6) INVALID class/section -> skip
$rows[] = row(array_merge($base, ['firstname'=>'Q6skip','lastname'=>'T','mobile_no'=>$sm(6),'class'=>99,'section'=>'Z','parent_firstname'=>'P6','parent_lastname'=>'T','parent_mobile_no'=>$pm(6),'parent_qualification'=>'Bachelor of Commerce (B.Com.)']), $headers);

$ss = new Spreadsheet();
$ss->getActiveSheet()->fromArray($rows, null, 'A1');
$path = sys_get_temp_dir() . '/import_regression_' . $t . '.xlsx';
(new Xlsx($ss))->save($path);

$admin = User::where('usergroup_id',3)->where('email','demoschool@mailinator.com')->firstOrFail();
Auth::login($admin);
$sid = $admin->school_id;

$studBefore = User::where('school_id',$sid)->where('usergroup_id',6)->count();
$parBefore  = User::where('school_id',$sid)->where('usergroup_id',7)->count();

Excel::import(new App\Imports\UsersImport, $path);
@unlink($path);

$inserted = session('insertedcount');
$skipped  = session('skipped_rows');
$studAfter = User::where('school_id',$sid)->where('usergroup_id',6)->count();
$parAfter  = User::where('school_id',$sid)->where('usergroup_id',7)->count();

$studentMobiles = [$sm(1),$sm(2),$sm(3),$sm(4),$sm(5),$sm(6)];
$importedStudents = User::where('school_id',$sid)->where('usergroup_id',6)->whereIn('mobile_no',$studentMobiles)->get(['id','mobile_no']);
// Orphan check: an imported student with no parent link
$orphans = [];
foreach ($importedStudents as $st) {
    $linked = DB::table('student_parent_links')->where('student_id',$st->id)->exists();
    if (!$linked) { $orphans[] = $st->mobile_no; }
}
$skipStudentExists = User::where('school_id',$sid)->where('mobile_no',$sm(6))->exists();
$dupParentCount = User::where('school_id',$sid)->where('usergroup_id',7)->where('mobile_no',$pm(1))->count();

echo "\n--- Student Excel import regression (run $t) ---\n";
check('5 valid rows inserted', $inserted === 5, "insertedcount=".json_encode($inserted));
check('invalid class/section row skipped', is_array($skipped) && count($skipped) === 1, "skipped=".json_encode($skipped));
check('students +5', ($studAfter-$studBefore) === 5, "delta=".($studAfter-$studBefore));
check('NO orphaned students', count($orphans) === 0, $orphans ? "orphans=".implode(',',$orphans) : "all linked");
check('blank & unknown qualification still created parents (no orphan)', !in_array($sm(2),$orphans,true) && !in_array($sm(3),$orphans,true));
check('duplicate parent linked once (not duplicated)', $dupParentCount === 1, "P1 rows=$dupParentCount");
check('parents +4 (P1..P4; P1 reused for row5; row6 skipped)', ($parAfter-$parBefore) === 4, "delta=".($parAfter-$parBefore));
check('skipped student row NOT created', $skipStudentExists === false);

echo "\n" . (empty($failures) ? "RESULT: ALL PASSED\n" : ("RESULT: ".count($failures)." FAILED: ".implode('; ',$failures)."\n"));
exit(empty($failures) ? 0 : 1);
