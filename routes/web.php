<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileUpload;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [FileUpload::class, 'index']);
Route::post('/file_upload', [FileUpload::class, 'file_upload']);
Route::get('/progress/{id}', function($id){
    return view('progress')->with('id', $id);
});
Route::post('/progress', [FileUpload::class, 'process_file']);
Route::get('/download/{path}', function($path){
    $pathToFile = storage_path()."/app/".base64_decode($path);
    return (file_exists($pathToFile)) ? response()->download($pathToFile) : abort(404);
});
