@extends('frontend.layout-guest')
@section('content')
<section class="text-gray-600 body-font">
  <div class="container px-5 py-24 mx-auto">
    <div class="flex flex-col text-center w-full mb-12">
      <h1 class="sm:text-3xl text-2xl font-medium title-font mb-4 text-gray-900">Verification</h1>
      <p class="lg:w-2/3 mx-auto leading-relaxed text-base">You are one step closser to go to the next step</p>
    </div>
    <div class="flex lg:w-1/3 w-full sm:flex-row flex-col mx-auto px-8 sm:space-x-4 sm:space-y-0 space-y-4 sm:px-0 items-end">
      <div class="relative flex-grow w-full text-center">
        <x-turnstile-widget theme="auto" language="en-us" callback="callbackFunction" errorCallback="errorCallbackFunction" />
      </div>
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script>
    function callbackFunction(token) {
        console.log("Token:", token);

        // Contoh: Kirim ke server via fetch atau AJAX
        fetch("{{ route('verify.turnstile') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({ token: token })
        })
        .then(response => response.json())
        .then(data => {
            console.log("Server response:", data);
            if (data.success) {
                // Redirect atau tampilkan pesan sukses
                window.location.href = "{{ route('filament.admin.auth.login') }}";
            } else {
                alert("Verification failed. Please try again.");
            }
        })
        .catch(err => {
            console.error("Error:", err);
        });
    }

    function errorCallbackFunction() {
        alert("Failed to verify the page, please try again.");
    }
</script>
@endpush
