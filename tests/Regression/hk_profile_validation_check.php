<?php
/**
 * Non-destructive check (S-N / Phase N): the relaxed school-profile validation
 * accepts Hong Kong data AND still accepts existing Indian-format data.
 * Mirrors the HK-relevant rules from App\Http\Requests\DetailRequest.
 *
 * Usage: docker compose exec -T app php tests/Regression/hk_profile_validation_check.php
 * Exit 0 = all pass.
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Validator;

$rules = [
    'moto'           => 'required|max:150',
    'landline_no'    => 'nullable|max:20',
    'pincode'        => 'nullable|max:20',
    'affiliation_no' => 'nullable|numeric',
    'affiliated_by'  => 'nullable|max:100',
    'board'          => 'nullable',
];

$cases = [
    'HK (8-digit phone, no pincode/affiliation, bilingual moto)' => [
        'moto' => 'Nurturing Youthful Minds, Embracing Holistic Growth | 言傳身教 希冀成材',
        'landline_no' => '54001530', 'pincode' => '', 'affiliation_no' => '',
        'affiliated_by' => '', 'board' => '',
    ],
    'HK (+852 formatted phone)' => [
        'moto' => '言傳身教 希冀成材',
        'landline_no' => '+852 54001530', 'pincode' => null, 'affiliation_no' => null,
        'affiliated_by' => null, 'board' => null,
    ],
    'Existing India data (10-digit phone, 6-digit pincode, affiliation) still valid' => [
        'moto' => 'Knowledge is power',
        'landline_no' => '0444123456', 'pincode' => '625001', 'affiliation_no' => '12345',
        'affiliated_by' => 'CBSE', 'board' => 'CBSE',
    ],
];

$fail = 0;
foreach ($cases as $label => $data) {
    $v = Validator::make($data, $rules);
    $ok = $v->passes();
    echo ($ok ? '  PASS ' : '  FAIL ') . $label . ($ok ? '' : ' — ' . json_encode($v->errors()->all())) . "\n";
    if (! $ok) { $fail++; }
}

// Negative control: moto over 150 chars must STILL be rejected (validation intact).
$tooLong = Validator::make(['moto' => str_repeat('x', 151)], ['moto' => 'required|max:150']);
$ok = $tooLong->fails();
echo ($ok ? '  PASS ' : '  FAIL ') . "moto > 150 chars is correctly rejected\n";
if (! $ok) { $fail++; }

echo "\n" . ($fail === 0 ? "RESULT: ALL PASSED\n" : "RESULT: {$fail} FAILED\n");
exit($fail === 0 ? 0 : 1);
