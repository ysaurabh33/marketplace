<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Marketplace Upload</title>
        <link rel="stylesheet" href="{{ asset('bootstrap.min.css') }}">
    </head>
    <body>
        <div class="container mt-4">
            <h1 class="mx-auto text-center">Marketplace file upload</h1>
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

            @if(Session::has('error'))
            <div class="alert alert-danger w-50 mx-auto">
                {{ Session::get('error') }}
            </div>
            @endif

            @if(Session::has('success'))
            <div class="alert alert-success w-50 mx-auto">
                {{ Session::get('success') }}
            </div>
            @endif

            <form method="post" action="{{ url('/file_upload') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <div class="row">
                        <div class="offset-3 col-2">
                            <label class="form-label">Marketplace</label>
                            <select class="form-select" name="marketplace">
                                <option value="1">Amazon US</option>
                                <option value="2">Walmart US</option>
                            </select>
                        </div>
                        <div class="col-2">
                            <label class="form-label">Upload Type</label>
                            <select class="form-select" name="type">
                                <option value="1">Quantity</option>
                                <option value="2">Price</option>
                                <option value="3">Quantity & Price</option>
                            </select>
                        </div>
                        <div class="col-2">
                            <label class="form-label">file</label>
                            <input type="file" class="form-control" name="file">
                        </div>
                    </div>                        
                </div>
                <div class="d-grid gap-2 col-3 mx-auto">
                    <button type="submit" class="btn btn-warning">Change On Marketplace</button>
                </div>
            </form>

            <div class="table-responsive mt-5 w-75 mx-auto">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Uploaded File</th>
                            <th scope="col">Marketplace</th>
                            <th scope="col">Processed File</th>
                            <th scope="col">Response File</th>
                            <th scope="col">Success Rate</th>
                            <th scope="col">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($data))
                            @foreach($data as $k => $dis)
                            <tr>
                                <th scope="row">{{ ($k + 1) }}</th>
                                <td><a href="{{ url('/download/'.base64_encode($dis->ufile)) }}">U-File</a></td>
                                <td>{{ ($dis->mp_id == 1) ? 'Amazon' : 'Walmart' }}</td>
                                <td><a href="{{ url('/download/'.base64_encode($dis->pfile)) }}">P-File</a></td>
                                <td><a href="{{ url('/download/'.base64_encode($dis->rfile)) }}">R-File</a></td>
                                <td>{{ $dis->success.'/'.$dis->total }}</td>
                                <td>{{ date('d M,Y', strtotime($dis->updated_at)) }}</td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="7" class="text-center">No Data found</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </body>
</html>
