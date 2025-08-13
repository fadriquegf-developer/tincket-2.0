<div id="loading">
    <div class="spinner-border text-light" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

@push('after_styles')
    <style>
        #loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
        }

        #loading.show {
            display: flex;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
    </style>
@endpush

@push('after_scripts')
    <script>
        // Updated loading functions for Bootstrap 5
        window.showLoading = function() {
            $('#loading').addClass('show');
        };

        window.hideLoading = function() {
            $('#loading').removeClass('show');
        };
    </script>
@endpush
