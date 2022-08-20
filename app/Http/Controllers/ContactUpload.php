<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\File;

class ContactUpload extends Controller
{
  public function createForm(){
    return view('welcome');
  }
  public function fileUpload(Request $req){
        $req->validate([
        'companies' => 'required|mimes:csv|max:2048',
        'contacts' => 'required|mimes:csv|max:2048'
        ]);
        $fileModel = new File;
        if($req->file()) {
        	$fileModel = new File;
            $fileNameComponies = time().'_'.$req->companies->getClientOriginalName();
            $fileNameContacts = time().'_'.$req->contacts->getClientOriginalName();
            $req->file('companies')->storeAs('uploads', $fileNameComponies, 'public');
            $req->file('contacts')->storeAs('uploads', $fileNameContacts, 'public');
            $fileModel->companies_file_path = $fileNameComponies;
            $fileModel->contacts_file_path = $fileNameContacts;
            $fileModel->save();
            return back()
            ->with('success','Files have been uploaded. We will process files');
        }
   }
}
