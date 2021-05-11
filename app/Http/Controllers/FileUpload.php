<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use App\Models\Files;
use App\Library\AmazonMWS;

class FileUpload extends Controller
{
    protected $marketplace;

    public function index()
    {
        /*$date = "54654";
        $path = 'marketplace/P'.$date.'.txt';
        $pfile = fopen('../storage/app/'.$path,'w');
        fputcsv($pfile,['sku', 'quantity', 'leadtime-to-ship'], "\t");
        fputcsv($pfile,['FRAG-544217', '1', '16'], "\t");
        fputcsv($pfile,['FRAG-543891', '1', '16'], "\t");
        fclose($pfile);
        dd('Done');*/

        //$marketplace = new AmazonMWS();
        //dd($marketplace->get_feed_result(123744018757, realpath('../storage/app/marketplace/response.txt')));
        //dd($marketplace->send_feed(realpath('../storage/app/marketplace/P54654.txt')));

        $data = Files::orderBy('id', 'desc')->limit(10)->get();
        return view('main')->with('data', $data);
    }

    public function file_upload(Request $request)
    {
        $validation = $request->validate([
            'marketplace' => 'required',
            'type' => 'required',
            'file' => 'required|mimetypes:application/csv,application/excel,application/vnd.ms-excel, application/vnd.msexcel,text/csv,text/anytext,text/plain,text/x-c,text/comma-separated-values,inode/x-empty,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);

        $date = date('YmdHis');
        $path = $request->file->storeAs('marketplace', 'U'.$date.'.'.$request->file->extension());

        $id = Files::create([
                    'mp_id' => $request->marketplace, 
                    'type' => $request->type, 
                    'ufile' => $path
                ]);

        if($request->marketplace == 1)
        {
            $this->amazon_feed($request->file, $date, $id->id);
            $marketplace = new AmazonMWS();
        } else if($request->marketplace == 2) {
            $this->walmart_feed($request->file, $date, $id->id);
        } else {
            session()->flash('error', 'Invalid Data!');
        }

        return redirect('/progress');
    }

    // feed id = 123752018757
    public function process_file(Request $request)
    {
        $filedata = Files::where([['feedid', '!=', ''], ['id', '=', $request->id]])->firstOrFail();
        $path = 'marketplace/R'.date('Ymdhis').'.txt';
        
        if($filedata->mp_id == 1)
        {
            $this->marketplace = new AmazonMWS();
            $response = $this->marketplace->get_feed_result($filedata->feedid);
            if(!empty($response) && preg_match_all('/\d+/', $response, $matches))
            {
                $filedata->success = $matches[0][1];
                $filedata->rfile = $path;
                file_put_contents('../storage/app/'.$path, $response);
            }
            session()->flash('success', 'Feed process successfully. Please check the response!');
        }
        else if($filedata->mp_id == 2)
        {
            //
        }
        else
        {
            $filedata->to_fetch = 3;    
            session()->flash('error', 'Something went wrong!');
        }
        
        $filedata->save();        
        return redirect('/');
    }

    public function amazon_feed($file, $date, $id)
    {
        // Read File
        $reader = IOFactory::load($file->path());
        $worksheets = $reader->getAllSheets();
        $count = 0;
        $header = [['sku', 'quantity', 'leadtime-to-ship'], ['sku', 'price'], ['sku', 'price', 'quantity', 'leadtime-to-ship']];
        $file = Files::find($id);
        
        $path = 'marketplace/P'.$date.'.txt';
        $pfile = fopen('../storage/app/'.$path,'w');
        fputcsv($pfile, $header[$file->type], "\t");
        
        foreach($worksheets as $sheet)
        {
            $rows = $sheet->toArray();
            foreach($rows as $rownumber => $col)
            {
                if($rownumber != 0)
                {
                    fputcsv($pfile, $col, "\t");
                    $count++;
                }                
            }
        }
        fclose($pfile);
        
        $file->pfile = $path;
        $file->total = $count;
        
        $response = $marketplace->send_feed(realpath('../storage/app/'.$path));
        if(isset($response['FeedSubmissionId']))
        {
            $file->feedid = $response['FeedSubmissionId'];
            $file->to_fetch = 1;
            $file->save();
        }
        else
        {
            $file->save();
            session()->flash('error', 'Something went wrong!');
            return redirect('/');
        }        
    }

    public function walmart_feed($path, $date, $id)
    {
        //
    }
}
