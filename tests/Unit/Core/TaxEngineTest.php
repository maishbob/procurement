<?php

namespace Tests\Unit\Core;

use Tests\TestCase;
use App\Core\TaxEngine\TaxEngine;

use Illuminate\Foundation\Testing\RefreshDatabase;

class TaxEngineTest extends TestCase
{
    use RefreshDatabase;

    protected TaxEngine $taxEngine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->taxEngine = new TaxEngine();
    }

    /**
     * Test VAT calculation at 16%
     */
    public function test_vat_calculation(): void
    {
        $amount = 10000;
        $vatRate = 16;

        $vat = $this->taxEngine->calculateVAT($amount, $vatRate);
        $this->assertEquals(1600, $vat['vat_amount']);
    }

    /**
     * Test WHT calculation with different rates
     */
    public function test_wht_calculation_rates(): void
    {
        $amount = 50000;

        // Standard rate (2%)
        $wht = $this->taxEngine->calculateWHT($amount, 'standard', 2);
        $this->assertEquals(1000, $wht['wht_amount']);

        // Higher rate (5%)
        $wht = $this->taxEngine->calculateWHT($amount, 'higher', 5);
        $this->assertEquals(2500, $wht['wht_amount']);
    }

    /**
     * Test net amount calculation after VAT and WHT
     */
    public function test_net_amount_after_tax(): void
    {
        $amount = 100000;

        $vat = $this->taxEngine->calculateVAT($amount, 16);
        $grossAmount = $amount + $vat['vat_amount'];

        $wht = $this->taxEngine->calculateWHT($grossAmount, 'standard', 2);
        $netAmount = $grossAmount - $wht['wht_amount'];

        $this->assertEquals(116000, $grossAmount);
        $this->assertEquals(2320, $wht['wht_amount']);
        $this->assertEquals(113680, $netAmount);
    }

    /**
     * Test WHT certificate generation
     */
    public function test_wht_certificate_validation(): void
    {
        $supplier = \App\Models\Supplier::factory()->create();
        $payment = \App\Models\Payment::factory()->create([
            'supplier_id' => $supplier->id,
            'withholding_tax_amount' => 5000,
        ]);

        $certificate = $this->taxEngine->generateWHTCertificate($payment);

        $this->assertNotNull($certificate);
        $this->assertEquals($supplier->kra_pin, $certificate['supplier_kra_pin']);
        $this->assertEquals(5000, $certificate['wht_amount']);
    }

    /**
     * Test KRA PIN validation
     */
    public function test_kra_pin_validation(): void
    {
        // Valid KRA PIN format: P followed by 9 digits and 1 letter
        $validPin = 'P123456789A';
        $this->assertTrue($this->taxEngine->validateKRAPIN($validPin));

        // Invalid formats
        $this->assertFalse($this->taxEngine->validateKRAPIN('123456789A')); // Missing P
        $this->assertFalse($this->taxEngine->validateKRAPIN('P12345678A')); // Wrong digit count
        $this->assertFalse($this->taxEngine->validateKRAPIN('P123456789')); // Missing letter
    }
}
