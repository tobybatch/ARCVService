@extends('store.layouts.service_master')

@section('title', 'Voucher Manager')

@section('content')

    @include('store.partials.navbar', ['headerTitle' => 'Voucher Manager'])

    <div class="content">
        <div class="col-container">
            <div class="col">
                <div>
                    <img src="{{ asset('store/assets/info-light.svg') }}">
                    <h2>This Family</h2>
                </div>
                <div class="alongside-container">
                    <div>
                        <h3>Main Carer:</h3>
                        <p>{{ $pri_carer->name }}</p>
                    </div>
                    <div>
                        <h3>Children:</h3>
                        <ul>
                          @foreach ( $children as $child )
                            <li>{{ $child->getAgeString() }}</li>
                          @endforeach
                        </ul>
                    </div>
                </div>
                <div class="warning">
                    @foreach( $family->getNoticeReasons() as $notices )
                    <p class="v-spaced">
                        <i class="fa fa-exclamation-circle" aria-hidden="true"></i>
                        Warning: {{ $notices['count'] }} {{ str_plural($notices['entity'], $notices['count']) }}
                        is {{ $notices['reason'] }}
                    </p>
                    @endforeach
                </div>
                <a href="{{ route("store.registration.edit", ['id' => $registration->id ]) }}" class="link">
                    <div class="link-button link-button-large">
                        <i class="fa fa-pencil button-icon" aria-hidden="true"></i>Go to edit family
                    </div>
                </a>
                <a href="{{ route("store.registration.index") }}" class="link">
                    <div class="link-button link-button-large">
                        <i class="fa fa-search button-icon" aria-hidden="true"></i>Find another family
                    </div>
                </a>
            </div>
            <div class="col">
                <div>
                    <img src="{{ asset('store/assets/history-light.svg') }}">
                    <h2>Collection History</h2>
                </div>
                <div>
                    <div class="emphasised-section">
                        <p>This family should collect:</p>
                        <p><b>{{ $family->entitlement }} vouchers per week</b></p>
                    </div>
                    @if (!empty($lastCollection))
                        <div class="emphasised-section">
                            <p>Their last collection was:</p>
                            <p><b>{{ $lastCollection }}</b></p>
                        </div>
                    @else
                        <div class="emphasised-section">
                            <p class="v-spaced">This family has not collected</p>
                        </div>
                    @endif
                </div>
                <!-- HIDDEN FOR ALPHA
                <div class="center">
                    <span id="brief-toggle" class="show clickable-span">
                      brief collection history
                      <i class="fa fa-caret-down" aria-hidden="true"></i>
                    </span>
                    <div id="brief" class="collapsed">
                        <table>
                            <tr>
                                <th>W/C</th>
                                <th>Vouchers collected</th>
                            </tr>
                            <tr>
                                <td>17/09/2018</td>
                                <td>0</td>
                            </tr>
                            <tr>
                                <td>10/09/2018</td>
                                <td>0</td>
                            </tr>
                        </table>
                    </div>
                </div> -->
                <button class="long-button">
                <i class="fa fa-clipboard button-icon" aria-hidden="true"></i>Full collection history
                </button>
            </div>
            <div class="col allocation">
                <div>
                    <img src="{{ asset('store/assets/allocation-light.svg') }}">
                    <h2>Allocate Vouchers</h2>
                </div>
                <form method="post" action="{{ route('store.registration.vouchers.post', [ 'registration' => $registration->id ]) }}">
                    {!! csrf_field() !!}
                    <div class="alongside-container">
                        <label>First voucher
                            <input id="first-voucher" name="start" type="text" autofocus>
                        </label>
                        <label>Last voucher
                            <input id="last-voucher" name="end" type="text">
                        </label>
                        <button id="range-add" class="add-button" type="submit">
                            <i class="fa fa-plus" aria-hidden="true"></i>
                        </button>
                    </div>
                </form>
                <p class="center no-margin">OR</p>
                <form method="post" action="{{ route('store.registration.vouchers.post', [ 'registration' => $registration->id ]) }}">
                {!! csrf_field() !!}
                    <div class="single-container">
                        <label for="add-voucher-input">Add individual vouchers
                            <input id="add-voucher-input" name="start" type="text">
                        </label>
                        <button class="add-button" type="submit">
                            <i class="fa fa-plus" aria-hidden="true"></i>
                        </button>
                    </div>
                </form>
                <div class="error-message">
                    <div class="error-icon-container">
                        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                    </div>
                    <span> {{Session::get('error_message')}}</span>
                </div>
                <button id="collection-button" class="long-button"><i class="fa fa-ticket button-icon" aria-hidden="true"></i>Go to voucher collection</button>
                <div class="center" id="vouchers-added">
                    <span class="clickable-span view" tabindex="0">
                        <i class="fa fa-list" aria-hidden="true"></i>
                    </span>
                    <span class="number-circle">{{ $vouchers_amount }}</span>
                    <div id="vouchers" class="collapsed">
                        <form id="unbundle" name="unbundle" action="" method="POST">
                            {!! method_field('delete') !!}
                            {!! csrf_field() !!}
                            <table>
                                <tr>
                                    <th>Voucher code</th>
                                    <th>Remove</th>
                                </tr>
                                @foreach( $vouchers as $voucher )
                                    <tr>
                                        <td>{{ $voucher->code }}</td>
                                        <td>
                                            <button type="submit" class="delete-button" formaction="{{ URL::route('store.registration.voucher.delete', ['registration' => $registration->id, 'voucher' => $voucher->id]) }}" id="{{ $voucher->id }}">
                                                <i class="fa fa-minus" aria-hidden="true"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </form>
                    </div>
                </div>
            </div>
            <div id="collection" class="col collection-section">
                <div>
                    <img src="{{ asset('store/assets/collection-light.svg') }}">
                    <h2>Voucher Pick Up</h2>
                </div>
                <div>
                    <p>There are {{ $vouchers_amount }} vouchers waiting for the family</p>
                </div>
                <form method="post"
                      action="{{ route('store.registration.vouchers.put', [ 'registration' => $registration->id ]) }}">
                    {!! method_field('put') !!}
                    {!! csrf_field() !!}
                    <div class="pick-up">
                        <div>
                            <i class="fa fa-user"></i>
                            <div>
                                <label for="collected-by">Collected by:</label>
                                <select id="collected-by" name="collected_by">
                                    @foreach( $carers as $carer )
                                        <option value="{{ $carer->id }}">{{ $carer->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <i class="fa fa-calendar"></i>
                            <div>
                                <label for="collected-on">Collected on:</label>
                                <input id="collected-on" name="collected_on" value="<?php echo date('Y-m-d');?>" type="date">
                            </div>
                        </div>
                        <div>
                            <i class="fa fa-home"></i>
                            <div>
                                <label for="collected-at">Collected at:</label>
                                <select id="collected-at" name="collected_at">
                                    {-- Will need to be a list of all local centres eventually --}
                                    <option value="{{ $centre->id }}">{{ $centre->name }}</option>
                                </select>
                            </div>
                        </div>
                        <button class="long-button submit"
                                type="submit">
                            Confirm pick up
                        </button>
                    </div>
                </form>
                <a href="{{ route("store.registration.voucher-manager", ['id' => $registration->id ]) }}" class="link">
                    <div class="link-button link-button-large">
                        <i class="fa fa-ticket button-icon" aria-hidden="true"></i>Change allocated vouchers
                    </div>
                </a>
            </div>
        </div>
    </div>

    @section('hoist-head')
        <script src="{{ asset('store/js/jquery-ui-1.12.1/jquery-ui.min.js')}}"></script>
    @endsection
    <script type="text/javascript">
        $(document).ready(
            function () {
                // Voucher collection section animations
                $('#collection-button').click(function (e) {
                    $('#collection').addClass('slide-in');
                    $('.allocation').addClass('fade-back');
                    e.preventDefault();
                });

                var vouchersInList = $('#vouchers tr').length;
                if(vouchersInList > 1) { // the first tr contains the table head
                    $('#vouchers-added').addClass('pulse');
                }

                var firstVoucher = $('#first-voucher');
                var lastVoucher = $('#last-voucher');

                // Handle first in range of vouchers
                firstVoucher.keypress(function(e) {
                    if(e.keyCode==13){
                        var firstValue = firstVoucher.val();
                        if(firstValue !== ""){
                            lastVoucher.focus();
                        }
                        e.preventDefault();
                    }
                });

                // Handle last in range of vouchers
                lastVoucher.keypress(function(e) {
                    if(e.keyCode==13){
                        var firstValue = firstVoucher.val();
                        if(firstValue === "") {
                            firstVoucher.focus();
                            e.preventDefault();
                            return
                        }

                        var lastValue = lastVoucher.val();
                        if(firstValue !== "" && lastValue !== "") {
                            $('#range-add').trigger('click');
                            e.preventDefault();
                        }
                    }
                });

                $('.clickable-span').click(function (e) {
                    // The next sibling is the content
                    var content = $('#vouchers');

                    if($(this).hasClass('view')) {
                        $(this).removeClass('view').addClass('hide');
                        content.removeClass('collapsed');
                    } else {
                        $(this).removeClass('hide').addClass('view');
                        content.addClass('collapsed');
                    }
                });

                // On enter keydown, run click functionality above (for a11y tabbing)
                $('.clickable-span').keypress(function(e){
                  if(e.which == 13) {
                    $('.clickable-span').click();
                  }
                })

                // Browser backup for lack of datepicker support eg. Safari
                // Reset back to English date format
                if ($('#collected-on')[0].type != 'date')
                    $('#collected-on').datepicker({ dateFormat: 'dd-mm-yyyy' }).val();

                $('#collected-on').valueAsDate = new Date();
            }
        );
    </script>
@endsection
