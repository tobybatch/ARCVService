<?php

namespace Tests\Unit\Controllers\Api;

use App\Market;
use App\Sponsor;
use Illuminate\Http\Request;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Voucher;
use App\Trader;
use App\User;
use App\Http\Controllers\API\TraderController;
use Auth;
use Carbon\Carbon;

class TraderControllerTest extends TestCase
{
    use DatabaseMigrations;

    protected $traders;
    protected $vouchers;
    protected $user;

    protected function setUp()
    {
        parent::setUp();
        $this->traders = factory(Trader::class, 2)->create();
        $this->vouchers = factory(Voucher::class, 'requested', 10)->create();
        $this->user = factory(User::class)->create();

        // Set up voucher states.
        Auth::login($this->user);
        foreach ($this->vouchers as $v) {
            $v->applyTransition('order');
            $v->applyTransition('print');
            $v->applyTransition('dispatch');
            $v->applyTransition('allocate');
            $v->trader_id = 1;
            $v->applyTransition('collect');
        }

        // Progress one to pending_payment.
        $this->vouchers[0]->applyTransition('confirm');

        // Progress a couple to reimbursed.
        $this->vouchers[1]->applyTransition('confirm');
        $this->vouchers[1]->applyTransition('payout');
        $this->vouchers[2]->applyTransition('confirm');
        $this->vouchers[2]->applyTransition('payout');

        // A voucher not belonging to trader 1.
        $this->vouchers[9]->trader_id = 2;
        $this->vouchers[9]->save();

        // Todo set some of the pended_at times to yesterday.
    }

  /**
   * Tests the for the api.trader.market route and controller.
   */
    public function testShowMarket()
    {
      $trader = factory(Trader::class, 'with_market_sponsor')->create();
      $this->user->traders()->sync([$trader->id]);
      $this->actingAs($this->user, 'api')
        ->get(route('api.trader.market', $trader->id))
        ->assertStatus(200)
        ->assertJsonFragment([
            'id' => $trader->market_id,
            'sponsor_id' => $trader->market->sponsor_id,
            'sponsor_shortcode' => $trader->market->sponsor_shortcode,
            'payment_message' => $trader->market->payment_message,
        ]);
    }

    public function testShowVoucherHistoryCompilesListOfPaymentHistory()
    {
        $traderController = new TraderController;
        $data = json_decode(
            $traderController
            ->showVoucherHistory($this->traders[0])->getContent()
        );
        $today = Carbon::now()->format('d-m-Y');

        // We should have one group of pended_on vouchers x3.
        $this->assertCount(1, $data);
        $this->assertEquals($data[0]->pended_on, $today);
        $this->assertCount(3, $data[0]->vouchers);
        // Check a few values as expected - just for fun.
        $this->assertEquals($this->vouchers[0]->code, $data[0]->vouchers[0]->code);
        $this->assertEquals($data[0]->vouchers[0]->reimbursed_on, '');
        $this->assertEquals($data[0]->vouchers[1]->recorded_on, $today);
        $this->assertEquals($data[0]->vouchers[2]->reimbursed_on, $today);
    }

    /**
     * Tests the email all voucher history API response.
     */
    public function testEmailVoucherHistoryAllDates()
    {
        // Sync the user with trader 1.
        $this->user->traders()->sync([1]);
        $this->actingAs($this->user, 'api')
            ->json('POST', route('api.trader.voucher-history-email', 1), [
                'submission_date' => null,
            ])
            ->assertStatus(202)
            ->assertJson([
                'message' => trans('api.messages.email_voucher_history')
            ])
        ;
    }

    /**
     * Tests the email specific date voucher history API response.
     */
    public function testEmailVoucherHistorySpecificDate()
    {
        // Sync the user with trader 1.
        $this->user->traders()->sync([1]);
        // There should be some vouchers pended today.
        $date = Carbon::now()->format('d-m-Y');
        $this->actingAs($this->user, 'api')
            ->json('POST', route('api.trader.voucher-history-email', 1), [
                'submission_date' => $date,
            ])
            ->assertStatus(202)
            ->assertJson([
                'message' => trans(
                    'api.messages.email_voucher_history_date', [
                        'date' => $date,
                    ]
                )
            ])
        ;
    }

    /**
     * Tests the voucher history not emailed to user not auth'd for trader.
     */
    public function testEmailVoucherHistoryToNonAuthdUser()
    {
        $this->actingAs($this->user, 'api')
            ->json('POST', route('api.trader.voucher-history-email', 1), [
                'submission_date' => null,
            ])
            ->assertStatus(403)
        ;
    }
}
