@extends('layouts.app')

@section('page-title', __('Racing & Result') )

@push('stylesheets')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.0.5/daterangepicker.min.css" integrity="sha512-rBi1cGvEdd3NmSAQhPWId5Nd6QxE8To4ADjM2a6n0BrqQdisZ/RPUlm0YycDzvNL1HHAh1nKZqI0kSbif+5upQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        
    </style>
@endpush

@section('page-toolbar')
    <div class="row mb-3 align-items-center">
        <div class="col">
            <ol class="breadcrumb bg-transparent mb-0">
                <li class="breadcrumb-item"><a class="text-secondary" href="{{ url("/") }}">{{ __('Home') }}</a></li>
            </ol>
        </div>
    </div>
    <!-- .row end -->
@endsection

@section('page-content')
    <div class="col-12">
        <div class="d-flex justify-content-between">
            <div class="col col-6 mb-3">
                <p>Currently Imported Dogs: {{ $dogImported }}, Dogs Awaiting Import: {{ $dogTo }}</p>
            </div>
            <div class="col col-3 mb-3">
                <div class="form-group row">
                    <label for="datepicker" class="col-sm-2 col-form-label">Date:</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control datepicker" id="datepicker">
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <table id="table_list" class="table align-middle mb-0 card-table" cellspacing="0">
                    <thead>
                    <tr>
                        <th>{{ __('Venue') }}</th>
                        <th></th>
                        <!-- <th>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;R1&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;R2&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;R3&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;R4&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;R5&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;R6&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;R7&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;R8&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;R9&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;R10&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;R11&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;R12</th> -->
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script>
        $(document).ready(function() {
            // Detect the time zone
            const userTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            $('#datepicker').val(moment().format('DD-MM-YYYY'));
            var table = $('#table_list')
                .addClass( 'nowrap' )
                .dataTable( {
                    "pageLength": 100,
                    responsive: true,
                    ordering: false,
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: 'https://dogpower.pro/get-racing-list',
                        data: function(d) {
                            d.datepicker = $('#datepicker').val(),
                            d.zone_time = userTimeZone
                        }
                    },
                    columns: [
                        { data: 'venue', name: 'venue', searchable: true, orderable: true },
                        { data: 'race', name: 'race', searchable: false, orderable: false },
                    ],
                    language : {
                        processing: '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
                    },
                });
        });
    </script>
    <script>
        $('#datepicker').on('apply.daterangepicker', function() {
            var oTable = $('#table_list').dataTable();
            oTable.fnDraw(false);
        });
    
        $('#datepicker').daterangepicker({
            drops: 'down',
            "locale": {
                "format": "MM/DD/YYYY",
                "separator": " - ",
                "applyLabel": "Apply",
                "cancelLabel": "Cancel",
                "fromLabel": "From",
                "toLabel": "To",
                "customRangeLabel": "Custom",
                "firstDay": 1
            },
            singleDatePicker: true,
            autoApply: true,
            autoUpdateInput: false,
        }, function(chosen_date) {
            $('#datepicker').val(chosen_date.format('DD-MM-YYYY'));
        });
    </script>
@endpush
