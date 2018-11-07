<?php

namespace App;

use Auth;
use DB;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Log;

class Bundle extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'entitlement',
        'registration_id',
        'collecting_carer_id',
        'disbursed_at',
        'disbursing_centre_id',
        'disbursing_user_id',
    ];

    protected $rules = [
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    /**
     * The attributes to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
    ];

    /**
     * These are turned into Date objects on get
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'disbursed_at'  // When it was handed out.
    ];

    /**
     * Adds voucher codes to a bundle
     * @param $voucherCodes
     * @return array
     */
    public function addVouchers($voucherCodes)
    {
        $self = $this;
        $errors = [];

        // Get current Codes for vouchers on the bundle (if any)
        $currentCodes = $this->vouchers
            ->pluck('code')
            ->toArray();

        // Calculate vouchers to add, so we don't try to add already bundled vouchers.
        $addBundleCodes = array_diff($voucherCodes, $currentCodes);

        // Find vouchers models that match codes to add.
        $addVouchers = Voucher::whereIn('code', $addBundleCodes)->get();

        // Add the voucher models to a specific bundle (this one)
        $addErrors = $this->alterVouchers($addVouchers, $addBundleCodes, $self);

        // if it threw any errors, merge those with the array.
        if (!empty($addErrors)) {
            $errors = array_merge_recursive($addErrors, $errors);
        }

        // an empty errors array means all good.
        return $errors;
    }


    /**
     * Refactored function that works out if we broke anything then adds vouchers.
     *
     * @param Collection $vouchers
     * @param array $codes
     * @param Bundle|null $bundle
     * @return array
     */
    public function alterVouchers(Collection $vouchers, array $codes = [], Bundle $bundle = null)
    {
        $errors = [];

        // codes may reference vouchers we can't find in the database
        // TODO: move this check further out?

        $missingCodes = array_diff($codes, $vouchers->pluck("code")->toArray());
        if ($missingCodes) {
            $errors["codes"] = $missingCodes;
        }

        // try to Run the vouchers we know are in the DB
        $vouchers->each(
            function (Voucher $voucher) use ($bundle) {
                $voucher->bundle()->associate($bundle)->save();
            }
        );

        return $errors;
    }

    /**
     * Syncs an array of voucher codes with vouchers();
     *
     * @param array $voucherCodes array of cleaned Voucher codes
     * @return array $errors Errors
     */
    public function syncVouchers(array $voucherCodes)
    {

        $self = $this;
        $errors = [];

        // If we get an unhandled exception, we should halt and rollback.
        try {
            DB::transaction(function () use ($self, $voucherCodes, $errors) {

                $currentCodes = $this->vouchers
                    ->pluck('code')
                    ->toArray();

                // Calculate vouchers to remove.
                $unBundleCodes = array_diff($currentCodes, $voucherCodes);

                // Find the vouchers to remove.
                $removeVouchers = $this->vouchers()->whereIn('code', $unBundleCodes)->get();

                // Sync them to a null bundle
                $removeErrors = $this->alterVouchers($removeVouchers, $unBundleCodes, null);

                if (!empty($removeErrors)) {
                    $errors = array_merge_recursive($removeErrors, $errors);
                }

                // use addVouchers to Add any vouchers.
                $errors = array_merge_recursive($this->addVouchers($voucherCodes), $errors);

                // Whoops! errors happened.
                if (!empty($errors)) {
                    throw new \Exception("Errors during transaction");
                };
            });
        } catch (\Throwable $e) {
            // Log it
            Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
            Log::error($e->getTraceAsString());
            // Add an error notification for the caller to deal with
            $errors["transaction"] = true;
        }
        return $errors;
    }

    /**
     * Get the Registration this bundle is for
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    /**
     * The vouchers in this Bundle
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }
    /**
     * Return the Carer it was disbursed to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function collectingCarer()
    {
        return $this->belongsTo(Carer::class);
    }

    /**
     * Return the Centre it was disbursed to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function disbursingCentre()
    {
        return $this->belongsTo(Centre::class);
    }

    /**
     * Return the CentreUser it was disbursed by
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function disbursingUser()
    {
        return $this->belongsTo(CentreUser::class);
    }

    /**
     * Scope to pull only disbursed bundles
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDisbursed($query)
    {
        return $query->where('disbursed_at', '!=', null);
    }
}

