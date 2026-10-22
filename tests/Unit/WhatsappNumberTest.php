<?php

namespace Tests\Unit;

use App\Helpers\SiteHelper;
use PHPUnit\Framework\TestCase;

/**
 * Regression tests for SiteHelper::whatsappNumber() (S-7).
 * Pure function — no database required. Default country code = 852 (Hong Kong).
 */
class WhatsappNumberTest extends TestCase
{
    public function test_local_8_digit_number_gets_default_hk_code(): void
    {
        $this->assertSame('85212345678', SiteHelper::whatsappNumber('12345678'));
    }

    public function test_local_number_with_leading_trunk_zero(): void
    {
        $this->assertSame('85212345678', SiteHelper::whatsappNumber('012345678'));
    }

    public function test_plus_international_is_not_double_prefixed(): void
    {
        $this->assertSame('85212345678', SiteHelper::whatsappNumber('+852 1234 5678'));
    }

    public function test_double_zero_international_prefix(): void
    {
        $this->assertSame('85212345678', SiteHelper::whatsappNumber('0085212345678'));
    }

    public function test_number_already_beginning_with_country_code(): void
    {
        $this->assertSame('85212345678', SiteHelper::whatsappNumber('85212345678'));
    }

    public function test_custom_country_code_override(): void
    {
        $this->assertSame('4412345678', SiteHelper::whatsappNumber('12345678', '44'));
    }

    public function test_empty_or_null_returns_null(): void
    {
        $this->assertNull(SiteHelper::whatsappNumber(''));
        $this->assertNull(SiteHelper::whatsappNumber(null));
        $this->assertNull(SiteHelper::whatsappNumber('   '));
    }

    public function test_too_short_returns_null(): void
    {
        $this->assertNull(SiteHelper::whatsappNumber('123'));
    }

    public function test_non_numeric_junk_returns_null(): void
    {
        $this->assertNull(SiteHelper::whatsappNumber('N/A'));
    }
}
