<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <title>Upload companies.csv and contacts.csv</title>
    <style type="text/css">
    .margin-30{
        margin-bottom: 30px;
    }
    </style>
</head>
<body>
    <div class="container mt-5">
        <form action="{{route('fileUpload')}}" method="post" enctype="multipart/form-data">
          <h3 class="text-center mb-5">Upload companies.csv and contacts.csv</h3>
            @csrf
            @if ($message = Session::get('success'))
            <div class="alert alert-success">
                <strong>{{ $message }}</strong>
            </div>
          @endif
          @if (count($errors) > 0)
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                      <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
          @endif
            <div class="custom-file col-md-12 margin-30">
                <input onchange="document.getElementById('file1').innerHTML = this.files[0].name" type="file" name="companies" class="custom-file-input" id="chooseCompanies">
                <label class="custom-file-label" for="chooseCompanies" id="file1" >Select companies.csv</label>
            </div>
            <div class="custom-file col-md-12">
                <input onchange="document.getElementById('file2').innerHTML = this.files[0].name" type="file" name="contacts" class="custom-file-input" id="chooseContacts">
                <label class="custom-file-label" for="chooseContacts" id="file2">Select contacts.csv</label>
            </div>
            <button type="submit" name="submit" class="btn btn-primary btn-block mt-4">
                Upload Files
            </button>
        </form>
    </div>
</body>
</html>
