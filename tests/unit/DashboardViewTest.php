<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

class DashboardViewTest extends CIUnitTestCase
{
    public function testDashboardRendersDefaultSavingsValuesWhenClientFieldsAreMissing(): void
    {
        ob_start();
        $output = view('client/dashboard', [
            'client' => (object) [
                'numero' => '0331234567',
                'solde'  => 1500,
            ],
        ]);
        $output .= ob_get_clean();

        $this->assertStringContainsString('0,00 Ar', $output);
        $this->assertStringContainsString('0 %', $output);
    }
}
