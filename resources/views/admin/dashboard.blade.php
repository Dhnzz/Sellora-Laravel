@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css"
        integrity="sha512-tS3S5qG0BlhnQROyJXvNjeEM4UpMXHrQfTGmbQ1gKmelCxlSEBUaxhRBj/EFTzpbP4RVSrpEikbmdJobCvhE3g=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
@endpush

@section('content')
    {{-- Transaction Carousel --}}
    <div class="row justify-content-md-between justify-content-center w-full">
        
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"
        integrity="sha512-bPs7Ae6pVvhOSiIcyUClR7/q2OAsRiovw4vAkX+zJbw3ShAeeqezq50RIIcIURq7Oa20rW2n2q+fyXBNcU9lrw=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script>
        $(document).ready(function() {
            function fetchFilteredData(type, filter) {
                $.ajax({
                    url: "{{ route('owner.dashboard.data') }}",
                    method: "GET",
                    data: {
                        type: type,
                        filter: filter
                    },
                    success: function(response) {
                        console.log(response);
                        if (type === 'omset') {
                            $('#omsetDisplay').html('Rp. ' + response.omset);
                        } else if (type === 'expense') {
                            $('#expenseDisplay').html('Rp. ' + response.expense);
                        } else if (type === 'income') {
                            $('#incomeDisplay').html('Rp. ' + response.income);
                        }
                    },
                    error: function(xhr) {
                        console.log('Error fetching omset:', xhr.responseText);
                        alert('Gagal memuat data omset. Silakan coba lagi.');
                    }
                });
            }

            // Event listener untuk perubahan pada selectbox
            $('#omsetFilter').on('change', function() {
                var type = $(this).data('type');
                var filter = $(this).val(); // Dapatkan nilai filter yang dipilih
                fetchFilteredData(type, filter); // Panggil fungsi AJAX
            });
            $('#expenseFilter').on('change', function() {
                var type = $(this).data('type');
                var filter = $(this).val(); // Dapatkan nilai filter yang dipilih
                fetchFilteredData(type, filter); // Panggil fungsi AJAX
            });
            $('#incomeFilter').on('change', function() {
                var type = $(this).data('type');
                var filter = $(this).val(); // Dapatkan nilai filter yang dipilih
                fetchFilteredData(type, filter); // Panggil fungsi AJAX
            });

        });
    </script>
@endpush
