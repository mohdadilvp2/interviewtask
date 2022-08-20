<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\File;

class ProcessFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will pick new files uploaded and will create companies and contacts in Trengo';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->logHere('Starting processing files cron');
        $files = File::where('status', File::STATUS_NEW)->take(4)->get();
        $fileCount = $files->count();
        if(!$fileCount) {
            $this->logHere('Could not find any new files');
            return;
        }

        $this->logHere("Picked {$fileCount} files");
        $fileIds = $files->pluck('id');
        // Updated status to already picked
        // File::whereIn('id', $fileIds)->update(['status' => File::STATUS_PROGRESS]);
        $trengo = new \App\Utils\Trengo(config('trengo.api_key'));
        foreach ($files as $file) {
            $componiesCsv = storage_path('app/public/uploads/'.$file->companies_file_path);
            $contactsCsv = storage_path('app/public/uploads/'.$file->contacts_file_path);
            // Get csv contents 
            list($companiesHeader, $companiesArray) = $this->getCSVHeaderAndRows($componiesCsv);
            list($contactsHeader, $contacts) = $this->getCSVHeaderAndRows($contactsCsv);

            // Create companies
            $companiesTrengoIdMapping = [];
            foreach ($companiesArray as $key => $company) {
                foreach ($companiesArray as $key => $company) {
                $results = $trengo->getProfile(10261969);
                print_r($results);
            }   

                // if(!empty($company['name'])) {
                //     $this->logHere("Creating company for ".$company['name'].", Now ".( count($companiesArray)- ($key+1))." left");
                //     $response = $trengo->createProfile(trim($company['name']));
                //     if($response['success']) {
                //         $companiesTrengoIdMapping[$company['id']] = $response['response'];
                //         // $contacts[0]['company_id'] = $company['id'];
                //     }
                //     else {
                //         $this->error('Failed to create company '.json_encode(['company' => $company, 'response' => $response]). "\n\n");
                //     }
                // }
                // break;
            }
            die();

            // Get already existing customfields
            $getAllCustomFields = $trengo->getCustomFields();
            $allCustomFieldsMapping = [];
            $this->logHere("Getting all already existing customfields");
            foreach ($getAllCustomFields['response'] ?? [] as $key => $customField) {
                if($customField['type'] == 'CONTACT') {
                    $allCustomFieldsMapping[$customField['title']] =  $customField;
                }
            }

            // Get all custom fields that need to be created
            $createCustomFieldsFor = array_diff($contactsHeader, config('trengo.contact_headers'));
            foreach ($createCustomFieldsFor as $key => $header) {
                if(empty($allCustomFieldsMapping[$header])) {
                    $this->logHere("Creating custom field for ".$header);
                    // create custom fields
                    $response = $trengo->createCustomField(trim($header), 'CONTACT');
                    if($response['success']) {
                        $allCustomFieldsMapping[$header] = $response['response'];
                    }
                    else {
                        $this->error('Failed to create customfields '.json_encode(['header' => $header, 'response' => $response]). "\n\n");
                    }

                }
            }

            foreach ($contacts as $key => $contact) {
                if(!empty($contact['email'])) {
                    $this->logHere("Creating contact for ".json_encode($contact).", Now ".( count($contacts)- ($key+1))." left");
                    // Create contact
                    // $response = $trengo->createContact(trimt($contact['email']), trim($contact['name']) ?? '');
                    $response = $trengo->createContact('mohdadilvp@gmail.com', $contact['name'] ?? '');
                    if($response['success']) {
                        $trengoContact = $response['response'];
                        // Attach company if we match the company_id with id of created company list
                        if(!empty($companiesTrengoIdMapping[$contact['company_id']])) {
                            $response = $trengo->attachContact($companiesTrengoIdMapping[$contact['company_id']]['id'], $trengoContact['id']);
                        }
                        foreach ($createCustomFieldsFor as $key => $header) {
                            // Create custom field if value is set in the column
                            if(!empty($allCustomFieldsMapping[$header]) && !empty($contact[$header])) {
                                $response = $trengo->addCustomFieldToContact($trengoContact['id'], $allCustomFieldsMapping[$header]['id'], $contact[$header]);
                            }
                        }
                    }
                    else {
                        $this->error('Failed to create contact '.json_encode(['contact' => $contact, 'response' => $response]). "\n\n");
                    }
                }
                break;
            }
            // unlink($componiesCsv);
            // Storage::disk('local')->delete($componiesCsv);
            // Storage::disk('local')->delete($contactsCsv);
            // unlink($contactsCsv);
            // $file->status = File::STATUS_DONE;
            // $file->save();
        }

    }

    private function getCSVHeaderAndRows($csvPath) {
        $header =[];
        $rows =[];
        if (($open = fopen($csvPath, "r")) !== FALSE) {
                while (($data = fgetcsv($open, 1000, ",")) !== FALSE) {
                    if (empty($header)) {
                        $header = $data;
                    }
                    else {
                        $eachRow = array_combine($header, $data);
                        $rows[] = $eachRow;
                    }
                }
                fclose($open);
        }
        return [$header, $rows];
    }

    private function logHere ($string) {
        $this->info("===========================");
        $this->info($string);
        $this->info("===========================");
    }
}
