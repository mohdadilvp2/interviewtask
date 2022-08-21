<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FileUpload;

/**
 * This is used to process new import request
 *
 * Class ProcessFileUpload
 * @package App\Console\Commands
 */
class ProcessFileUpload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:file_uploads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will pickup 4 new request to import files to Trengo and will process it';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $this->logHere('Starting');
        // Take 4 file uploads with status new
        $files = FileUpload::where('status', FileUpload::STATUS_NEW)->take(4)
            ->get();
        $fileCount = $files->count();
        if (!$fileCount)
        {
            $this->logHere('Could not find any new  upload');
            return;
        }

        $this->logHere("Picked {$fileCount} upload request");
        $fileIds = $files->pluck('id');
        // Update status to progress, So next cron will not pick them, even if cron overlap
        FileUpload::whereIn('id', $fileIds)->update(['status' => FileUpload::STATUS_PROGRESS]);

        // Rate limit of trengo api is handled \App\Utils\Trengo
        $trengo = new \App\Utils\Trengo(config('trengo.api_key'));
        foreach ($files as $file)
        {
            $reports = ['companies_created' => 0, 'companies_failed' => 0, 'contacts_created' => 0, 'contacts_failed' => 0, ];
            // Get csv paths
            $companiesCsv = storage_path('app/public/uploads/' . $file->companies_file_path);
            $contactsCsv = storage_path('app/public/uploads/' . $file->contacts_file_path);
            // Get csv contents
            try
            {
                list($companiesHeader, $companiesArray) = $this->getCSVHeaderAndRows($companiesCsv);
                list($contactsHeader, $contacts) = $this->getCSVHeaderAndRows($contactsCsv);
            }
            catch(\Exception $e)
            {
                $this->error("File read error, Error: " . $e->getMessage());
                $file->status = FileUpload::STATUS_ERROR;
                $file->save();
                return false;
            }
            // Create companies
            $companiesTrengoIdMapping = [];
            foreach ($companiesArray as $key => $company)
            {
                // Create companies
                if (!empty($company['name']))
                {
                    $this->logHere("Creating company for " . $company['name'] . ", Now " . (count($companiesArray) - ($key + 1)) . " left");
                    $response = $trengo->createProfile(trim($company['name']));
                    if ($response['success'])
                    {
                        $reports['companies_created']++;
                        // Make a mapping between company_id in csv and trengo response
                        $companiesTrengoIdMapping[$company['id']] = $response['response'];
                    }
                    else
                    {
                        $reports['companies_failed']++;
                        $this->error('Failed to create company ' . json_encode(['company' => $company, 'response' => $response]));

                    }
                }
            }

            // Get already existing customfields
            $getAllCustomFields = $trengo->getCustomFields();
            $allCustomFieldsMapping = [];
            $this->logHere("Getting already existing customfields");
            foreach ($getAllCustomFields['response'] ?? [] as $key => $customField)
            {
                if ($customField['type'] == 'CONTACT')
                {
                    // Store that in an array for future use
                    $allCustomFieldsMapping[$customField['title']] = $customField;
                }
            }

            // Get an array of all custom fields that need to be created, from contacts.csv header
            $createCustomFieldsFor = array_diff($contactsHeader, config('trengo.contact_headers'));
            foreach ($createCustomFieldsFor as $key => $header)
            {
                if (empty($allCustomFieldsMapping[$header]))
                {
                    $this->logHere("Creating custom field for " . $header);
                    // create custom field, and store that to array for future use.
                    $response = $trengo->createCustomField(trim($header) , 'CONTACT');
                    if ($response['success'])
                    {
                        $allCustomFieldsMapping[$header] = $response['response'];
                    }
                    else
                    {
                        $this->error('Failed to create customfields ' . json_encode(['header' => $header, 'response' => $response]));
                    }

                }
            }

            foreach ($contacts as $key => $contact)
            {
                if (!empty($contact['email']))
                {
                    $this->logHere("Creating contact for " . json_encode($contact) . ", Now " . (count($contacts) - ($key + 1)) . " left");
                    // Create contact
                    $response = $trengo->createContact(trim($contact['email']) , trim($contact['name']) ?? '');
                    if ($response['success'])
                    {
                        $reports['contacts_created']++;
                        $trengoContact = $response['response'];
                        // Attach company if we match the company_id with id of created company list
                        if (!empty($companiesTrengoIdMapping[$contact['company_id']]))
                        {
                            $response = $trengo->attachContact($companiesTrengoIdMapping[$contact['company_id']]['id'], $trengoContact['id']);
                        }
                        foreach ($createCustomFieldsFor as $key => $header)
                        {
                            // Create custom field if value is set in the column, Get custom_field id from $allCustomFieldsMapping
                            if (!empty($allCustomFieldsMapping[$header]) && !empty($contact[$header]))
                            {
                                $response = $trengo->addCustomFieldToContact($trengoContact['id'], $allCustomFieldsMapping[$header]['id'], $contact[$header]);
                            }
                        }
                    }
                    else
                    {
                        $reports['contacts_failed']++;
                        $this->error('Failed to create contact ' . json_encode(['contact' => $contact, 'response' => $response]));

                    }
                }
            }
            // We make this more informative
            $this->logHere("Report Start");
            $this->logHere(print_r($reports, true));
            $this->logHere("Report End");
            // Delete files after processing
            \Storage::disk('local')
                ->delete($componiesCsv);
            \Storage::disk('local')->delete($contactsCsv);
            // Update status to done
            $file->status = FileUpload::STATUS_DONE;
            $file->save();
            // TODO: here we can notify user that your files imported
            
        }

    }

    /**
     * Function to get csv header and rows
     *
     * @param string $csvPath
     *
     * @return array
     */
    private function getCSVHeaderAndRows(string $csvPath)
    {
        $header = [];
        $rows = [];
        if (($open = fopen($csvPath, "r")) !== false)
        {
            while (($data = fgetcsv($open, 1000, ",")) !== false)
            {
                if (empty($header))
                {
                    $header = $data;
                }
                else
                {
                    $eachRow = array_combine($header, $data);
                    $rows[] = $eachRow;
                }
            }
            fclose($open);
        }
        return [$header, $rows];
    }

    /**
     * Function to show logs
     *
     * @param string $string
     *
     * @return bool
     */
    private function logHere(string $string)
    {
        $this->info($string);
        $this->info("=================================================================================");
        return true;
    }
}