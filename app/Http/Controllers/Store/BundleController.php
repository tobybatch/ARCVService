<?php

namespace App\Http\Controllers\Store;

use App\Bundle;
use App\Carer;
use App\Centre;
use App\Voucher;
use App\Registration;
use App\Http\Requests\StoreAppendBundleRequest;
use App\Http\Requests\StoreUpdateBundleRequest;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use Log;

class BundleController extends Controller
{
    /**
     * Returns the voucher-manager page for a given registration
     *
     * @param Registration $registration
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */

    public function create(Registration $registration)
    {
        $user = Auth::user();
        $data = [
            "user_name" => $user->name,
            "centre_name" => ($user->centre) ? $user->centre->name : null,
        ];

        // Grabs a copy of all carers
        $carers = $registration->family->carers->all();
        $bundle = $registration->currentBundle()->vouchers;

        $sorted_bundle = $bundle->sortBy('code');

        // Find the last collected bundle.
        $lastCollectedBundle = $registration->bundles()
            ->whereNotNull('disbursed_at')
            ->orderBy('id', 'desc')
            ->limit(1)
            ->first();
        ;

        // Turn it's disbursement date into a human date.
        $lastCollection = ($lastCollectedBundle && (!empty($lastCollectedBundle->disbursed_at)))
            // 'disbursed_at' is auto-carbon'd by the Bundle model
            ? $lastCollectedBundle->disbursed_at->format('l jS \of F Y')
            : null
        ;

        return view('store.manage_vouchers', array_merge(
            $data,
            [
                "registration" => $registration,
                "lastCollection" => $lastCollection,
                "family" => $registration->family,
                "children" => $registration->family->children,
                "centre" => $registration->centre,
                "carers" => $carers,
                "pri_carer" => array_shift($carers),
                "vouchers" => $sorted_bundle,
                "vouchers_amount" => count($bundle)
            ]
        ));
    }

    /**
     * List all the bundles
     */
    public function index()
    {
    }

    /**
     * Does a single or multiple voucher.
     *
     * @param StoreAppendBundleRequest $request
     * @param Registration $registration
     * @return \Illuminate\Http\RedirectResponse
     */

    public function addVouchersToCurrentBundle(StoreAppendBundleRequest $request, Registration $registration)
    {
        /** @var Bundle $bundle */
        // Get current Bundle
        $bundle = $registration->currentBundle();

        // There should always be a start. The request will fail before validation before this point if there isn't
        $start_match = Voucher::splitShortcodeNumeric($request->get("start"));

        // Gets the whole string match and plumbs it onto the start of the voucher codes.
        $voucherCodes[] = $start_match[0];

        // Check we have an end;
        if (!empty($request->get("end"))) {
            $end_match = Voucher::splitShortcodeNumeric($request->get("end"));

            // Grab integer versions of the thing.
            $startval = intval($start_match['number']);
            $endval = intval($end_match['number']);

            // Generate codes!
            for ($val = $startval+1; $val <= $endval; $val++) {
                // Assemble code, add to voucherCodes[]
                // We appear to be producing codes that are "0" str_pad on the left, to variable characters
                // We'll use the $start's numeric length as the value to pad to.
                $voucherCodes[] = $start_match['shortcode'] . str_pad(
                    $val,
                    strlen($start_match['number']),
                    "0",
                    STR_PAD_LEFT
                );
            }
        };

        // return to page
        $errors = $bundle->addVouchers($voucherCodes);

        // Return to manager in all cases
        $successRoute = $failRoute = route(
            'store.registration.voucher-manager',
            ['registration' => $registration->id]
        );

        return $this->redirectAfterRequest($errors, $successRoute, $failRoute);
    }

    /**
     * Create OR replace a registrations current active bundle
     *
     * @param StoreUpdateBundleRequest $request
     * @param Registration $registration
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(StoreUpdateBundleRequest $request, Registration $registration)
    {
        // Init for later
        $errors = [];

        // Default return to manager
        $successRoute = $failRoute = route(
            'store.registration.voucher-manager',
            ['registration' => $registration->id]
        );

        // Filter inputs for only our interests
        $inputs = $request->only([
            'collected_at',
            'collected_by',
            'collected_on'
        ]);

        // If we don't mention them in form input because we are updating status of existing bundle vouchers
        if ($request->exists('vouchers')) {
            $inputs['vouchers'] = $request->input('vouchers');
        }

        /** @var \App\Bundle $bundle */
        $bundle = $registration->currentBundle();

        // Are we updating vouchers?
        if (array_key_exists('vouchers', $inputs)) {
            // remove empty values

            $voucherCodes = array_filter(
                $inputs['vouchers'],
                function ($value) {
                    return !empty($value);
                }
            );

            $voucherCodes = (!empty($voucherCodes))
                ? Voucher::cleanCodes(($voucherCodes))
                : []; // Will result in the removal of the vouchers from the bundle.

            // sync vouchers.
            $errors[] = $bundle->syncVouchers($voucherCodes);
        }

        // Check we have values on our inputs; This should have been covered in validation...
        if (isset($inputs['collected_at']) &&
            isset($inputs['collected_by']) &&
            isset($inputs['collected_on'])
        ) {

            // Check there are actual vouchers to disburse, or this is a bit.
            if (empty($bundle->vouchers)) {

                $errors["empty"] = true;

            } else {

                // Add the date;
                $bundle->disbursed_at = Carbon::createFromFormat(
                    'Y-m-d',
                    $inputs['collected_on']
                )->startOfDay()->toDateTimeString();

                try {
                    // Find and add the carer
                    $carer = Carer::findOrFail($inputs['collected_by']);
                    $bundle->collectingCarer()->associate($carer);

                    // Find and add the centre
                    $centre = Centre::findOrFail($inputs['collected_at']);
                    $bundle->disbursingCentre()->associate($centre);

                    // Add the current user as disbursingUser.
                    $bundle->disbursingUser()->associate(Auth::user());

                    // Store it.
                    $bundle->save();

                } catch (\Exception $e) {
                    // Fires if finOrFail() or save() breaks
                    // Log that error by hand
                    Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
                    Log::error($e->getTraceAsString());
                    $errors['transaction'] = true;
                }

                // Return to Index as we've disbursed, and user may want to search
                $successRoute = route(
                    'store.registration.index'
                );
            }
        }

        return $this->redirectAfterRequest($errors, $successRoute, $failRoute);
    }

    /**
     * Removes a single voucher from a bundle
     * @param Registration $registration
     * @param Voucher $voucher
     * @return \Illuminate\Http\RedirectResponse
     */
    public function removeVoucherFromCurrentBundle(Registration $registration, Voucher $voucher)
    {
        /** @var Bundle $bundle */
        $bundle = $registration->currentBundle();

        // It is attached to our bundle, right?
        if ($voucher->bundle_id == $bundle->id) {
            // Call alterVouchers with no codes to check, and no bundle to detransiton and remove it.
            $errors = $bundle->alterVouchers(collect([$voucher]), [], null);
        } else {
            // Error it out (how did you get here?
            $errors["foreign"] = [$voucher->code];
        }

        // Back to manager in all cases
        $successRoute = $failRoute = route(
            'store.registration.voucher-manager',
            ['registration' => $registration->id]
        );

        return $this->redirectAfterRequest($errors, $successRoute, $failRoute);
    }

    /**
     * Filters and prepares errors before returning to the voucher-manager
     *
     * @param $errors
     * @param $successRoute
     * @param $failRoute
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectAfterRequest($errors, $successRoute, $failRoute)
    {
        if (!empty($errors)) {
            // Assemble messages
            $messages = [];
            foreach ($errors as $type => $values) {
                switch ($type) {
                    case "transaction":
                        if ($values) {
                            $messages[] = 'Database transaction problem';
                        }
                        break;
                    case "transition":
                        $messages[] = "Transition problem with: " . join(', ', $values);
                        break;
                    case "codes":
                        $messages[] = "Bad code problem with: " . join(', ', $values);
                        break;
                    case "foreign":
                        $messages[] = "Action denied on a foreign voucher: " . join(', ', $values);
                        break;
                    case "empty":
                        if ($values) {
                            $messages[] = "Action denied on empty bundle";
                        }
                        break;
                    default:
                        $messages[] = 'There was an unknown error';
                        break;
                }
            }
            // Spit the basic error messages back
            return redirect($failRoute)
                ->withInput()
                ->with('error_message', join(', ', $messages) . '.');
        } else {
            // Otherwise, sure, return to the new view.
            return redirect($successRoute)
                ->with('message', 'Voucher bundle updated.');
        }
    }
}
