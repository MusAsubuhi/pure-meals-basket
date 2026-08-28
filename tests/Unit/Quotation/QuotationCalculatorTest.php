<?php

namespace Tests\Unit\Quotation;

use App\Services\Quotation\QuotationCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class QuotationCalculatorTest extends TestCase
{
    private QuotationCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new QuotationCalculator;
    }

    /** @test */
    public function zero_subtotal_and_zero_discount_gives_zero_total(): void
    {
        $result = $this->calculator->calculate(0, 0);

        $this->assertSame(0.0, $result->subtotal);
        $this->assertSame(0.0, $result->discount);
        $this->assertSame(0.0, $result->total);
    }

    /** @test */
    public function simple_subtotal_without_discount(): void
    {
        $result = $this->calculator->calculate(100000, 0);

        $this->assertSame(100000.0, $result->subtotal);
        $this->assertSame(0.0, $result->discount);
        $this->assertSame(100000.0, $result->total);
    }

    /** @test */
    public function discount_is_subtracted_from_subtotal(): void
    {
        $result = $this->calculator->calculate(100000, 5000);

        $this->assertSame(100000.0, $result->subtotal);
        $this->assertSame(5000.0, $result->discount);
        $this->assertSame(95000.0, $result->total);
    }

    /** @test */
    public function discount_equal_to_subtotal_gives_zero_total(): void
    {
        $result = $this->calculator->calculate(50000, 50000);

        $this->assertSame(50000.0, $result->subtotal);
        $this->assertSame(50000.0, $result->discount);
        $this->assertSame(0.0, $result->total);
    }

    /** @test */
    public function negative_subtotal_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->calculator->calculate(-1000, 0);
    }

    /** @test */
    public function negative_discount_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->calculator->calculate(100000, -5000);
    }

    /** @test */
    public function discount_exceeding_subtotal_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->calculator->calculate(5000, 10000);
    }
}
