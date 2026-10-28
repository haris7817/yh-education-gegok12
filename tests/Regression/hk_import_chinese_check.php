<?php
/**
 * Regression test — HK localization of the student Excel import (goodwill iteration).
 *
 * Verifies, with a synthetic in-memory XLSX fixture (no client data):
 *   - Traditional Chinese text (lastname, address) imports byte-intact
 *   - new optional columns school_name / grade / home_phone are stored
 *   - blank country/state/city fall back to the Hong Kong reference rows
 *   - missing new columns do not break the legacy format (second sheet row omits them)
 *   - the student is linked to a parent (no orphan)
 *
 * SAFE: runs against the seeded local dev DB, inserts only synthetic rows with a
 * unique run token, performs NO reset/delete. Re-runnable.
 *
 * Usage (inside the app container):
 *   php tests/Regression/hk_import_chinese_check.php
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\StudentParentLink;

$failures = [];
function check(string $label, bool $ok, string $detail = '') {
    global $failures;
    echo ($ok ? "  PASS " : "  FAIL ") . $label . ($detail ? " — $detail" : "") . "\n";
    if (!$ok) { $failures[] = $label; }
}

$headers = ['firstname','lastname','mobile_no','email','gender','date_of_birth','blood_group','class','section','address','city','state','country','pincode','birth_place','native_place','mother_tongue','caste','sub_caste','aadhar_number','joining_date','admission_number','EMIS_number','roll_number','id_card_number','board_registration_number','mode_of_transport','driver_name','driver_contact_number','siblings','siblings_count','sibling_relation','sibling_name','sibling_date_of_birth','sibling_class','notes','parent_firstname','parent_lastname','parent_mobile_no','parent_alternate_no','parent_email','parent_qualification','parent_occupation','parent_sub_occupation','parent_designation','parent_organization_name','parent_official_address','parent_annual_income','relation','school_name','grade','home_phone'];
function row(array $vals, array $headers): array {
    $r = array_fill_keys($headers, '');
    foreach ($vals as $k => $v) { $r[$k] = $v; }
    return array_values($r);
}

$t  = substr((string) time(), -6);
$sm = fn(int $n) => '93' . $t . str_pad((string)$n, 2, '0', STR_PAD_LEFT);
$pm = fn(int $n) => '94' . $t . str_pad((string)$n, 2, '0', STR_PAD_LEFT);

$chineseLast   = '陳家欣';
$chineseAddr   = '將軍澳景嶺路1號';
$daySchool     = '將軍澳官立小學';

$rows = [$headers];
// 1) HK row: Chinese name/address, new columns, BLANK country/state/city
$rows[] = row([
    'firstname' => 'HKimp' . $t, 'lastname' => $chineseLast,
    'mobile_no' => $sm(1), 'gender' => 'female', 'date_of_birth' => '2016-03-04',
    'class' => 1, 'section' => 'A',
    'address' => $chineseAddr,
    'mother_tongue' => 'Cantonese', 'joining_date' => '2026-10-01',
    'admission_number' => 'HKIMP' . $t, 'roll_number' => '91',
    'siblings' => 'no',
    'parent_firstname' => '張美心', 'parent_mobile_no' => $pm(1), 'relation' => 'mother',
    'school_name' => $daySchool, 'grade' => 'P4', 'home_phone' => '21234567',
], $headers);
// 2) legacy-style row (old columns, Indian location) to prove nothing regressed
$rows[] = row([
    'firstname' => 'Legacy' . $t, 'lastname' => 'T',
    'mobile_no' => $sm(2), 'gender' => 'male', 'date_of_birth' => '2014-01-01',
    'class' => 2, 'section' => 'B',
    'address' => '1 Test Rd', 'city' => 'Madurai', 'state' => 'Tamilnadu', 'country' => 'India',
    'pincode' => '625001', 'mother_tongue' => 'Tamil', 'joining_date' => '2024-06-01',
    'admission_number' => '99' . $t, 'roll_number' => '92', 'siblings' => 'no',
    'parent_firstname' => 'PLeg', 'parent_mobile_no' => $pm(2), 'relation' => 'father',
], $headers);

// Build the XLSX fixture in a temp file
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->fromArray($rows, null, 'A1', true);
$file = sys_get_temp_dir() . '/hk_import_check_' . $t . '.xlsx';
(new Xlsx($spreadsheet))->save($file);

Auth::loginUsingId(User::where('usergroup_id', 3)->value('id'));
Excel::import(new \App\Imports\UsersImport(), $file);
@unlink($file);

echo "HK import regression (token $t)\n";

$hk = User::where('registration_number', 'HKIMP' . $t)->first();
check('HK student imported', !is_null($hk));

if ($hk) {
    $p = Userprofile::where('user_id', $hk->id)->first();
    check('Chinese lastname intact', $p->lastname === $chineseLast, (string) $p->lastname);
    check('Chinese address intact', $p->address === $chineseAddr, (string) $p->address);
    check('school_name stored', $p->school_name === $daySchool, (string) $p->school_name);
    check('grade stored', $p->grade === 'P4', (string) $p->grade);
    check('home_phone stored', $p->alternate_no === '21234567', (string) $p->alternate_no);
    check('country defaulted to Hong Kong', optional($p->country)->name === 'Hong Kong', (string) optional($p->country)->name);
    check('parent linked (no orphan)', StudentParentLink::where('student_id', $hk->id)->exists());
}

$legacy = User::where('mobile_no', $sm(2))->first();
check('legacy row imported', !is_null($legacy));
if ($legacy) {
    $p2 = Userprofile::where('user_id', $legacy->id)->first();
    check('legacy city preserved (Madurai)', optional($p2->city)->name === 'Madurai', (string) optional($p2->city)->name);
    check('missing new columns stored as null', is_null($p2->school_name) && is_null($p2->grade));
    check('legacy parent linked', StudentParentLink::where('student_id', $legacy->id)->exists());
}

echo $failures ? "\nRESULT: FAILURES: " . implode(', ', $failures) . "\n" : "\nRESULT: ALL PASSED\n";
exit($failures ? 1 : 0);
