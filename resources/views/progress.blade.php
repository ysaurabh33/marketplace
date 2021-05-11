<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Marketplace Progress</title>
        <link rel="stylesheet" href="{{ asset('bootstrap.min.css') }}">
    </head>
    <body>
        <div class="container mt-4">
            <h1 class="mx-auto text-center">Marketplace Feed In-Progress</h1>
            <hr class="w-50 mx-auto">
            
            @if($errors->any())
            <div class="alert alert-danger w-50 mx-auto">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="text-center">
                <p>Please wait while progress bar is completed...</p>
                <p class="text-danger fw-bolder">Do not close the tab</p>
            </div>

            <div class="progress w-50 mx-auto mb-5">
                <div class="progress-bar" id="bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
            </div>

            <form method="post" action="{{ url('/progress') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" value="{{ $id }}" />
                <div class="d-grid gap-2 col-3 mx-auto">
                    <button id="progressBtn" style="display:none;" type="submit" class="btn btn-warning">Fetech Result</button>
                </div>
            </form>
        </div>

        <script>
            var bar = document.getElementById('bar');
            var progressBtn = document.getElementById('progressBtn');
            setInterval(function(){ 
                let percent = parseInt(bar.style.width.replace('%', ''));
                if(percent <= 100)
                {
                    percent += 3
                    percent = (percent > 100) ? 100 : percent;
                    bar.style.width = percent+'%';
                    bar.innerHTML = percent+'%';    
                    if(percent == 100)
                    {
                        progressBtn.style.display = 'block';
                    }
                }                                

            }, 5000);
        </script>
    </body>
</html>
