<?php

use Illuminate\Database\Seeder;

class VouchersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // get or create the seeder user
        $user = App\User::where('name', 'demoseeder')->first();
        if (!$user) {
            $user = factory(App\User::class)->create(['name' => 'demoseeder']);
        };

        Auth::login($user);

        // for each Sponsor, instantiate *over 100* vouchers!
        $batch_size = 101;
        $sponsors = \App\Sponsor::all();

        foreach ($sponsors as $sponsor) {
            for ($i = 1; $i <= $batch_size; $i++) {
                $voucher = factory(App\Voucher::class)->create([
                    'code' => $sponsor->shortcode . str_pad($i, 8, "0", STR_PAD_LEFT),
                    'sponsor_id' => $sponsor->id,
                ]);
                $voucher->applyTransition('order');
                $voucher->applyTransition('print');
                $voucher->applyTransition('dispatch');
                $voucher->applyTransition('allocate');
            }
        }
    }
}
